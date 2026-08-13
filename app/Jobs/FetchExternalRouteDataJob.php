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

        // Fetch routes from Jonty's mirror of OpenFlights
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

            $data = str_getcsv($line);
            
            // OpenFlights format: 
            // 0: Airline, 1: Airline ID, 2: Source airport, 3: Source airport ID, 
            // 4: Destination airport, 5: Destination airport ID, 6: Codeshare, 7: Stops, 8: Equipment
            
            if (count($data) >= 9) {
                $operator = $data[0] !== '\\N' ? $data[0] : null;
                $depIcao = $data[2] !== '\\N' ? $data[2] : null;
                $arrIcao = $data[4] !== '\\N' ? $data[4] : null;
                $equipment = $data[8] !== '\\N' ? $data[8] : null;

                // OpenFlights primarily uses IATA codes for airports in this dataset, but sometimes ICAO.
                // Assuming we want to store it as provided, or we would need an IATA to ICAO mapping.
                // For simplicity as requested, we store it directly.
                if (strlen($depIcao) <= 4 && strlen($arrIcao) <= 4 && $depIcao && $arrIcao) {
                    $hashString = $depIcao . '-' . $arrIcao . '-' . ($operator ?? 'NA') . '-EXTERNAL';
                    $hash = md5($hashString);

                    $upsertData[] = [
                        'original_tenant_id' => null, // External
                        'flight_number' => null,
                        'operator' => $operator,
                        'departure_icao' => $depIcao,
                        'arrival_icao' => $arrIcao,
                        'block_time' => null,
                        'route_type' => 'Scheduled',
                        'distance' => null,
                        'aircraft_types' => $equipment ? str_replace(' ', ',', $equipment) : null,
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
