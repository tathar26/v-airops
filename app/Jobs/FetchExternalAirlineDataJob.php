<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemGlobalAirline;
use Illuminate\Support\Facades\Http;

class FetchExternalAirlineDataJob implements ShouldQueue
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

        // Fetch airlines from Jonty's mirror of OpenFlights
        $url = 'https://raw.githubusercontent.com/jpatokal/openflights/master/data/airlines.dat';
        
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
            
            // OpenFlights format: 
            // 0: Airline ID, 1: Name, 2: Alias, 3: IATA, 4: ICAO, 5: Callsign, 6: Country, 7: Active
            
            if (count($data) >= 8) {
                $name = $data[1] !== '\\N' ? $data[1] : null;
                $iata = $data[3] !== '\\N' ? mb_substr($data[3], 0, 3) : null;
                $icao = $data[4] !== '\\N' ? mb_substr($data[4], 0, 4) : null;
                $callsign = $data[5] !== '\\N' ? $data[5] : null;
                $country = $data[6] !== '\\N' ? $data[6] : null;
                $active = $data[7] === 'Y';

                if ($name && ($iata || $icao)) {
                    // For the hash, we'll use name + iata + icao
                    $hashString = $name . '-' . ($iata ?? '') . '-' . ($icao ?? '');
                    $hash = md5($hashString);

                    $upsertData[] = [
                        'name' => $name,
                        'iata' => $iata,
                        'icao' => $icao,
                        'callsign' => $callsign,
                        'country' => $country,
                        'active' => $active,
                        'hash' => $hash,
                    ];

                    if (count($upsertData) >= $chunkSize) {
                        SystemGlobalAirline::upsert(
                            $upsertData,
                            ['hash'],
                            ['name', 'iata', 'icao', 'callsign', 'country', 'active']
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
            SystemGlobalAirline::upsert(
                $upsertData,
                ['hash'],
                ['name', 'iata', 'icao', 'callsign', 'country', 'active']
            );
        }
    }
}
