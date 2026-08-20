<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AircraftType;

class AircraftTypeManager extends Component
{
    use WithPagination;

    public $showAddModal = false;
    public $editMode = false;
    public $editingId = null;

    public $code = '';
    public $name = '';

    public $showGlobalImportModal = false;
    public $searchGlobal = '';
    public $selectedGlobalAircraft = [];
    public $selectAllGlobal = false;

    public function updatingSearchGlobal()
    {
        $this->resetPage();
    }

    protected $rules = [
        'code' => 'required|string|max:10',
        'name' => 'required|string|max:255',
    ];

    public function openAddModal()
    {
        $this->reset(['code', 'name', 'editingId', 'editMode']);
        $this->showAddModal = true;
    }

    public function openGlobalImportModal()
    {
        $this->reset(['searchGlobal', 'selectedGlobalAircraft', 'selectAllGlobal']);
        $this->showGlobalImportModal = true;
    }

    public function updatedSelectAllGlobal($value)
    {
        if ($value) {
            $query = \App\Models\SystemGlobalAircraft::query();
            if ($this->searchGlobal) {
                $query->where('code', 'like', '%' . $this->searchGlobal . '%')
                      ->orWhere('name', 'like', '%' . $this->searchGlobal . '%');
            }
            $this->selectedGlobalAircraft = $query->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedGlobalAircraft = [];
        }
    }

    protected function getActiveTenantId(): int
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        if (!$tenantId) {
            $tenantId = \App\Models\Tenant::first()?->id ?? 1;
        }
        return (int) $tenantId;
    }

    public function importGlobalTypes()
    {
        if (empty($this->selectedGlobalAircraft)) {
            session()->flash('error', 'Please select at least one aircraft type to import.');
            return;
        }

        $globalAircraft = \App\Models\SystemGlobalAircraft::whereIn('id', $this->selectedGlobalAircraft)->get();
        $tenantId = $this->getActiveTenantId();
        $importedCount = 0;

        foreach ($globalAircraft as $aircraft) {
            AircraftType::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => strtoupper($aircraft->code)],
                ['name' => $aircraft->name]
            );
            $importedCount++;
        }

        $this->reset(['showGlobalImportModal', 'selectedGlobalAircraft', 'selectAllGlobal', 'searchGlobal']);
        session()->flash('message', "Successfully imported {$importedCount} aircraft type(s) into your fleet configuration.");
    }

    public function editAircraftType($id)
    {
        $tenantId = $this->getActiveTenantId();
        $aircraftType = AircraftType::where('tenant_id', $tenantId)->findOrFail($id);
        $this->editingId = $aircraftType->id;
        $this->code = $aircraftType->code;
        $this->name = $aircraftType->name;
        $this->editMode = true;
        $this->showAddModal = true;
    }

    public function deleteAircraftType($id)
    {
        $tenantId = $this->getActiveTenantId();
        AircraftType::where('tenant_id', $tenantId)->findOrFail($id)->delete();
    }

    public function saveAircraftType()
    {
        $this->validate();
        $tenantId = $this->getActiveTenantId();

        if ($this->editMode) {
            $aircraftType = AircraftType::where('tenant_id', $tenantId)->findOrFail($this->editingId);
            $aircraftType->update([
                'code' => strtoupper($this->code),
                'name' => $this->name,
            ]);
        } else {
            AircraftType::create([
                'tenant_id' => $tenantId,
                'code' => strtoupper($this->code),
                'name' => $this->name,
            ]);
        }

        $this->reset(['code', 'name', 'showAddModal', 'editMode', 'editingId']);
    }

    public function render()
    {
        $tenantId = $this->getActiveTenantId();
        $aircraftTypes = AircraftType::where('tenant_id', $tenantId)->get();

        $globalQuery = \App\Models\SystemGlobalAircraft::query();
        if ($this->searchGlobal) {
            $globalQuery->where('code', 'like', '%' . $this->searchGlobal . '%')
                        ->orWhere('name', 'like', '%' . $this->searchGlobal . '%');
        }
        $globalAircraft = $this->showGlobalImportModal ? $globalQuery->orderBy('code')->paginate(15) : null;

        return view('livewire.aircraft-type-manager', [
            'aircraftTypes' => $aircraftTypes,
            'globalAircraft' => $globalAircraft,
        ])->layout('layouts.app');
    }
}
