<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemGlobalFlight;
use App\Models\SystemGlobalAirline;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * FetchGlobalRoutesJob
 *
 * Combines TWO public route datasets into a single, enriched global route table:
 *
 *  1. Jonty (airline_routes.json):
 *       Provides: departure ICAO, arrival ICAO, carrier IATA + name, block time (min), distance (km)
 *
 *  2. OpenFlights (routes.dat):
 *       Provides: departure/arrival (IATA→ICAO mapped), carrier IATA, aircraft equipment types
 *
 * Merge strategy (keyed on dep_icao + arr_icao + operator_iata):
 *  - block_time     → Jonty
 *  - distance       → Jonty (km → NM)
 *  - aircraft_types → OpenFlights
 *  - flight_number  → NULL (resolved at import time via AirLabs, or fictional number generated)
 *  - airline name   → Jonty (upserted into system_global_airlines)
 */
class FetchGlobalRoutesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 900; // 15 minutes – fetches + merges two datasets

    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        ini_set('memory_limit', '1024M');
        set_time_limit(1200);

        Log::info('FetchGlobalRoutesJob: Starting combined route fetch...');

        try {
            // ----------------------------------------------------------------
            // STEP 1: Build Airport IATA → ICAO map (from OpenFlights airports.dat)
            // ----------------------------------------------------------------
            $airportIataToIcao = $this->fetchAirportIataToIcaoMap();
            Log::info('FetchGlobalRoutesJob: Airport IATA→ICAO map built (' . count($airportIataToIcao) . ' entries).');

            // ----------------------------------------------------------------
            // STEP 2: Fetch OpenFlights routes → build equipment map
            //   Key: "DEP_ICAO|ARR_ICAO|OPERATOR_IATA" → aircraft_types string
            // ----------------------------------------------------------------
            $equipmentMap = $this->fetchOpenFlightsEquipmentMap($airportIataToIcao);
            Log::info('FetchGlobalRoutesJob: OpenFlights equipment map built (' . count($equipmentMap) . ' routes).');

            // ----------------------------------------------------------------
            // STEP 3: Fetch Jonty routes + upsert airlines + merge with equipment
            //   flight_number is left NULL here — resolved at import time.
            // ----------------------------------------------------------------
            $this->fetchJontyAndMerge($equipmentMap);

            // ----------------------------------------------------------------
            // STEP 4: Fix IATA collisions in system_global_airlines
            //   Remove defunct/inactive duplicates that share an IATA code
            //   with a known active airline. e.g. "United Feeder Service" (U2)
            //   clashes with easyJet.
            // ----------------------------------------------------------------
            $this->fixAirlineIataCollisions();

            Log::info('FetchGlobalRoutesJob: Completed successfully.');
        } catch (\Throwable $e) {
            Log::error('FetchGlobalRoutesJob failed: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Download OpenFlights airports.dat and return IATA (3-letter) → ICAO (4-letter) map.
     */
    private function fetchAirportIataToIcaoMap(): array
    {
        $url = 'https://raw.githubusercontent.com/jpatokal/openflights/master/data/airports.dat';
        $response = Http::timeout(60)->get($url);

        if (!$response->successful()) {
            Log::warning('FetchGlobalRoutesJob: Could not fetch airports.dat (status ' . $response->status() . ').');
            return [];
        }

        $map = [];
        foreach (explode("\n", $response->body()) as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $data = str_getcsv($line, ',', '"', '\\');
            if (count($data) >= 6) {
                $iata = ($data[4] !== '\\N' && $data[4] !== '') ? $data[4] : null;
                $icao = ($data[5] !== '\\N' && $data[5] !== '') ? $data[5] : null;
                if ($iata && strlen($iata) === 3 && $icao && strlen($icao) === 4) {
                    $map[$iata] = $icao;
                }
            }
        }
        return $map;
    }

    /**
     * Download OpenFlights routes.dat and build a map of:
     *   "DEP_ICAO|ARR_ICAO|OPERATOR_IATA" → aircraft_types (comma-separated ICAO equipment codes)
     *
     * Also builds aircraft IATA→ICAO from planes.dat for equipment mapping.
     */
    private function fetchOpenFlightsEquipmentMap(array $airportIataToIcao): array
    {
        // Build aircraft IATA → ICAO map for equipment normalization
        $planeIataToIcao = [];
        $planesResponse = Http::timeout(60)->get('https://raw.githubusercontent.com/jpatokal/openflights/master/data/planes.dat');
        if ($planesResponse->successful()) {
            foreach (explode("\n", $planesResponse->body()) as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $data = str_getcsv($line, ',', '"', '\\');
                if (count($data) >= 3) {
                    $iata = $data[1] !== '\\N' ? $data[1] : null;
                    $icao = $data[2] !== '\\N' ? $data[2] : null;
                    if ($iata && $icao && strlen($icao) >= 2) {
                        $planeIataToIcao[$iata] = $icao;
                    }
                }
            }
        }

        $equipmentMap = [];
        $routesResponse = Http::timeout(60)->get('https://raw.githubusercontent.com/jpatokal/openflights/master/data/routes.dat');
        if (!$routesResponse->successful()) {
            Log::warning('FetchGlobalRoutesJob: Could not fetch routes.dat.');
            return [];
        }

        foreach (explode("\n", $routesResponse->body()) as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $data = str_getcsv($line, ',', '"', '\\');
            if (count($data) < 9) continue;

            $operator = ($data[0] !== '\\N' && $data[0] !== '') ? strtoupper($data[0]) : null;
            $depRaw   = ($data[2] !== '\\N' && $data[2] !== '') ? $data[2] : null;
            $arrRaw   = ($data[4] !== '\\N' && $data[4] !== '') ? $data[4] : null;
            $equipment = ($data[8] !== '\\N' && $data[8] !== '') ? $data[8] : null;

            if (!$operator || !$depRaw || !$arrRaw) continue;

            // Resolve IATA airports to ICAO
            $depIcao = (strlen($depRaw) === 3 && isset($airportIataToIcao[$depRaw])) ? $airportIataToIcao[$depRaw] : $depRaw;
            $arrIcao = (strlen($arrRaw) === 3 && isset($airportIataToIcao[$arrRaw])) ? $airportIataToIcao[$arrRaw] : $arrRaw;

            if (strlen($depIcao) !== 4 || strlen($arrIcao) !== 4) continue;

            // Map aircraft equipment IATA → ICAO
            $types = [];
            if ($equipment) {
                foreach (explode(' ', $equipment) as $equip) {
                    $equip = trim($equip);
                    if ($equip === '') continue;
                    $types[] = $planeIataToIcao[$equip] ?? $equip;
                }
            }

            $key = strtoupper($depIcao) . '|' . strtoupper($arrIcao) . '|' . $operator;

            // Merge equipment: if same route appears multiple times with different aircraft, combine them
            if (isset($equipmentMap[$key]) && !empty($types)) {
                $existing = explode(',', $equipmentMap[$key]);
                $merged = array_unique(array_merge($existing, $types));
                $equipmentMap[$key] = implode(',', $merged);
            } else {
                $equipmentMap[$key] = !empty($types) ? implode(',', $types) : null;
            }
        }

        return $equipmentMap;
    }

    /**
     * Fetch Jonty airline_routes.json, merge aircraft equipment from OpenFlights map,
     * and upsert everything into system_global_flights + system_global_airlines.
     * flight_number is stored as NULL — it is resolved at import time.
     */
    private function fetchJontyAndMerge(array $equipmentMap): void
    {
        $url = 'https://raw.githubusercontent.com/Jonty/airline-route-data/refs/heads/main/airline_routes.json';
        $response = Http::timeout(120)->get($url);

        if (!$response->successful()) {
            Log::error('FetchGlobalRoutesJob: Jonty fetch failed (status ' . $response->status() . ').');
            return;
        }

        $data = $response->json();
        if (empty($data)) {
            Log::warning('FetchGlobalRoutesJob: Jonty JSON was empty.');
            return;
        }

        // Build IATA → ICAO map from Jonty's airport root keys
        $iataToIcao = [];
        foreach ($data as $iata => $airportData) {
            if (!empty($airportData['icao'])) {
                $iataToIcao[$iata] = $airportData['icao'];
            }
        }

        $flightsUpsert  = [];
        $airlinesUpsert = [];
        $chunkSize      = 500;

        foreach ($data as $depIata => $airportData) {
            if ($this->batch() && $this->batch()->cancelled()) {
                return;
            }

            $depIcao = $airportData['icao'] ?? null;
            if (!$depIcao || strlen($depIcao) !== 4) continue;

            foreach ($airportData['routes'] ?? [] as $route) {
                $arrIata = $route['iata'] ?? null;
                if (!$arrIata) continue;

                $arrIcao = $iataToIcao[$arrIata] ?? null;
                if (!$arrIcao || strlen($arrIcao) !== 4) continue;

                foreach ($route['carriers'] ?? [] as $carrier) {
                    $operatorIata = isset($carrier['iata']) ? strtoupper(mb_substr($carrier['iata'], 0, 3)) : null;
                    $operatorName = $carrier['name'] ?? null;

                    if (!$operatorIata) continue;

                    // --- Upsert airline record ---
                    if ($operatorName) {
                        $airlineHash = md5($operatorIata . '|' . $operatorName);
                        $airlinesUpsert[$airlineHash] = [
                            'name'     => $operatorName,
                            'iata'     => $operatorIata,
                            'icao'     => null,
                            'callsign' => null,
                            'country'  => null,
                            'active'   => true,
                            'hash'     => $airlineHash,
                        ];
                    }

                    // --- Build route record ---
                    $blockMins = $route['min'] ?? 0;
                    $blockTime = null;
                    if ($blockMins > 0) {
                        $blockTime = sprintf('%02d:%02d', floor($blockMins / 60), $blockMins % 60);
                    }

                    $distanceNm = isset($route['km']) ? round($route['km'] * 0.539957) : null;

                    // Merge aircraft equipment from OpenFlights map
                    $equipKey      = strtoupper($depIcao) . '|' . strtoupper($arrIcao) . '|' . $operatorIata;
                    $aircraftTypes = $equipmentMap[$equipKey] ?? null;

                    // Hash keyed on dep+arr+operator (stable)
                    $hashKey   = strtoupper($depIcao) . '_' . strtoupper($arrIcao) . '_' . $operatorIata;
                    $routeHash = md5($hashKey);

                    $flightsUpsert[] = [
                        'original_tenant_id' => null,
                        'flight_number'      => null,  // Resolved at import time (AirLabs or fictional)
                        'operator'           => $operatorIata,
                        'departure_icao'     => strtoupper($depIcao),
                        'arrival_icao'       => strtoupper($arrIcao),
                        'block_time'         => $blockTime,
                        'route_type'         => 'Scheduled',
                        'distance'           => $distanceNm,
                        'aircraft_types'     => $aircraftTypes,
                        'route_hash'         => $routeHash,
                    ];

                    if (count($flightsUpsert) >= $chunkSize) {
                        $this->flushAirlines($airlinesUpsert);
                        $airlinesUpsert = [];

                        SystemGlobalFlight::upsert(
                            $flightsUpsert,
                            ['route_hash'],
                            ['operator', 'departure_icao', 'arrival_icao', 'flight_number', 'block_time', 'distance', 'aircraft_types']
                        );
                        $flightsUpsert = [];
                    }
                }
            }
        }

        // Flush remaining
        $this->flushAirlines($airlinesUpsert);
        if (!empty($flightsUpsert)) {
            SystemGlobalFlight::upsert(
                $flightsUpsert,
                ['route_hash'],
                ['operator', 'departure_icao', 'arrival_icao', 'flight_number', 'block_time', 'distance', 'aircraft_types']
            );
        }
    }

    /**
     * Upsert a batch of airline records.
     */
    private function flushAirlines(array $airlinesUpsert): void
    {
        if (!empty($airlinesUpsert)) {
            SystemGlobalAirline::upsert(
                array_values($airlinesUpsert),
                ['hash'],
                ['name', 'iata']
            );
        }
    }

    /**
     * Remove defunct airline duplicates caused by IATA code reuse over time.
     *
     * Strategy: for each IATA code that has multiple airline records, delete any
     * records where active = false/0 AND a record with active = true already exists
     * for the same IATA. This safely cleans up cases like "United Feeder Service"
     * (defunct, active=0) conflicting with "easyJet" (active=1) for IATA U2.
     */
    private function fixAirlineIataCollisions(): void
    {
        // Find IATA codes that have at least one active=1 AND at least one active=0 record
        $collisionIatas = DB::table('system_global_airlines')
            ->select('iata')
            ->groupBy('iata')
            ->havingRaw('SUM(active = 1) > 0 AND SUM(active = 0) > 0')
            ->pluck('iata');

        foreach ($collisionIatas as $iata) {
            $deleted = SystemGlobalAirline::where('iata', $iata)
                ->where('active', false)
                ->delete();

            if ($deleted > 0) {
                Log::info("FetchGlobalRoutesJob: Removed $deleted inactive duplicate(s) for IATA '$iata' (IATA collision cleanup).");
            }
        }
    }
}
