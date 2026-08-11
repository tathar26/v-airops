<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pirep;
use Illuminate\Support\Facades\Log;

class AcarsController extends Controller
{
    /**
     * Connect to ACARS / Start tracking a flight.
     */
    public function connect(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:routes,id',
            'airframe_id' => 'required|exists:airframes,id',
        ]);

        $pirep = Pirep::create([
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant_id,
            'route_id' => $request->route_id,
            'airframe_id' => $request->airframe_id,
            'status' => 'flying',
            'flight_log' => []
        ]);

        return response()->json([
            'message' => 'Connected successfully',
            'pirep_id' => $pirep->id,
        ]);
    }

    /**
     * Receive telemetry data.
     */
    public function telemetry(Request $request, $pirepId)
    {
        $pirep = Pirep::where('user_id', auth()->id())->findOrFail($pirepId);

        if ($pirep->status !== 'flying') {
            return response()->json(['error' => 'Flight is not currently active'], 400);
        }

        $log = $pirep->flight_log ?? [];
        $log[] = [
            'timestamp' => now()->toIso8601String(),
            'lat' => $request->input('lat'),
            'lon' => $request->input('lon'),
            'alt' => $request->input('alt'),
            'gs' => $request->input('gs'),
            'phase' => $request->input('phase'),
        ];
        
        $pirep->flight_log = $log;
        $pirep->save();

        return response()->json(['message' => 'Telemetry received']);
    }

    /**
     * File PIREP (End flight).
     */
    public function filePirep(Request $request, $pirepId)
    {
        $pirep = Pirep::where('user_id', auth()->id())->findOrFail($pirepId);

        $request->validate([
            'block_fuel' => 'required|integer',
            'zfw' => 'required|integer',
            'touchdown_rate_fpm' => 'required|integer',
        ]);

        $pirep->update([
            'status' => 'filed',
            'block_fuel' => $request->block_fuel,
            'zfw' => $request->zfw,
            'touchdown_rate_fpm' => $request->touchdown_rate_fpm,
            'cost_index' => $request->input('cost_index', 0),
        ]);

        return response()->json([
            'message' => 'PIREP filed successfully',
            'pirep' => $pirep
        ]);
    }
}
