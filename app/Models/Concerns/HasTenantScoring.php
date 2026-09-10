<?php

namespace App\Models\Concerns;

trait HasTenantScoring
{
    /**
     * Default scoring criteria settings for virtual airlines.
     */
    public static function defaultScoringSettings(): array
    {
        return [
            // Base Starting Points
            'base_points' => 150,

            // Landing Touchdown Rate (FPM) Criteria & Points
            'fpm_butter_threshold' => 120,
            'fpm_butter_points' => 50,
            'fpm_good_threshold' => 200,
            'fpm_good_points' => 30,
            'fpm_fair_threshold' => 350,
            'fpm_fair_points' => 15,
            'fpm_firm_threshold' => 500,
            'fpm_firm_penalty' => 10,
            'fpm_hard_threshold' => 650,
            'fpm_hard_penalty' => 30,
            'fpm_reject_threshold' => 650,
            'fpm_reject_penalty' => 60,
            'fpm_danger_threshold' => 800,
            'fpm_danger_penalty' => 100,

            // Engine Operations
            'engine_warmup_seconds' => 180,
            'engine_warmup_penalty' => 30,
            'engine_cooldown_seconds' => 180,
            'engine_cooldown_penalty' => 30,
            'engine_start_interval_seconds' => 60,
            'engine_start_interval_bonus' => 10,
            'engines_shutdown_clean_bonus' => 10,

            // Flaps Operations
            'flaps_parking_bonus' => 10,
            'flaps_retracted_violation_penalty' => 10,
            'takeoff_flaps_penalty' => 10,

            // Fuel Operations
            'min_landing_fuel_kg' => 1000,
            'low_fuel_penalty' => 50,
            'max_landing_fuel_kg' => 5000,
            'excess_fuel_penalty' => 25,

            // Network & Social Bonuses
            'online_network_bonus' => 50,
            'shared_cockpit_bonus' => 50,
            'prep_time_bonus' => 25,

            // Flight Duration Bonuses
            'flight_time_bonus_under_1h' => 10,
            'flight_time_bonus_1_to_2h' => 25,
            'flight_time_bonus_2_to_3h' => 50,
            'flight_time_bonus_3_to_4h' => 75,
            'flight_time_bonus_over_4h' => 100,

            // Failure Rules
            'max_sim_rate' => 1.0,
            'max_bounces' => 1,
            'invalidate_on_negative_score' => true,
        ];
    }

    /**
     * Get effective scoring criteria for this airline (custom merged over defaults).
     */
    public function getScoringSettings(): array
    {
        $defaults = static::defaultScoringSettings();
        $custom = $this->scoring_settings ?? [];

        if (!is_array($custom)) {
            return $defaults;
        }

        return array_merge($defaults, array_filter($custom, fn($val) => !is_null($val)));
    }
}
