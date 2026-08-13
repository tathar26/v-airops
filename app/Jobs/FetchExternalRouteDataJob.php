<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemGlobalFlight;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FetchExternalRouteDataJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        // 1. Fetch planes to build IATA -> ICAO mapping
        $planesUrl = 'https://raw.githubusercontent.com/jpatokal/openflights/master/data/planes.dat';
        $planesResponse = Http::timeout(60)->get($planesUrl);
        $iataToIcao = [];
        
        if ($planesResponse->successful()) {
            $planeLines = explode("\n", $planesResponse->body());
            foreach ($planeLines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $data = str_getcsv($line, ',', '"', '\\');
                if (count($data) >= 3) {
                    $iata = $data[1] !== '\\N' ? $data[1] : null;
                    $icao = $data[2] !== '\\N' ? $data[2] : null;
                    if ($iata && $icao && $icao !== '\\N' && strlen($icao) >= 2) {
                        $iataToIcao[$iata] = $icao;
                    }
                }
            }
        }

        // 1.5 Fetch airports to build Airport IATA -> ICAO mapping
        $airportsUrl = 'https://raw.githubusercontent.com/jpatokal/openflights/master/data/airports.dat';
        $airportsResponse = Http::timeout(60)->get($airportsUrl);
        $airportIataToIcao = [];

        if ($airportsResponse->successful()) {
            $airportLines = explode("\n", $airportsResponse->body());
            foreach ($airportLines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $data = str_getcsv($line, ',', '"', '\\');
                if (count($data) >= 6) {
                    $iata = $data[4] !== '\\N' && $data[4] !== '' ? $data[4] : null;
                    $icao = $data[5] !== '\\N' && $data[5] !== '' ? $data[5] : null;
                    if ($iata && strlen($iata) === 3 && $icao && strlen($icao) === 4) {
                        $airportIataToIcao[$iata] = $icao;
                    }
                }
            }
        }

        // 2. Fetch routes from Jonty's mirror of OpenFlights
        $url = 'https://raw.githubusercontent.com/jpatokal/openflights/master/data/routes.dat';
        $response = Http::timeout(60)->get($url);

        if (!$response->successful()) {
            return;
        }

        $lines = explode("\n", $response->body());
        $upsertData = [];
        $chunkSize = 500;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $data = str_getcsv($line, ',', '"', '\\');
            
            if (count($data) >= 9) {
                $operator = $data[0] !== '\\N' ? $data[0] : null;
                $depRaw = $data[2] !== '\\N' ? $data[2] : null;
                $arrRaw = $data[4] !== '\\N' ? $data[4] : null;
                
                // Map to ICAO if they are 3 letters
                $depIcao = (strlen($depRaw) === 3 && isset($airportIataToIcao[$depRaw])) ? $airportIataToIcao[$depRaw] : $depRaw;
                $arrIcao = (strlen($arrRaw) === 3 && isset($airportIataToIcao[$arrRaw])) ? $airportIataToIcao[$arrRaw] : $arrRaw;
                $equipment = $data[8] !== '\\N' ? $data[8] : null;

                if (strlen($depIcao) <= 4 && strlen($arrIcao) <= 4 && $depIcao && $arrIcao) {
                    $hashString = $depIcao . '-' . $arrIcao . '-' . ($operator ?? 'NA') . '-EXTERNAL';
                    $hash = md5($hashString);

                    // Map IATA equipment to ICAO equipment
                    $mappedEquipment = [];
                    if ($equipment) {
                        $equipList = explode(' ', $equipment);
                        foreach ($equipList as $equip) {
                            if (isset($iataToIcao[$equip])) {
                                $mappedEquipment[] = $iataToIcao[$equip];
                            } else {
                                $mappedEquipment[] = $equip; // Fallback to raw if no mapping found
                            }
                        }
                    }
                    $aircraftTypes = !empty($mappedEquipment) ? implode(',', $mappedEquipment) : null;

                    $upsertData[] = [
                        'original_tenant_id' => null, // External
                        'flight_number' => null,
                        'operator' => $operator,
                        'departure_icao' => $depIcao,
                        'arrival_icao' => $arrIcao,
                        'block_time' => null,
                        'route_type' => 'Scheduled',
                        'distance' => null,
                        'aircraft_types' => $aircraftTypes,
                        'route_hash' => $hash,
                    ];

                    if (count($upsertData) >= $chunkSize) {
                        SystemGlobalFlight::upsert(
                            $upsertData,
                            ['route_hash'],
                            ['operator', 'departure_icao', 'arrival_icao', 'aircraft_types']
                        );
                        $upsertData = [];
                        
                        if ($this->batch() && $this->batch()->cancelled()) {
                            return;
                        }
                    }
                }
            }
        }

        if (!empty($upsertData)) {
            SystemGlobalFlight::upsert(
                $upsertData,
                ['route_hash'],
                ['operator', 'departure_icao', 'arrival_icao', 'aircraft_types']
            );
        }
    }
}
