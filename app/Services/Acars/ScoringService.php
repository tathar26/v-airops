<?php

namespace App\Services\Acars;

use App\Models\Pirep;
use App\Models\AcarsEvent;
use App\Models\AcarsPosition;
use App\Models\Tenant;
use App\Jobs\RecalculatePilotStatistics;

class ScoringService
{
    /**
     * Evaluate vertical touchdown speed against virtual airline tolerances.
     *
     * @param float $fpm Touchdown rate in feet per minute
     * @param array|null $rules Custom scoring rules or null for defaults
     * @return array{grade: string, penalty: int}
     */
    public function evaluateLandingGrade(float $fpm, ?array $rules = null): array
    {
        $rules = $rules ?? Tenant::defaultScoringSettings();
        $absFpm = (int) round(abs($fpm));

        if ($absFpm <= ($rules['fpm_butter_threshold'] ?? 120)) {
            return ['grade' => 'Butter / Perfect', 'penalty' => 0];
        }
        if ($absFpm <= ($rules['fpm_good_threshold'] ?? 200)) {
            return ['grade' => 'Smooth / Good', 'penalty' => 0];
        }
        if ($absFpm <= ($rules['fpm_fair_threshold'] ?? 350)) {
            return ['grade' => 'Normal / Fair', 'penalty' => 0];
        }
        if ($absFpm <= ($rules['fpm_firm_threshold'] ?? 500)) {
            return ['grade' => 'Firm', 'penalty' => (int) ($rules['fpm_firm_penalty'] ?? 10)];
        }
        if ($absFpm <= ($rules['fpm_hard_threshold'] ?? 650)) {
            return ['grade' => 'Hard', 'penalty' => (int) ($rules['fpm_hard_penalty'] ?? 30)];
        }
        if ($absFpm <= ($rules['fpm_danger_threshold'] ?? 800)) {
            return ['grade' => 'Very Hard', 'penalty' => (int) ($rules['fpm_reject_penalty'] ?? 60)];
        }

        return ['grade' => 'Structural Danger', 'penalty' => (int) ($rules['fpm_danger_penalty'] ?? 100)];
    }

