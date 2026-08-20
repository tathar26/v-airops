<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Route;
use App\Models\AircraftType;
use Livewire\WithFileUploads;

class RouteManager extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';
    public $selectedRouteType = '';
    public $filterDepIcao = '';
    public $filterArrIcao = '';
    public $filterAircraftType = '';
    public $perPage = 25;

    public $selectedRoutes = [];
    public $selectAll = false;

    public $showMassUpdateModal = false;
    public $massTargetIcao = '';
    public $massStripPrefix = '';
    public $massSelectedAircraftTypes = [];

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

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedRouteType' => ['except' => ''],
        'filterDepIcao' => ['except' => ''],
        'filterArrIcao' => ['except' => ''],
        'filterAircraftType' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedRouteType()
    {
        $this->resetPage();
    }

    public function updatingFilterDepIcao()
    {
        $this->resetPage();
    }

    public function updatingFilterArrIcao()
    {
        $this->resetPage();
    }

    public function updatingFilterAircraftType()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'selectedRouteType', 'filterDepIcao', 'filterArrIcao', 'filterAircraftType']);
        $this->resetPage();
    }

    protected function getActiveTenantId(): int
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        if (!$tenantId) {
            $tenantId = \App\Models\Tenant::first()?->id ?? 1;
        }
        return (int) $tenantId;
    }

    protected function buildQuery(int $tenantId)
    {
        $query = Route::with('aircraftTypes')->where('tenant_id', $tenantId);

        if (!empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('flight_number', 'like', "%{$s}%")
                  ->orWhere('callsign', 'like', "%{$s}%")
                  ->orWhere('callsign_icao', 'like', "%{$s}%")
                  ->orWhere('operator', 'like', "%{$s}%")
                  ->orWhere('departure_icao', 'like', "%{$s}%")
                  ->orWhere('arrival_icao', 'like', "%{$s}%")
                  ->orWhere('route_type', 'like', "%{$s}%");
            });
        }

        if (!empty($this->selectedRouteType)) {
            $query->where('route_type', $this->selectedRouteType);
        }

        if (!empty($this->filterDepIcao)) {
            $query->where('departure_icao', strtoupper(trim($this->filterDepIcao)));
        }

        if (!empty($this->filterArrIcao)) {
            $query->where('arrival_icao', strtoupper(trim($this->filterArrIcao)));
        }

        if (!empty($this->filterAircraftType)) {
            $typeId = $this->filterAircraftType;
            $query->whereHas('aircraftTypes', function($aq) use ($typeId) {
                $aq->where('aircraft_types.id', $typeId);
            });
        }

        return $query;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $tenantId = $this->getActiveTenantId();
            $this->selectedRoutes = $this->buildQuery($tenantId)->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedRoutes = [];
        }
    }

    public function openMassUpdateModal()
    {
        if (empty($this->selectedRoutes)) {
            session()->flash('error', 'Please select at least one route to mass update.');
            return;
        }

        $tenantId = $this->getActiveTenantId();
        $tenant = \App\Models\Tenant::find($tenantId);
        $this->massTargetIcao = $tenant && !empty($tenant->icao) ? strtoupper($tenant->icao) : 'VOPS';
        $this->massStripPrefix = '';
        $this->massSelectedAircraftTypes = [];
        $this->showMassUpdateModal = true;
    }

    public function applyMassUpdate()
    {
        if (empty($this->selectedRoutes)) {
            return;
        }

        $tenantId = $this->getActiveTenantId();
        $routes = Route::where('tenant_id', $tenantId)->whereIn('id', $this->selectedRoutes)->get();
        $updatedCount = 0;

        foreach ($routes as $route) {
            $flightNum = strtoupper(trim($route->flight_number));
            $suffix = $flightNum;

            if (!empty($this->massStripPrefix)) {
                $pfx = strtoupper(trim($this->massStripPrefix));
                if (str_starts_with($flightNum, $pfx)) {
                    $suffix = substr($flightNum, strlen($pfx));
                }
            } else {
                $stripped = preg_replace('/^[A-Z0-9]{2,3}/', '', $flightNum);
                $suffix = !empty($stripped) ? $stripped : $flightNum;
            }

            $callsignIcao = strtoupper(trim($this->massTargetIcao));
            $fullCallsign = $callsignIcao . $suffix;

            $route->update([
                'callsign_icao'   => $callsignIcao,
                'callsign_suffix' => $suffix,
                'callsign'        => $fullCallsign,
                'operator'        => $callsignIcao,
            ]);

            if (!empty($this->massSelectedAircraftTypes)) {
                $route->aircraftTypes()->syncWithoutDetaching($this->massSelectedAircraftTypes);
            }

            $updatedCount++;
        }

        $this->showMassUpdateModal = false;
        $this->selectedRoutes = [];
        $this->selectAll = false;
        session()->flash('message', "Successfully updated {$updatedCount} route(s).");
    }

    public function massDelete()
    {
        if (empty($this->selectedRoutes)) {
            return;
        }

        $tenantId = $this->getActiveTenantId();
        $count = count($this->selectedRoutes);
        Route::where('tenant_id', $tenantId)->whereIn('id', $this->selectedRoutes)->delete();

        $this->selectedRoutes = [];
        $this->selectAll = false;
        session()->flash('message', "Successfully deleted {$count} route(s).");
    }

    protected function rules(): array
    {
        return [
            'flight_number' => 'required|string|max:12',
            'callsign_icao' => 'required|string|min:2|max:4|alpha',
            'callsign_suffix' => 'required|string|max:8',
            'departure_icao' => 'required|string|size:4',
            'arrival_icao' => 'required|string|size:4',
            'block_time' => 'nullable|string|max:10',
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
        
        $tenantId = $this->getActiveTenantId();
        $tenant = \App\Models\Tenant::find($tenantId);
        $this->callsign_icao = $tenant->icao ?? 'VOPS';
        $this->showAddModal = true;
    }

    public function editRoute($id)
    {
        $tenantId = $this->getActiveTenantId();
        $route = Route::with('aircraftTypes')->where('tenant_id', $tenantId)->findOrFail($id);
        $tenant = \App\Models\Tenant::find($tenantId);
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
        $this->callsign_icao = $route->callsign_icao ?: ($route->operator ?: ($tenant->icao ?? 'VOPS'));
        $this->callsign_suffix = $route->callsign_suffix ?: '';

        if (empty($this->callsign_suffix)) {
            if ($route->callsign) {
                $rawCs = strtoupper(trim($route->callsign));
                if (str_starts_with($rawCs, $this->callsign_icao)) {
                    $this->callsign_suffix = substr($rawCs, strlen($this->callsign_icao));
                } else {
                    $this->callsign_suffix = $rawCs;
                }
            } else {
                $this->callsign_suffix = preg_replace('/^[A-Z0-9]{2,3}/', '', $route->flight_number) ?: $route->flight_number;
            }
        }

        $this->editMode = true;
        $this->showAddModal = true;
    }

    public function deleteRoute($id)
    {
        $tenantId = $this->getActiveTenantId();
        Route::where('tenant_id', $tenantId)->findOrFail($id)->delete();
        session()->flash('message', 'Route deleted successfully.');
    }

    public function saveRoute()
    {
        $this->validate();
        $tenantId = $this->getActiveTenantId();

        $depAirport = \App\Models\Airport::fetchAndCreate($this->departure_icao);
        $arrAirport = \App\Models\Airport::fetchAndCreate($this->arrival_icao);

        if (empty($this->distance) && $depAirport && $arrAirport) {
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
            $total_minutes = round(($this->distance / 420) * 60 + 30);
            $hours = floor($total_minutes / 60);
            $minutes = $total_minutes % 60;
            $this->block_time = sprintf('%02d:%02d', $hours, $minutes);
        }

        $cleanIcao = strtoupper(trim($this->callsign_icao));
        $cleanSuffix = strtoupper(trim($this->callsign_suffix));
        $fullCallsign = $cleanIcao . $cleanSuffix;

        $routePayload = [
            'flight_number'   => strtoupper(trim($this->flight_number)),
            'callsign'        => $fullCallsign,
            'callsign_icao'   => $cleanIcao,
            'callsign_suffix' => $cleanSuffix,
            'operator'        => $cleanIcao,
            'departure_icao'  => strtoupper($this->departure_icao),
            'arrival_icao'    => strtoupper($this->arrival_icao),
            'block_time'      => $this->block_time,
            'distance'        => $this->distance,
            'route_string'    => $this->route_string,
            'route_type'      => $this->route_type,
        ];

        if ($this->editMode) {
            $route = Route::where('tenant_id', $tenantId)->findOrFail($this->editingId);
            $route->update($routePayload);
            $route->aircraftTypes()->sync($this->selectedAircraftTypes);
            session()->flash('message', 'Route updated successfully.');
        } else {
            $routePayload['tenant_id'] = $tenantId;
            $route = Route::create($routePayload);
            $route->aircraftTypes()->sync($this->selectedAircraftTypes);
            session()->flash('message', 'Route created successfully.');
        }

        $this->reset(['flight_number', 'callsign_icao', 'callsign_suffix', 'departure_icao', 'arrival_icao', 'block_time', 'distance', 'route_string', 'route_type', 'selectedAircraftTypes', 'showAddModal', 'editMode', 'editingId']);
    }

    public function importCsv()
    {
        $this->validate([
            'csvFile' => 'required|mimes:csv,txt|max:2048',
        ]);

        $tenantId = $this->getActiveTenantId();
        $tenant = \App\Models\Tenant::find($tenantId);
        $defaultIcao = $tenant && !empty($tenant->icao) ? strtoupper($tenant->icao) : 'VOPS';
        $importedCount = 0;

        if (($handle = fopen($this->csvFile->getRealPath(), "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            
            // Default column index positions
            $idx = [
                'flight_number'   => 0,
                'departure_icao'  => 1,
                'arrival_icao'    => 2,
                'block_time'      => 3,
                'route_type'      => 4,
                'callsign'        => 5,
                'callsign_icao'   => null,
                'callsign_suffix' => null,
                'aircraft_types'  => null,
                'route_string'    => null,
                'distance'        => null,
            ];

            if ($header) {
                $lowerHeader = array_map(fn($h) => strtolower(trim($h)), $header);
                foreach ($lowerHeader as $i => $col) {
                    if (in_array($col, ['flight_number', 'flight_num', 'flight', 'flightno'])) $idx['flight_number'] = $i;
                    if (in_array($col, ['departure_icao', 'departure', 'origin', 'dep', 'dep_icao'])) $idx['departure_icao'] = $i;
                    if (in_array($col, ['arrival_icao', 'arrival', 'destination', 'arr', 'arr_icao'])) $idx['arrival_icao'] = $i;
                    if (in_array($col, ['block_time', 'time', 'duration', 'flight_time'])) $idx['block_time'] = $i;
                    if (in_array($col, ['route_type', 'type'])) $idx['route_type'] = $i;
                    if (in_array($col, ['callsign', 'atc_callsign', 'telephony'])) $idx['callsign'] = $i;
                    if (in_array($col, ['callsign_icao', 'airline_icao', 'icao_prefix', 'operator'])) $idx['callsign_icao'] = $i;
                    if (in_array($col, ['callsign_suffix', 'suffix', 'flight_suffix'])) $idx['callsign_suffix'] = $i;
                    if (in_array($col, ['aircraft_types', 'aircraft', 'fleet', 'aircraft_type'])) $idx['aircraft_types'] = $i;
                    if (in_array($col, ['route_string', 'route', 'routing'])) $idx['route_string'] = $i;
                    if (in_array($col, ['distance', 'dist', 'nm'])) $idx['distance'] = $i;
                }
            }

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty($data) || !isset($data[$idx['flight_number']]) || empty(trim($data[$idx['flight_number']]))) {
                    continue;
                }

                $fltNum   = strtoupper(trim($data[$idx['flight_number']]));
                $depIcao  = isset($data[$idx['departure_icao']]) ? strtoupper(trim($data[$idx['departure_icao']])) : '';
                $arrIcao  = isset($data[$idx['arrival_icao']]) ? strtoupper(trim($data[$idx['arrival_icao']])) : '';

                if (strlen($depIcao) < 3 || strlen($arrIcao) < 3) {
                    continue;
                }

                $depAirport = \App\Models\Airport::fetchAndCreate($depIcao);
                $arrAirport = \App\Models\Airport::fetchAndCreate($arrIcao);

                // Distance calculation
                $distance = ($idx['distance'] !== null && isset($data[$idx['distance']]) && is_numeric($data[$idx['distance']])) 
                    ? (int)$data[$idx['distance']] 
                    : null;

                if (!$distance && $depAirport && $arrAirport) {
                    $earth_radius = 3440.065;
                    $lat1 = deg2rad($depAirport->lat);
                    $lon1 = deg2rad($depAirport->lon);
                    $lat2 = deg2rad($arrAirport->lat);
                    $lon2 = deg2rad($arrAirport->lon);
                    $dLat = $lat2 - $lat1;
                    $dLon = $lon2 - $lon1;
                    $a = sin($dLat/2) * sin($dLat/2) + cos($lat1) * cos($lat2) * sin($dLon/2) * sin($dLon/2);
                    $c = 2 * asin(sqrt($a));
                    $distance = (int) round($earth_radius * $c);
                }

                // Block time calculation
                $blockTime = ($idx['block_time'] !== null && isset($data[$idx['block_time']]) && !empty(trim($data[$idx['block_time']]))) 
                    ? trim($data[$idx['block_time']]) 
                    : null;

                if (!$blockTime && $distance) {
                    $total_minutes = round(($distance / 420) * 60 + 30);
                    $hours = floor($total_minutes / 60);
                    $minutes = $total_minutes % 60;
                    $blockTime = sprintf('%02d:%02d', $hours, $minutes);
                }

                $routeType = ($idx['route_type'] !== null && isset($data[$idx['route_type']]) && !empty(trim($data[$idx['route_type']]))) 
                    ? ucfirst(strtolower(trim($data[$idx['route_type']]))) 
                    : 'Scheduled';

                if (!in_array($routeType, ['Scheduled', 'Charter', 'Cargo'])) {
                    $routeType = 'Scheduled';
                }

                $routeString = ($idx['route_string'] !== null && isset($data[$idx['route_string']])) 
                    ? trim($data[$idx['route_string']]) 
                    : null;

                // Callsign components
                $rawCallsign = ($idx['callsign'] !== null && isset($data[$idx['callsign']]) && !empty(trim($data[$idx['callsign']]))) 
                    ? strtoupper(trim($data[$idx['callsign']])) 
                    : null;

                $callsignIcao = ($idx['callsign_icao'] !== null && isset($data[$idx['callsign_icao']]) && !empty(trim($data[$idx['callsign_icao']]))) 
                    ? strtoupper(trim($data[$idx['callsign_icao']])) 
                    : null;

                $callsignSuffix = ($idx['callsign_suffix'] !== null && isset($data[$idx['callsign_suffix']]) && !empty(trim($data[$idx['callsign_suffix']]))) 
                    ? strtoupper(trim($data[$idx['callsign_suffix']])) 
                    : null;

                if (!$callsignIcao) {
                    $callsignIcao = $defaultIcao;
                }

                if (!$callsignSuffix) {
                    if ($rawCallsign) {
                        if (str_starts_with($rawCallsign, $callsignIcao)) {
                            $callsignSuffix = substr($rawCallsign, strlen($callsignIcao));
                        } else {
                            $callsignSuffix = $rawCallsign;
                        }
                    } else {
                        // Strip leading letters if any
                        $stripped = preg_replace('/^[A-Z0-9]{2,3}/', '', $fltNum);
                        $callsignSuffix = !empty($stripped) ? $stripped : $fltNum;
                    }
                }

                $fullCallsign = $rawCallsign ?: ($callsignIcao . $callsignSuffix);

                $route = Route::updateOrCreate(
                    [
                        'tenant_id'     => $tenantId, 
                        'flight_number' => $fltNum
                    ],
                    [
                        'callsign'        => $fullCallsign,
                        'callsign_icao'   => $callsignIcao,
                        'callsign_suffix' => $callsignSuffix,
                        'operator'        => $callsignIcao,
                        'departure_icao'  => $depIcao,
                        'arrival_icao'    => $arrIcao,
                        'block_time'      => $blockTime ?? '02:00',
                        'route_type'      => $routeType,
                        'distance'        => $distance,
                        'route_string'    => $routeString,
                    ]
                );

                // Aircraft Types syncing
                if ($idx['aircraft_types'] !== null && isset($data[$idx['aircraft_types']]) && !empty(trim($data[$idx['aircraft_types']]))) {
                    $typeCodes = preg_split('/[,|\/]+/', trim($data[$idx['aircraft_types']]));
                    $aircraftIds = [];
                    foreach ($typeCodes as $tCode) {
                        $tCode = strtoupper(trim($tCode));
                        if (!empty($tCode)) {
                            $acType = AircraftType::firstOrCreate(
                                ['tenant_id' => $tenantId, 'code' => $tCode],
                                ['name' => $tCode . ' Aircraft']
                            );
                            $aircraftIds[] = $acType->id;
                        }
                    }
                    if (!empty($aircraftIds)) {
                        $route->aircraftTypes()->syncWithoutDetaching($aircraftIds);
                    }
                }

                $importedCount++;
            }
            fclose($handle);
        }

        $this->reset('csvFile');
        session()->flash('message', "Successfully imported/updated {$importedCount} route(s) in your network.");
    }

    public function downloadTemplate()
    {
        $content = "flight_number,departure_icao,arrival_icao,block_time,route_type,callsign_icao,callsign_suffix,callsign,aircraft_types,route_string\n" .
                   "U28161,EGLL,LFPG,01:30,Scheduled,EZY,8161,EZY8161,A320,DCT BOVIS L608 DET L608\n" .
                   "U22141,EGKK,LGAV,03:45,Scheduled,EZY,2141,EZY2141,A320|A321,DVR UL9 KONAN\n" .
                   "U21928,LFMT,EGKK,01:55,Scheduled,EZY,1928,EZY1928,A319,\n" .
                   "U23314,LSGG,LEMD,02:05,Scheduled,EZS,3314,EZS3314,A320,\n";

        return response()->streamDownload(function() use ($content) {
            echo $content;
        }, 'routes_template.csv');
    }

    public function render()
    {
        $tenantId = $this->getActiveTenantId();
        $tenant = \App\Models\Tenant::find($tenantId);
        
        $query = $this->buildQuery($tenantId);

        $routes = $query->orderBy('flight_number', 'asc')->paginate($this->perPage);
        $totalRoutesCount = Route::where('tenant_id', $tenantId)->count();
        $aircraftTypes = AircraftType::where('tenant_id', $tenantId)->orderBy('code')->get();
        $availableIcaos = $tenant ? $tenant->getAllIcaos() : ['VOPS'];

        // Distinct departures and arrivals for dropdown filters
        $allDepIcaos = Route::where('tenant_id', $tenantId)->distinct()->orderBy('departure_icao')->pluck('departure_icao');
        $allArrIcaos = Route::where('tenant_id', $tenantId)->distinct()->orderBy('arrival_icao')->pluck('arrival_icao');

        // Autocomplete suggestions list
        $autocompleteRoutes = Route::where('tenant_id', $tenantId)->select('flight_number', 'callsign', 'callsign_icao', 'departure_icao', 'arrival_icao')->get();
        $autocompleteList = $autocompleteRoutes->pluck('flight_number')
            ->merge($autocompleteRoutes->pluck('callsign'))
            ->merge($autocompleteRoutes->pluck('callsign_icao'))
            ->merge($allDepIcaos)
            ->merge($allArrIcaos)
            ->merge($aircraftTypes->pluck('code'))
            ->filter()
            ->unique()
            ->values()
            ->take(80)
            ->toArray();

        return view('livewire.route-manager', [
            'routes' => $routes,
            'totalRoutesCount' => $totalRoutesCount,
            'aircraftTypes' => $aircraftTypes,
            'availableIcaos' => $availableIcaos,
            'allDepIcaos' => $allDepIcaos,
            'allArrIcaos' => $allArrIcaos,
            'autocompleteList' => $autocompleteList,
        ])->layout('layouts.app');
    }
}
