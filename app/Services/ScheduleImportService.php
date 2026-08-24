<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\Route;
use App\Models\Tenant;
use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ScheduleImportService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retrySleepMs;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.schedules_api.base_url', 'https://schedules.artmex-hosting.com'), '/');
        $this->apiKey = config('services.schedules_api.key');
        $this->timeout = (int) config('services.schedules_api.timeout', 30);
        $this->retryAttempts = (int) config('services.schedules_api.retry_attempts', 3);
        $this->retrySleepMs = (int) config('services.schedules_api.retry_sleep', 500);
    }

    /**
     * Create a pre-configured HTTP client instance.
     */
    protected function client(): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->retry($this->retryAttempts, $this->retrySleepMs, function ($exception, $request) {
                Log::warning('Schedules API request failed, retrying...', [
                    'url' => (string) $request->url(),
                    'error' => $exception->getMessage(),
                ]);
                return true;
            }, throw: false)
            ->acceptJson();

        if (!empty($this->apiKey)) {
            $client->withHeaders(['X-API-Key' => $this->apiKey]);
        }

        return $client;
    }

    /**
     * Perform a health check on the microservice.
     *
     * @return array{status: string, database: string, airports_seeded: int, active_flights_count: int, timestamp: string}
     */
    public function healthCheck(): array
    {
        try {
            $response = $this->client()->get('/health');
            if ($response->successful()) {
                return $response->json();
            }

            throw new RuntimeException("Health check returned status {$response->status()}: " . $response->body());
        } catch (\Throwable $e) {
            Log::error('ScheduleImportService::healthCheck failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch global system metrics and overview statistics from the API.
     *
     * @return array
     */
    public function getStats(): array
    {
        try {
            $response = $this->client()->get('/api/v1/stats');
            if ($response->successful()) {
                return $response->json();
            }

            throw new RuntimeException("Stats request returned status {$response->status()}: " . $response->body());
        } catch (\Throwable $e) {
            Log::error('ScheduleImportService::getStats failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch all discovered routes and summary statistics for a specific airline.
     *
     * @param string $airlineIcao 3-letter airline ICAO code
     * @param int $limit Max results (1-1000)
     * @param int $offset Offset index
     * @return array
     */
    public function getAirlineSummary(string $airlineIcao, int $limit = 100, int $offset = 0): array
    {
        $response = $this->client()->get("/api/v1/schedules/" . strtoupper(trim($airlineIcao)), [
            'limit' => min(1000, max(1, $limit)),
            'offset' => max(0, $offset),
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new RuntimeException("Airline summary request failed for {$airlineIcao} ({$response->status()}): " . $response->body());
    }

    /**
     * Query synthesized airline schedules with filtering, sorting, and pagination.
     *
     * @param array{
     *     airline_icao?: ?string,
     *     origin_icao?: ?string,
     *     destination_icao?: ?string,
     *     callsign?: ?string,
     *     sort_by?: ?string,
     *     order?: ?string
     * } $filters
     * @param int $limit Page size limit (1 to 1000)
     * @param int $offset Starting offset
     * @return array{items: array, total: int, limit: int, offset: int, has_more: bool}
     */
    public function querySchedules(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $params = [
            'limit' => min(1000, max(1, $limit)),
            'offset' => max(0, $offset),
        ];

        if (!empty($filters['airline_icao'])) {
            $params['airline_icao'] = strtoupper(trim($filters['airline_icao']));
        }
        if (!empty($filters['origin_icao'])) {
            $params['origin_icao'] = strtoupper(trim($filters['origin_icao']));
        }
        if (!empty($filters['destination_icao'])) {
            $params['destination_icao'] = strtoupper(trim($filters['destination_icao']));
        }
        if (!empty($filters['callsign'])) {
            $params['callsign'] = strtoupper(trim($filters['callsign']));
        }
        if (!empty($filters['sort_by'])) {
            $params['sort_by'] = $filters['sort_by'];
        }
        if (!empty($filters['order'])) {
            $params['order'] = strtolower($filters['order']) === 'asc' ? 'asc' : 'desc';
        }

        $response = $this->client()->get('/api/v1/schedules', $params);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('ScheduleImportService::querySchedules failed', [
            'status' => $response->status(),
            'body' => $response->body(),
            'params' => $params,
        ]);

        throw new RuntimeException("Schedule query failed with status {$response->status()}: " . $response->body());
    }

    /**
     * Stream schedules from the API page by page using a PHP Generator to minimize memory usage.
     *
     * @param array $filters Query filters (airline_icao, origin_icao, destination_icao, etc.)
     * @param int $pageSize Number of records per API page (default 500)
     * @param int|null $maxRecords Maximum total records to stream (null for all available)
     * @return Generator<int, array> Yields array of schedule items for each page
     */
    public function streamSchedules(array $filters = [], int $pageSize = 500, ?int $maxRecords = null): Generator
    {
        $offset = 0;
        $totalFetched = 0;
        $pageSize = min(1000, max(1, $pageSize));

        do {
            $currentLimit = $pageSize;
            if ($maxRecords !== null) {
                $remaining = $maxRecords - $totalFetched;
                if ($remaining <= 0) {
                    break;
                }
                $currentLimit = min($pageSize, $remaining);
            }

            $response = $this->querySchedules($filters, $currentLimit, $offset);
            $items = $response['items'] ?? [];
            $hasMore = (bool) ($response['has_more'] ?? false);

            if (empty($items)) {
                break;
            }

            yield $items;

            $count = count($items);
            $totalFetched += $count;
            $offset += $count;

            if ($maxRecords !== null && $totalFetched >= $maxRecords) {
                break;
            }
        } while ($hasMore);
    }

    /**
     * Import an array of schedule items into a specific Tenant's local database.
     * Performs bulk upserts for both missing airports and routes.
     *
     * @param int|Tenant $tenant Tenant instance or Tenant ID
     * @param array $schedules Array of schedule items from the API
     * @param string|null $targetIcao Optional override for the target VA ICAO callsign prefix
     * @param string|null $stripPrefix Optional prefix to strip from callsign/flight numbers (e.g., 'U2', 'FR')
     * @return array{routes_imported: int, airports_synced: int}
     */
    public function importSchedulesToTenant(
        int|Tenant $tenant,
        array $schedules,
        ?string $targetIcao = null,
        ?string $stripPrefix = null
    ): array {
        if (empty($schedules)) {
            return ['routes_imported' => 0, 'airports_synced' => 0];
        }

        $tenantModel = is_int($tenant) ? Tenant::find($tenant) : $tenant;
        $tenantId = $tenantModel ? $tenantModel->id : (int) $tenant;

        $defaultTenantIcao = !empty($targetIcao)
            ? strtoupper(trim($targetIcao))
            : ($tenantModel && !empty($tenantModel->icao) ? strtoupper($tenantModel->icao) : 'VOPS');

        // 1. Sync embedded origin & destination airports in bulk
        $airportsSynced = $this->syncAirportsFromSchedules($schedules);

        // 2. Prepare routes for bulk upsert
        $routesImported = 0;
        $chunks = array_chunk($schedules, 100);

        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($chunk, $tenantId, $defaultTenantIcao, $stripPrefix, &$routesImported) {
                foreach ($chunk as $item) {
                    $depIcao = strtoupper(trim($item['origin_icao'] ?? ''));
                    $arrIcao = strtoupper(trim($item['destination_icao'] ?? ''));
                    $operatorIcao = strtoupper(trim($item['airline_icao'] ?? $defaultTenantIcao));
                    $rawCallsign = strtoupper(trim($item['callsign'] ?? ''));

                    if (empty($depIcao) || empty($arrIcao) || strlen($depIcao) !== 4 || strlen($arrIcao) !== 4) {
                        continue;
                    }

                    // Determine suffix and full tenant callsign
                    $suffix = $rawCallsign;
                    if (!empty($stripPrefix)) {
                        $pfx = strtoupper(trim($stripPrefix));
                        if (str_starts_with($rawCallsign, $pfx)) {
                            $suffix = substr($rawCallsign, strlen($pfx));
                        }
                    } else {
                        // Auto-strip leading 2-3 character airline prefix (e.g., KLM1234 -> 1234, BAW1420 -> 1420)
                        $stripped = preg_replace('/^[A-Z0-9]{2,3}/', '', $rawCallsign);
                        $suffix = !empty($stripped) ? $stripped : $rawCallsign;
                    }

                    $callsignIcao = $defaultTenantIcao;
                    $fullCallsign = $callsignIcao . $suffix;
                    $flightNumber = !empty($rawCallsign) ? $rawCallsign : ($operatorIcao . $suffix);

                    // Determine block time (HH:MM)
                    $durationMins = (int) ($item['duration_minutes'] ?? 0);
                    $blockTime = '02:00';
                    if ($durationMins > 0) {
                        $blockTime = sprintf('%02d:%02d', floor($durationMins / 60), $durationMins % 60);
                    } elseif (!empty($item['departure_time_utc']) && !empty($item['arrival_time_utc'])) {
                        $blockTime = $this->calculateBlockTimeFromUtc($item['departure_time_utc'], $item['arrival_time_utc']);
                    }

                    // Compute distance in Nautical Miles if airports coordinates exist
                    $distanceNm = null;
                    $originAirport = $item['origin_airport'] ?? null;
                    $destAirport = $item['destination_airport'] ?? null;
                    if (
                        isset($originAirport['latitude'], $originAirport['longitude'], $destAirport['latitude'], $destAirport['longitude'])
                    ) {
                        $distanceNm = $this->calculateDistanceNm(
                            (float) $originAirport['latitude'],
                            (float) $originAirport['longitude'],
                            (float) $destAirport['latitude'],
                            (float) $destAirport['longitude']
                        );
                    }

                    $timesObserved = $item['times_observed'] ?? 1;

                    // Upsert or update route for tenant
                    Route::withoutGlobalScopes()->updateOrCreate(
                        [
                            'tenant_id'      => $tenantId,
                            'flight_number'  => $flightNumber,
                            'departure_icao' => $depIcao,
                            'arrival_icao'   => $arrIcao,
                        ],
                        [
                            'callsign'        => $fullCallsign,
                            'callsign_icao'   => $callsignIcao,
                            'callsign_suffix' => $suffix,
                            'operator'        => $operatorIcao,
                            'block_time'      => $blockTime,
                            'route_type'      => 'Scheduled',
                            'distance'        => $distanceNm,
                            'remarks'         => "Imported from Worldwide Schedules API (Observed: {$timesObserved}x)",
                        ]
                    );

                    $routesImported++;
                }
            });
        }

        return [
            'routes_imported' => $routesImported,
            'airports_synced' => $airportsSynced,
        ];
    }

    /**
     * Batch upsert embedded origin and destination airport objects into the local airports table.
     *
     * @param array $schedules
     * @return int Number of airports upserted
     */
    protected function syncAirportsFromSchedules(array $schedules): int
    {
        $airportsMap = [];

        foreach ($schedules as $item) {
            foreach (['origin_airport', 'destination_airport'] as $key) {
                if (!empty($item[$key]) && is_array($item[$key])) {
                    $ap = $item[$key];
                    $icao = strtoupper(trim($ap['icao'] ?? ''));
                    if (strlen($icao) === 4 && isset($ap['latitude'], $ap['longitude'])) {
                        $airportsMap[$icao] = [
                            'icao' => $icao,
                            'name' => $ap['name'] ?? $icao,
                            'lat' => (float) $ap['latitude'],
                            'lon' => (float) $ap['longitude'],
                        ];
                    }
                }
            }
        }

        if (empty($airportsMap)) {
            return 0;
        }

        $records = array_values($airportsMap);

        // Perform bulk upsert on local airports table
        Airport::upsert(
            $records,
            ['icao'],
            ['name', 'lat', 'lon']
        );

        return count($records);
    }

    /**
     * Calculate Great-Circle distance between two coordinates in Nautical Miles (Haversine formula).
     *
     * @param float $lat1 Latitude 1 in decimal degrees
     * @param float $lon1 Longitude 1 in decimal degrees
     * @param float $lat2 Latitude 2 in decimal degrees
     * @param float $lon2 Longitude 2 in decimal degrees
     * @return int Distance in Nautical Miles
     */
    public function calculateDistanceNm(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadiusKm = 6371.0;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        $distanceKm = $angle * $earthRadiusKm;
        return (int) round($distanceKm * 0.539957); // 1 km = 0.539957 NM
    }

    /**
     * Calculate block time string from departure and arrival UTC time strings.
     */
    protected function calculateBlockTimeFromUtc(string $depTime, string $arrTime): string
    {
        try {
            $dep = strtotime("1970-01-01 $depTime UTC");
            $arr = strtotime("1970-01-01 $arrTime UTC");

            if ($dep === false || $arr === false) {
                return '02:00';
            }

            $diff = $arr - $dep;
            if ($diff < 0) {
                $diff += 86400; // Passed midnight
            }

            $hours = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);

            return sprintf('%02d:%02d', $hours, $minutes);
        } catch (\Throwable) {
            return '02:00';
        }
    }
}
