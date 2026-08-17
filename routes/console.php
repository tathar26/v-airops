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

