<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Route;
use App\Models\AircraftType;
use App\Models\SystemGlobalAircraft;
use App\Models\SystemGlobalFlight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessAirlineDataJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        // 1. Upsert Aircraft
        AircraftType::where('tenant_id', $this->tenantId)->chunk(500, function ($aircrafts) {
            $upsertData = [];
            foreach ($aircrafts as $aircraft) {
                $upsertData[] = [
                    'original_tenant_id' => $this->tenantId,
                    'code' => $aircraft->code,
                    'name' => $aircraft->name,
                ];
            }

            if (!empty($upsertData)) {
                SystemGlobalAircraft::upsert(
                    $upsertData,
                    ['code'], // Unique constraint
                    ['original_tenant_id', 'name'] // Columns to update
                );
            }
        });

        // 2. Upsert Flights
        Route::with('aircraftTypes')->where('tenant_id', $this->tenantId)->chunk(500, function ($routes) {
            $upsertData = [];
            foreach ($routes as $route) {
                // Generate a unique hash for the route
                $hashString = $route->departure_icao . '-' . $route->arrival_icao . '-' . ($route->operator ?? 'NA') . '-' . ($route->flight_number ?? 'NA');
                $hash = md5($hashString);

                $aircraftTypes = $route->aircraftTypes->pluck('code')->implode(',');

                $upsertData[] = [
                    'original_tenant_id' => $this->tenantId,
                    'flight_number' => $route->flight_number,
                    'operator' => $route->operator ?? null,
                    'departure_icao' => $route->departure_icao,
                    'arrival_icao' => $route->arrival_icao,
                    'block_time' => $route->block_time,
                    'route_type' => $route->route_type,
                    'distance' => $route->distance,
                    'aircraft_types' => $aircraftTypes,
                    'route_hash' => $hash,
                ];
            }

            if (!empty($upsertData)) {
                SystemGlobalFlight::upsert(
                    $upsertData,
                    ['route_hash'], // Unique constraint
                    ['original_tenant_id', 'flight_number', 'operator', 'block_time', 'route_type', 'distance', 'aircraft_types'] // Columns to update
                );
            }
        });
    }
}
