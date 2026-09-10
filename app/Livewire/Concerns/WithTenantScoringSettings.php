<?php

namespace App\Livewire\Concerns;

use App\Models\Tenant;
use App\Services\Acars\ScoringService;

trait WithTenantScoringSettings
{
    // Scoring Criteria Settings
    public $base_points = 150;
    public $fpm_butter_threshold = 120;
    public $fpm_butter_points = 50;
    public $fpm_good_threshold = 200;
    public $fpm_good_points = 30;
    public $fpm_fair_threshold = 350;
    public $fpm_fair_points = 15;
    public $fpm_firm_threshold = 500;
    public $fpm_firm_penalty = 10;
    public $fpm_hard_threshold = 650;
    public $fpm_hard_penalty = 30;
    public $fpm_reject_threshold = 650;
    public $fpm_reject_penalty = 60;
    public $fpm_danger_threshold = 800;
    public $fpm_danger_penalty = 100;

    public $engine_warmup_seconds = 180;
    public $engine_warmup_penalty = 30;
    public $engine_cooldown_seconds = 180;
    public $engine_cooldown_penalty = 30;
    public $engine_start_interval_seconds = 60;
    public $engine_start_interval_bonus = 10;
    public $engines_shutdown_clean_bonus = 10;

    public $flaps_parking_bonus = 10;
    public $flaps_retracted_violation_penalty = 10;
    public $takeoff_flaps_penalty = 10;

    public $min_landing_fuel_kg = 1000;
    public $low_fuel_penalty = 50;
    public $max_landing_fuel_kg = 5000;
    public $excess_fuel_penalty = 25;

    public $online_network_bonus = 50;
    public $shared_cockpit_bonus = 50;
    public $prep_time_bonus = 25;

    public $flight_time_bonus_under_1h = 10;
    public $flight_time_bonus_1_to_2h = 25;
    public $flight_time_bonus_2_to_3h = 50;
    public $flight_time_bonus_3_to_4h = 75;
    public $flight_time_bonus_over_4h = 100;

    public $max_sim_rate = 1.0;
    public $max_bounces = 1;
    public $invalidate_on_negative_score = true;

    /* =========================================================================
     | PIREP Scoring Settings Management
     |======================================================================== */

