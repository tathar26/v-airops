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
    protected $targetIcao;
    protected $stripPrefix;

    /**
     * Create a new job instance.
     */
    public function __construct($tenantId, array $globalFlightIds, ?string $targetIcao = null, ?string $stripPrefix = null)
    {
        $this->tenantId = $tenantId;
        $this->globalFlightIds = $globalFlightIds;
        $this->targetIcao = $targetIcao;
        $this->stripPrefix = $stripPrefix;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $targetTenantId = $this->tenantId ?: (\App\Models\Tenant::first()?->id ?? 1);
        $tenant = \App\Models\Tenant::find($targetTenantId);
        $defaultTenantIcao = !empty($this->targetIcao) 
            ? strtoupper(trim($this->targetIcao)) 
            : ($tenant && !empty($tenant->icao) ? strtoupper($tenant->icao) : 'VOPS');

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

            // --- Step 3: Determine ATC Callsign (ICAO Prefix + Suffix) ---
            $cleanFlightNum = strtoupper(trim($flightNum));
            $suffix = $cleanFlightNum;

            if (!empty($this->stripPrefix)) {
                $pfx = strtoupper(trim($this->stripPrefix));
                if (str_starts_with($cleanFlightNum, $pfx)) {
                    $suffix = substr($cleanFlightNum, strlen($pfx));
                }
            } else {
                // Auto strip leading letters or IATA prefix (e.g. U29999 -> 9999, FR2605 -> 2605, BA1181 -> 1181)
                $stripped = preg_replace('/^[A-Z0-9]{2,3}/', '', $cleanFlightNum);
                $suffix = !empty($stripped) ? $stripped : $cleanFlightNum;
            }

            $callsignIcao = $defaultTenantIcao;
            $fullCallsign = $callsignIcao . $suffix;

            // Create or Update Route for the specific tenant
            $route = Route::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id'      => $targetTenantId,
                    'flight_number'  => $flightNum,
                    'departure_icao' => $flight->departure_icao,
                    'arrival_icao'   => $flight->arrival_icao,
                ],
                [
                    'callsign'        => $fullCallsign,
                    'callsign_icao'   => $callsignIcao,
                    'callsign_suffix' => $suffix,
                    'operator'        => $callsignIcao,
                    'block_time'      => $blockTime ?? '02:00',
                    'route_type'      => $flight->route_type ?? 'Scheduled',
                    'distance'        => $flight->distance,
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

                    $ac = AircraftType::withoutGlobalScopes()->firstOrCreate(
                        ['tenant_id' => $targetTenantId, 'code' => $code],
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
