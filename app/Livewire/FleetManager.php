<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Airframe;
use App\Models\AircraftType;
use App\Services\ScheduleImportService;
use Livewire\WithFileUploads;

class FleetManager extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';
    public $filterAircraftType = '';
    public $perPage = 25;

    public $showAddModal = false;
    public $editMode = false;
    public $editingId = null;

    public $registration = '';
    public $aircraft_type_id = '';
    public $name = '';

    public $csvFile;

    // Global import modal state
    public $showGlobalImportModal = false;
    public $importMode = 'api';
    public $filterAircraftCode = '';
    public $searchRealWorld = '';
    public $selectedRealWorldAirframes = [];
    public $selectAllRealWorld = false;

    // Live Fleet API state
    public $apiOperatorIcao = '';
    public $apiFleetResults = [];
    public $selectedApiAirframes = [];
    public $selectAllApiAirframes = false;
    public $searchApiFleet = '';
    public $apiErrorMessage = null;
    public $apiSuccessMessage = null;
    public $apiLimit = 500;

    public $globalAircraftCode = '';
    public $quantityToGenerate = 1;
    public $registrationPrefix = 'G-';
    public $customRegistrationsText = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterAircraftType' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterAircraftType()
    {
        $this->resetPage();
    }

    public function updatingSearchRealWorld()
    {
        $this->resetPage('realWorldPage');
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterAircraftType']);
        $this->resetPage();
    }

    protected $rules = [
        'registration' => 'required|string|max:10',
        'aircraft_type_id' => 'required|exists:aircraft_types,id',
        'name' => 'nullable|string|max:50',
    ];

    public function openAddModal()
    {
        $this->reset(['registration', 'aircraft_type_id', 'name', 'editingId', 'editMode', 'csvFile']);
        $this->showAddModal = true;
    }

    protected function getActiveTenantId(): int
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        if (!$tenantId) {
            $tenantId = \App\Models\Tenant::first()?->id ?? 1;
        }
        return (int) $tenantId;
    }

    public function editAirframe($id)
    {
        $tenantId = $this->getActiveTenantId();
        $airframe = Airframe::where('tenant_id', $tenantId)->findOrFail($id);
        $this->editingId = $airframe->id;
        $this->registration = $airframe->registration;
        $this->aircraft_type_id = $airframe->aircraft_type_id;
        $this->name = $airframe->name;
        $this->editMode = true;
        $this->showAddModal = true;
    }

    public function deleteAirframe($id)
    {
        $tenantId = $this->getActiveTenantId();
        Airframe::where('tenant_id', $tenantId)->findOrFail($id)->delete();
    }

    public function saveAirframe()
    {
        $this->validate();
        $tenantId = $this->getActiveTenantId();

        if ($this->editMode) {
            $airframe = Airframe::where('tenant_id', $tenantId)->findOrFail($this->editingId);
            $airframe->update([
                'aircraft_type_id' => $this->aircraft_type_id,
                'registration' => $this->registration,
                'name' => $this->name,
            ]);
        } else {
            Airframe::create([
                'tenant_id' => $tenantId,
                'aircraft_type_id' => $this->aircraft_type_id,
                'registration' => $this->registration,
                'name' => $this->name,
            ]);
        }

        $this->reset(['registration', 'aircraft_type_id', 'name', 'showAddModal', 'editMode', 'editingId']);
    }

    public function importCsv()
    {
        $this->validate([
            'csvFile' => 'required|mimes:csv,txt|max:2048',
        ]);

        $tenantId = $this->getActiveTenantId();
        $importedCount = 0;

        if (($handle = fopen($this->csvFile->getRealPath(), "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            
            // Map header positions if named headers exist
            $regIdx = 0;
            $typeIdx = 1;
            $nameIdx = 2;

            if ($header) {
                $lowerHeader = array_map(fn($h) => strtolower(trim($h)), $header);
                foreach ($lowerHeader as $i => $col) {
                    if (in_array($col, ['registration', 'reg', 'tail_number'])) $regIdx = $i;
                    if (in_array($col, ['aircraft_type', 'type', 'aircraft_type_id', 'icao', 'icao_code'])) $typeIdx = $i;
                    if (in_array($col, ['name', 'nickname', 'operator'])) $nameIdx = $i;
                }
            }

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty($data) || !isset($data[$regIdx]) || empty(trim($data[$regIdx]))) {
                    continue;
                }

                $reg = strtoupper(trim($data[$regIdx]));
                $typeRaw = isset($data[$typeIdx]) ? trim($data[$typeIdx]) : '';
                $name = isset($data[$nameIdx]) ? trim($data[$nameIdx]) : '';

                if (empty($typeRaw)) {
                    continue;
                }

                $aircraftTypeId = null;

                if (is_numeric($typeRaw)) {
                    $existingType = AircraftType::where('tenant_id', $tenantId)->find((int)$typeRaw);
                    if ($existingType) {
                        $aircraftTypeId = $existingType->id;
                    }
                }

                if (!$aircraftTypeId) {
                    $typeCode = strtoupper($typeRaw);
                    $aircraftType = AircraftType::firstOrCreate(
                        ['tenant_id' => $tenantId, 'code' => $typeCode],
                        ['name' => $typeCode . ' Aircraft']
                    );
                    $aircraftTypeId = $aircraftType->id;
                }

                Airframe::updateOrCreate(
                    ['tenant_id' => $tenantId, 'registration' => $reg],
                    [
                        'aircraft_type_id' => $aircraftTypeId,
                        'name'             => $name ?: ($reg . ' Airframe'),
                    ]
                );

                $importedCount++;
            }
            fclose($handle);
        }

        $this->reset('csvFile');
        session()->flash('message', "Successfully imported/updated {$importedCount} airframe(s) in your fleet.");
    }

    public function downloadTemplate()
    {
        $content = "registration,aircraft_type,name\n" .
                   "G-EZYM,A320,Spirit of easyJet\n" .
                   "G-EZTA,A320,Pride of the Fleet\n" .
                   "G-EZAO,A319,City of London\n" .
                   "OE-LKH,A320,Vienna Explorer\n" .
                   "HB-JYA,A320,Geneva Jet\n";

        return response()->streamDownload(function() use ($content) {
            echo $content;
        }, 'fleet_template.csv');
    }

    public function openGlobalImportModal()
    {
        $tenantId = $this->getActiveTenantId();
        $tenant = \App\Models\Tenant::find($tenantId);
        $defaultIcao = $tenant && !empty($tenant->icao) ? strtoupper($tenant->icao) : '';

        $this->reset([
            'importMode',
            'searchRealWorld',
            'filterAircraftCode',
            'selectedRealWorldAirframes',
            'selectAllRealWorld',
            'globalAircraftCode',
            'registrationPrefix',
            'quantityToGenerate',
            'customRegistrationsText',
            'apiFleetResults',
            'selectedApiAirframes',
            'selectAllApiAirframes',
            'searchApiFleet',
            'apiErrorMessage',
            'apiSuccessMessage',
        ]);

        $this->apiOperatorIcao = $defaultIcao;
        $this->importMode = 'api';
        $this->registrationPrefix = 'G-';
        $this->quantityToGenerate = 5;
        $this->showGlobalImportModal = true;
    }

    public function fetchApiFleet()
    {
        $this->apiErrorMessage = null;
        $this->apiSuccessMessage = null;
        $this->selectedApiAirframes = [];
        $this->selectAllApiAirframes = false;

        $operator = strtoupper(trim($this->apiOperatorIcao));
        if (empty($operator)) {
            $this->apiErrorMessage = 'Please enter an airline / operator ICAO code (e.g. DLH, KLM, BAW).';
            return;
        }

        try {
            $service = app(ScheduleImportService::class);
            $limit = max(1, min(5000, (int) $this->apiLimit));
            $results = $service->getFleetByOperator($operator, $limit);

            if (empty($results)) {
                $this->apiFleetResults = [];
                $this->apiErrorMessage = "No aircraft found in the fleet database for operator '{$operator}'.";
                return;
            }

            foreach ($results as &$item) {
                $item['resolved_typecode'] = $service->resolveAircraftTypeCode(
                    $item['typecode'] ?? null,
                    $item['model'] ?? null,
                    $item['manufacturername'] ?? null
                );
            }
            unset($item);

            $this->apiFleetResults = $results;
            $this->apiSuccessMessage = "Discovered " . count($results) . " aircraft for operator '{$operator}'.";
        } catch (\Throwable $e) {
            $this->apiFleetResults = [];
            $this->apiErrorMessage = 'Failed to fetch fleet from API: ' . $e->getMessage();
        }
    }

    public function getFilteredApiFleetProperty(): array
    {
        if (empty($this->apiFleetResults)) {
            return [];
        }

        $s = strtoupper(trim($this->searchApiFleet));
        if (empty($s)) {
            return $this->apiFleetResults;
        }

        return array_values(array_filter($this->apiFleetResults, function ($item) use ($s) {
            $reg = strtoupper($item['registration'] ?? '');
            $type = strtoupper($item['resolved_typecode'] ?? ($item['typecode'] ?? ''));
            $rawType = strtoupper($item['typecode'] ?? '');
            $model = strtoupper($item['model'] ?? '');
            $mfg = strtoupper($item['manufacturername'] ?? '');
            $hex = strtoupper($item['icao24'] ?? '');

            return str_contains($reg, $s) || str_contains($type, $s) || str_contains($rawType, $s) || str_contains($model, $s) || str_contains($mfg, $s) || str_contains($hex, $s);
        }));
    }

    public function updatedSelectAllApiAirframes($value)
    {
        if ($value) {
            $visibleRegistrations = collect($this->filtered_api_fleet)
                ->pluck('registration')
                ->filter()
                ->map(fn($r) => strtoupper($r))
                ->toArray();
            $this->selectedApiAirframes = array_values(array_unique(array_merge($this->selectedApiAirframes, $visibleRegistrations)));
        } else {
            $this->selectedApiAirframes = [];
        }
    }

    public function importSelectedApiAirframes()
    {
        if (empty($this->selectedApiAirframes)) {
            $this->apiErrorMessage = 'Please select at least one airframe to import.';
            return;
        }

        $tenantId = $this->getActiveTenantId();
        $selectedSet = array_flip(array_map('strtoupper', $this->selectedApiAirframes));

        $aircraftToImport = array_filter($this->apiFleetResults, function ($item) use ($selectedSet) {
            $reg = strtoupper(trim($item['registration'] ?? ''));
            return isset($selectedSet[$reg]);
        });

        if (empty($aircraftToImport)) {
            $this->apiErrorMessage = 'No valid matching airframes found to import.';
            return;
        }

        try {
            $service = app(ScheduleImportService::class);
            $result = $service->importAirframesToTenant($tenantId, $aircraftToImport);

            $this->reset([
                'showGlobalImportModal',
                'apiFleetResults',
                'selectedApiAirframes',
                'selectAllApiAirframes',
                'searchApiFleet',
                'apiErrorMessage',
                'apiSuccessMessage',
            ]);

            session()->flash('message', "Successfully imported {$result['airframes_imported']} airframe(s) into your fleet ({$result['aircraft_types_created']} new aircraft types created).");
        } catch (\Throwable $e) {
            $this->apiErrorMessage = 'Import failed: ' . $e->getMessage();
        }
    }

    public function importAllApiAirframes()
    {
        if (empty($this->apiFleetResults)) {
            $this->apiErrorMessage = 'No airframes available to import. Please fetch a fleet first.';
            return;
        }

        $tenantId = $this->getActiveTenantId();

        try {
            $service = app(ScheduleImportService::class);
            $result = $service->importAirframesToTenant($tenantId, $this->apiFleetResults);

            $this->reset([
                'showGlobalImportModal',
                'apiFleetResults',
                'selectedApiAirframes',
                'selectAllApiAirframes',
                'searchApiFleet',
                'apiErrorMessage',
                'apiSuccessMessage',
            ]);

            session()->flash('message', "Successfully imported all {$result['airframes_imported']} airframe(s) into your fleet ({$result['aircraft_types_created']} new aircraft types created).");
        } catch (\Throwable $e) {
            $this->apiErrorMessage = 'Import failed: ' . $e->getMessage();
        }
    }

    public function updatedSelectAllRealWorld($value)
    {
        if ($value) {
            $query = \App\Models\SystemGlobalAirframe::query();
            if ($this->filterAircraftCode) {
                $query->where('icao_code', $this->filterAircraftCode);
            }
            if ($this->searchRealWorld) {
                $query->where(function($q) {
                    $q->where('registration', 'like', '%' . $this->searchRealWorld . '%')
                      ->orWhere('operator', 'like', '%' . $this->searchRealWorld . '%')
                      ->orWhere('name', 'like', '%' . $this->searchRealWorld . '%');
                });
            }
            $this->selectedRealWorldAirframes = $query->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedRealWorldAirframes = [];
        }
    }

    public function importSelectedRealWorldAirframes()
    {
        if (empty($this->selectedRealWorldAirframes)) {
            session()->flash('error', 'Please select at least one real-world airframe to import.');
            return;
        }

        $tenantId = $this->getActiveTenantId();
        $realAirframes = \App\Models\SystemGlobalAirframe::whereIn('id', $this->selectedRealWorldAirframes)->get();
        $importedCount = 0;

        foreach ($realAirframes as $globalAirframe) {
            // Create or get local AircraftType based on ICAO code
            $code = strtoupper($globalAirframe->icao_code);
            $typeName = $globalAirframe->name ?: ($code . ' Aircraft');

            $aircraftType = AircraftType::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                ['name' => $typeName]
            );

            Airframe::updateOrCreate(
                ['tenant_id' => $tenantId, 'registration' => strtoupper($globalAirframe->registration)],
                ['aircraft_type_id' => $aircraftType->id, 'name' => $globalAirframe->operator ?: $typeName]
            );
            $importedCount++;
        }

        $this->reset(['showGlobalImportModal', 'selectedRealWorldAirframes', 'selectAllRealWorld', 'searchRealWorld']);
        session()->flash('message', "Successfully imported {$importedCount} real-world airframe(s) into your fleet.");
    }

    public function importGlobalAirframes()
    {
        $this->validate([
            'globalAircraftCode' => 'required|string',
            'quantityToGenerate' => 'required|integer|min:1|max:50',
        ]);

        $tenantId = $this->getActiveTenantId();
        $globalAircraft = \App\Models\SystemGlobalAircraft::where('code', $this->globalAircraftCode)->first();

        $code = strtoupper($this->globalAircraftCode);
        $typeName = $globalAircraft ? $globalAircraft->name : ($code . ' Aircraft');

        // Create or get local AircraftType
        $aircraftType = AircraftType::firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => $code],
            ['name' => $typeName]
        );

        $registrations = [];

        if (!empty(trim($this->customRegistrationsText))) {
            $rawRegs = preg_split('/[\s,\n]+/', $this->customRegistrationsText);
            foreach ($rawRegs as $r) {
                $r = strtoupper(trim($r));
                if (!empty($r)) {
                    $registrations[] = $r;
                }
            }
        } else {
            $prefix = strtoupper(trim($this->registrationPrefix ?: 'G-'));
            for ($i = 1; $i <= $this->quantityToGenerate; $i++) {
                $suffix = sprintf('%02d', $i);
                $registrations[] = $prefix . 'VA' . $suffix;
            }
        }

        $importedCount = 0;
        foreach ($registrations as $reg) {
            Airframe::updateOrCreate(
                ['tenant_id' => $tenantId, 'registration' => $reg],
                ['aircraft_type_id' => $aircraftType->id, 'name' => $typeName]
            );
            $importedCount++;
        }

        $this->reset(['showGlobalImportModal', 'globalAircraftCode', 'customRegistrationsText']);
        session()->flash('message', "Successfully imported {$importedCount} airframe(s) for {$code} into your fleet.");
    }

    public function render()
    {
        $tenantId = $this->getActiveTenantId();
        
        $query = Airframe::with('aircraftType')->where('tenant_id', $tenantId);

        if (!empty($this->search)) {
            $s = trim($this->search);
            $query->where(function($q) use ($s) {
                $q->where('registration', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%")
                  ->orWhereHas('aircraftType', function($aq) use ($s) {
                      $aq->where('code', 'like', "%{$s}%")
                         ->orWhere('name', 'like', "%{$s}%");
                  });
            });
        }

        if (!empty($this->filterAircraftType)) {
            $query->where('aircraft_type_id', $this->filterAircraftType);
        }

        $airframes = $query->orderBy('registration', 'asc')->paginate($this->perPage);
        $totalAirframesCount = Airframe::where('tenant_id', $tenantId)->count();
        $aircraftTypes = AircraftType::where('tenant_id', $tenantId)->orderBy('code')->get();
        $globalAircraftTypes = \App\Models\SystemGlobalAircraft::orderBy('code')->get();

        // Autocomplete suggestions list (all unique registrations & names for current tenant)
        $allTenantAirframes = Airframe::where('tenant_id', $tenantId)->select('registration', 'name')->get();
        $autocompleteList = $allTenantAirframes->pluck('registration')
            ->merge($allTenantAirframes->pluck('name'))
            ->merge($aircraftTypes->pluck('code'))
            ->merge($aircraftTypes->pluck('name'))
            ->filter()
            ->unique()
            ->values()
            ->take(60)
            ->toArray();

        $realWorldAirframes = null;
        if ($this->showGlobalImportModal && $this->importMode === 'real_world') {
            $realQuery = \App\Models\SystemGlobalAirframe::query();
            if ($this->filterAircraftCode) {
                $realQuery->where('icao_code', $this->filterAircraftCode);
            }
            if ($this->searchRealWorld) {
                $realQuery->where(function($q) {
                    $q->where('registration', 'like', '%' . $this->searchRealWorld . '%')
                      ->orWhere('operator', 'like', '%' . $this->searchRealWorld . '%')
                      ->orWhere('name', 'like', '%' . $this->searchRealWorld . '%');
                });
            }
            $realWorldAirframes = $realQuery->orderBy('registration')->paginate(15, ['*'], 'realWorldPage');
        }

        $existingRegistrations = Airframe::where('tenant_id', $tenantId)
            ->pluck('registration')
            ->map(fn($r) => strtoupper($r))
            ->flip()
            ->toArray();

        return view('livewire.fleet-manager', [
            'airframes' => $airframes,
            'totalAirframesCount' => $totalAirframesCount,
            'aircraftTypes' => $aircraftTypes,
            'autocompleteList' => $autocompleteList,
            'globalAircraftTypes' => $globalAircraftTypes,
            'realWorldAirframes' => $realWorldAirframes,
            'filteredApiFleet' => $this->filtered_api_fleet,
            'existingRegistrations' => $existingRegistrations,
        ])->layout('layouts.app');
    }
}