    public function loadScoringSettings(Tenant $tenant)
    {
        $settings = $tenant->getScoringSettings();
        foreach ($settings as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }

    public function saveScoringSettings()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $tenant = Tenant::findOrFail($tenantId);

        $this->validate([
            'base_points'              => 'required|integer|min:0|max:1000',
            'fpm_butter_threshold'     => 'required|integer|min:20|max:500',
            'fpm_butter_points'        => 'required|integer|min:0|max:500',
            'fpm_good_threshold'       => 'required|integer|min:50|max:800',
            'fpm_good_points'          => 'required|integer|min:0|max:500',
            'fpm_fair_threshold'       => 'required|integer|min:100|max:1000',
            'fpm_fair_points'          => 'required|integer|min:0|max:500',
            'fpm_firm_threshold'       => 'required|integer|min:150|max:1200',
            'fpm_firm_penalty'         => 'required|integer|min:0|max:500',
            'fpm_hard_threshold'       => 'required|integer|min:200|max:1500',
            'fpm_hard_penalty'         => 'required|integer|min:0|max:500',
            'fpm_reject_threshold'     => 'required|integer|min:250|max:2000',
            'fpm_reject_penalty'       => 'required|integer|min:0|max:500',
            'fpm_danger_threshold'     => 'required|integer|min:300|max:2500',
            'fpm_danger_penalty'       => 'required|integer|min:0|max:500',
            'engine_warmup_seconds'    => 'required|integer|min:0|max:1800',
            'engine_warmup_penalty'    => 'required|integer|min:0|max:200',
            'engine_cooldown_seconds'  => 'required|integer|min:0|max:1800',
            'engine_cooldown_penalty'  => 'required|integer|min:0|max:200',
            'min_landing_fuel_kg'      => 'required|numeric|min:0',
            'low_fuel_penalty'         => 'required|integer|min:0|max:500',
            'max_landing_fuel_kg'      => 'required|numeric|min:0',
            'excess_fuel_penalty'      => 'required|integer|min:0|max:500',
            'online_network_bonus'     => 'required|integer|min:0|max:500',
            'shared_cockpit_bonus'     => 'required|integer|min:0|max:500',
            'max_sim_rate'             => 'required|numeric|min:1|max:16',
            'max_bounces'              => 'required|integer|min:0|max:5',
        ]);

        $scoringSettings = [
            'base_points'                       => (int) $this->base_points,
            'fpm_butter_threshold'              => (int) $this->fpm_butter_threshold,
            'fpm_butter_points'                 => (int) $this->fpm_butter_points,
            'fpm_good_threshold'                => (int) $this->fpm_good_threshold,
            'fpm_good_points'                   => (int) $this->fpm_good_points,
            'fpm_fair_threshold'                => (int) $this->fpm_fair_threshold,
            'fpm_fair_points'                   => (int) $this->fpm_fair_points,
            'fpm_firm_threshold'                => (int) $this->fpm_firm_threshold,
            'fpm_firm_penalty'                  => (int) $this->fpm_firm_penalty,
            'fpm_hard_threshold'                => (int) $this->fpm_hard_threshold,
            'fpm_hard_penalty'                  => (int) $this->fpm_hard_penalty,
            'fpm_reject_threshold'              => (int) $this->fpm_reject_threshold,
            'fpm_reject_penalty'                => (int) $this->fpm_reject_penalty,
            'fpm_danger_threshold'              => (int) $this->fpm_danger_threshold,
            'fpm_danger_penalty'                => (int) $this->fpm_danger_penalty,
            'engine_warmup_seconds'             => (int) $this->engine_warmup_seconds,
            'engine_warmup_penalty'             => (int) $this->engine_warmup_penalty,
            'engine_cooldown_seconds'           => (int) $this->engine_cooldown_seconds,
            'engine_cooldown_penalty'           => (int) $this->engine_cooldown_penalty,
            'engine_start_interval_seconds'     => (int) $this->engine_start_interval_seconds,
            'engine_start_interval_bonus'       => (int) $this->engine_start_interval_bonus,
            'engines_shutdown_clean_bonus'      => (int) $this->engines_shutdown_clean_bonus,
            'flaps_parking_bonus'               => (int) $this->flaps_parking_bonus,
            'flaps_retracted_violation_penalty' => (int) $this->flaps_retracted_violation_penalty,
            'takeoff_flaps_penalty'             => (int) $this->takeoff_flaps_penalty,
            'min_landing_fuel_kg'               => (float) $this->min_landing_fuel_kg,
            'low_fuel_penalty'                  => (int) $this->low_fuel_penalty,
            'max_landing_fuel_kg'               => (float) $this->max_landing_fuel_kg,
            'excess_fuel_penalty'               => (int) $this->excess_fuel_penalty,
            'online_network_bonus'              => (int) $this->online_network_bonus,
            'shared_cockpit_bonus'              => (int) $this->shared_cockpit_bonus,
            'prep_time_bonus'                   => (int) $this->prep_time_bonus,
            'flight_time_bonus_under_1h'        => (int) $this->flight_time_bonus_under_1h,
            'flight_time_bonus_1_to_2h'         => (int) $this->flight_time_bonus_1_to_2h,
            'flight_time_bonus_2_to_3h'         => (int) $this->flight_time_bonus_2_to_3h,
            'flight_time_bonus_3_to_4h'         => (int) $this->flight_time_bonus_3_to_4h,
            'flight_time_bonus_over_4h'         => (int) $this->flight_time_bonus_over_4h,
            'max_sim_rate'                      => (float) $this->max_sim_rate,
            'max_bounces'                       => (int) $this->max_bounces,
            'invalidate_on_negative_score'      => (bool) $this->invalidate_on_negative_score,
        ];

        $tenant->scoring_settings = $scoringSettings;
        $tenant->save();

        session()->flash('scoring_message', 'PIREP scoring criteria saved successfully.');
    }

    public function resetScoringSettings()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $tenant = Tenant::findOrFail($tenantId);

        $tenant->scoring_settings = null;
        $tenant->save();

        $this->loadScoringSettings($tenant);

        session()->flash('scoring_message', 'Scoring criteria have been reset to system defaults.');
    }

    public function recalculateAirlinePireps()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $scoringService = new ScoringService();
        $result = $scoringService->rescorePireps($tenantId, false);

        session()->flash('scoring_message', "Successfully recalculate {$result['rescored_count']} past PIREPs and updated statistics for {$result['affected_pilots']} pilots against your airline's criteria.");
    }
}
