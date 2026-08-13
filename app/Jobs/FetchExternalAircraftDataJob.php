<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemGlobalAircraft;
use Illuminate\Support\Facades\Http;

class FetchExternalAircraftDataJob implements ShouldQueue
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

        // Fetch planes from Jpatokal's OpenFlights mirror
        $url = 'https://raw.githubusercontent.com/jpatokal/openflights/master/data/planes.dat';
        
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
            
            // OpenFlights planes.dat format: 
            // 0: Name, 1: IATA, 2: ICAO
            
            if (count($data) >= 3) {
                $name = $data[0] !== '\\N' ? $data[0] : null;
                // $iata = $data[1] !== '\\N' ? $data[1] : null;
                $icao = $data[2] !== '\\N' ? $data[2] : null;
                
                // We must have an ICAO code to map properly in flight sim environments
                if ($name && $icao && $icao !== '\\N' && strlen($icao) >= 2) {
                    $upsertData[] = [
                        'original_tenant_id' => null,
                        'code' => $icao,
                        'name' => $name,
                    ];

                    if (count($upsertData) >= $chunkSize) {
                        SystemGlobalAircraft::upsert(
                            $upsertData,
                            ['code'],
                            ['name']
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
            SystemGlobalAircraft::upsert(
                $upsertData,
                ['code'],
                ['name']
            );
        }
    }
}
