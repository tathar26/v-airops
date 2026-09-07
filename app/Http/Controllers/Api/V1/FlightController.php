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

        // Check if user has a PIREP with status 'reply_needed'
        $hasReplyNeeded = \App\Models\Pirep::where('user_id', $user->id)
            ->whereIn('status', ['reply_needed', 'reply needed'])
            ->exists();

        if ($hasReplyNeeded) {
            return response()->json([
                'has_booking' => false,
                'reply_needed' => true,
                'message' => 'You have PIREPs requiring urgent reply before you can start or book new flights. Please respond to your pending PIREPs.'
            ], 403);
        }

        // 1. Check if user has an active booking in the VA system within the last 24h
        $booking = Booking::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'dispatched', 'in_flight'])
            ->where('updated_at', '>=', \Carbon\Carbon::now()->subHours(24))
            ->with([
                'route' => function ($q) { $q->withoutGlobalScopes(); },
                'airframe' => function ($q) { $q->withoutGlobalScopes(); },
                'airframe.aircraftType' => function ($q) { $q->withoutGlobalScopes(); },
                'tenant' => function ($q) { $q->withoutGlobalScopes(); },
            ])
            ->latest('id')
            ->first();

        if ($booking) {
            $sb = $booking->simbrief_data ?? [];
            $targetTenantId = $booking->tenant_id ?? ($user->getActiveTenantId() ?? $user->tenant_id);

            $targetFlightNum = $sb['params']['callsign'] 
                ?? ($sb['atc']['callsign'] 
                ?? ($sb['general']['flight_number'] 
                ?? ($booking->route?->callsign 
                ?? ($booking->route?->flight_number 
                ?? ($booking->tenant?->icao ? $booking->tenant->icao . '101' : 'SVK101')))));

            $targetDep = $sb['origin']['icao_code'] ?? ($sb['general']['origin'] ?? ($booking->route?->departure_icao ?? 'EGLL'));
            $targetArr = $sb['destination']['icao_code'] ?? ($sb['general']['destination'] ?? ($booking->route?->arrival_icao ?? 'LFPG'));
            $targetAircraft = $booking->airframe?->aircraftType?->code ?? ($sb['aircraft']['icao_code'] ?? ($booking->route?->aircraftTypes?->first()?->code ?? 'A320'));
            $targetOfpId = (string) ($sb['params']['ofp_id'] ?? ($sb['general']['ofp_id'] ?? $booking->id));

            $rawAlt = (int) ($sb['general']['initial_altitude'] ?? ($sb['general']['cruise_altitude'] ?? ($sb['params']['fl'] ?? ($booking->route?->flight_level ?? 36000))));
            $plannedAltitude = ($rawAlt > 0 && $rawAlt < 1000) ? $rawAlt * 100 : $rawAlt;

            $plannedFuel = (float) ($sb['fuel']['plan_ramp'] ?? ($sb['fuel']['ramp'] ?? ($sb['fuel']['plan_takeoff'] ?? 6500.0)));
            $plannedZfw = (float) ($sb['weights']['est_zfw'] ?? ($sb['weights']['zfw'] ?? 58000.0));
            $routeString = $booking->route?->route_string ?? ($sb['general']['route'] ?? 'DIRECT');

            // Find or synchronize active flight for this booking
            $flight = AcarsActiveFlight::where('user_id', $user->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if ($flight) {
                // Update flight with latest booking details if changed
                $flight->update([
                    'tenant_id' => $targetTenantId,
                    'flight_number' => strtoupper($targetFlightNum),
                    'origin_icao' => strtoupper($targetDep ?? $flight->origin_icao),
                    'destination_icao' => strtoupper($targetArr ?? $flight->destination_icao),
                    'route' => $routeString,
                    'aircraft_type' => strtoupper($targetAircraft),
                    'planned_altitude' => $plannedAltitude,
                    'planned_fuel_kg' => $plannedFuel,
                    'planned_zfw_kg' => $plannedZfw,
                    'simbrief_ofp_id' => $targetOfpId,
                ]);
            } else {
                $flight = AcarsActiveFlight::create([
                    'user_id' => $user->id,
                    'tenant_id' => $targetTenantId,
                    'flight_number' => strtoupper($targetFlightNum),
                    'origin_icao' => strtoupper($targetDep ?? 'EGLL'),
                    'destination_icao' => strtoupper($targetArr ?? 'LFPG'),
                    'route' => $routeString,
                    'aircraft_type' => strtoupper($targetAircraft),
                    'planned_altitude' => $plannedAltitude,
                    'planned_fuel_kg' => $plannedFuel,
                    'planned_zfw_kg' => $plannedZfw,
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
                return response()->json([
                    'has_booking' => false,
                    'message' => 'No active flight or booking found for pilot'
                ], 404);
            }
        }

        return response()->json([
            'has_booking' => true,
            'id' => $flight->id,
            'flight_id' => $flight->id,
            'booking_id' => $booking?->id ?? $flight->id,
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
        $targetTenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        // Archive previous active flights for this user
        AcarsActiveFlight::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $rawAlt = (int) $request->input('planned_altitude', 34000);
        $plannedAltitude = ($rawAlt > 0 && $rawAlt < 1000) ? $rawAlt * 100 : $rawAlt;

        $flight = AcarsActiveFlight::create([
            'user_id' => $user->id,
            'tenant_id' => $targetTenantId,
            'flight_number' => strtoupper($request->input('flight_number')),
            'origin_icao' => strtoupper($request->input('origin_icao')),
            'destination_icao' => strtoupper($request->input('destination_icao')),
            'route' => $request->input('route'),
            'aircraft_type' => $request->input('aircraft_type', 'A320'),
            'planned_altitude' => $plannedAltitude,
            'planned_fuel_kg' => (float) $request->input('planned_fuel_kg', 6500.0),
            'planned_zfw_kg' => (float) $request->input('planned_zfw_kg', 58000.0),
            'simbrief_ofp_id' => $request->input('simbrief_ofp_id'),
            'status' => 'active',
        ]);

        return response()->json([
            'has_booking' => true,
            'id' => $flight->id,
            'flight_id' => $flight->id,
            'booking_id' => $flight->id,
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
            ->with([
                'route' => function ($q) { $q->withoutGlobalScopes(); },
                'airframe' => function ($q) { $q->withoutGlobalScopes(); },
                'airframe.aircraftType' => function ($q) { $q->withoutGlobalScopes(); },
                'tenant' => function ($q) { $q->withoutGlobalScopes(); },
            ])
            ->latest('id')
            ->first();

        if (!$booking) {
            return response()->json([
                'has_booking' => false,
                'message' => 'No active booking found'
            ], 404);
        }

        $sb = $booking->simbrief_data ?? [];
        $targetFlightNum = $sb['params']['callsign'] 
            ?? ($sb['atc']['callsign'] 
            ?? ($sb['general']['flight_number'] 
            ?? ($booking->route?->callsign 
            ?? ($booking->route?->flight_number 
            ?? ($booking->tenant?->icao ? $booking->tenant->icao . '101' : 'SVK101')))));

        $targetDep = $sb['origin']['icao_code'] ?? ($sb['general']['origin'] ?? ($booking->route?->departure_icao ?? 'EGLL'));
        $targetArr = $sb['destination']['icao_code'] ?? ($sb['general']['destination'] ?? ($booking->route?->arrival_icao ?? 'LFPG'));
        $targetAircraft = $booking->airframe?->aircraftType?->code ?? ($sb['aircraft']['icao_code'] ?? ($booking->route?->aircraftTypes?->first()?->code ?? 'A320'));
        $targetRegistration = $booking->airframe?->registration ?? ($sb['aircraft']['reg'] ?? '');
        $targetOfpId = (string) ($sb['params']['ofp_id'] ?? ($sb['general']['ofp_id'] ?? $booking->id));

        $rawAlt = (int) ($sb['general']['initial_altitude'] ?? ($sb['general']['cruise_altitude'] ?? ($sb['params']['fl'] ?? ($booking->route?->flight_level ?? 36000))));
        $plannedAltitude = ($rawAlt > 0 && $rawAlt < 1000) ? $rawAlt * 100 : $rawAlt;

        $plannedFuel = (float) ($sb['fuel']['plan_ramp'] ?? ($sb['fuel']['ramp'] ?? ($sb['fuel']['plan_takeoff'] ?? 6500.0)));
        $plannedZfw = (float) ($sb['weights']['est_zfw'] ?? ($sb['weights']['zfw'] ?? 58000.0));
        $routeString = $booking->route?->route_string ?? ($sb['general']['route'] ?? 'DIRECT');

        // Construct clean lightweight OFP summary (~1KB) to prevent Nginx FastCGI buffer overflow
        $compactSimbrief = [
            'ofp_id' => $targetOfpId,
            'simbrief_id' => $targetOfpId,
            'flight_number' => strtoupper($targetFlightNum),
            'origin_icao' => strtoupper($targetDep),
            'destination_icao' => strtoupper($targetArr),
            'origin_name' => $sb['origin']['name'] ?? $targetDep,
            'destination_name' => $sb['destination']['name'] ?? $targetArr,
            'route' => $routeString,
            'aircraft_type' => strtoupper($targetAircraft),
            'aircraft_name' => $booking->airframe?->name ?? ($sb['aircraft']['name'] ?? strtoupper($targetAircraft)),
            'planned_altitude' => $plannedAltitude,
            'planned_fuel_kg' => $plannedFuel,
            'planned_zfw_kg' => $plannedZfw,
            'cost_index' => (int) ($sb['general']['cost_index'] ?? 4),
            'alternate_icao' => $sb['alternate']['icao_code'] ?? ($sb['general']['alternate'] ?? ''),
            'network' => $sb['network'] ?? ($booking->simbrief_data['network'] ?? ($booking->user?->pilotProfiles()->where('tenant_id', $booking->tenant_id)->first()?->preferred_network ?? 'Offline')),
            'passengers' => (int) ($sb['weights']['pax_count'] ?? ($booking->simbrief_data['passengers'] ?? ($booking->passengers ?? 170))),
            'cargo_kg' => (float) ($sb['weights']['cargo'] ?? ($booking->simbrief_data['cargo_kg'] ?? ($booking->cargo ?? 1500.0))),
        ];

        $targetNetwork = $compactSimbrief['network'];
        $targetPax = $compactSimbrief['passengers'];
        $targetCargo = $compactSimbrief['cargo_kg'];

        return response()->json([
            'has_booking' => true,
            'id' => $booking->id,
            'flight_id' => $booking->id,
            'booking_id' => $booking->id,
            'flight_number' => strtoupper($targetFlightNum),
            'origin_icao' => strtoupper($targetDep),
            'destination_icao' => strtoupper($targetArr),
            'route' => $routeString,
            'network' => $targetNetwork,
            'passengers' => $targetPax,
            'cargo_kg' => $targetCargo,
            'aircraft_type' => strtoupper($targetAircraft),
            'airframe' => $targetRegistration,
            'planned_altitude' => $plannedAltitude,
            'planned_fuel_kg' => $plannedFuel,
            'planned_zfw_kg' => $plannedZfw,
            'simbrief_data' => $compactSimbrief,
            'simbrief_ofp_id' => $targetOfpId,
            'status' => $booking->status,
        ]);
    }
}
