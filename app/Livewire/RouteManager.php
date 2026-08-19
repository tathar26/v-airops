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
    public $callsign_icao = '';
    public $callsign_suffix = '';
    public $departure_icao = '';
    public $arrival_icao = '';
    public $block_time = '';
    public $distance = '';
    public $route_string = '';
    public $route_type = 'Scheduled';
    public $selectedAircraftTypes = [];

    public $csvFile;

    protected function rules(): array
    {
        return [
            'flight_number' => 'required|string|max:12',
            'callsign_icao' => 'required|string|min:2|max:4|alpha',
            'callsign_suffix' => 'required|string|max:8',
            'departure_icao' => 'required|string|size:4',
            'arrival_icao' => 'required|string|size:4',
            'block_time' => 'nullable|string|max:10', // e.g., '02:30'
            'distance' => 'nullable|numeric',
            'route_string' => 'nullable|string|max:255',
            'route_type' => 'required|string|in:Scheduled,Charter,Cargo',
            'selectedAircraftTypes' => 'array',
            'selectedAircraftTypes.*' => 'exists:aircraft_types,id',
        ];
    }

    public function openAddModal()
    {
        $this->reset(['flight_number', 'callsign_suffix', 'departure_icao', 'arrival_icao', 'block_time', 'distance', 'route_string', 'route_type', 'selectedAircraftTypes', 'editingId', 'editMode', 'csvFile']);
        
        $tenant = auth()->user()->tenant;
        $this->callsign_icao = $tenant->icao ?? 'VOPS';
        $this->showAddModal = true;
    }

    public function editRoute($id)
    {
        $route = Route::with('aircraftTypes')->where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $tenant = auth()->user()->tenant;
        $availableIcaos = $tenant ? $tenant->getAllIcaos() : [];

        $this->editingId = $route->id;
        $this->flight_number = $route->flight_number;
        $this->departure_icao = $route->departure_icao;
        $this->arrival_icao = $route->arrival_icao;
        $this->block_time = $route->block_time;
        $this->distance = $route->distance;
        $this->route_string = $route->route_string;
        $this->route_type = $route->route_type ?? 'Scheduled';
        $this->selectedAircraftTypes = $route->aircraftTypes->pluck('id')->toArray();

        // Determine Callsign ICAO prefix and Callsign Suffix
        $this->callsign_icao = $route->operator ?: ($tenant->icao ?? 'VOPS');
        $this->callsign_suffix = '';

        if ($route->callsign) {
            $rawCs = strtoupper(trim($route->callsign));
            $matched = false;
            foreach ($availableIcaos as $icaoOption) {
                if (str_starts_with($rawCs, $icaoOption)) {
                    $this->callsign_icao = $icaoOption;
                    $this->callsign_suffix = substr($rawCs, strlen($icaoOption));
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                if ($this->callsign_icao && str_starts_with($rawCs, $this->callsign_icao)) {
                    $this->callsign_suffix = substr($rawCs, strlen($this->callsign_icao));
                } else {
                    $this->callsign_suffix = $rawCs;
                }
            }
        } else {
            $this->callsign_suffix = preg_replace('/^[A-Z]{2,4}/', '', $route->flight_number) ?: $route->flight_number;
        }

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

        $depAirport = \App\Models\Airport::fetchAndCreate($this->departure_icao);
        $arrAirport = \App\Models\Airport::fetchAndCreate($this->arrival_icao);

        if (empty($this->distance) && $depAirport && $arrAirport) {
            // Haversine formula in NM
            $earth_radius = 3440.065;
            $lat1 = deg2rad($depAirport->lat);
            $lon1 = deg2rad($depAirport->lon);
            $lat2 = deg2rad($arrAirport->lat);
            $lon2 = deg2rad($arrAirport->lon);
            $dLat = $lat2 - $lat1;
            $dLon = $lon2 - $lon1;
            $a = sin($dLat/2) * sin($dLat/2) + cos($lat1) * cos($lat2) * sin($dLon/2) * sin($dLon/2);
            $c = 2 * asin(sqrt($a));
            $this->distance = round($earth_radius * $c);
        }

        if (empty($this->block_time) && !empty($this->distance)) {
            // Rough estimate: 420 kts + 30 mins taxi
            $total_minutes = round(($this->distance / 420) * 60 + 30);
            $hours = floor($total_minutes / 60);
            $minutes = $total_minutes % 60;
            $this->block_time = sprintf('%02d:%02d', $hours, $minutes);
        }

        $cleanIcao = strtoupper(trim($this->callsign_icao));
        $cleanSuffix = strtoupper(trim($this->callsign_suffix));
        $fullCallsign = $cleanIcao . $cleanSuffix;

        $routePayload = [
            'flight_number' => strtoupper(trim($this->flight_number)),
            'callsign' => $fullCallsign,
            'operator' => $cleanIcao,
            'departure_icao' => strtoupper($this->departure_icao),
            'arrival_icao' => strtoupper($this->arrival_icao),
            'block_time' => $this->block_time,
            'distance' => $this->distance,
            'route_string' => $this->route_string,
            'route_type' => $this->route_type,
        ];

        if ($this->editMode) {
            $route = Route::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->editingId);
            $route->update($routePayload);
            $route->aircraftTypes()->sync($this->selectedAircraftTypes);
        } else {
            $routePayload['tenant_id'] = auth()->user()->tenant_id;
            $route = Route::create($routePayload);
            $route->aircraftTypes()->sync($this->selectedAircraftTypes);
        }

        $this->reset(['flight_number', 'callsign_icao', 'callsign_suffix', 'departure_icao', 'arrival_icao', 'block_time', 'distance', 'route_string', 'route_type', 'selectedAircraftTypes', 'showAddModal', 'editMode', 'editingId']);
    }

    public function importCsv()
    {
        $this->validate([
            'csvFile' => 'required|mimes:csv,txt|max:2048',
        ]);

        $tenant = auth()->user()->tenant;
        $defaultIcao = $tenant->icao ?? 'VOPS';

        if (($handle = fopen($this->csvFile->getRealPath(), "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 4) {
                    \App\Models\Airport::fetchAndCreate($data[1]);
                    \App\Models\Airport::fetchAndCreate($data[2]);

                    $fltNum = strtoupper(trim($data[0]));
                    $callsign = isset($data[5]) ? strtoupper(trim($data[5])) : $fltNum;
                    $operator = isset($data[6]) ? strtoupper(trim($data[6])) : $defaultIcao;

                    Route::updateOrCreate(
                        ['tenant_id' => auth()->user()->tenant_id, 'flight_number' => $fltNum],
                        [
                            'callsign' => $callsign,
                            'operator' => $operator,
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
        $content = "flight_number,departure_icao,arrival_icao,block_time,route_type,callsign,operator\nU28161,EGLL,LFPG,01:30,Scheduled,EZY8161,EZY\n";
        return response()->streamDownload(function() use ($content) {
            echo $content;
        }, 'routes_template.csv');
    }

    public function render()
    {
        $tenant = auth()->user()->tenant;
        $tenantId = auth()->user()->tenant_id;
        $routes = Route::with('aircraftTypes')->where('tenant_id', $tenantId)->get();
        $aircraftTypes = AircraftType::where('tenant_id', $tenantId)->get();
        $availableIcaos = $tenant ? $tenant->getAllIcaos() : ['VOPS'];

        return view('livewire.route-manager', [
            'routes' => $routes,
            'aircraftTypes' => $aircraftTypes,
            'availableIcaos' => $availableIcaos,
        ])->layout('layouts.app');
    }
}
