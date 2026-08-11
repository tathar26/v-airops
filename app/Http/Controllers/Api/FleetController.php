<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Airframe;

class FleetController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $airframes = Airframe::with('aircraftType')->where('tenant_id', $tenantId)->get();

        return response()->json([
            'status' => 'success',
            'data' => $airframes
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'registration' => 'required|string|max:10',
            'aircraft_type_id' => 'required|exists:aircraft_types,id',
            'name' => 'nullable|string|max:50',
        ]);

        $validated['tenant_id'] = $tenantId;

        $airframe = Airframe::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $airframe
        ], 201);
    }
}
