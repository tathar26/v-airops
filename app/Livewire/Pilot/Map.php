<?php

namespace App\Livewire\Pilot;

use Livewire\Component;

class Map extends Component
{
    public $aircraftFilter = '';
    public $dateRangeFilter = 'all'; // 'all', '30', '7'

    public function updated($propertyName)
    {
        // Whenever a filter is updated, we dispatch a browser event
        // to tell AlpineJS to redraw the globe with the new routes.
        // We do this by calling the logic in render() but sending an event.
        // Actually, Livewire 3 automatically updates the DOM. But for the globe, 
        // we can just re-dispatch the data via an event.
        $this->dispatch('map-updated');
    }

    public function render()
    {
        // Base query for accepted PIREPs for this user
        $query = \App\Models\Pirep::where('user_id', auth()->id())
            ->whereIn('status', ['accepted', 'Accepted', 'complete', 'Complete', 'approved', 'Approved'])
            ->with(['route', 'airframe.aircraftType']);

        // Filter by Aircraft (Airframe ID)
        if ($this->aircraftFilter !== '') {
            $query->where('airframe_id', $this->aircraftFilter);
        }

        // Filter by Date Range
        if ($this->dateRangeFilter === '30') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($this->dateRangeFilter === '7') {
            $query->where('created_at', '>=', now()->subDays(7));
        }

        $pireps = $query->get();

        // Get unique routes from the filtered PIREPs
        $flownRoutes = $pireps->pluck('route')->unique('id')->filter();

        // Get unique airframes ever flown by this pilot (for the dropdown)
        // We do this without the filters applied so the dropdown doesn't shrink.
        $allFlownPireps = \App\Models\Pirep::where('user_id', auth()->id())
            ->whereIn('status', ['accepted', 'Accepted', 'complete', 'Complete', 'approved', 'Approved'])
            ->with('airframe.aircraftType')
            ->get();
            
        $availableAircraft = $allFlownPireps->pluck('airframe')->unique('id')->filter();

        // Extract all unique ICAOs from the *filtered* routes
        $icaos = collect();
        foreach ($flownRoutes as $route) {
            if ($route->departure_icao) $icaos->push($route->departure_icao);
            if ($route->arrival_icao) $icaos->push($route->arrival_icao);
        }
        $icaos = $icaos->unique();

        $airports = \App\Models\Airport::whereIn('icao', $icaos)->get()->keyBy('icao');

        $routesData = $flownRoutes->map(function ($route) {
            return [
                'dep' => $route->departure_icao,
                'arr' => $route->arrival_icao,
            ];
        })->values();

        // If this is a subsequent request, dispatch event with new data for the globe
        $this->dispatch('update-globe-data', routes: $routesData, airports: $airports);

        return view('livewire.pilot.map', [
            'routesData' => $routesData,
            'airports' => $airports,
            'availableAircraft' => $availableAircraft
        ])->layout('layouts.app');
    }
}
