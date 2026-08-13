<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SystemGlobalFlight;
use App\Jobs\ImportGlobalDataToVAJob;
use Illuminate\Support\Facades\Bus;

class GlobalNetworkImport extends Component
{
    use WithPagination;

    public $searchDeparture = '';
    public $searchArrival = '';
    public $searchOperator = '';
    
    public $selectedFlights = [];
    public $selectAll = false;

    public $batchId = null;

    protected $queryString = [
        'searchDeparture' => ['except' => ''],
        'searchArrival' => ['except' => ''],
        'searchOperator' => ['except' => ''],
    ];

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedFlights = $this->buildQuery()->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedFlights = [];
        }
    }

    protected function buildQuery()
    {
        $query = SystemGlobalFlight::query();

        if ($this->searchDeparture) {
            $query->where('departure_icao', 'like', '%' . strtoupper($this->searchDeparture) . '%');
        }

        if ($this->searchArrival) {
            $query->where('arrival_icao', 'like', '%' . strtoupper($this->searchArrival) . '%');
        }

        if ($this->searchOperator) {
            $query->where('operator', 'like', '%' . $this->searchOperator . '%');
        }

        return $query;
    }

    public function importSelected()
    {
        if (empty($this->selectedFlights)) {
            session()->flash('error', 'Please select at least one flight to import.');
            return;
        }

        // We chunk the IDs into batches of 200 so we don't overwhelm a single job
        $chunks = array_chunk($this->selectedFlights, 200);
        $jobs = [];

        foreach ($chunks as $chunk) {
            $jobs[] = new ImportGlobalDataToVAJob(auth()->user()->tenant_id, $chunk);
        }

        $batch = Bus::batch($jobs)
            ->name('Import Global Data to VA')
            ->dispatch();

        $this->batchId = $batch->id;
        $this->selectedFlights = [];
        $this->selectAll = false;

        session()->flash('message', 'Import started! Please wait while it processes.');
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
        return view('livewire.global-network-import', [
            'flights' => $this->buildQuery()->paginate(50),
            'currentBatch' => $this->batch
        ])->layout('layouts.app');
    }
}
