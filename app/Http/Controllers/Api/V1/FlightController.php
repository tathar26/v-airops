<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DispatchFlightRequest;
use App\Models\AcarsActiveFlight;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Check if user has an active booking in the VA system within the last 24h
        $booking = Booking::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'dispatched', 'in_flight'])
            ->where('updated_at', '>=', \Carbon\Carbon::now()->subHours(24))
            ->with(['route', 'airframe.aircraftType'])
            ->latest('id')
            ->first();

        if ($booking) {
            $sb = $booking->simbrief_data ?? [];
            $targetFlightNum = $sb['params']['callsign'] 
                ?? ($sb['atc']['callsign'] 
                ?? ($sb['general']['flight_number'] 
                ?? ($booking->route?->callsign 
                ?? ($booking->route?->flight_number ?? 'SVK204'))));

            $targetDep = $sb['origin']['icao_code'] ?? ($sb['general']['origin'] ?? ($booking->route?->departure_icao ?? 'EGLL'));
            $targetArr = $sb['destination']['icao_code'] ?? ($sb['general']['destination'] ?? ($booking->route?->arrival_icao ?? 'LFPG'));
            $targetOfpId = (string) ($sb['params']['ofp_id'] ?? $booking->id);

            // Find or synchronize active flight for this booking
            $flight = AcarsActiveFlight::where('user_id', $user->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if ($flight) {
                // Update flight with latest booking details if changed
                $flight->update([
                    'flight_number' => strtoupper($targetFlightNum),
                    'origin_icao' => strtoupper($targetDep),
                    'destination_icao' => strtoupper($targetArr),
                    'route' => $booking->route?->route_string ?? ($sb['general']['route'] ?? $flight->route),
                    'aircraft_type' => $booking->airframe?->aircraftType?->code ?? ($sb['aircraft']['icao_code'] ?? $flight->aircraft_type),
                    'planned_altitude' => (int) ($sb['general']['initial_altitude'] ?? ($flight->planned_altitude ?? 34000)),
                    'planned_fuel_kg' => (float) ($sb['fuel']['plan_ramp'] ?? $flight->planned_fuel_kg),
                    'planned_zfw_kg' => (float) ($sb['weights']['est_zfw'] ?? $flight->planned_zfw_kg),
                    'simbrief_ofp_id' => $targetOfpId,
                ]);
            } else {
                $flight = AcarsActiveFlight::create([
                    'user_id' => $user->id,
                    'flight_number' => strtoupper($targetFlightNum),
                    'origin_icao' => strtoupper($targetDep),
                    'destination_icao' => strtoupper($targetArr),
                    'route' => $booking->route?->route_string ?? ($sb['general']['route'] ?? 'DVR L10 RCM UN874 KENT DCT'),
                    'aircraft_type' => $booking->airframe?->aircraftType?->code ?? ($sb['aircraft']['icao_code'] ?? 'A320'),
                    'planned_altitude' => (int) ($sb['general']['initial_altitude'] ?? 34000),
                    'planned_fuel_kg' => (float) ($sb['fuel']['plan_ramp'] ?? 6800.0),
                    'planned_zfw_kg' => (float) ($sb['weights']['est_zfw'] ?? 59500.0),
                    'simbrief_ofp_id' => $targetOfpId,
                    'status' => 'active',
                ]);
            }
        } else {
            $flight = AcarsActiveFlight::where('user_id', $user->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if (!$flight) {
                // Fallback default dispatch
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

    public function dispatch(DispatchFlightRequest $request): JsonResponse
    {
        $user = $request->user();

        // Archive previous active flights for this user
        AcarsActiveFlight::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $flight = AcarsActiveFlight::create([
            'user_id' => $user->id,
            'flight_number' => strtoupper($request->input('flight_number')),
            'origin_icao' => strtoupper($request->input('origin_icao')),
            'destination_icao' => strtoupper($request->input('destination_icao')),
            'route' => $request->input('route'),
            'aircraft_type' => $request->input('aircraft_type', 'A320'),
            'planned_altitude' => (int) $request->input('planned_altitude', 34000),
            'planned_fuel_kg' => (float) $request->input('planned_fuel_kg', 6500.0),
            'planned_zfw_kg' => (float) $request->input('planned_zfw_kg', 58000.0),
            'simbrief_ofp_id' => $request->input('simbrief_ofp_id'),
            'status' => 'active',
        ]);

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

    public function bookingActive(Request $request): JsonResponse
    {
        $booking = Booking::withoutGlobalScopes()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'dispatched', 'in_flight'])
            ->where('updated_at', '>=', \Carbon\Carbon::now()->subHours(24))
            ->with(['route', 'airframe.aircraftType'])
            ->latest('id')
            ->first();

        if (!$booking) {
            return response()->json([
                'has_booking' => false,
                'message' => 'No active booking found'
            ], 404);
        }

        return response()->json([
            'has_booking' => true,
            'id' => $booking->id,
            'flight_number' => $booking->route?->flight_number ?? $booking->route?->callsign ?? 'SVK101',
            'origin_icao' => $booking->route?->departure_icao,
            'destination_icao' => $booking->route?->arrival_icao,
            'route' => $booking->route?->route_string,
            'aircraft_type' => $booking->airframe?->aircraftType?->code ?? 'A320',
            'airframe' => $booking->airframe?->registration,
            'simbrief_data' => $booking->simbrief_data,
            'status' => $booking->status,
        ]);
    }
}
