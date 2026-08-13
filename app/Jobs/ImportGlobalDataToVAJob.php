<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemGlobalFlight;
use App\Models\Route;
use App\Models\AircraftType;
use App\Models\Airport;

class ImportGlobalDataToVAJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenantId;
    protected $globalFlightIds;

    /**
     * Create a new job instance.
     */
    public function __construct($tenantId, array $globalFlightIds)
    {
        $this->tenantId = $tenantId;
        $this->globalFlightIds = $globalFlightIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        // Process in chunks to manage memory
        $flights = SystemGlobalFlight::whereIn('id', $this->globalFlightIds)->get();

        foreach ($flights as $flight) {
            if ($this->batch() && $this->batch()->cancelled()) {
                return;
            }

            // Ensure airports exist locally
            Airport::fetchAndCreate($flight->departure_icao);
            Airport::fetchAndCreate($flight->arrival_icao);

            $operatorPrefix = !empty($flight->operator) ? strtoupper($flight->operator) : 'FL';
            if (!empty($flight->flight_number)) {
                $flightNum = $flight->flight_number;
            } else {
                // Generate a realistic, deterministic fictional flight number based on the route.
                // Using abs(crc32) ensures the same dep+arr+operator always gets the same number,
                // so re-importing the same route doesn't create duplicates with different numbers.
                $seed = abs(crc32($flight->departure_icao . $flight->arrival_icao . $operatorPrefix));
                $flightNum = $operatorPrefix . (($seed % 8999) + 1000); // Always 4-digit suffix, e.g. EZY2345
            }

            // Create or Update Route for the specific tenant
            $route = Route::updateOrCreate(
                [
                    'tenant_id' => $this->tenantId,
                    'flight_number' => $flightNum,
                    'departure_icao' => $flight->departure_icao,
                    'arrival_icao' => $flight->arrival_icao,
                ],
                [
                    'operator' => $flight->operator,
                    'block_time' => $flight->block_time ?? '02:00', // Provide a fallback
                    'route_type' => $flight->route_type ?? 'Scheduled',
                    'distance' => $flight->distance,
                ]
            );

            // Parse and sync aircraft types
            if ($flight->aircraft_types) {
                $codes = explode(',', $flight->aircraft_types);
                $aircraftIds = [];

                foreach ($codes as $code) {
                    $code = trim($code);
                    if (empty($code)) continue;

                    $ac = AircraftType::firstOrCreate(
                        ['tenant_id' => $this->tenantId, 'code' => $code],
                        ['name' => $code . ' Aircraft']
                    );
                    $aircraftIds[] = $ac->id;
                }

                if (!empty($aircraftIds)) {
                    $route->aircraftTypes()->syncWithoutDetaching($aircraftIds);
                }
            }
        }
    }
}
