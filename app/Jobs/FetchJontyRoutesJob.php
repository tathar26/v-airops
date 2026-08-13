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

class FetchJontyRoutesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes for the large Jonty JSON dataset

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        \Illuminate\Support\Facades\Log::info('FetchJontyRoutesJob: Starting fetch from GitHub...');

        try {
            $url = 'https://raw.githubusercontent.com/Jonty/airline-route-data/refs/heads/main/airline_routes.json';
            $response = Http::timeout(120)->get($url);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::error('FetchJontyRoutesJob: HTTP request failed with status ' . $response->status());
                return;
            }

            $data = $response->json();
            if (empty($data)) {
                \Illuminate\Support\Facades\Log::warning('FetchJontyRoutesJob: Downloaded JSON was empty.');
                return;
            }

            \Illuminate\Support\Facades\Log::info('FetchJontyRoutesJob: JSON parsed successfully. Processing routes...');

        // 1. Build an IATA -> ICAO mapping for airports based on the root keys
        $iataToIcao = [];
        foreach ($data as $iata => $airportData) {
            if (!empty($airportData['icao'])) {
                $iataToIcao[$iata] = $airportData['icao'];
            }
        }

        $flightsUpsertData = [];
        $airlinesUpsertData = [];
        $chunkSize = 500;

        foreach ($data as $depIata => $airportData) {
            if ($this->batch() && $this->batch()->cancelled()) {
                return;
            }

            $depIcao = $airportData['icao'] ?? null;
            if (!$depIcao || strlen($depIcao) !== 4) continue;

            $routes = $airportData['routes'] ?? [];

            foreach ($routes as $route) {
                $arrIata = $route['iata'] ?? null;
                if (!$arrIata) continue;

                $arrIcao = $iataToIcao[$arrIata] ?? null;
                if (!$arrIcao || strlen($arrIcao) !== 4) continue;

                $carriers = $route['carriers'] ?? [];
                
                foreach ($carriers as $carrier) {
                    $operatorIata = isset($carrier['iata']) ? mb_substr($carrier['iata'], 0, 3) : null;
                    $operatorName = $carrier['name'] ?? null;

                    if ($operatorIata && $operatorName) {
                        $airlineHash = md5($operatorIata . '-' . $operatorName);
                        $airlinesUpsertData[$airlineHash] = [
                            'name' => $operatorName,
                            'iata' => $operatorIata,
                            'icao' => null, // Jonty's JSON doesn't provide carrier ICAO, but we have IATA
                            'callsign' => null,
                            'country' => null,
                            'active' => true,
                            'hash' => $airlineHash
                        ];
                    }

                    // For the flight operator, we store the IATA code or Name so we can join it later
                    $operatorKey = $operatorIata ?? $operatorName;

                    if ($operatorKey) {
                        $blockMins = $route['min'] ?? 0;
                        $blockTime = null;
                        if ($blockMins > 0) {
                            $hours = floor($blockMins / 60);
                            $minutes = $blockMins % 60;
                            $blockTime = sprintf('%02d:%02d', $hours, $minutes);
                        }

                        $flightHash = md5($depIcao . '-' . $arrIcao . '-' . $operatorKey . '-JONTY');
                        $flightsUpsertData[] = [
                            'original_tenant_id' => null,
                            'flight_number' => null,
                            'operator' => $operatorKey,
                            'departure_icao' => $depIcao,
                            'arrival_icao' => $arrIcao,
                            'block_time' => $blockTime,
                            'route_type' => 'Scheduled',
                            'distance' => $route['km'] ?? null, // actually in km but we can store it directly or convert. The DB field is distance. Let's just store the km as distance for now, or convert to NM.
                            // Convert KM to NM: 1 km = 0.539957 NM
                            // Let's store in NM for consistency with the rest of V-Ops
                            // 'distance' => isset($route['km']) ? round($route['km'] * 0.539957) : null,
                            'aircraft_types' => null,
                            'route_hash' => $flightHash,
                        ];

                        // To match the rest of V-Ops, we should convert KM to NM
                        if (isset($route['km'])) {
                            $flightsUpsertData[count($flightsUpsertData) - 1]['distance'] = round($route['km'] * 0.539957);
                        }
                    }
                }
            }

            if (count($flightsUpsertData) >= $chunkSize) {
                if (!empty($airlinesUpsertData)) {
                    SystemGlobalAirline::upsert(
                        array_values($airlinesUpsertData),
                        ['hash'],
                        ['name', 'iata']
                    );
                    $airlinesUpsertData = [];
                }

                SystemGlobalFlight::upsert(
                    $flightsUpsertData,
                    ['route_hash'],
                    ['operator', 'departure_icao', 'arrival_icao', 'block_time', 'distance']
                );
                $flightsUpsertData = [];
            }
        }

        if (!empty($airlinesUpsertData)) {
            SystemGlobalAirline::upsert(
                array_values($airlinesUpsertData),
                ['hash'],
                ['name', 'iata']
            );
        }

        if (!empty($flightsUpsertData)) {
            SystemGlobalFlight::upsert(
                $flightsUpsertData,
                ['route_hash'],
                ['operator', 'departure_icao', 'arrival_icao', 'block_time', 'distance']
            );
        }

        \Illuminate\Support\Facades\Log::info('FetchJontyRoutesJob: Completed processing Jonty routes successfully.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('FetchJontyRoutesJob failed with exception: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
