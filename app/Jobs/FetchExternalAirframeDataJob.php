<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemGlobalAirframe;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchExternalAirframeDataJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600; // 10 minutes

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

        Log::info('FetchExternalAirframeDataJob: Starting fetch from OpenFlights/PlaneAlert repository...');

        try {
            $url = 'https://raw.githubusercontent.com/sdr-enthusiasts/plane-alert-db/main/plane-alert-db.csv';
            $response = Http::timeout(120)->get($url);

            if (!$response->successful()) {
                Log::error('FetchExternalAirframeDataJob: HTTP request failed with status ' . $response->status());
                return;
            }

            $lines = explode("\n", $response->body());
            $upsertData = [];
            $chunkSize = 500;

            foreach ($lines as $index => $line) {
                if ($index === 0) continue; // Skip CSV header line ($ICAO,$Registration,$Operator,$Type,$ICAO Type,...)

                $line = trim($line);
                if (empty($line)) continue;

                $data = str_getcsv($line, ',', '"', '\\');

                // CSV columns: 
                // 0: $ICAO (hex), 1: $Registration, 2: $Operator, 3: $Type, 4: $ICAO Type
                if (count($data) >= 5) {
                    $reg = trim($data[1]);
                    $operator = trim($data[2]);
                    $typeName = trim($data[3]);
                    $icaoType = trim($data[4]);

                    // Remove leading $ if present in column data
                    $reg = ltrim($reg, '$');
                    $operator = ltrim($operator, '$');
                    $typeName = ltrim($typeName, '$');
                    $icaoType = ltrim($icaoType, '$');

                    if (!empty($reg) && !empty($icaoType) && strlen($reg) <= 20) {
                        $upsertData[$reg] = [
                            'registration' => strtoupper($reg),
                            'icao_code' => strtoupper($icaoType),
                            'operator' => !empty($operator) ? mb_substr($operator, 0, 255) : null,
                            'name' => !empty($typeName) ? mb_substr($typeName, 0, 255) : null,
                        ];

                        if (count($upsertData) >= $chunkSize) {
                            SystemGlobalAirframe::upsert(
                                array_values($upsertData),
                                ['registration'],
                                ['icao_code', 'operator', 'name']
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
                SystemGlobalAirframe::upsert(
                    array_values($upsertData),
                    ['registration'],
                    ['icao_code', 'operator', 'name']
                );
            }

            Log::info('FetchExternalAirframeDataJob: Completed fetching real-world airframes successfully.');
        } catch (\Throwable $e) {
            Log::error('FetchExternalAirframeDataJob failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
