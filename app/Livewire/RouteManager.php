<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\Route;
use App\Models\AircraftType;
use Livewire\WithFileUploads;

class RouteManager extends Component
{
    use WithFileUploads;

    public $showAddModal = false;
    public $editMode = false;
    public $editingId = null;

    public $flight_number = '';
    public $departure_icao = '';
    public $arrival_icao = '';
    public $block_time = '';
    public $route_string = '';
    public $route_type = 'Scheduled';
    public $selectedAircraftTypes = [];

    public $csvFile;

    protected $rules = [
        'flight_number' => 'required|string|max:10',
        'departure_icao' => 'required|string|size:4',
        'arrival_icao' => 'required|string|size:4',
        'block_time' => 'required|string|max:10', // e.g., '02:30'
        'route_string' => 'nullable|string|max:255',
        'route_type' => 'required|string|in:Scheduled,Charter,Cargo',
        'selectedAircraftTypes' => 'array',
        'selectedAircraftTypes.*' => 'exists:aircraft_types,id',
    ];

    public function openAddModal()
    {
        $this->reset(['flight_number', 'departure_icao', 'arrival_icao', 'block_time', 'route_string', 'route_type', 'selectedAircraftTypes', 'editingId', 'editMode', 'csvFile']);
        $this->showAddModal = true;
    }

    public function editRoute($id)
    {
        $route = Route::with('aircraftTypes')->where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->editingId = $route->id;
        $this->flight_number = $route->flight_number;
        $this->departure_icao = $route->departure_icao;
        $this->arrival_icao = $route->arrival_icao;
        $this->block_time = $route->block_time;
        $this->route_string = $route->route_string;
        $this->route_type = $route->route_type ?? 'Scheduled';
        $this->selectedAircraftTypes = $route->aircraftTypes->pluck('id')->toArray();
        $this->editMode = true;
        $this->showAddModal = true;
    }

    public function deleteRoute($id)
    {
        Route::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
    }

    public function saveRoute()
    {
        $this->validate();

        \App\Models\Airport::fetchAndCreate($this->departure_icao);
        \App\Models\Airport::fetchAndCreate($this->arrival_icao);

        if ($this->editMode) {
            $route = Route::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->editingId);
            $route->update([
                'flight_number' => $this->flight_number,
                'departure_icao' => strtoupper($this->departure_icao),
                'arrival_icao' => strtoupper($this->arrival_icao),
                'block_time' => $this->block_time,
                'route_string' => $this->route_string,
                'route_type' => $this->route_type,
            ]);
            $route->aircraftTypes()->sync($this->selectedAircraftTypes);
        } else {
            $route = Route::create([
                'tenant_id' => auth()->user()->tenant_id,
                'flight_number' => $this->flight_number,
                'departure_icao' => strtoupper($this->departure_icao),
                'arrival_icao' => strtoupper($this->arrival_icao),
                'block_time' => $this->block_time,
                'route_string' => $this->route_string,
                'route_type' => $this->route_type,
            ]);
            $route->aircraftTypes()->sync($this->selectedAircraftTypes);
        }

        $this->reset(['flight_number', 'departure_icao', 'arrival_icao', 'block_time', 'route_string', 'route_type', 'selectedAircraftTypes', 'showAddModal', 'editMode', 'editingId']);
    }

    public function importCsv()
    {
        $this->validate([
            'csvFile' => 'required|mimes:csv,txt|max:2048',
        ]);

        if (($handle = fopen($this->csvFile->getRealPath(), "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if(count($data) >= 4) {
                    \App\Models\Airport::fetchAndCreate($data[1]);
                    \App\Models\Airport::fetchAndCreate($data[2]);

                    Route::updateOrCreate(
                        ['tenant_id' => auth()->user()->tenant_id, 'flight_number' => $data[0]],
                        [
                            'departure_icao' => strtoupper($data[1]),
                            'arrival_icao' => strtoupper($data[2]),
                            'block_time' => $data[3],
                            'route_type' => isset($data[4]) ? $data[4] : 'Scheduled',
                        ]
                    );
                }
            }
            fclose($handle);
        }

        $this->reset('csvFile');
        session()->flash('message', 'Routes imported successfully.');
    }

    public function downloadTemplate()
    {
        $content = "flight_number,departure_icao,arrival_icao,block_time,route_type\nEZY123,EGLL,LFPG,01:30,Scheduled\n";
        return response()->streamDownload(function() use ($content) {
            echo $content;
        }, 'routes_template.csv');
    }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;
        $routes = Route::with('aircraftTypes')->where('tenant_id', $tenantId)->get();
        $aircraftTypes = AircraftType::where('tenant_id', $tenantId)->get();

        return view('livewire.route-manager', [
            'routes' => $routes,
            'aircraftTypes' => $aircraftTypes,
        ])->layout('layouts.app');
    }
}
