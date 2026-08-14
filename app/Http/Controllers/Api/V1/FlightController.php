<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AcarsActiveFlight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();

        $flight = AcarsActiveFlight::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (!$flight) {
            // Auto-generate test dispatch for seamless first-run experience
            $flight = AcarsActiveFlight::create([
                'user_id' => $user->id,
                'flight_number' => 'SVK204',
                'origin_icao' => 'EGLL',
                'destination_icao' => 'LFPG',
                'route' => 'DVR L10 RCM UN874 KENT DCT',
                'aircraft_type' => 'A320',
                'planned_altitude' => 34000,
                'planned_fuel_kg' => 6800.0,
                'planned_zfw_kg' => 59500.0,
                'simbrief_ofp_id' => '1049281',
                'status' => 'active',
            ]);
        }

        return response()->json([
            'id' => $flight->id,
            'flight_number' => $flight->flight_number,
            'origin_icao' => $flight->origin_icao,
            'destination_icao' => $flight->destination_icao,
            'route' => $flight->route,
            'aircraft_type' => $flight->aircraft_type,
            'planned_altitude' => $flight->planned_altitude,
            'planned_fuel_kg' => (float) $flight->planned_fuel_kg,
            'planned_zfw_kg' => (float) $flight->planned_zfw_kg,
            'simbrief_ofp_id' => $flight->simbrief_ofp_id,
            'status' => $flight->status,
            'created_at' => $flight->created_at->toISOString(),
        ]);
    }
}
