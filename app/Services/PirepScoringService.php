<?php

namespace App\Services;

use App\Models\Pirep;
use App\Models\ScoringCriteria;
use App\Models\PilotProfile;
use App\Models\Rank;

class PirepScoringService
{
    /**
     * Process a PIREP and award/deduct points based on criteria.
     * Updates the PilotProfile's flight time, points, and rank.
     */
    public function processPirep(Pirep $pirep)
    {
        // Only process Accepted or Complete PIREPs
        if (!in_array($pirep->status, ['Accepted', 'Complete'])) {
            return;
        }

        // Avoid re-scoring if already scored
        if ($pirep->points_awarded !== 0) {
            return; 
        }

        $tenantId = $pirep->tenant_id;
        $userId = $pirep->user_id;

        // Calculate flight time (if not set, we'll try to get it from route block_time as fallback)
        $flightTimeMinutes = $pirep->flight_time;
        if (!$flightTimeMinutes && $pirep->route) {
            $parts = explode(':', $pirep->route->block_time);
            if (count($parts) >= 2) {
                $flightTimeMinutes = (int)$parts[0] * 60 + (int)$parts[1];
            }
        }
        $pirep->flight_time = $flightTimeMinutes;

        // Base points for a flight
        $points = 100; // default base points

        // Fetch custom scoring criteria for this tenant
        $criteria = ScoringCriteria::where('tenant_id', $tenantId)->get();

        foreach ($criteria as $criterion) {
            if ($this->evaluateCondition($pirep, $criterion->condition)) {
                $points += $criterion->points_awarded;
            }
        }

        // Store points on PIREP
        $pirep->points_awarded = $points;
        $pirep->save();

        // Update Pilot Profile
        $profile = PilotProfile::firstOrCreate(
            ['user_id' => $userId, 'tenant_id' => $tenantId],
            ['flight_time' => 0, 'points' => 0]
        );

        $profile->flight_time += $flightTimeMinutes;
        $profile->points += $points;

        // Check for Rank upgrade
        $newRank = Rank::where('tenant_id', $tenantId)
            ->where('min_hours', '<=', floor($profile->flight_time / 60))
            ->where('min_points', '<=', $profile->points)
            ->orderBy('min_hours', 'desc')
            ->orderBy('min_points', 'desc')
            ->first();

        if ($newRank && $profile->rank_id !== $newRank->id) {
            $profile->rank_id = $newRank->id;
        }

        $profile->save();
    }

    /**
     * Evaluate a basic condition string against the PIREP.
     * e.g., 'touchdown_rate_fpm < -500'
     */
    protected function evaluateCondition(Pirep $pirep, string $condition): bool
    {
        // Very basic evaluator for demo purposes. 
        // In a real system, you might use a rule engine or ExpressionLanguage.
        
        // Example: 'touchdown_rate_fpm < -500'
        if (preg_match('/([a-z_]+)\s*(<|>|<=|>=|==|!=)\s*(-?\d+)/i', $condition, $matches)) {
            $field = $matches[1];
            $operator = $matches[2];
            $value = (float)$matches[3];

            $pirepValue = (float)($pirep->$field ?? 0);

            return match ($operator) {
                '<' => $pirepValue < $value,
                '>' => $pirepValue > $value,
                '<=' => $pirepValue <= $value,
                '>=' => $pirepValue >= $value,
                '==' => $pirepValue == $value,
                '!=' => $pirepValue != $value,
                default => false,
            };
        }

        // Simple boolean flag check if condition exists in flight_log (just as an example)
        if (is_array($pirep->flight_log)) {
            // e.g. condition could be 'diversion_detected'
            if (isset($pirep->flight_log[$condition]) && $pirep->flight_log[$condition]) {
                return true;
            }
        }

        return false;
    }
}
