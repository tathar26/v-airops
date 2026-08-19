<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Run the global data aggregation daily at midnight
Schedule::command('system:aggregate-airline-data')->daily();

// Automatically process missing pilot callsigns hourly
Schedule::job(new \App\Jobs\AssignMissingCallsignsJob)->hourly();

// Automatically purge unflown bookings older than 24 hours
Schedule::job(new \App\Jobs\PurgeUnflownBookingsJob)->hourly();

// Automatically process PIREPs and recalculate pilot hours and points every 5 minutes
Schedule::job(new \App\Jobs\ProcessPirepsAndRecalculateStatsJob)->everyFiveMinutes();

Artisan::command('pireps:process', function () {
    $this->info('Processing PIREPs and recalculating statistics for all pilots...');
    (new \App\Jobs\ProcessPirepsAndRecalculateStatsJob())->handle();
    $this->info('PIREP processing and statistics recalculation completed.');
})->purpose('Process PIREPs and recalculate hours and points for all pilots');

Artisan::command('pireps:rescore-all', function () {
    $this->info('Rescoring all existing PIREPs against VA scoring rules and failure conditions...');

    $scoringService = new \App\Services\Acars\ScoringService();
    $pireps = \App\Models\Pirep::all();
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

        $res = $scoringService->evaluatePirepData($evalData);

        // Keep manual staff acceptances/invalidations if status was already staff-reviewed
        $currentStatus = strtolower($pirep->status);
        $finalStatus = in_array($currentStatus, ['accepted', 'invalidated', 'rejected']) 
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

        $userIds[$pirep->user_id] = true;
        $rescoredCount++;
    }

    foreach (array_keys($userIds) as $userId) {
        \App\Jobs\RecalculatePilotStatistics::dispatchSync($userId);
    }

    $this->info("Successfully rescored {$rescoredCount} PIREPs and updated statistics for all affected pilots.");
})->purpose('Rescore all existing PIREPs against VA scoring rules and failure conditions');

