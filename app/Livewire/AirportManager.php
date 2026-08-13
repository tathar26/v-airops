<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Airport;
use Illuminate\Support\Facades\Http;

class AirportManager extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editMode = false;
    public $editingId = null;

    public $icao = '';
    public $name = '';
    public $lat = '';
    public $lon = '';
    public $elevation = '';
    public $metadata_keys = [];
    public $metadata_values = [];

    protected $rules = [
        'icao' => 'required|string|size:4',
        'name' => 'required|string|max:255',
        'lat' => 'required|numeric',
        'lon' => 'required|numeric',
        'elevation' => 'nullable|numeric',
    ];

    public function openModal()
    {
        $this->reset(['icao', 'name', 'lat', 'lon', 'elevation', 'metadata_keys', 'metadata_values', 'editMode', 'editingId']);
        $this->showModal = true;
    }

    public function editAirport($id)
    {
        $airport = Airport::findOrFail($id);
        $this->editingId = $airport->id;
        $this->icao = $airport->icao;
        $this->name = $airport->name;
        $this->lat = $airport->lat;
        $this->lon = $airport->lon;
        $this->elevation = $airport->elevation;
        
        $this->metadata_keys = [];
        $this->metadata_values = [];
        if (is_array($airport->metadata)) {
            foreach ($airport->metadata as $key => $value) {
                $this->metadata_keys[] = $key;
                $this->metadata_values[] = $value;
            }
        }
        
        $this->editMode = true;
        $this->showModal = true;
    }

    public function addMetadataField()
    {
        $this->metadata_keys[] = '';
        $this->metadata_values[] = '';
    }

    public function removeMetadataField($index)
    {
        unset($this->metadata_keys[$index]);
        unset($this->metadata_values[$index]);
        $this->metadata_keys = array_values($this->metadata_keys);
        $this->metadata_values = array_values($this->metadata_values);
    }

    public function fetchAirportData()
    {
        $this->validate([
            'icao' => 'required|string|size:4'
        ]);

        $icao = strtoupper($this->icao);
        
        try {
            // Fetch from mwgg/Airports repository JSON
            $response = Http::get('https://raw.githubusercontent.com/mwgg/Airports/master/airports.json');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data[$icao])) {
                    $airport = $data[$icao];
                    $this->name = $airport['name'] ?? '';
                    $this->lat = $airport['lat'] ?? '';
                    $this->lon = $airport['lon'] ?? '';
                    $this->elevation = $airport['elevation'] ?? '';
                    
                    session()->flash('success', "Data fetched successfully for {$icao}");
                } else {
                    session()->flash('error', "Airport {$icao} not found in database.");
                }
            } else {
                session()->flash('error', "Failed to fetch airport database.");
            }
        } catch (\Exception $e) {
            session()->flash('error', "Error fetching data: " . $e->getMessage());
        }
    }

    public function saveAirport()
    {
        $this->validate();

        $metadata = [];
        foreach ($this->metadata_keys as $index => $key) {
            if (!empty($key)) {
                $metadata[$key] = $this->metadata_values[$index];
            }
        }

        if ($this->editMode) {
            $airport = Airport::findOrFail($this->editingId);
            $airport->update([
                'icao' => strtoupper($this->icao),
                'name' => $this->name,
                'lat' => $this->lat,
                'lon' => $this->lon,
                'elevation' => $this->elevation,
                'metadata' => $metadata
            ]);
        } else {
            Airport::updateOrCreate(
                ['icao' => strtoupper($this->icao)],
                [
                    'name' => $this->name,
                    'lat' => $this->lat,
                    'lon' => $this->lon,
                    'elevation' => $this->elevation,
                    'metadata' => $metadata
                ]
            );
        }

        $this->showModal = false;
        $this->reset(['icao', 'name', 'lat', 'lon', 'elevation', 'metadata_keys', 'metadata_values', 'editMode', 'editingId']);
    }

    public function deleteAirport($id)
    {
        Airport::findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.airport-manager', [
            'airports' => Airport::orderBy('icao')->paginate(20)
        ])->layout('layouts.app');
    }
}
