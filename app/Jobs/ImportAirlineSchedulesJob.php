<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\ScheduleImportService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportAirlineSchedulesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     *
     * @param int $tenantId Target tenant ID
     * @param array $filters Query filters for the API or specific schedule IDs
     * @param string|null $targetIcao Target callsign ICAO prefix
     * @param string|null $stripPrefix Prefix to strip from flight numbers
     * @param int|null $maxRecords Maximum records to import
     * @param array|null $directSchedules Pre-fetched schedule objects (optional)
     */
    public function __construct(
        protected int $tenantId,
        protected array $filters = [],
        protected ?string $targetIcao = null,
        protected ?string $stripPrefix = null,
        protected ?int $maxRecords = null,
        protected ?array $directSchedules = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ScheduleImportService $service): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            Log::error("ImportAirlineSchedulesJob: Tenant with ID {$this->tenantId} not found.");
            return;
        }

        Log::info("ImportAirlineSchedulesJob: Starting schedule import for Tenant {$tenant->name} (ID: {$tenant->id})...", [
            'filters' => $this->filters,
            'maxRecords' => $this->maxRecords,
            'directCount' => $this->directSchedules ? count($this->directSchedules) : 0,
        ]);

        try {
            // Case 1: Direct list of schedule objects passed (e.g. from UI multi-select)
            if (!empty($this->directSchedules)) {
                $result = $service->importSchedulesToTenant(
                    $tenant,
                    $this->directSchedules,
                    $this->targetIcao,
                    $this->stripPrefix
                );

                Log::info("ImportAirlineSchedulesJob: Directly imported {$result['routes_imported']} routes and synced {$result['airports_synced']} airports for Tenant {$tenant->id}.");
                return;
            }

            // Case 2: Stream from API using filters
            $totalRoutes = 0;
            $totalAirports = 0;

            foreach ($service->streamSchedules($this->filters, 200, $this->maxRecords) as $chunk) {
                if ($this->batch() && $this->batch()->cancelled()) {
                    Log::info("ImportAirlineSchedulesJob: Batch was cancelled, stopping import.");
                    return;
                }

                $result = $service->importSchedulesToTenant(
                    $tenant,
                    $chunk,
                    $this->targetIcao,
                    $this->stripPrefix
                );

                $totalRoutes += $result['routes_imported'];
                $totalAirports += $result['airports_synced'];
            }

            Log::info("ImportAirlineSchedulesJob: Completed import for Tenant {$tenant->id}. Total routes: {$totalRoutes}, Total airports synced: {$totalAirports}.");
        } catch (\Throwable $e) {
            Log::error("ImportAirlineSchedulesJob failed for Tenant {$tenant->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
