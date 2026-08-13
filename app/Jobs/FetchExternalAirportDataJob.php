<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchExternalAirportDataJob implements ShouldQueue
{
    use \Illuminate\Bus\Batchable, \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

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

        ini_set('memory_limit', '512M');
        set_time_limit(300);

        \Illuminate\Support\Facades\Log::info('FetchExternalAirportDataJob: Starting fetch...');

        try {
            $url = 'https://raw.githubusercontent.com/jpatokal/openflights/master/data/airports.dat';
            $response = \Illuminate\Support\Facades\Http::timeout(120)->get($url);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::error('FetchExternalAirportDataJob: HTTP request failed with status ' . $response->status());
                return;
            }

        $lines = explode("\n", $response->body());
        $upsertData = [];
        $chunkSize = 500;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $data = str_getcsv($line, ',', '"', '\\');
            
            // OpenFlights airports.dat format:
            // 0: Airport ID, 1: Name, 2: City, 3: Country, 4: IATA, 5: ICAO, 6: Latitude, 7: Longitude, 8: Altitude
            if (count($data) >= 9) {
                $iata = $data[4] !== '\\N' && $data[4] !== '' ? $data[4] : null;
                $icao = $data[5] !== '\\N' && $data[5] !== '' ? $data[5] : null;

                if ($icao && strlen($icao) <= 4) {
                    $upsertData[] = [
                        'name' => $data[1] !== '\\N' ? $data[1] : null,
                        'city' => $data[2] !== '\\N' ? $data[2] : null,
                        'country' => $data[3] !== '\\N' ? $data[3] : null,
                        'iata' => $iata,
                        'icao' => $icao,
                        'latitude' => is_numeric($data[6]) ? (float) $data[6] : null,
                        'longitude' => is_numeric($data[7]) ? (float) $data[7] : null,
                        'elevation' => is_numeric($data[8]) ? (int) $data[8] : null,
                    ];

                    if (count($upsertData) >= $chunkSize) {
                        \App\Models\SystemGlobalAirport::upsert(
                            $upsertData,
                            ['icao'],
                            ['name', 'city', 'country', 'iata', 'latitude', 'longitude', 'elevation']
                        );
                        $upsertData = [];
                    }
                }
            }
        }

        if (!empty($upsertData)) {
            \App\Models\SystemGlobalAirport::upsert(
                $upsertData,
                ['icao'],
                ['name', 'city', 'country', 'iata', 'latitude', 'longitude', 'elevation']
            );
        }

        \Illuminate\Support\Facades\Log::info('FetchExternalAirportDataJob: Completed successfully.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('FetchExternalAirportDataJob failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
