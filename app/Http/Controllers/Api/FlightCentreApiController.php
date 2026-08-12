<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FlightCentreApiController extends Controller
{
    public function destinations(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $currentAirportId = $request->user()->pilotProfile->current_airport_id;
        
        if (!$currentAirportId) {
            return response()->json(['error' => 'No current location set'], 400);
        }

        $currentAirport = \App\Models\Airport::find($currentAirportId);

        // Get routes originating from current airport for this tenant
        $routes = \App\Models\Route::where('tenant_id', $tenantId)
            ->where('departure_icao', $currentAirport->icao)
            ->get();

        $arrivalIcaos = $routes->pluck('arrival_icao')->unique();

        $destinations = \App\Models\Airport::whereIn('icao', $arrivalIcaos)->get();

        return response()->json([
            'current' => $currentAirport,
            'destinations' => $destinations,
            'routes' => $routes
        ]);
    }

    public function network(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        
        // Get all unique airports that have routes for this tenant
        $routes = \App\Models\Route::where('tenant_id', $tenantId)->get();
        $icaos = $routes->pluck('departure_icao')->merge($routes->pluck('arrival_icao'))->unique();
        
        $airports = \App\Models\Airport::whereIn('icao', $icaos)->get();
        $hubs = \App\Models\TenantHub::where('tenant_id', $tenantId)->where('is_base', true)->pluck('airport_id');

        return response()->json([
            'airports' => $airports,
            'hubs' => $hubs,
            'routes' => $routes
        ]);
    }

    public function updateLocation(Request $request)
    {
        $request->validate(['airport_id' => 'required|exists:airports,id']);
        $profile = $request->user()->pilotProfile;
        $profile->current_airport_id = $request->airport_id;
        $profile->save();

        return response()->json(['success' => true]);
    }
}
