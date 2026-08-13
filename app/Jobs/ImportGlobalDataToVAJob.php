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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     *
     * For each route being imported:
     *  1. Try to find a real flight number via the AirLabs API (dep_icao + arr_icao + airline_iata)
     *  2. AirLabs returns multiple rows per route (one per weekday) — we take the first match.
     *  3. If AirLabs returns nothing (or key not set), generate a deterministic fictional number.
     *     e.g. U2 EGKK→LIMC → "U21256", FR EGKK→LIMC → "FR5487"
     *     Uses crc32 of the route key so the same route always gets the same fictional number.
     */
    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $flights = SystemGlobalFlight::whereIn('id', $this->globalFlightIds)->get();
        $apiKey  = config('services.airlabs.key', env('AIRLABS_API_KEY'));

        foreach ($flights as $flight) {
            if ($this->batch() && $this->batch()->cancelled()) {
                return;
            }

            // Ensure airports exist locally
            Airport::fetchAndCreate($flight->departure_icao);
            Airport::fetchAndCreate($flight->arrival_icao);

            $operatorPrefix = !empty($flight->operator) ? strtoupper($flight->operator) : 'FL';
            $flightNum      = null;
            $blockTime      = $flight->block_time;

            // --- Step 1: Real flight number lookup via AirLabs ---
            if (!empty($apiKey)) {
                $flightNum = $this->lookupAirlabsFlightNumber(
                    $flight->departure_icao,
                    $flight->arrival_icao,
                    $flight->operator,
                    $apiKey,
                    $blockTime // passed by reference — AirLabs duration may improve it
                );
            }

            // --- Step 2: Fall back to deterministic fictional flight number ---
            if (empty($flightNum)) {
                $seed      = abs(crc32($flight->departure_icao . $flight->arrival_icao . $operatorPrefix));
                $flightNum = $operatorPrefix . (($seed % 8999) + 1000); // e.g. U21256, FR5487
            }

            // Create or Update Route for the specific tenant
            $route = Route::updateOrCreate(
                [
                    'tenant_id'      => $this->tenantId,
                    'flight_number'  => $flightNum,
                    'departure_icao' => $flight->departure_icao,
                    'arrival_icao'   => $flight->arrival_icao,
                ],
                [
                    'operator'   => $flight->operator,
                    'block_time' => $blockTime ?? '02:00',
                    'route_type' => $flight->route_type ?? 'Scheduled',
                    'distance'   => $flight->distance,
                ]
            );

            // Write the resolved flight number back to the global network DB
            // so other VAs benefit from this lookup on their next import.
            if ($flight->flight_number !== $flightNum) {
                $flight->flight_number = $flightNum;
                if ($blockTime && $blockTime !== $flight->block_time) {
                    $flight->block_time = $blockTime;
                }
                $flight->save();
            }

            // Parse and sync aircraft types
            if ($flight->aircraft_types) {
                $codes      = explode(',', $flight->aircraft_types);
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

    /**
     * Query AirLabs Routes API to find the real flight number for a specific route.
     *
     * AirLabs returns multiple rows per route (one per operating weekday), so we
     * deduplicate and take the first matching flight_iata.
     *
     * Also updates $blockTime (by reference) if AirLabs provides a more accurate
     * duration in minutes than the Jonty estimate.
     *
     * Returns the flight_iata string (e.g. "U21256") or null if not found.
     */
    private function lookupAirlabsFlightNumber(
        string  $depIcao,
        string  $arrIcao,
        string  $operatorIata,
        string  $apiKey,
        ?string &$blockTime
    ): ?string {
        try {
            $response = Http::timeout(10)->get('https://airlabs.co/api/v9/routes', [
                'dep_icao'     => strtoupper($depIcao),
                'arr_icao'     => strtoupper($arrIcao),
                'airline_iata' => strtoupper($operatorIata),
                'api_key'      => $apiKey,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $routes = $response->json('response') ?? $response->json() ?? [];
            if (!is_array($routes) || empty($routes)) {
                return null;
            }

            // AirLabs returns one row per weekday — take the first valid flight_iata
            foreach ($routes as $route) {
                $flightIata = trim($route['flight_iata'] ?? '');
                if (empty($flightIata)) continue;

                // Improve block time with AirLabs accurate duration if available
                $duration = $route['duration'] ?? null;
                if ($duration && is_numeric($duration) && $duration > 0) {
                    $blockTime = sprintf('%02d:%02d', floor($duration / 60), $duration % 60);
                }

                return $flightIata; // e.g. "U21256"
            }
        } catch (\Exception $e) {
            Log::warning("ImportGlobalDataToVAJob: AirLabs lookup failed for {$depIcao}→{$arrIcao} ({$operatorIata}): " . $e->getMessage());
        }

        return null;
    }
}
