<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityContribution;
use App\Models\ActivityLeg;
use App\Models\ActivityLegProgress;
use App\Models\ActivityRegistration;
use App\Models\ActivitySlot;
use App\Models\ActivityWave;
use App\Models\PilotProfile;
use App\Models\Pirep;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityService
{
    /**
     * Evaluate a submitted/accepted PIREP against all active activities.
     */
    public function evaluatePirep(Pirep $pirep): void
    {
        if (!in_array(strtolower($pirep->status), ['accepted', 'complete', 'approved'])) {
            return;
        }

        $tenantId = $pirep->tenant_id;
        $userId = $pirep->user_id;

        // Fetch all active activities for this airline
        $activities = Activity::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['legs', 'teams', 'slots'])
            ->get();

        foreach ($activities as $activity) {
            try {
                $this->evaluatePirepForActivity($pirep, $activity);
            } catch (\Throwable $e) {
                Log::error("Failed evaluating activity [ID: {$activity->id}] for PIREP [ID: {$pirep->id}]: " . $e->getMessage());
            }
        }
    }

    /**
     * Evaluate a PIREP against a specific activity.
     */
    public function evaluatePirepForActivity(Pirep $pirep, Activity $activity): bool
    {
        // 1. Time Window & Leeway Check
        if (!$this->isWithinTimeWindow($pirep, $activity)) {
            return false;
        }

        // 2. Restrictions Check
        if (!$this->passesRestrictions($pirep, $activity)) {
            return false;
        }

        // 3. Evaluate by Type
        return match ($activity->type) {
            'event'               => $this->evaluateEvent($pirep, $activity),
            'slotted_event'       => $this->evaluateSlottedEvent($pirep, $activity),
            'focus_airport'       => $this->evaluateFocusAirport($pirep, $activity),
            'tour', 'roster', 'curated_roster' => $this->evaluateMultiLeg($pirep, $activity),
            'community_goal'      => $this->evaluateCommunityGoal($pirep, $activity),
            'community_challenge' => $this->evaluateCommunityChallenge($pirep, $activity),
            default               => false,
        };
    }

    // ── Multi-Leg Processing (Tours, Rosters, Curated Rosters) ───────────────

    protected function evaluateMultiLeg(Pirep $pirep, Activity $activity): bool
    {
        // Registration is required for tours, rosters, and curated rosters
        $reg = $activity->registrations()
            ->where('user_id', $pirep->user_id)
            ->first();

        if (!$reg) {
            return false;
        }

        if ($reg->isCompleted() && !$activity->allow_repeat) {
            return false;
        }

        $nextLeg = $reg->getNextIncompleteLeg();
        if (!$nextLeg) {
            return false;
        }

        // Check leg airports match
        $depIcao = strtoupper($this->resolveDepIcao($pirep));
        $arrIcao = strtoupper($this->resolveArrIcao($pirep));

        if ($depIcao !== strtoupper($nextLeg->dep_icao) || $arrIcao !== strtoupper($nextLeg->arr_icao)) {
            return false;
        }

        // Check Route-based restriction if enabled
        if ($activity->subtype === 'route_based' && $nextLeg->route_id) {
            if (!$pirep->route_id || (int)$pirep->route_id !== (int)$nextLeg->route_id) {
                return false;
            }
        }

        // Check Roster mandatory airframe
        if ($activity->type === 'roster' && $nextLeg->airframe_id) {
            if (!$pirep->airframe_id || (int)$pirep->airframe_id !== (int)$nextLeg->airframe_id) {
                return false;
            }
        }

        // Check Curated Roster aircraft type
        if ($activity->type === 'curated_roster' && $nextLeg->aircraft_type_id) {
            $pirepAircraftTypeId = $pirep->aircraft_type_id ?? ($pirep->airframe ? $pirep->airframe->aircraft_type_id : null);
            if (!$pirepAircraftTypeId || (int)$pirepAircraftTypeId !== (int)$nextLeg->aircraft_type_id) {
                return false;
            }
        }

        // Sequential requirement: departed after landing of previous leg
        $lastCompleted = $reg->legProgress()
            ->where('is_valid', true)
            ->orderBy('completed_at', 'desc')
            ->first();

        $pirepDepartureTime = $this->resolveTakeoffTime($pirep);
        if ($lastCompleted && $pirepDepartureTime && $pirepDepartureTime->lt($lastCompleted->completed_at)) {
            return false;
        }

        // Record leg completion
        $completedTime = $this->resolveLandingTime($pirep) ?? Carbon::now('UTC');
        ActivityLegProgress::firstOrCreate(
            [
                'registration_id' => $reg->id,
                'activity_leg_id' => $nextLeg->id,
            ],
            [
                'pirep_id'     => $pirep->id,
                'is_valid'     => true,
                'completed_at' => $completedTime,
            ]
        );

        // Calculate Points
        $legPoints = $this->calculateActivityPoints($pirep, $activity);

        if ($activity->point_award_mode === 'per_leg') {
            $this->awardPointsToPilot($pirep->user_id, $pirep->tenant_id, $legPoints);
            $reg->points_awarded += $legPoints;
            $reg->save();
        }

        // Check if all legs are now completed
        $remainingLeg = $reg->getNextIncompleteLeg();
        if (!$remainingLeg) {
            $totalBonus = 0;
            if ($activity->point_award_mode === 'all_on_completion') {
                $totalBonus = $legPoints * $activity->legs()->count();
                $this->awardPointsToPilot($pirep->user_id, $pirep->tenant_id, $totalBonus);
            } elseif ($activity->point_award_mode === 'final_leg_only') {
                $totalBonus = $legPoints;
                $this->awardPointsToPilot($pirep->user_id, $pirep->tenant_id, $totalBonus);
            }

            $reg->markAsCompleted($totalBonus);
        }

        return true;
    }

    // ── Events & Focus Airport ───────────────────────────────────────────────

    protected function evaluateEvent(Pirep $pirep, Activity $activity): bool
    {
        if ($activity->registration_required && !$activity->isRegistered($pirep->user)) {
            return false;
        }

        $criteria = $activity->event_criteria ?? [];
        $depIcao = strtoupper($this->resolveDepIcao($pirep));
        $arrIcao = strtoupper($this->resolveArrIcao($pirep));

        if ($activity->subtype === 'route_based') {
            $allowedRoutes = $criteria['routes'] ?? [];
            if (!empty($allowedRoutes) && !in_array((int)$pirep->route_id, array_map('intval', $allowedRoutes))) {
                return false;
            }
        } else {
            $depAirports = array_map('strtoupper', $criteria['departure_airports'] ?? []);
            $arrAirports = array_map('strtoupper', $criteria['arrival_airports'] ?? []);
            $operator = strtoupper($criteria['operator'] ?? 'AND');

            $depMatches = empty($depAirports) || in_array($depIcao, $depAirports);
            $arrMatches = empty($arrAirports) || in_array($arrIcao, $arrAirports);

            if ($operator === 'AND' && (!($depMatches && $arrMatches))) {
                return false;
            }
            if ($operator === 'OR' && (!($depMatches || $arrMatches))) {
                return false;
            }
        }

        $points = $this->calculateActivityPoints($pirep, $activity);
        $this->awardPointsToPilot($pirep->user_id, $pirep->tenant_id, $points);

        // Update registration points if registered
        $reg = $activity->getPilotRegistration($pirep->user);
        if ($reg) {
            $reg->points_awarded += $points;
            $reg->save();
        }

        return true;
    }

    protected function evaluateSlottedEvent(Pirep $pirep, Activity $activity): bool
    {
        // Must have a slot reservation
        $slot = ActivitySlot::where('activity_id', $activity->id)
            ->where('user_id', $pirep->user_id)
            ->first();

        if (!$slot) {
            return false;
        }

        // Must match event criteria
        return $this->evaluateEvent($pirep, $activity);
    }

    protected function evaluateFocusAirport(Pirep $pirep, Activity $activity): bool
    {
        $focus = strtoupper($activity->event_criteria['focus_airport'] ?? '');
        if (!$focus) {
            return false;
        }

        $depIcao = strtoupper($this->resolveDepIcao($pirep));
        $arrIcao = strtoupper($this->resolveArrIcao($pirep));

        if ($depIcao !== $focus && $arrIcao !== $focus) {
            return false;
        }

        $points = $this->calculateActivityPoints($pirep, $activity);
        $this->awardPointsToPilot($pirep->user_id, $pirep->tenant_id, $points);

        return true;
    }

    // ── Community Goals & Challenges ─────────────────────────────────────────

    protected function evaluateCommunityGoal(Pirep $pirep, Activity $activity): bool
    {
        $metricValue = $this->calculateMetricValue($pirep, $activity->community_metric);
        if ($metricValue <= 0) {
            return false;
        }

        // Record contribution
        $participationPoints = $activity->points_value;
        ActivityContribution::create([
            'activity_id'    => $activity->id,
            'team_id'        => null,
            'user_id'        => $pirep->user_id,
            'pirep_id'       => $pirep->id,
            'metric_value'   => $metricValue,
            'points_awarded' => $participationPoints,
        ]);

        if ($participationPoints > 0) {
            $this->awardPointsToPilot($pirep->user_id, $pirep->tenant_id, $participationPoints);
        }

        // Increment target count
        $prevCurrent = $activity->community_current;
        $activity->community_current += $metricValue;
        $activity->save();

        // Check if goal was just reached
        if ($prevCurrent < $activity->community_target && $activity->community_current >= $activity->community_target) {
            $this->distributeCommunityGoalCompletionRewards($activity);
        }

        return true;
    }

    protected function evaluateCommunityChallenge(Pirep $pirep, Activity $activity): bool
    {
        $teams = $activity->teams;
        if ($teams->count() !== 2) {
            return false;
        }

        $team1 = $teams[0];
        $team2 = $teams[1];

        // One team per PIREP: check team 1 first, then team 2
        $matchedTeam = null;
        if ($this->pirepMatchesTeamFilters($pirep, $team1)) {
            $matchedTeam = $team1;
        } elseif ($this->pirepMatchesTeamFilters($pirep, $team2)) {
            $matchedTeam = $team2;
        }

        if (!$matchedTeam) {
            return false;
        }

        $metricValue = $this->calculateMetricValue($pirep, $matchedTeam->count_type);
        if ($metricValue <= 0) {
            return false;
        }

        $participationPoints = $activity->points_value;
        ActivityContribution::create([
            'activity_id'    => $activity->id,
            'team_id'        => $matchedTeam->id,
            'user_id'        => $pirep->user_id,
            'pirep_id'       => $pirep->id,
            'metric_value'   => $metricValue,
            'points_awarded' => $participationPoints,
        ]);

        if ($participationPoints > 0) {
            $this->awardPointsToPilot($pirep->user_id, $pirep->tenant_id, $participationPoints);
        }

        $matchedTeam->current_value += $metricValue;
        $matchedTeam->save();

        return true;
    }

    protected function pirepMatchesTeamFilters(Pirep $pirep, $team): bool
    {
        $filters = $team->filters ?? [];
        if (empty($filters)) {
            return true;
        }

        $depIcao = strtoupper($this->resolveDepIcao($pirep));
        $arrIcao = strtoupper($this->resolveArrIcao($pirep));

        if (!empty($filters['departure_airports']) && !in_array($depIcao, array_map('strtoupper', $filters['departure_airports']))) {
            return false;
        }

        if (!empty($filters['arrival_airports']) && !in_array($arrIcao, array_map('strtoupper', $filters['arrival_airports']))) {
            return false;
        }

        return true;
    }

    // ── Retroactive Reprocessing ─────────────────────────────────────────────

    /**
     * Reprocess an activity from scratch by re-evaluating historical PIREPs.
     */
    public function reprocessActivity(Activity $activity): void
    {
        DB::transaction(function () use ($activity) {
            // 1. Clear existing leg progress & contributions
            ActivityLegProgress::whereHas('registration', fn($q) => $q->where('activity_id', $activity->id))->delete();
            ActivityContribution::where('activity_id', $activity->id)->delete();

            // Reset registrations
            $activity->registrations()->update([
                'status'         => 'in_progress',
                'points_awarded' => 0,
                'completed_at'   => null,
            ]);

            // Reset community metric
            $activity->community_current = 0;
            $activity->save();

            // Reset team metrics
            foreach ($activity->teams as $team) {
                $team->current_value = 0;
                $team->save();
            }

            // 2. Fetch historical valid PIREPs within the activity timeframe
            $leeway = $activity->time_leeway_minutes ?? 0;
            $start = $activity->start_at->copy()->subMinutes($leeway);
            $end = $activity->end_at->copy()->addMinutes($leeway);

            $pireps = Pirep::where('tenant_id', $activity->tenant_id)
                ->whereIn(DB::raw('LOWER(status)'), ['accepted', 'complete', 'approved'])
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($pireps as $pirep) {
                $this->evaluatePirepForActivity($pirep, $activity);
            }
        });
    }

    // ── Slot & Wave Generation ───────────────────────────────────────────────

    /**
     * Generate slot records for a wave across designated networks.
     */
    public function generateSlotsForWave(ActivityWave $wave, array $networks = ['offline']): void
    {
        $activity = $wave->activity;
        if (!$activity) {
            return;
        }

        $startDate = $activity->start_at->copy()->startOfDay();
        $startTimeParts = explode(':', $wave->start_time);
        $endTimeParts = explode(':', $wave->end_time);

        $start = $startDate->copy()->setTime((int)($startTimeParts[0] ?? 12), (int)($startTimeParts[1] ?? 0));
        $end = $startDate->copy()->setTime((int)($endTimeParts[0] ?? 16), (int)($endTimeParts[1] ?? 0));

        if ($end->lt($start)) {
            $end->addDay();
        }

        $interval = max(5, $wave->interval_minutes);

        foreach ($networks as $network) {
            $current = $start->copy();
            while ($current->lt($end)) {
                ActivitySlot::firstOrCreate(
                    [
                        'activity_id' => $activity->id,
                        'network'     => $network,
                        'slot_time'   => $current->copy(),
                    ],
                    [
                        'wave_id' => $wave->id,
                    ]
                );

                $current->addMinutes($interval);
            }
        }
    }

    // ── Validation & Inspection Helpers ──────────────────────────────────────

    protected function isWithinTimeWindow(Pirep $pirep, Activity $activity): bool
    {
        $leeway = $activity->time_leeway_minutes ?? 0;
        $windowStart = $activity->start_at->copy()->subMinutes($leeway);
        $windowEnd = $activity->end_at->copy()->addMinutes($leeway);

        $takeoff = $this->resolveTakeoffTime($pirep) ?? $pirep->created_at;
        $landing = $this->resolveLandingTime($pirep) ?? $pirep->created_at;

        return match ($activity->time_award_scale) {
            'takeoff_only'  => $takeoff && $takeoff->between($windowStart, $windowEnd),
            'landing_only'  => $landing && $landing->between($windowStart, $windowEnd),
            'entire_flight' => $takeoff && $landing && $takeoff->between($windowStart, $windowEnd) && $landing->between($windowStart, $windowEnd),
            'any_part'      => ($takeoff && $takeoff->between($windowStart, $windowEnd)) || ($landing && $landing->between($windowStart, $windowEnd)),
            default         => true,
        };
    }

    protected function passesRestrictions(Pirep $pirep, Activity $activity): bool
    {
        $restrictions = $activity->restrictions ?? [];
        if (empty($restrictions)) {
            return true;
        }

        // Network Restriction
        if (!empty($restrictions['networks'])) {
            $pirepNet = strtolower($pirep->network ?? 'offline');
            $allowedNets = array_map('strtolower', $restrictions['networks']);
            if (!in_array($pirepNet, $allowedNets)) {
                return false;
            }
        }

        // Fleet Restriction
        if (!empty($restrictions['fleets'])) {
            $pirepFleet = $pirep->aircraft_type_id ?? ($pirep->airframe ? $pirep->airframe->aircraft_type_id : null);
            if (!$pirepFleet || !in_array($pirepFleet, $restrictions['fleets'])) {
                return false;
            }
        }

        // Callsign Prefix Restriction
        if (!empty($restrictions['callsign_prefixes'])) {
            $callsign = strtoupper($pirep->callsign ?? '');
            $matched = false;
            foreach ($restrictions['callsign_prefixes'] as $prefix) {
                if (str_starts_with($callsign, strtoupper($prefix))) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        // Landing Rate Restriction (in fpm)
        if (isset($restrictions['max_landing_rate']) && $restrictions['max_landing_rate'] !== null) {
            $rate = abs((float)($pirep->landing_rate ?? 0));
            if ($rate > abs((float)$restrictions['max_landing_rate'])) {
                return false;
            }
        }

        return true;
    }

    protected function calculateActivityPoints(Pirep $pirep, Activity $activity): int
    {
        if ($activity->points_mode === 'percentage') {
            $basePoints = max(100, $pirep->points_awarded);
            return (int) round(($basePoints * $activity->points_value) / 100);
        }

        return (int) $activity->points_value;
    }

    protected function calculateMetricValue(Pirep $pirep, ?string $metric): int
    {
        return match ($metric) {
            'passengers'  => max(1, (int)($pirep->passengers ?? 0)),
            'freight'     => max(1, (int)($pirep->cargo ?? 0)),
            'flights'     => 1,
            'distance'    => max(1, (int)($pirep->distance ?? 0)),
            'flight_time' => max(1, (int)round(($pirep->flight_time ?? 60) / 60)),
            default       => 1,
        };
    }

    protected function awardPointsToPilot(int $userId, int $tenantId, int $points): void
    {
        if ($points <= 0) {
            return;
        }

        $profile = PilotProfile::firstOrCreate(
            ['user_id' => $userId, 'tenant_id' => $tenantId],
            ['flight_time' => 0, 'points' => 0]
        );

        $profile->points += $points;
        $profile->save();
    }

    protected function distributeCommunityGoalCompletionRewards(Activity $activity): void
    {
        $completionPoints = $activity->community_completion_points;
        if ($completionPoints <= 0) {
            return;
        }

        $contributors = ActivityContribution::where('activity_id', $activity->id)
            ->select('user_id', DB::raw('SUM(metric_value) as total_metric'))
            ->groupBy('user_id')
            ->orderBy('total_metric', 'desc')
            ->get();

        $totalContributors = $contributors->count();
        if ($totalContributors === 0) {
            return;
        }

        foreach ($contributors as $index => $contributor) {
            $multiplier = 1.0;

            if ($activity->community_tiered_multipliers) {
                $rankRatio = ($index + 1) / $totalContributors;
                if ($rankRatio <= 0.10) {
                    $multiplier = 3.0; // Top 10%
                } elseif ($rankRatio <= 0.25) {
                    $multiplier = 2.0; // Top 25%
                } elseif ($rankRatio <= 0.50) {
                    $multiplier = 1.5; // Top 50%
                } elseif ($rankRatio <= 0.75) {
                    $multiplier = 1.25; // Top 75%
                }
            }

            $awardedPoints = (int) round($completionPoints * $multiplier);
            $this->awardPointsToPilot($contributor->user_id, $activity->tenant_id, $awardedPoints);
        }
    }

    protected function resolveDepIcao(Pirep $pirep): string
    {
        if ($pirep->route && $pirep->route->departure_icao) {
            return $pirep->route->departure_icao;
        }

        if (is_array($pirep->flight_log) && !empty($pirep->flight_log['origin'])) {
            return $pirep->flight_log['origin'];
        }

        return $pirep->dep_icao ?? '';
    }

    protected function resolveArrIcao(Pirep $pirep): string
    {
        if ($pirep->route && $pirep->route->arrival_icao) {
            return $pirep->route->arrival_icao;
        }

        if (is_array($pirep->flight_log) && !empty($pirep->flight_log['destination'])) {
            return $pirep->flight_log['destination'];
        }

        return $pirep->arr_icao ?? '';
    }

    protected function resolveTakeoffTime(Pirep $pirep): ?Carbon
    {
        if (!empty($pirep->flight_log['takeoff_time'])) {
            return Carbon::parse($pirep->flight_log['takeoff_time']);
        }

        return $pirep->created_at;
    }

    protected function resolveLandingTime(Pirep $pirep): ?Carbon
    {
        if (!empty($pirep->flight_log['landing_time'])) {
            return Carbon::parse($pirep->flight_log['landing_time']);
        }

        return $pirep->updated_at ?? $pirep->created_at;
    }
}