    /**
     * Comprehensive PIREP scoring, failure rule evaluation, and status calculation engine.
     *
     * Metric: Touchdown Rate (FPM) is used for landing evaluations, points, and failure rules.
     *
     * @param array $data Flight parameters, telemetry summary, and OFP data
     * @param int|null $tenantId Virtual airline context ID
     * @return array Result containing status, total_score, hours_awarded, points_awarded, failure_reasons, breakdown
     */
    public function evaluatePirepData(array $data, ?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? ($data['tenant_id'] ?? null);
        $rules = Tenant::defaultScoringSettings();

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $rules = $tenant->getScoringSettings();
            }
        }

        $penalties = [];
        $bonuses = [];
        $failureReasons = [];

        // Base Starting Points
        $startingPoints = (int) ($rules['base_points'] ?? 150);
        $totalScore = $startingPoints;
        $bonuses[] = ['description' => 'Starting Base Points', 'points' => $startingPoints];

        // 1. Landing Evaluation (Touchdown Rate in FPM - PRIMARY METRIC)
        $rawFpm = (float) ($data['touchdown_fpm'] ?? ($data['touchdown_rate_fpm'] ?? 0.0));
        $absFpm = (int) round(abs($rawFpm));
        $landingGrade = 'Normal / Fair';
        $fpmPoints = 0;

        $butterThresh = (int) ($rules['fpm_butter_threshold'] ?? 120);
        $goodThresh = (int) ($rules['fpm_good_threshold'] ?? 200);
        $fairThresh = (int) ($rules['fpm_fair_threshold'] ?? 350);
        $firmThresh = (int) ($rules['fpm_firm_threshold'] ?? 500);
        $hardThresh = (int) ($rules['fpm_hard_threshold'] ?? 650);
        $rejectThresh = (int) ($rules['fpm_reject_threshold'] ?? 650);
        $dangerThresh = (int) ($rules['fpm_danger_threshold'] ?? 800);

        if ($absFpm <= $butterThresh) {
            $landingGrade = 'Butter / Perfect';
            $fpmPoints = (int) ($rules['fpm_butter_points'] ?? 50);
        } elseif ($absFpm <= $goodThresh) {
            $landingGrade = 'Smooth / Good';
            $fpmPoints = (int) ($rules['fpm_good_points'] ?? 30);
        } elseif ($absFpm <= $fairThresh) {
            $landingGrade = 'Normal / Fair';
            $fpmPoints = (int) ($rules['fpm_fair_points'] ?? 15);
        } elseif ($absFpm <= $firmThresh) {
            $landingGrade = 'Firm';
            $fpmPoints = -abs((int) ($rules['fpm_firm_penalty'] ?? 10));
        } elseif ($absFpm <= $hardThresh) {
            $landingGrade = 'Hard';
            $fpmPoints = -abs((int) ($rules['fpm_hard_penalty'] ?? 30));
        } elseif ($absFpm >= $dangerThresh) {
            $landingGrade = 'Structural Danger';
            $fpmPoints = -abs((int) ($rules['fpm_danger_penalty'] ?? 100));
            $failureReasons[] = "Invalidated due to Extreme Landing Rate (-{$absFpm} FPM >= {$dangerThresh} FPM)";
        } else {
            // Between hard and danger (exceeds rejection threshold)
            $landingGrade = 'Very Hard (Excessive Touchdown Rate)';
            $fpmPoints = -abs((int) ($rules['fpm_reject_penalty'] ?? 60));
            $failureReasons[] = "Rejected due to Hard Landing Rate (-{$absFpm} FPM >= {$rejectThresh} FPM)";
        }

        $totalScore += $fpmPoints;
        $fpmDisplay = $absFpm > 0 ? "-{$absFpm}" : "0";
        if ($fpmPoints >= 0) {
            $bonuses[] = [
                'description' => "Landing Evaluation ({$landingGrade}: {$fpmDisplay} FPM)",
                'points' => $fpmPoints,
            ];
        } else {
            $penalties[] = [
                'category' => 'Landing Evaluation',
                'description' => "Landing Evaluation ({$landingGrade}: {$fpmDisplay} FPM)",
                'points_deducted' => abs($fpmPoints),
            ];
        }

        // 2. Engines Operations
        $startInterval = (int) ($data['engine_start_interval_seconds'] ?? 60);
        $minStartInterval = (int) ($rules['engine_start_interval_seconds'] ?? 60);
        if ($startInterval >= $minStartInterval) {
            $bonusPts = (int) ($rules['engine_start_interval_bonus'] ?? 10);
            $totalScore += $bonusPts;
            $bonuses[] = ['description' => "Engine Start Sequence (>= 00:01:00 between starts)", 'points' => $bonusPts];
        }

        $enginesShutdownClean = (bool) ($data['engines_shutdown_clean'] ?? true);
        if ($enginesShutdownClean) {
            $shutdownBonus = (int) ($rules['engines_shutdown_clean_bonus'] ?? 10);
            $totalScore += $shutdownBonus;
            $bonuses[] = ['description' => 'Engines Shutdown Properly', 'points' => $shutdownBonus];
        }

        $warmupSecs = (int) ($data['engine_warmup_seconds'] ?? 180);
        $reqWarmup = (int) ($rules['engine_warmup_seconds'] ?? 180);
        if ($warmupSecs < $reqWarmup) {
            $warmupPenalty = (int) ($rules['engine_warmup_penalty'] ?? 30);
            $totalScore -= $warmupPenalty;
            $penalties[] = [
                'category' => 'Engine Wear & Tear',
                'description' => "Engines Not Warmed Up (< " . sprintf('%02d:%02d', floor($reqWarmup/60), $reqWarmup%60) . ")",
                'points_deducted' => $warmupPenalty,
            ];
        }

        $cooldownSecs = (int) ($data['engine_cooldown_seconds'] ?? 180);
        $reqCooldown = (int) ($rules['engine_cooldown_seconds'] ?? 180);
        if ($cooldownSecs < $reqCooldown) {
            $cooldownPenalty = (int) ($rules['engine_cooldown_penalty'] ?? 30);
            $totalScore -= $cooldownPenalty;
            $penalties[] = [
                'category' => 'Engine Wear & Tear',
                'description' => "Engines Not Cooled Down (< " . sprintf('%02d:%02d', floor($reqCooldown/60), $reqCooldown%60) . ")",
                'points_deducted' => $cooldownPenalty,
            ];
        }

        // 3. Flaps Operations
        $flapsRetractedParking = (bool) ($data['flaps_retracted_before_parking'] ?? true);
        $flapsRetractedEarly = (bool) ($data['flaps_retracted_too_early'] ?? false);
        if ($flapsRetractedParking && !$flapsRetractedEarly) {
            $flapsBonus = (int) ($rules['flaps_parking_bonus'] ?? 10);
            $totalScore += $flapsBonus;
            $bonuses[] = ['description' => 'Flaps Retracted Before Parking', 'points' => $flapsBonus];
        } else {
            $flapsPen = (int) ($rules['flaps_retracted_violation_penalty'] ?? 10);
            $totalScore -= $flapsPen;
            $penalties[] = [
                'category' => 'Flaps Violation',
                'description' => 'Flaps retracted too early or not retracted after landing before parking',
                'points_deducted' => $flapsPen,
            ];
        }

        $takeoffFlapsSet = (bool) ($data['takeoff_flaps_set'] ?? true);
        if (!$takeoffFlapsSet) {
            $toFlapsPen = (int) ($rules['takeoff_flaps_penalty'] ?? 10);
            $totalScore -= $toFlapsPen;
            $penalties[] = [
                'category' => 'Flaps Violation',
                'description' => 'Flaps not set for Takeoff (Min Level: 1+F)',
                'points_deducted' => $toFlapsPen,
            ];
        }

        // 4. Flight Duration Bonuses
        $blockMins = (int) ($data['block_time_minutes'] ?? 0);
        $flightLengthPts = 0;
        if ($blockMins < 60) {
            $flightLengthPts = (int) ($rules['flight_time_bonus_under_1h'] ?? 10);
        } elseif ($blockMins <= 120) {
            $flightLengthPts = (int) ($rules['flight_time_bonus_1_to_2h'] ?? 25);
        } elseif ($blockMins <= 180) {
            $flightLengthPts = (int) ($rules['flight_time_bonus_2_to_3h'] ?? 50);
        } elseif ($blockMins <= 240) {
            $flightLengthPts = (int) ($rules['flight_time_bonus_3_to_4h'] ?? 75);
        } else {
            $flightLengthPts = (int) ($rules['flight_time_bonus_over_4h'] ?? 100);
        }
        $totalScore += $flightLengthPts;
        $bonuses[] = ['description' => "Flight Duration Bonus ({$blockMins} mins)", 'points' => $flightLengthPts];

        // 5. Preparation Time (Between 00:20:00 and 00:40:00)
        $prepMins = (int) ($data['prep_time_minutes'] ?? 25);
        if ($prepMins >= 20 && $prepMins <= 40) {
            $prepPts = (int) ($rules['prep_time_bonus'] ?? 25);
            $totalScore += $prepPts;
            $bonuses[] = ['description' => "Realistic Preparation Time Bonus ({$prepMins} mins)", 'points' => $prepPts];
        }

        // 6. Fuel Operations
        $landingFuelKg = (float) ($data['landing_fuel_kg'] ?? ($data['fuel_used_kg'] > 0 ? 3000 : 3000));
        $minFuel = (float) ($rules['min_landing_fuel_kg'] ?? 1000);
        $maxFuel = (float) ($rules['max_landing_fuel_kg'] ?? 5000);

        if ($landingFuelKg < $minFuel) {
            $lowFuelPen = (int) ($rules['low_fuel_penalty'] ?? 50);
            $totalScore -= $lowFuelPen;
            $penalties[] = [
                'category' => 'Fuel Violation',
                'description' => "Landing with too little Fuel (< " . number_format($minFuel) . " kg)",
                'points_deducted' => $lowFuelPen,
            ];
        } elseif ($landingFuelKg > $maxFuel) {
            $excessFuelPen = (int) ($rules['excess_fuel_penalty'] ?? 25);
            $totalScore -= $excessFuelPen;
            $penalties[] = [
                'category' => 'Fuel Violation',
                'description' => "Landing with too much Fuel (> " . number_format($maxFuel) . " kg)",
                'points_deducted' => $excessFuelPen,
            ];
        }

        // 7. Diversion Check
        $originIcao = strtoupper($data['origin_icao'] ?? '');
        $destIcao = strtoupper($data['destination_icao'] ?? '');
        $actualDestIcao = strtoupper($data['actual_destination_icao'] ?? $destIcao);

        $isDiversion = ($actualDestIcao !== '' && $destIcao !== '' && $actualDestIcao !== $destIcao);
        if ($isDiversion) {
            $completionPct = (float) ($data['route_completion_pct'] ?? 0.80);
            $deduction = (int) round(150 * (1.0 - $completionPct));
            $totalScore -= $deduction;
            $penalties[] = [
                'category' => 'Diversion',
                'description' => "Diversion Detected (Landed at {$actualDestIcao} instead of {$destIcao})",
                'points_deducted' => $deduction,
            ];
            $failureReasons[] = "Diversion Detected: Landed at {$actualDestIcao} instead of {$destIcao}";
        }

        // 8. Network Connectivity
        $network = strtoupper($data['network_connected'] ?? 'OFFLINE');
        if (in_array($network, ['VATSIM', 'IVAO', 'POSCON'])) {
            $netBonus = (int) ($rules['online_network_bonus'] ?? 50);
            $totalScore += $netBonus;
            $bonuses[] = ['description' => "Online Network Bonus ($network)", 'points' => $netBonus];
        }

        // 9. Social (Shared Cockpit)
        $sharedCockpit = (bool) ($data['shared_cockpit'] ?? false);
        if ($sharedCockpit) {
            $sharedBonus = (int) ($rules['shared_cockpit_bonus'] ?? 50);
            $totalScore += $sharedBonus;
            $bonuses[] = ['description' => 'Shared Cockpit Flight Bonus', 'points' => $sharedBonus];
        }

        // --- FAILURE RULES EVALUATION ---
        if (!empty($data['gear_up_landing'])) {
            $failureReasons[] = 'Gear Up Landing Detected';
        }

        if (!empty($data['midair_refuel_detected'])) {
            $failureReasons[] = 'Mid-air Refueling / Fuel Increase Detected Mid-Flight';
        }

        if (!empty($data['rejected_livery'])) {
            $failureReasons[] = 'Flown Livery is Rejected';
        }

        $simRateMax = (float) ($data['sim_rate_max'] ?? 1.0);
        $maxAllowedSimRate = (float) ($rules['max_sim_rate'] ?? 1.0);
        if ($simRateMax > $maxAllowedSimRate) {
            $failureReasons[] = "Time Acceleration Detected (Sim Rate: {$simRateMax}x > allowed {$maxAllowedSimRate}x)";
        }

        $bounceCount = (int) ($data['bounce_count'] ?? 0);
        $maxAllowedBounces = (int) ($rules['max_bounces'] ?? 1);
        if ($bounceCount > $maxAllowedBounces) {
            $failureReasons[] = "Multiple Landings or Bounce Detected ({$bounceCount} bounces > max {$maxAllowedBounces})";
        }

        $schedMins = (int) ($data['scheduled_time_minutes'] ?? $blockMins);
        if ($schedMins > 0) {
            $maxPermitted = max($schedMins + 35, (int) round($schedMins * 1.20));
            if ($blockMins > $maxPermitted) {
                $failureReasons[] = "Longer than permitted scheduled Flight Length ({$blockMins}m vs max {$maxPermitted}m permitted)";
            }
        }

        $avgMins = (int) ($data['average_time_minutes'] ?? $schedMins);
        if ($avgMins > 0) {
            $maxExpected = max($avgMins + 60, (int) round($avgMins * 1.20));
            if ($blockMins > $maxExpected && !in_array("Longer than permitted scheduled Flight Length", $failureReasons)) {
                $failureReasons[] = "Longer than expected flight length recorded ({$blockMins}m vs max expected {$maxExpected}m)";
            }
        }

        if (!empty($data['new_livery_detected'])) {
            $failureReasons[] = 'New Livery or Airframe Detected';
        }

        if (!empty($data['random_review'])) {
            $failureReasons[] = 'Random PIREP Review Sampled';
        }

        $invalidateOnNegative = (bool) ($rules['invalidate_on_negative_score'] ?? true);
        if ($totalScore < 0 && $invalidateOnNegative) {
            $failureReasons[] = "Negative Points Awarded ({$totalScore} pts)";
        }

        // --- DETERMINE STATUS & CREDITS ---
        $status = 'complete';
        $hoursAwarded = $blockMins;
        $pointsAwarded = max(0, $totalScore);

        $isInvalidated = false;
        $isRejected = false;
        $isAwaitingReview = false;

        foreach ($failureReasons as $reason) {
            if (
                str_contains($reason, 'Extreme Landing Rate') ||
                str_contains($reason, 'Gear Up') ||
                str_contains($reason, 'Mid-air Refueling') ||
                str_contains($reason, 'Livery is Rejected') ||
                str_contains($reason, 'permitted scheduled Flight Length') ||
                str_contains($reason, 'Negative Points') ||
                str_contains($reason, 'Time Acceleration Detected')
            ) {
                $isInvalidated = true;
            } elseif (str_contains($reason, 'Hard Landing Rate')) {
                $isRejected = true;
            } else {
                $isAwaitingReview = true;
            }
        }

        if ($isInvalidated) {
            $status = 'invalidated';
            $hoursAwarded = 0;
            $pointsAwarded = 0;
        } elseif ($isRejected) {
            $status = 'rejected';
            $hoursAwarded = $blockMins;
            $pointsAwarded = 0;
        } elseif ($isAwaitingReview) {
            $status = 'awaiting_review';
            $hoursAwarded = $blockMins;
            $pointsAwarded = max(0, $totalScore);
        }

        return [
            'status' => $status,
            'total_score' => $totalScore,
            'landing_grade' => $landingGrade,
            'hours_awarded' => $hoursAwarded,
            'points_awarded' => $pointsAwarded,
            'failure_reasons' => $failureReasons,
            'penalties' => $penalties,
            'bonuses' => $bonuses,
        ];
    }

    /**
     * Rescore existing PIREPs and recalculate statistics for all affected pilots.
     *
     * @param int|null $tenantId Optional tenant ID to scope recalculation to a specific airline
     * @param bool $forceStatus Whether to overwrite manual staff acceptances/rejections
     * @return array{rescored_count: int, affected_pilots: int}
     */
    public function rescorePireps(?int $tenantId = null, bool $forceStatus = false): array
    {
        $query = Pirep::withoutGlobalScopes();
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $pireps = $query->get();
        $rescoredCount = 0;
        $userIds = [];

        foreach ($pireps as $pirep) {
            $fLog = $pirep->flight_log ?? [];
            if (is_string($fLog)) {
                $fLog = json_decode($fLog, true) ?? [];
            }

            $evalData = [
                'touchdown_fpm' => (float) ($fLog['touchdown_fpm'] ?? $pirep->touchdown_rate_fpm ?? 0),
                'touchdown_gforce' => (float) ($fLog['touchdown_gforce'] ?? $pirep->landing_g ?? 1.0),
                'gear_up_landing' => (bool) ($fLog['gear_up_landing'] ?? false),
                'midair_refuel_detected' => (bool) ($fLog['midair_refuel_detected'] ?? false),
                'sim_rate_max' => (float) ($fLog['sim_rate_max'] ?? 1.0),
                'bounce_count' => (int) ($fLog['bounce_count'] ?? 0),
                'block_time_minutes' => (int) ($fLog['block_time_minutes'] ?? $pirep->flight_time ?? 0),
                'prep_time_minutes' => (int) ($fLog['prep_time_minutes'] ?? 25),
                'fuel_used_kg' => (float) ($fLog['fuel_used_kg'] ?? $pirep->fuel_used ?? 0),
                'landing_fuel_kg' => (float) ($fLog['landing_fuel_kg'] ?? 3000),
                'origin_icao' => $fLog['origin'] ?? ($pirep->route?->departure_icao ?? ''),
                'destination_icao' => $fLog['destination'] ?? ($pirep->route?->arrival_icao ?? ''),
                'actual_destination_icao' => $fLog['destination'] ?? ($pirep->route?->arrival_icao ?? ''),
                'network_connected' => $fLog['network'] ?? ($pirep->network ?? 'OFFLINE'),
                'shared_cockpit' => (bool) ($fLog['shared_cockpit'] ?? false),
                'engine_start_interval_seconds' => (int) ($fLog['engine_start_interval_seconds'] ?? 60),
                'engines_shutdown_clean' => (bool) ($fLog['engines_shutdown_clean'] ?? true),
                'engine_warmup_seconds' => (int) ($fLog['engine_warmup_seconds'] ?? 180),
                'engine_cooldown_seconds' => (int) ($fLog['engine_cooldown_seconds'] ?? 180),
                'flaps_retracted_before_parking' => (bool) ($fLog['flaps_retracted_before_parking'] ?? true),
                'flaps_retracted_too_early' => (bool) ($fLog['flaps_retracted_too_early'] ?? false),
                'takeoff_flaps_set' => (bool) ($fLog['takeoff_flaps_set'] ?? true),
                'scheduled_time_minutes' => (int) ($fLog['block_time_minutes'] ?? $pirep->flight_time ?? 0),
                'average_time_minutes' => (int) ($fLog['block_time_minutes'] ?? $pirep->flight_time ?? 0),
            ];

            $res = $this->evaluatePirepData($evalData, $pirep->tenant_id);

            // Keep manual staff acceptances/invalidations unless $forceStatus is true
            $currentStatus = strtolower($pirep->status ?? 'accepted');
            $finalStatus = (!$forceStatus && in_array($currentStatus, ['accepted', 'invalidated', 'rejected']))
                ? $currentStatus
                : $res['status'];

            $fLog['eval_status'] = $finalStatus;
            $fLog['landing_grade'] = $res['landing_grade'];
            $fLog['failure_reasons'] = $res['failure_reasons'];
            $fLog['penalties'] = $res['penalties'];
            $fLog['bonuses'] = $res['bonuses'];

            $pirep->update([
                'status' => $finalStatus,
                'flight_time' => $res['hours_awarded'],
                'points_awarded' => $res['points_awarded'],
                'flight_log' => $fLog,
            ]);

            if ($pirep->user_id) {
                $userIds[$pirep->user_id] = true;
            }
            $rescoredCount++;
        }

        foreach (array_keys($userIds) as $userId) {
            RecalculatePilotStatistics::dispatchSync($userId);
        }

        return [
            'rescored_count' => $rescoredCount,
            'affected_pilots' => count($userIds),
        ];
    }
}
