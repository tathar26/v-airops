<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\AircraftType;

class AircraftTypeManager extends Component
{
    public $showAddModal = false;
    public $editMode = false;
    public $editingId = null;

    public $code = '';
    public $name = '';

    protected $rules = [
        'code' => 'required|string|max:10',
        'name' => 'required|string|max:255',
    ];

    public function openAddModal()
    {
        $this->reset(['code', 'name', 'editingId', 'editMode']);
        $this->showAddModal = true;
    }

    public function editAircraftType($id)
    {
        $aircraftType = AircraftType::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->editingId = $aircraftType->id;
        $this->code = $aircraftType->code;
        $this->name = $aircraftType->name;
        $this->editMode = true;
        $this->showAddModal = true;
    }

    public function deleteAircraftType($id)
    {
        AircraftType::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
    }

    public function saveAircraftType()
    {
        $this->validate();

        if ($this->editMode) {
            $aircraftType = AircraftType::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->editingId);
            $aircraftType->update([
                'code' => strtoupper($this->code),
                'name' => $this->name,
            ]);
        } else {
            AircraftType::create([
                'tenant_id' => auth()->user()->tenant_id,
                'code' => strtoupper($this->code),
                'name' => $this->name,
            ]);
        }

        $this->reset(['code', 'name', 'showAddModal', 'editMode', 'editingId']);
    }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;
        $aircraftTypes = AircraftType::where('tenant_id', $tenantId)->get();

        return view('livewire.aircraft-type-manager', [
            'aircraftTypes' => $aircraftTypes
        ])->layout('layouts.app');
    }
}
