<?php

namespace App\Services\Acars;

class ScoringService
{
    /**
     * Evaluate vertical touchdown speed against strict virtual airline tolerances.
     *
     * @param float $fpm
     * @return array{grade: string, penalty: int}
     */
    public function evaluateLandingGrade(float $fpm): array
    {
        $absFpm = abs($fpm);

        if ($absFpm <= 180) {
            return ['grade' => 'Butter / Soft', 'penalty' => 0];
        }

        if ($absFpm <= 350) {
            return ['grade' => 'Good', 'penalty' => 5];
        }

        if ($absFpm <= 500) {
            return ['grade' => 'Firm', 'penalty' => 15];
        }

        if ($absFpm <= 800) {
            return ['grade' => 'Hard', 'penalty' => 35];
        }

        return ['grade' => 'Structural Danger', 'penalty' => 60];
    }
}
