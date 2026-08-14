<?php

namespace App\Jobs;

use App\Models\AcarsPosition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class ProcessTelemetryPingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $telemetryData) {}

    public function handle(): void
    {
        // 1. Insert telemetry record into DB
        AcarsPosition::create($this->telemetryData);

        // 2. Cache latest position in Redis if redis extension and connection is available
        try {
            $flightId = $this->telemetryData['flight_id'] ?? null;
            if ($flightId) {
                Redis::setex("flight:live:{$flightId}", 600, json_encode($this->telemetryData));
            }
        } catch (\Throwable $e) {
            // Silently continue if Redis is unconfigured in dev environment
        }
    }
}
