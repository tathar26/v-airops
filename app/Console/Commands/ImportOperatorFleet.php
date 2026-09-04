<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ScheduleImportService;
use Illuminate\Console\Command;

class ImportOperatorFleet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fleet:import
        {--tenant= : Target Tenant ID or Airline ICAO code}
        {--operator= : Airline/Operator 3-letter ICAO code (e.g. DLH, KLM, BAW, EZY)}
        {--limit=2000 : Maximum number of aircraft to fetch from the API}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query the Worldwide Fleet microservice and import airframes into a Virtual Airline';

    /**
     * Execute the console command.
     */
    public function handle(ScheduleImportService $service): int
    {
        $this->info('✈️ Worldwide Airline Fleet Import Tool');
        $this->line('Connecting to microservice at: ' . config('services.schedules_api.base_url', 'https://schedules.artmex-hosting.com'));

        // 1. Resolve Target Tenant
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

        $this->line("Target Airline Tenant: <comment>{$tenant->name} ({$tenant->icao})</comment> [ID: {$tenant->id}]");

        // 2. Resolve Operator ICAO
        $operator = $this->option('operator');
        if (empty($operator)) {
            $operator = $tenant->icao;
        }

        if (empty($operator)) {
            $this->error('✗ Operator ICAO is required. Pass --operator=XXX (e.g. DLH, KLM, BAW).');
            return self::FAILURE;
        }

        $operator = strtoupper(trim($operator));
        $limit = max(1, min(10000, (int) $this->option('limit')));

        $this->line("Querying fleet database for operator: <info>{$operator}</info> (limit: {$limit})...");

        // 3. Fetch from API
        try {
            $fleet = $service->getFleetByOperator($operator, $limit);
        } catch (\Throwable $e) {
            $this->error('✗ Failed to query fleet database: ' . $e->getMessage());
            return self::FAILURE;
        }

        $count = count($fleet);
        if ($count === 0) {
            $this->warn("No airframes found in the Fleet Database for operator '{$operator}'.");
            return self::SUCCESS;
        }

        $this->info("✓ Discovered {$count} aircraft for '{$operator}'. Importing into tenant fleet...");

        // 4. Import Airframes
        $result = $service->importAirframesToTenant($tenant, $fleet);

        $this->newLine();
        $this->info("==========================================");
        $this->info("✓ Fleet Import Complete!");
        $this->line("  • Airframes Imported / Updated: <info>{$result['airframes_imported']}</info>");
        $this->line("  • Aircraft Types Created:       <info>{$result['aircraft_types_created']}</info>");
        if ($result['skipped'] > 0) {
            $this->line("  • Skipped (Missing Reg):        <comment>{$result['skipped']}</comment>");
        }
        $this->info("==========================================");

        // Preview sample of imported aircraft
        $sample = array_slice($fleet, 0, 10);
        $rows = array_map(function ($item) {
            return [
                $item['registration'] ?? 'N/A',
                $item['typecode'] ?? 'N/A',
                $item['model'] ?? 'N/A',
                $item['manufacturername'] ?? 'N/A',
                $item['icao24'] ?? 'N/A',
                $item['built'] ?? 'N/A',
            ];
        }, $sample);

        $this->table(['Registration', 'Type', 'Model', 'Manufacturer', 'ICAO24 (Hex)', 'Built'], $rows);
        if ($count > 10) {
            $this->line("... and " . ($count - 10) . " more aircraft.");
        }

        return self::SUCCESS;
    }
}
