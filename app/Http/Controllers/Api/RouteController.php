<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Route;

class RouteController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $routes = Route::with('aircraftTypes')->where('tenant_id', $tenantId)->get();

        return response()->json([
            'status' => 'success',
            'data' => $routes
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'flight_number' => 'required|string|max:10',
            'departure_icao' => 'required|string|size:4',
            'arrival_icao' => 'required|string|size:4',
            'block_time' => 'required|string|max:10',
            'route_string' => 'nullable|string|max:255',
            'aircraft_types' => 'nullable|array',
            'aircraft_types.*' => 'exists:aircraft_types,id',
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['departure_icao'] = strtoupper($validated['departure_icao']);
        $validated['arrival_icao'] = strtoupper($validated['arrival_icao']);

        $route = Route::create([
            'tenant_id' => $tenantId,
            'flight_number' => $validated['flight_number'],
            'departure_icao' => $validated['departure_icao'],
            'arrival_icao' => $validated['arrival_icao'],
            'block_time' => $validated['block_time'],
            'route_string' => $validated['route_string'] ?? null,
        ]);

        if (!empty($validated['aircraft_types'])) {
            $route->aircraftTypes()->sync($validated['aircraft_types']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $route->load('aircraftTypes')
        ], 201);
    }
}
