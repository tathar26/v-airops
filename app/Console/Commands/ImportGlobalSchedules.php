<?php

namespace App\Console\Commands;

use App\Jobs\ImportAirlineSchedulesJob;
use App\Models\Tenant;
use App\Services\ScheduleImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportGlobalSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedules:import
        {--tenant= : Target Tenant ID or Airline ICAO code}
        {--airline= : Filter by 2-3 letter airline ICAO (e.g. KLM, BAW, DLH)}
        {--origin= : Filter by origin 4-letter ICAO airport code}
        {--destination= : Filter by destination 4-letter ICAO airport code}
        {--callsign= : Filter by specific callsign}
        {--target-icao= : Override target VA callsign prefix}
        {--strip-prefix= : Custom prefix to strip from callsign}
        {--limit= : Maximum number of schedules to import}
        {--queue : Dispatch as a background queued job instead of executing synchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query the Worldwide Schedules microservice and import schedules into a Virtual Airline';

    /**
     * Execute the console command.
     */
    public function handle(ScheduleImportService $service): int
    {
        $this->info('✈️ Worldwide Airline Schedules Import Tool');
        $this->line('Connecting to microservice at: ' . config('services.schedules_api.base_url', 'https://schedules.artmex-hosting.com'));

        // 1. Health check
        try {
            $health = $service->healthCheck();
            $this->info("✓ Microservice online (Status: {$health['status']}, DB: {$health['database']}, Active Flights: {$health['active_flights_count']})");
        } catch (\Throwable $e) {
            $this->error('✗ Failed to connect to Schedules microservice: ' . $e->getMessage());
            return self::FAILURE;
        }

        // 2. Resolve Target Tenant
        $tenantInput = $this->option('tenant');
        $tenant = null;

        if (!empty($tenantInput)) {
            if (is_numeric($tenantInput)) {
                $tenant = Tenant::find($tenantInput);
            } else {
                $tenant = Tenant::where('icao', strtoupper(trim($tenantInput)))->first();
            }
        }

        if (!$tenant) {
            $tenant = Tenant::first();
        }

        if (!$tenant) {
            $this->error('✗ No tenant found in the local database. Please create a tenant before importing.');
            return self::FAILURE;
        }

        // 3. Build Filters
        $filters = [];
        if ($airline = $this->option('airline')) {
            $filters['airline_icao'] = strtoupper(trim($airline));
        }
        if ($origin = $this->option('origin')) {
            $filters['origin_icao'] = strtoupper(trim($origin));
        }
        if ($dest = $this->option('destination')) {
            $filters['destination_icao'] = strtoupper(trim($dest));
        }
        if ($callsign = $this->option('callsign')) {
            $filters['callsign'] = strtoupper(trim($callsign));
        }

        $targetIcao = $this->option('target-icao') ?: $tenant->icao ?: 'VOPS';
        $stripPrefix = $this->option('strip-prefix');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        // 4. Initial Query to check total available
        try {
            $initialCheck = $service->querySchedules($filters, 1, 0);
            $totalAvailable = $initialCheck['total'] ?? 0;
        } catch (\Throwable $e) {
            $this->error('✗ Failed to query schedules: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->table(['Parameter', 'Value'], [
            ['Target Tenant', "{$tenant->name} (ID: {$tenant->id}, ICAO: {$tenant->icao})"],
            ['Callsign ICAO Prefix', $targetIcao],
            ['Strip Prefix', $stripPrefix ?: '(Auto-strip airline code)'],
            ['Filter Airline ICAO', $filters['airline_icao'] ?? '(All)'],
            ['Filter Origin ICAO', $filters['origin_icao'] ?? '(All)'],
            ['Filter Destination ICAO', $filters['destination_icao'] ?? '(All)'],
            ['Filter Callsign', $filters['callsign'] ?? '(All)'],
            ['Total Matching on API', number_format($totalAvailable)],
            ['Import Limit', $limit ? number_format($limit) : 'All matching'],
        ]);

        if ($totalAvailable === 0) {
            $this->warn('No schedules found matching the specified filters.');
            return self::SUCCESS;
        }

        $recordsToImport = $limit ? min($limit, $totalAvailable) : $totalAvailable;

        // 5. Handle Queued Execution
        if ($this->option('queue')) {
            ImportAirlineSchedulesJob::dispatch($tenant->id, $filters, $targetIcao, $stripPrefix, $limit);
            $this->info("✓ Dispatched background import job for {$recordsToImport} schedules (Tenant: {$tenant->name}).");
            return self::SUCCESS;
        }

        // 6. Execute Synchronously with Interactive Progress Bar
        $this->newLine();
        $this->info("Importing up to {$recordsToImport} schedules in memory-safe chunks...");

        $progressBar = $this->output->createProgressBar($recordsToImport);
        $progressBar->start();

        $totalRoutesImported = 0;
        $totalAirportsSynced = 0;

        try {
            foreach ($service->streamSchedules($filters, 200, $limit) as $chunk) {
                $result = $service->importSchedulesToTenant($tenant, $chunk, $targetIcao, $stripPrefix);
                $totalRoutesImported += $result['routes_imported'];
                $totalAirportsSynced += $result['airports_synced'];

                $progressBar->advance(count($chunk));
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info("🎉 Import complete!");
            $this->line("• Routes imported / updated: <fg=green>{$totalRoutesImported}</>");
            $this->line("• Airports synchronized: <fg=green>{$totalAirportsSynced}</>");

            Log::info("ImportGlobalSchedules completed: {$totalRoutesImported} routes imported for Tenant {$tenant->id}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("✗ An error occurred during import: " . $e->getMessage());
            Log::error('ImportGlobalSchedules failed: ' . $e->getMessage(), ['exception' => $e]);
            return self::FAILURE;
        }
    }
}
