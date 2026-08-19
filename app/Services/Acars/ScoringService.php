<?php

namespace App\Services\Acars;

use App\Models\Pirep;
use App\Models\AcarsEvent;
use App\Models\AcarsPosition;

class ScoringService
{
    /**
     * Evaluate vertical touchdown speed against virtual airline tolerances.
     *
     * @param float $fpm
     * @return array{grade: string, penalty: int}
     */
    public function evaluateLandingGrade(float $fpm): array
    {
        $absFpm = abs($fpm);

        if ($absFpm <= 124) {
            return ['grade' => 'Perfect', 'penalty' => 0];
        }
        if ($absFpm <= 180) {
            return ['grade' => 'Butter / Good', 'penalty' => 0];
        }
        if ($absFpm <= 350) {
            return ['grade' => 'Fair', 'penalty' => 10];
        }
        if ($absFpm <= 500) {
            return ['grade' => 'Firm', 'penalty' => 15];
        }
        if ($absFpm <= 800) {
            return ['grade' => 'Hard', 'penalty' => 35];
        }

        return ['grade' => 'Structural Danger', 'penalty' => 60];
    }

    /**
     * Comprehensive PIREP scoring, failure rule evaluation, and status calculation engine.
     *
     * @param array $data Flight parameters, telemetry summary, and OFP data
     * @return array Result containing status, total_score, hours_awarded, points_awarded, failure_reasons, breakdown
     */
    public function evaluatePirepData(array $data): array
    {
        $penalties = [];
        $bonuses = [];
        $failureReasons = [];

        // Base Starting Points = 150
        $startingPoints = 150;
        $totalScore = $startingPoints;
        $bonuses[] = ['description' => 'Starting Base Points', 'points' => 150];

        // 1. Landing Evaluation (G-Force)
        $gForce = (float) ($data['touchdown_gforce'] ?? 1.0);
        $landingGrade = 'Firm';
        $gPoints = 0;

        if ($gForce >= 2.00) {
            $landingGrade = 'Extremely hard (Excessive G-Force)';
            $gPoints = -50;
            $failureReasons[] = 'Invalidated due to Excessive Landing G-Force (>= 2.00 G)';
        } elseif ($gForce >= 1.70) {
            $landingGrade = 'Very hard (Excessive G-Force)';
            $gPoints = -25;
            $failureReasons[] = 'Rejected due to Excessive Landing G-Force (1.70 G - 1.99 G)';
        } elseif ($gForce >= 1.50) {
            $landingGrade = 'Hard';
            $gPoints = -10;
        } elseif ($gForce >= 1.40) {
            $landingGrade = 'Firm';
            $gPoints = 0;
        } elseif ($gForce >= 1.35) {
            $landingGrade = 'Fair';
            $gPoints = 10;
        } elseif ($gForce >= 1.25) {
            $landingGrade = 'Good';
            $gPoints = 25;
        } elseif ($gForce >= 1.20) {
            $landingGrade = 'Perfect';
            $gPoints = 50;
        } elseif ($gForce >= 1.15) {
            $landingGrade = 'Good';
            $gPoints = 25;
        } elseif ($gForce >= 1.10) {
            $landingGrade = 'Fair';
            $gPoints = 10;
        } elseif ($gForce >= 1.05) {
            $landingGrade = 'Soft';
            $gPoints = -10;
        } else {
            $landingGrade = 'Very soft';
            $gPoints = -25;
        }

        $totalScore += $gPoints;
        if ($gPoints >= 0) {
            $bonuses[] = ['description' => "Landing Grade Evaluation ($landingGrade: {$gForce} G)", 'points' => $gPoints];
        } else {
            $penalties[] = ['category' => 'Landing Evaluation', 'description' => "Landing Grade ($landingGrade: {$gForce} G)", 'points_deducted' => abs($gPoints)];
        }

        // 2. Engines (Airbus)
        $startInterval = (int) ($data['engine_start_interval_seconds'] ?? 60);
        if ($startInterval >= 60) {
            $totalScore += 10;
            $bonuses[] = ['description' => 'Engine Start Sequence (>= 00:01:00 between starts)', 'points' => 10];
        }

        $enginesShutdownClean = (bool) ($data['engines_shutdown_clean'] ?? true);
        if ($enginesShutdownClean) {
            $totalScore += 10;
            $bonuses[] = ['description' => 'Engines Shutdown Properly', 'points' => 10];
        }

        $warmupSecs = (int) ($data['engine_warmup_seconds'] ?? 180);
        if ($warmupSecs < 180) {
            $totalScore -= 30;
            $penalties[] = ['category' => 'Engine Wear & Tear', 'description' => 'Engines Not Warmed Up (< 00:03:00)', 'points_deducted' => 30];
        }

        $cooldownSecs = (int) ($data['engine_cooldown_seconds'] ?? 180);
        if ($cooldownSecs < 180) {
            $totalScore -= 30;
            $penalties[] = ['category' => 'Engine Wear & Tear', 'description' => 'Engines Not Cooled Down (< 00:03:00)', 'points_deducted' => 30];
        }

        // 3. Flaps
        $flapsRetractedParking = (bool) ($data['flaps_retracted_before_parking'] ?? true);
        $flapsRetractedEarly = (bool) ($data['flaps_retracted_too_early'] ?? false);
        if ($flapsRetractedParking && !$flapsRetractedEarly) {
            $totalScore += 10;
            $bonuses[] = ['description' => 'Flaps Retracted Before Parking', 'points' => 10];
        } else {
            $totalScore -= 10;
            $penalties[] = ['category' => 'Flaps Violation', 'description' => 'Flaps retracted too early or not retracted after landing before parking', 'points_deducted' => 10];
        }

        $takeoffFlapsSet = (bool) ($data['takeoff_flaps_set'] ?? true);
        if (!$takeoffFlapsSet) {
            $totalScore -= 10;
            $penalties[] = ['category' => 'Flaps Violation', 'description' => 'Flaps not set for Takeoff (Min Level: 1+F)', 'points_deducted' => 10];
        }

        // 4. Flight Length
        $blockMins = (int) ($data['block_time_minutes'] ?? 0);
        $flightLengthPts = 0;
        if ($blockMins < 60) {
            $flightLengthPts = 10;
        } elseif ($blockMins <= 120) {
            $flightLengthPts = 25;
        } elseif ($blockMins <= 180) {
            $flightLengthPts = 50;
        } elseif ($blockMins <= 240) {
            $flightLengthPts = 75;
        } else {
            $flightLengthPts = 100;
        }
        $totalScore += $flightLengthPts;
        $bonuses[] = ['description' => "Flight Length Bonus ({$blockMins} mins)", 'points' => $flightLengthPts];

        // 5. Preparation Time (Between 00:20:00 and 00:40:00)
        $prepMins = (int) ($data['prep_time_minutes'] ?? 25);
        if ($prepMins >= 20 && $prepMins <= 40) {
            $totalScore += 25;
            $bonuses[] = ['description' => "Preparation Time Bonus ({$prepMins} mins)", 'points' => 25];
        }

        // 6. Fuel - Landing
        $landingFuelKg = (float) ($data['landing_fuel_kg'] ?? ($data['fuel_used_kg'] > 0 ? 3000 : 3000));
        if ($landingFuelKg < 1000) {
            $totalScore -= 50;
            $penalties[] = ['category' => 'Fuel Violation', 'description' => 'Landing with too little Fuel (< 1,000 kg)', 'points_deducted' => 50];
        } elseif ($landingFuelKg > 5000) {
            $totalScore -= 25;
            $penalties[] = ['category' => 'Fuel Violation', 'description' => 'Landing with too much Fuel (> 5,000 kg)', 'points_deducted' => 25];
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
            $penalties[] = ['category' => 'Diversion', 'description' => "Diversion Detected (Landed at {$actualDestIcao} instead of {$destIcao})", 'points_deducted' => $deduction];
            $failureReasons[] = "Diversion Detected: Landed at {$actualDestIcao} instead of {$destIcao}";
        }

        // 8. Network Connectivity
        $network = strtoupper($data['network_connected'] ?? 'OFFLINE');
        if (in_array($network, ['VATSIM', 'IVAO', 'POSCON'])) {
            $totalScore += 50;
            $bonuses[] = ['description' => "Online Network Bonus ($network)", 'points' => 50];
        }

        // 9. Social (Shared Cockpit)
        $sharedCockpit = (bool) ($data['shared_cockpit'] ?? false);
        if ($sharedCockpit) {
            $totalScore += 50;
            $bonuses[] = ['description' => 'Shared Cockpit Flight Bonus', 'points' => 50];
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
        if ($simRateMax > 1.0) {
            $failureReasons[] = "Time Acceleration Detected (Sim Rate: {$simRateMax}x)";
        }

        $bounceCount = (int) ($data['bounce_count'] ?? 0);
        if ($bounceCount > 1) {
            $failureReasons[] = "Multiple Landings or Bounce Detected ({$bounceCount} bounces)";
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

        if ($totalScore < 0) {
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
                str_contains($reason, '>= 2.00 G') ||
                str_contains($reason, 'Gear Up') ||
                str_contains($reason, 'Mid-air Refueling') ||
                str_contains($reason, 'Livery is Rejected') ||
                str_contains($reason, 'permitted scheduled Flight Length') ||
                str_contains($reason, 'Negative Points')
            ) {
                $isInvalidated = true;
            } elseif (str_contains($reason, '1.70 G')) {
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
}
