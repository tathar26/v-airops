<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

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

Artisan::command('pireps:rescore-all {--tenant= : Filter by Virtual Airline Tenant ID or Slug} {--force-status : Force update status even for manually accepted/rejected PIREPs}', function () {
    $tenantOption = $this->option('tenant');
    $forceStatus = (bool) $this->option('force-status');

    $tenantId = null;
    if ($tenantOption) {
        $tenant = is_numeric($tenantOption)
            ? \App\Models\Tenant::find($tenantOption)
            : \App\Models\Tenant::where('slug', $tenantOption)->first();

        if (!$tenant) {
            $this->error("Tenant '{$tenantOption}' not found.");
            return 1;
        }
        $tenantId = $tenant->id;
        $this->info("Rescoring PIREPs for airline: {$tenant->name} (ID: {$tenant->id})...");
    } else {
        $this->info("Rescoring all existing PIREPs across all airlines against VA scoring rules...");
    }

    $scoringService = new \App\Services\Acars\ScoringService();
    $result = $scoringService->rescorePireps($tenantId, $forceStatus);

    $this->info("Successfully rescored {$result['rescored_count']} PIREPs and updated statistics for {$result['affected_pilots']} pilots.");
})->purpose('Rescore existing PIREPs against VA scoring rules and failure conditions');

