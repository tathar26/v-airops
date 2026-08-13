<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\Airframe;
use App\Models\AircraftType;
use Livewire\WithFileUploads;

class FleetManager extends Component
{
    use WithFileUploads;

    public $showAddModal = false;
    public $editMode = false;
    public $editingId = null;

    public $registration = '';
    public $aircraft_type_id = '';
    public $name = '';

    public $csvFile;

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

    public function editAirframe($id)
    {
        $airframe = Airframe::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->editingId = $airframe->id;
        $this->registration = $airframe->registration;
        $this->aircraft_type_id = $airframe->aircraft_type_id;
        $this->name = $airframe->name;
        $this->editMode = true;
        $this->showAddModal = true;
    }

    public function deleteAirframe($id)
    {
        Airframe::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
    }

    public function saveAirframe()
    {
        $this->validate();

        if ($this->editMode) {
            $airframe = Airframe::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->editingId);
            $airframe->update([
                'aircraft_type_id' => $this->aircraft_type_id,
                'registration' => $this->registration,
                'name' => $this->name,
            ]);
        } else {
            Airframe::create([
                'tenant_id' => auth()->user()->tenant_id,
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

        if (($handle = fopen($this->csvFile->getRealPath(), "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if(count($data) >= 3) {
                    Airframe::updateOrCreate(
                        ['tenant_id' => auth()->user()->tenant_id, 'registration' => $data[0]],
                        ['aircraft_type_id' => $data[1], 'name' => $data[2]]
                    );
                }
            }
            fclose($handle);
        }

        $this->reset('csvFile');
        session()->flash('message', 'Fleet imported successfully.');
    }

    public function downloadTemplate()
    {
        $content = "registration,aircraft_type_id,name\nG-EZYM,1,Spirit of easyJet\n";
        return response()->streamDownload(function() use ($content) {
            echo $content;
        }, 'fleet_template.csv');
    }

    public $showGlobalImportModal = false;
    public $globalAircraftCode = '';
    public $registrationPrefix = 'G-';
    public $quantityToGenerate = 5;
    public $customRegistrationsText = '';

    public function openGlobalImportModal()
    {
        $this->reset(['globalAircraftCode', 'registrationPrefix', 'quantityToGenerate', 'customRegistrationsText']);
        $this->registrationPrefix = 'G-';
        $this->quantityToGenerate = 5;
        $this->showGlobalImportModal = true;
    }

    public function importGlobalAirframes()
    {
        $this->validate([
            'globalAircraftCode' => 'required|string',
            'quantityToGenerate' => 'required|integer|min:1|max:50',
        ]);

        $tenantId = auth()->user()->tenant_id;
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
        $tenantId = auth()->user()->tenant_id;
        $airframes = Airframe::with('aircraftType')->where('tenant_id', $tenantId)->get();
        $aircraftTypes = AircraftType::all();
        $globalAircraftTypes = \App\Models\SystemGlobalAircraft::orderBy('code')->get();

        return view('livewire.fleet-manager', [
            'airframes' => $airframes,
            'aircraftTypes' => $aircraftTypes,
            'globalAircraftTypes' => $globalAircraftTypes,
        ])->layout('layouts.app');
    }
}
