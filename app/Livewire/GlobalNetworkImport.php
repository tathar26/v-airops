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

    public function search()
    {
        $this->resetPage();
    }

    public function hasSearch(): bool
    {
        return $this->searchDeparture !== '' || $this->searchArrival !== '' || $this->searchOperator !== '';
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            if (!$this->hasSearch()) {
                $this->selectAll = false;
                return;
            }
            $this->selectedFlights = $this->buildQuery()->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedFlights = [];
        }
    }

    protected function buildQuery()
    {
        $query = SystemGlobalFlight::query()
            ->select(
                'system_global_flights.*',
                'sga.name as airline_name',
                'sga.iata as airline_iata',
                'sga.icao as airline_icao',
                'dep_airport.name as dep_name',
                'dep_airport.iata as dep_iata',
                'arr_airport.name as arr_name',
                'arr_airport.iata as arr_iata'
            )
            // Use a single-key join on iata only for performance (most operators are stored as IATA codes)
            ->leftJoin('system_global_airlines as sga', 'system_global_flights.operator', '=', 'sga.iata')
            ->leftJoin('system_global_airports as dep_airport', 'system_global_flights.departure_icao', '=', 'dep_airport.icao')
            ->leftJoin('system_global_airports as arr_airport', 'system_global_flights.arrival_icao', '=', 'arr_airport.icao')
            ->whereRaw('CHAR_LENGTH(system_global_flights.departure_icao) = 4 AND CHAR_LENGTH(system_global_flights.arrival_icao) = 4')
            ->orderBy('system_global_flights.id');

        if ($this->searchDeparture) {
            $query->where(function($q) {
                $q->where('system_global_flights.departure_icao', 'like', strtoupper($this->searchDeparture) . '%')
                  ->orWhere('dep_airport.iata', 'like', strtoupper($this->searchDeparture) . '%')
                  ->orWhere('dep_airport.name', 'like', '%' . $this->searchDeparture . '%');
            });
        }

        if ($this->searchArrival) {
            $query->where(function($q) {
                $q->where('system_global_flights.arrival_icao', 'like', strtoupper($this->searchArrival) . '%')
                  ->orWhere('arr_airport.iata', 'like', strtoupper($this->searchArrival) . '%')
                  ->orWhere('arr_airport.name', 'like', '%' . $this->searchArrival . '%');
            });
        }

        if ($this->searchOperator) {
            $query->where(function($q) {
                $q->where('system_global_flights.operator', 'like', '%' . $this->searchOperator . '%')
                  ->orWhere('system_global_flights.flight_number', 'like', '%' . $this->searchOperator . '%')
                  ->orWhere('sga.name', 'like', '%' . $this->searchOperator . '%')
                  ->orWhere('sga.icao', 'like', '%' . strtoupper($this->searchOperator) . '%')
                  ->orWhere('sga.iata', 'like', '%' . strtoupper($this->searchOperator) . '%');
            });
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
            'flights' => $this->hasSearch() ? $this->buildQuery()->paginate(25) : null,
            'currentBatch' => $this->batch
        ])->layout('layouts.app');
    }
}
