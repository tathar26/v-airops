<?php

namespace App\Livewire;

use App\Jobs\ImportAirlineSchedulesJob;
use App\Models\Tenant;
use App\Services\ScheduleImportService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class GlobalNetworkImport extends Component
{
    use WithPagination;

    public $searchDeparture = '';
    public $searchArrival = '';
    public $searchOperator = '';

    public $targetIcao = '';
    public $stripPrefix = '';

    public $selectedFlights = [];
    public $selectAll = false;

    public $batchId = null;
    public $errorMessage = null;

    protected $queryString = [
        'searchDeparture' => ['except' => ''],
        'searchArrival' => ['except' => ''],
        'searchOperator' => ['except' => ''],
    ];

    public function mount()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);
        $this->targetIcao = $tenant && !empty($tenant->icao) ? strtoupper($tenant->icao) : 'VOPS';
    }

    public function search()
    {
        $this->resetPage();
        $this->selectedFlights = [];
        $this->selectAll = false;
        $this->errorMessage = null;
    }

    public function hasSearch(): bool
    {
        return trim($this->searchDeparture) !== '' || trim($this->searchArrival) !== '' || trim($this->searchOperator) !== '';
    }

    /**
     * Build the filter array for the Schedules microservice API.
     */
    protected function buildApiFilters(): array
    {
        $filters = [];

        $dep = strtoupper(trim($this->searchDeparture));
        if (!empty($dep)) {
            $filters['origin_icao'] = $dep;
        }

        $arr = strtoupper(trim($this->searchArrival));
        if (!empty($arr)) {
            $filters['destination_icao'] = $arr;
        }

        $op = strtoupper(trim($this->searchOperator));
        if (!empty($op)) {
            if (strlen($op) <= 3) {
                $filters['airline_icao'] = $op;
            } else {
                $filters['callsign'] = $op;
            }
        }

        return $filters;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            if (!$this->hasSearch()) {
                $this->selectAll = false;
                return;
            }

            try {
                $service = app(ScheduleImportService::class);
                $filters = $this->buildApiFilters();
                // Get the current page items IDs/keys
                $result = $service->querySchedules($filters, 50, ($this->getPage() - 1) * 50);
                $items = $result['items'] ?? [];

                $this->selectedFlights = array_map(function ($item) {
                    return $item['id'] ?? ($item['callsign'] . '_' . $item['origin_icao'] . '_' . $item['destination_icao']);
                }, $items);
            } catch (\Throwable $e) {
                $this->selectedFlights = [];
            }
        } else {
            $this->selectedFlights = [];
        }
    }

    /**
     * Import selected flights from the search results.
     */
    public function importSelected()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        if (!$tenantId) {
            $tenantId = Tenant::first()?->id;
        }

        $filters = $this->buildApiFilters();

        if (empty($this->selectedFlights)) {
            session()->flash('error', 'Please select at least one flight to import.');
            return;
        }

        try {
            $service = app(ScheduleImportService::class);
            // Fetch the specific schedule objects matching the current filters
            $response = $service->querySchedules($filters, 1000, 0);
            $allItems = $response['items'] ?? [];

            $selectedItems = array_filter($allItems, function ($item) {
                $key = $item['id'] ?? ($item['callsign'] . '_' . $item['origin_icao'] . '_' . $item['destination_icao']);
                return in_array((string) $key, array_map('strval', $this->selectedFlights), true)
                    || in_array((int) $key, $this->selectedFlights, true);
            });

            if (empty($selectedItems)) {
                session()->flash('error', 'Unable to locate selected schedule records from API.');
                return;
            }

            $chunks = array_chunk(array_values($selectedItems), 100);
            $jobs = [];

            foreach ($chunks as $chunk) {
                $jobs[] = new ImportAirlineSchedulesJob(
                    tenantId: $tenantId,
                    filters: [],
                    targetIcao: $this->targetIcao,
                    stripPrefix: $this->stripPrefix,
                    directSchedules: $chunk
                );
            }

            $batch = Bus::batch($jobs)
                ->name('Import Selected Global Schedules')
                ->dispatch();

            $this->batchId = $batch->id;
            $this->selectedFlights = [];
            $this->selectAll = false;

            session()->flash('message', 'Import started! Please wait while it processes.');
        } catch (\Throwable $e) {
            Log::error('GlobalNetworkImport::importSelected failed: ' . $e->getMessage());
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Import all matching schedules directly from the microservice.
     */
    public function importAllMatching()
    {
        if (!$this->hasSearch()) {
            session()->flash('error', 'Please apply at least one filter before importing all.');
            return;
        }

        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        if (!$tenantId) {
            $tenantId = Tenant::first()?->id;
        }

        $filters = $this->buildApiFilters();

        $batch = Bus::batch([
            new ImportAirlineSchedulesJob(
                tenantId: $tenantId,
                filters: $filters,
                targetIcao: $this->targetIcao,
                stripPrefix: $this->stripPrefix,
                maxRecords: null
            ),
        ])
        ->name('Import All Matching Global Schedules')
        ->dispatch();

        $this->batchId = $batch->id;
        $this->selectedFlights = [];
        $this->selectAll = false;

        session()->flash('message', 'Bulk import started! All matching schedules are being synced in the background.');
    }

    public function getBatchProperty()
    {
        if (!$this->batchId) {
            return null;
        }

        return Bus::findBatch($this->batchId);
    }

    public function updateBatchProgress()
    {
        $batch = $this->batch;

        if ($batch && $batch->finished()) {
            $this->batchId = null;
            session()->flash('message', 'Import completed successfully!');
        }
    }

    public function render()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);
        $availableIcaos = $tenant ? $tenant->getAllIcaos() : ['VOPS'];

        $paginatedFlights = null;

        if ($this->hasSearch()) {
            try {
                $service = app(ScheduleImportService::class);
                $perPage = 50;
                $currentPage = LengthAwarePaginator::resolveCurrentPage();
                $offset = ($currentPage - 1) * $perPage;

                $result = $service->querySchedules($this->buildApiFilters(), $perPage, $offset);

                $items = $result['items'] ?? [];
                $total = $result['total'] ?? 0;

                $paginatedFlights = new LengthAwarePaginator(
                    $items,
                    $total,
                    $perPage,
                    $currentPage,
                    ['path' => LengthAwarePaginator::resolveCurrentPath()]
                );
            } catch (\Throwable $e) {
                $this->errorMessage = 'Failed to connect to Worldwide Schedules API: ' . $e->getMessage();
                Log::warning('GlobalNetworkImport API query error: ' . $e->getMessage());
            }
        }

        return view('livewire.global-network-import', [
            'tenant' => $tenant,
            'flights' => $paginatedFlights,
            'availableIcaos' => $availableIcaos,
            'currentBatch' => $this->batch,
        ])->layout('layouts.app');
    }
}

