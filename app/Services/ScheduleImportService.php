<?php

namespace App\Services;

use App\Models\AircraftType;
use App\Models\Airframe;
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
            ->retry($this->retryAttempts, $this->retrySleepMs, function ($exception) {
                Log::warning('Schedules API request failed, retrying...', [
                    'error' => $exception instanceof \Throwable ? $exception->getMessage() : (string) $exception,
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

        if ($response->status() === 401) {
            throw new RuntimeException("Schedules API returned 401 Unauthorized. Please verify that 'SCHEDULES_API_KEY' is set in your .env or Docker environment.");
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
            DB::transaction(function () use ($chunk, $tenantModel, $tenantId, $defaultTenantIcao, $stripPrefix, &$routesImported) {
                foreach ($chunk as $item) {
                    $depIcao = strtoupper(trim($item['origin_icao'] ?? ''));
                    $arrIcao = strtoupper(trim($item['destination_icao'] ?? ''));
                    $operatorIcao = strtoupper(trim($item['airline_icao'] ?? $defaultTenantIcao));
                    $rawCallsign = strtoupper(trim($item['callsign'] ?? ''));

                    if (empty($depIcao) || empty($arrIcao) || strlen($depIcao) !== 4 || strlen($arrIcao) !== 4) {
                        continue;
                    }

                    // Extract numeric/alphanumeric suffix from incoming flight/callsign
                    $suffix = $tenantModel
                        ? $tenantModel->extractCallsignSuffix($rawCallsign, $stripPrefix)
                        : $this->fallbackExtractSuffix($rawCallsign, $stripPrefix);

                    // Target VA Call sign & Commercial Flight Number
                    $callsignIcao = $defaultTenantIcao;
                    $fullCallsign = $callsignIcao . $suffix;

                    $flightNumber = $tenantModel
                        ? $tenantModel->resolveFlightNumberForTarget($rawCallsign, $defaultTenantIcao, $stripPrefix)
                        : $this->fallbackResolveTargetFlightNumber($defaultTenantIcao, $suffix);

                    $operator = $defaultTenantIcao;

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

    /**
     * Fallback resolution for flight numbers when no tenant model is loaded.
     */
    public function fallbackResolveFlightNumber(string $callsign, ?string $airlineIcao = null): string
    {
        $cleanCallsign = strtoupper(trim($callsign));
        if (empty($cleanCallsign)) {
            return 'FL0001';
        }

        $defaultIcaoToIata = [
            'EZY' => 'U2', 'EZS' => 'DS', 'EJU' => 'EC',
            'RYR' => 'FR', 'RUK' => 'RK', 'MAY' => 'M4',
            'BAW' => 'BA', 'KLM' => 'KL', 'DLH' => 'LH',
            'AFR' => 'AF', 'WZZ' => 'W6', 'WUK' => 'W9',
            'THY' => 'TK', 'SAS' => 'SK', 'FIN' => 'AY',
            'IBE' => 'IB', 'TAP' => 'TP', 'SWR' => 'LX',
            'AUA' => 'OS', 'BEL' => 'SN', 'UAE' => 'EK',
            'QTR' => 'QR', 'ETD' => 'EY', 'QFA' => 'QF',
            'ANZ' => 'NZ', 'SIA' => 'SQ', 'CPA' => 'CX',
            'ANA' => 'NH', 'JAL' => 'JL', 'AAL' => 'AA',
            'DAL' => 'DL', 'UAL' => 'UA', 'SWA' => 'WN',
            'ACA' => 'AC', 'VLG' => 'VY', 'TRA' => 'HV',
            'AZA' => 'AZ', 'EIN' => 'EI', 'NVR' => 'N9',
            'GWI' => '4U', 'VOE' => 'V7', 'EXS' => 'LS',
            'TOM' => 'BY', 'EWG' => 'EW', 'TUI' => 'X3',
        ];

        $prefix3 = substr($cleanCallsign, 0, 3);
        if (isset($defaultIcaoToIata[$prefix3])) {
            return $defaultIcaoToIata[$prefix3] . substr($cleanCallsign, 3);
        }

        if (!empty($airlineIcao) && isset($defaultIcaoToIata[strtoupper($airlineIcao)])) {
            $iata = $defaultIcaoToIata[strtoupper($airlineIcao)];
            $stripped = preg_replace('/^[A-Z0-9]{2,3}/', '', $cleanCallsign);
            $suffix = !empty($stripped) ? $stripped : $cleanCallsign;
            return $iata . $suffix;
        }

        return $cleanCallsign;
    }

    /**
     * Fallback extraction of callsign suffix.
     */
    public function fallbackExtractSuffix(string $callsign, ?string $stripPrefix = null): string
    {
        $clean = strtoupper(trim($callsign));
        if (empty($clean)) {
            return '1001';
        }

        if (!empty($stripPrefix)) {
            $pfx = strtoupper(trim($stripPrefix));
            if (str_starts_with($clean, $pfx)) {
                $suffix = substr($clean, strlen($pfx));
                if (!empty($suffix)) {
                    return $suffix;
                }
            }
        }

        $stripped = preg_replace('/^[A-Z]{2,4}/', '', $clean);
        return !empty($stripped) ? $stripped : $clean;
    }

    /**
     * Fallback resolution for target flight numbers.
     */
    public function fallbackResolveTargetFlightNumber(string $targetIcao, string $suffix): string
    {
        $defaultIcaoToIata = [
            'EZY' => 'U2', 'EZS' => 'DS', 'EJU' => 'EC',
            'RYR' => 'FR', 'RUK' => 'RK', 'MAY' => 'M4',
            'BAW' => 'BA', 'KLM' => 'KL', 'DLH' => 'LH',
            'AFR' => 'AF', 'WZZ' => 'W6', 'WUK' => 'W9',
            'THY' => 'TK', 'SAS' => 'SK', 'FIN' => 'AY',
            'IBE' => 'IB', 'TAP' => 'TP', 'SWR' => 'LX',
            'AUA' => 'OS', 'BEL' => 'SN', 'UAE' => 'EK',
            'QTR' => 'QR', 'ETD' => 'EY', 'QFA' => 'QF',
            'ANZ' => 'NZ', 'SIA' => 'SQ', 'CPA' => 'CX',
            'ANA' => 'NH', 'JAL' => 'JL', 'AAL' => 'AA',
            'DAL' => 'DL', 'UAL' => 'UA', 'SWA' => 'WN',
            'ACA' => 'AC', 'VLG' => 'VY', 'TRA' => 'HV',
            'AZA' => 'AZ', 'EIN' => 'EI', 'NVR' => 'N9',
            'GWI' => '4U', 'VOE' => 'V7', 'EXS' => 'LS',
            'TOM' => 'BY', 'EWG' => 'EW', 'TUI' => 'X3',
        ];

        $targetUpper = strtoupper(trim($targetIcao));
        $fnPrefix = $defaultIcaoToIata[$targetUpper] ?? $targetUpper;

        return $fnPrefix . $suffix;
    }

    /**
     * Query the fleet for a specific airline / operator by its 3-letter ICAO code.
     *
     * @param string $operatorIcao e.g. DLH, KLM, BAW
     * @param int $limit Max results (1 to 10000)
     * @param int $offset Offset index
     * @return array Array of aircraft items
     */
    public function getFleetByOperator(string $operatorIcao, int $limit = 2000, int $offset = 0): array
    {
        $operator = strtoupper(trim($operatorIcao));
        $response = $this->client()->get("/api/fleet/{$operator}", [
            'limit' => min(10000, max(1, $limit)),
            'offset' => max(0, $offset),
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        if ($response->status() === 401) {
            throw new RuntimeException("Fleet API returned 401 Unauthorized. Please verify that 'SCHEDULES_API_KEY' is set in your environment.");
        }

        if ($response->status() === 404) {
            return [];
        }

        Log::error('ScheduleImportService::getFleetByOperator failed', [
            'status' => $response->status(),
            'body' => $response->body(),
            'operator' => $operator,
        ]);

        throw new RuntimeException("Fleet query failed for operator {$operator} with status {$response->status()}: " . $response->body());
    }

    /**
     * Lookup a single aircraft by its registration / tail number.
     *
     * @param string $registration
     * @return array|null
     */
    public function getAircraftByRegistration(string $registration): ?array
    {
        $reg = strtoupper(trim($registration));
        $response = $this->client()->get("/api/aircraft/{$reg}");

        if ($response->successful()) {
            return $response->json();
        }

        if ($response->status() === 404) {
            return null;
        }

        throw new RuntimeException("Aircraft lookup failed for {$reg} with status {$response->status()}: " . $response->body());
    }

    /**
     * Lookup a single aircraft by its 24-bit ICAO hex code.
     *
     * @param string $icao24
     * @return array|null
     */
    public function getAircraftByHex(string $icao24): ?array
    {
        $hex = strtolower(trim($icao24));
        $response = $this->client()->get("/api/hex/{$hex}");

        if ($response->successful()) {
            return $response->json();
        }

        if ($response->status() === 404) {
            return null;
        }

        throw new RuntimeException("Aircraft lookup failed for hex {$hex} with status {$response->status()}: " . $response->body());
    }

    /**
     * Fetch summary statistics from the Fleet Database.
     *
     * @return array
     */
    public function getFleetStats(): array
    {
        $response = $this->client()->get('/api/fleet-stats');

        if ($response->successful()) {
            return $response->json();
        }

        throw new RuntimeException("Fleet stats request failed with status {$response->status()}: " . $response->body());
    }

    /**
     * Import an array of aircraft items (from the Fleet API) into a specific Tenant's local fleet.
     * Resolves or creates AircraftType records, then creates or updates Airframe records.
     *
     * @param int|Tenant $tenant
     * @param array $aircraftList
     * @return array{airframes_imported: int, aircraft_types_created: int, skipped: int}
     */
    public function importAirframesToTenant(int|Tenant $tenant, array $aircraftList): array
    {
        if (empty($aircraftList)) {
            return ['airframes_imported' => 0, 'aircraft_types_created' => 0, 'skipped' => 0];
        }

        $tenantModel = is_int($tenant) ? Tenant::find($tenant) : $tenant;
        $tenantId = $tenantModel ? $tenantModel->id : (int) $tenant;

        $airframesImported = 0;
        $typesCreated = 0;
        $skipped = 0;

        // Cache existing aircraft types for this tenant: [code => id]
        $existingTypes = AircraftType::where('tenant_id', $tenantId)
            ->pluck('id', 'code')
            ->mapWithKeys(fn($id, $code) => [strtoupper($code) => $id])
            ->toArray();

        // Process in chunks within a transaction
        $chunks = array_chunk($aircraftList, 100);

        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($chunk, $tenantId, &$existingTypes, &$airframesImported, &$typesCreated, &$skipped) {
                foreach ($chunk as $item) {
                    $reg = strtoupper(trim($item['registration'] ?? ''));
                    if (empty($reg)) {
                        $skipped++;
                        continue;
                    }

                    // Resolve clean 2-4 char ICAO Type Code (e.g. A20N, B738, A320)
                    $typeCode = $this->resolveAircraftTypeCode(
                        $item['typecode'] ?? null,
                        $item['model'] ?? null,
                        $item['manufacturername'] ?? null
                    );
                    $typeCode = strtoupper(substr(trim($typeCode), 0, 10));

                    // Ensure AircraftType exists
                    if (!isset($existingTypes[$typeCode])) {
                        $manufacturer = trim((string)($item['manufacturername'] ?? ''));
                        $model = trim((string)($item['model'] ?? ''));
                        $typeName = trim($manufacturer . ' ' . $model);
                        if (empty($typeName)) {
                            $typeName = $typeCode . ' Aircraft';
                        }
                        $typeName = substr($typeName, 0, 255);

                        $aircraftType = AircraftType::firstOrCreate(
                            ['tenant_id' => $tenantId, 'code' => $typeCode],
                            ['name' => $typeName]
                        );

                        $existingTypes[$typeCode] = $aircraftType->id;
                        $typesCreated++;
                    }

                    $typeId = $existingTypes[$typeCode];

                    // Resolve airframe friendly name (e.g., "Airbus A320-214 (SL)" or model)
                    $airframeName = trim(($item['manufacturername'] ?? '') . ' ' . ($item['model'] ?? ''));
                    if (empty($airframeName)) {
                        $airframeName = trim((string)($item['model'] ?? '')) ?: ($typeCode . ' Airframe');
                    }
                    $airframeName = substr($airframeName, 0, 255);

                    Airframe::updateOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'registration' => substr($reg, 0, 20),
                        ],
                        [
                            'aircraft_type_id' => $typeId,
                            'name' => $airframeName,
                        ]
                    );

                    $airframesImported++;
                }
            });
        }

        return [
            'airframes_imported' => $airframesImported,
            'aircraft_types_created' => $typesCreated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Convenience method to fetch and import the entire fleet of an operator directly into a Tenant.
     *
     * @param int|Tenant $tenant
     * @param string $operatorIcao
     * @param int $limit
     * @return array{airframes_imported: int, aircraft_types_created: int, skipped: int, total_fetched: int}
     */
    public function importOperatorFleetToTenant(int|Tenant $tenant, string $operatorIcao, int $limit = 2000): array
    {
        $fleet = $this->getFleetByOperator($operatorIcao, $limit);
        $result = $this->importAirframesToTenant($tenant, $fleet);
        $result['total_fetched'] = count($fleet);
        return $result;
    }

    /**
     * Resolve a clean, valid 2-4 character ICAO aircraft type designator (e.g. A320, A20N, B738).
     * Strictly bounded to max 10 characters to prevent database column truncation.
     *
     * @param string|null $rawTypeCode
     * @param string|null $model
     * @param string|null $manufacturer
     * @return string
     */
    public function resolveAircraftTypeCode(?string $rawTypeCode, ?string $model = null, ?string $manufacturer = null): string
    {
        $candidate = strtoupper(trim((string) $rawTypeCode));

        // 1. If candidate is already a clean 2-4 character alphanumeric ICAO designator
        if (preg_match('/^[A-Z0-9]{2,4}$/', $candidate)) {
            return $candidate;
        }

        // 2. Build search pool from raw typecode, model, and manufacturer
        $pool = strtoupper($candidate . ' ' . ($model ?? '') . ' ' . ($manufacturer ?? ''));

        // Airbus A320neo / ceo family
        if (str_contains($pool, 'A320')) {
            if (str_contains($pool, 'NEO') || str_contains($pool, '271N') || str_contains($pool, '251N') || str_contains($pool, 'A20N')) {
                return 'A20N';
            }
            return 'A320';
        }
        if (str_contains($pool, 'A321')) {
            if (str_contains($pool, 'NEO') || str_contains($pool, '271NX') || str_contains($pool, '272N') || str_contains($pool, '251NX') || str_contains($pool, 'A21N')) {
                return 'A21N';
            }
            return 'A321';
        }
        if (str_contains($pool, 'A319')) {
            if (str_contains($pool, 'NEO') || str_contains($pool, 'A19N')) {
                return 'A19N';
            }
            return 'A319';
        }
        if (str_contains($pool, 'A318')) {
            return 'A318';
        }

        // Airbus Widebodies
        if (str_contains($pool, 'A330')) {
            if (str_contains($pool, 'NEO') || str_contains($pool, '900') || str_contains($pool, 'A339')) {
                return 'A339';
            }
            if (str_contains($pool, '800') || str_contains($pool, 'A338')) {
                return 'A338';
            }
            if (str_contains($pool, '200') || str_contains($pool, 'A332')) {
                return 'A332';
            }
            return 'A333';
        }
        if (str_contains($pool, 'A350')) {
            if (str_contains($pool, '1000') || str_contains($pool, 'A35K')) {
                return 'A35K';
            }
            return 'A359';
        }
        if (str_contains($pool, 'A380') || str_contains($pool, 'A388')) {
            return 'A388';
        }
        if (str_contains($pool, 'A340')) {
            if (str_contains($pool, '600')) return 'A346';
            if (str_contains($pool, '500')) return 'A345';
            if (str_contains($pool, '200')) return 'A342';
            return 'A343';
        }
        if (str_contains($pool, 'A220') || str_contains($pool, 'CS100') || str_contains($pool, 'CS300')) {
            if (str_contains($pool, '300') || str_contains($pool, 'BCS3')) return 'BCS3';
            return 'BCS1';
        }

        // Boeing 737 / MAX
        if (str_contains($pool, '737') || str_contains($pool, 'B73')) {
            if (str_contains($pool, 'MAX 8') || str_contains($pool, 'B38M')) return 'B38M';
            if (str_contains($pool, 'MAX 9') || str_contains($pool, 'B39M')) return 'B39M';
            if (str_contains($pool, 'MAX 10') || str_contains($pool, 'B3XM')) return 'B3XM';
            if (str_contains($pool, 'MAX 7') || str_contains($pool, 'B37M')) return 'B37M';
            if (str_contains($pool, '800') || str_contains($pool, '-8') || str_contains($pool, 'B738')) return 'B738';
            if (str_contains($pool, '700') || str_contains($pool, '-7') || str_contains($pool, 'B737')) return 'B737';
            if (str_contains($pool, '900') || str_contains($pool, '-9') || str_contains($pool, 'B739')) return 'B739';
            if (str_contains($pool, '600') || str_contains($pool, 'B736')) return 'B736';
            return 'B738';
        }

        // Boeing 787 Dreamliner
        if (str_contains($pool, '787') || str_contains($pool, 'B78')) {
            if (str_contains($pool, '10') || str_contains($pool, 'B78X')) return 'B78X';
            if (str_contains($pool, '9') || str_contains($pool, 'B789')) return 'B789';
            return 'B788';
        }

        // Boeing 777
        if (str_contains($pool, '777') || str_contains($pool, 'B77')) {
            if (str_contains($pool, '300') || str_contains($pool, '3ER') || str_contains($pool, 'B77W')) return 'B77W';
            if (str_contains($pool, '200') || str_contains($pool, '2ER') || str_contains($pool, '2LR') || str_contains($pool, 'B772')) return 'B772';
            if (str_contains($pool, 'F') || str_contains($pool, 'B77F')) return 'B77F';
            return 'B77W';
        }

        // Boeing 747
        if (str_contains($pool, '747') || str_contains($pool, 'B74')) {
            if (str_contains($pool, '8') || str_contains($pool, 'B748')) return 'B748';
            return 'B744';
        }

        // Boeing 757 & 767
        if (str_contains($pool, '757') || str_contains($pool, 'B75')) {
            if (str_contains($pool, '300') || str_contains($pool, 'B753')) return 'B753';
            return 'B752';
        }
        if (str_contains($pool, '767') || str_contains($pool, 'B76')) {
            if (str_contains($pool, '400') || str_contains($pool, 'B764')) return 'B764';
            if (str_contains($pool, '200') || str_contains($pool, 'B762')) return 'B762';
            return 'B763';
        }

        // Embraer E-Jets
        if (str_contains($pool, 'E190') || str_contains($pool, '190') || str_contains($pool, 'ERJ-190')) {
            if (str_contains($pool, 'E2') || str_contains($pool, 'E290')) return 'E290';
            return 'E190';
        }
        if (str_contains($pool, 'E195') || str_contains($pool, '195') || str_contains($pool, 'ERJ-195')) {
            if (str_contains($pool, 'E2') || str_contains($pool, 'E295')) return 'E295';
            return 'E195';
        }
        if (str_contains($pool, 'E175') || str_contains($pool, '175')) return 'E175';
        if (str_contains($pool, 'E170') || str_contains($pool, '170')) return 'E170';

        // Bombardier / Mitsubishi CRJ
        if (str_contains($pool, 'CRJ')) {
            if (str_contains($pool, '900') || str_contains($pool, 'CRJ9')) return 'CRJ9';
            if (str_contains($pool, '700') || str_contains($pool, 'CRJ7')) return 'CRJ7';
            if (str_contains($pool, '1000') || str_contains($pool, 'CRJX')) return 'CRJX';
            if (str_contains($pool, '200') || str_contains($pool, 'CRJ2')) return 'CRJ2';
            return 'CRJ9';
        }

        // Turboprops (ATR, Dash 8)
        if (str_contains($pool, 'ATR')) {
            if (str_contains($pool, '72') || str_contains($pool, 'AT7')) return 'AT76';
            if (str_contains($pool, '42') || str_contains($pool, 'AT4')) return 'AT45';
            return 'AT76';
        }
        if (str_contains($pool, 'DASH 8') || str_contains($pool, 'Q400') || str_contains($pool, 'DH8D')) {
            return 'DH8D';
        }

        // Generic regex extraction for ICAO-like tokens (e.g. A320, B738, E190, AT72)
        if (preg_match('/\b(A3[0-8]\d|B7[0-8]\d|E1[79][05]|E2[9][05]|CRJ\d|AT[47]\d|DH8[A-D])\b/i', $pool, $m)) {
            return strtoupper($m[1]);
        }

        // Generic Boeing 7xx extraction
        if (preg_match('/\b(7[3-8]\d)\b/i', $pool, $m)) {
            return 'B' . $m[1];
        }

        // Sanitize candidate to alphanumeric and clamp to max 4 chars
        $clean = preg_replace('/[^A-Z0-9]/', '', $candidate);
        if (strlen($clean) >= 2) {
            return substr($clean, 0, 4);
        }

        return 'A320';
    }
}


