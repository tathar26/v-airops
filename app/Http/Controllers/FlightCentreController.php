<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FlightCentreController extends Controller
{
    public function index()
    {
        return view('flight-centre.index');
    }

    public function bookFlightMap()
    {
        return view('flight-centre.book-map');
    }

    public function flightsTable(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $query = \App\Models\Route::where('tenant_id', $tenantId);

        // Get pilot's current location
        $profile = $request->user()->pilotProfiles()->first();
        if ($profile && $profile->current_airport_id) {
            $airport = \App\Models\Airport::find($profile->current_airport_id);
            if ($airport) {
                $query->where('departure_icao', $airport->icao);
            }
        }

        // Simple filtering if needed
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('callsign', 'like', "%{$search}%")
                  ->orWhere('departure_icao', 'like', "%{$search}%")
                  ->orWhere('arrival_icao', 'like', "%{$search}%");
            });
        }

        if ($request->has('dep')) {
            $query->where('departure_icao', $request->dep);
        }

        if ($request->has('arr')) {
            $query->where('arrival_icao', $request->arr);
        }

        $routes = $query->paginate(15);
        
        // Get unique airports for dropdowns
        $allRoutes = \App\Models\Route::where('tenant_id', $tenantId)->get();
        $departureIcaos = $allRoutes->pluck('departure_icao')->unique()->sort();
        $arrivalIcaos = $allRoutes->pluck('arrival_icao')->unique()->sort();

        return view('flight-centre.flights-table', compact('routes', 'departureIcaos', 'arrivalIcaos'));
    }

    public function destinationMap()
    {
        return view('flight-centre.destination-map');
    }
}
