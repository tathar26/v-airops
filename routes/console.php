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

