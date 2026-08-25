<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FlightCentreApiController extends Controller
{
    public function destinations(Request $request)
    {
        $tenantId = $request->user()->getActiveTenantId() ?? $request->user()->tenant_id;
        $profile = $request->user()->pilotProfiles()->where('tenant_id', $tenantId)->first();
        if (!$profile) {
            $profile = \App\Models\PilotProfile::create([
                'user_id' => $request->user()->id,
                'tenant_id' => $tenantId
            ]);
        }
        $currentAirportId = $profile->current_airport_id;
        
        if (!$currentAirportId) {
            // 1. Try to find last completed PIREP destination
            $lastPirep = \App\Models\Pirep::where('user_id', $request->user()->id)
                ->where('status', 'ACCEPTED')
                ->latest('created_at')
                ->first();

            if ($lastPirep && $lastPirep->route) {
                $airport = \App\Models\Airport::where('icao', $lastPirep->route->arrival_icao)->first();
                if ($airport) {
                    $currentAirportId = $airport->id;
                }
            }

            // 2. Fallback to Tenant Base Hub
            if (!$currentAirportId) {
                $baseHub = \App\Models\TenantHub::where('tenant_id', $tenantId)->where('is_base', true)->first();
                if ($baseHub) {
                    $currentAirportId = $baseHub->airport_id;
                }
            }

            if ($currentAirportId) {
                $profile->current_airport_id = $currentAirportId;
                $profile->save();
            } else {
                return response()->json(['error' => 'No current location set and no base hub defined.'], 400);
            }
        }

        $currentAirport = \App\Models\Airport::find($currentAirportId);

        // Get routes originating from current airport for this tenant
        $routes = \App\Models\Route::with('aircraftTypes')
            ->where('tenant_id', $tenantId)
            ->where('departure_icao', $currentAirport->icao)
            ->get();

        $arrivalIcaos = $routes->pluck('arrival_icao')->unique();

        $destinations = \App\Models\Airport::whereIn('icao', $arrivalIcaos)->get();
        $hubs = \App\Models\TenantHub::where('tenant_id', $tenantId)->where('is_base', true)->pluck('airport_id');

        return response()->json([
            'current' => $currentAirport,
            'destinations' => $destinations,
            'routes' => $routes,
            'hubs' => $hubs
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
        $tenantId = $request->user()->getActiveTenantId() ?? $request->user()->tenant_id;
        $profile = $request->user()->pilotProfiles()->where('tenant_id', $tenantId)->first();
        if (!$profile) {
            $profile = \App\Models\PilotProfile::firstOrCreate([
                'user_id' => $request->user()->id,
                'tenant_id' => $tenantId,
            ]);
        }
        $profile->current_airport_id = $request->airport_id;
        $profile->save();

        return response()->json(['success' => true]);
    }

    public function book(Request $request)
    {
        $request->validate(['route_id' => 'required|exists:routes,id']);

        $tenantId = $request->user()->getActiveTenantId() ?? $request->user()->tenant_id;

        // Block booking if pilot has unacknowledged active NOTAMs
        if ($request->user()->hasUnreadNotams($tenantId)) {
            return response()->json([
                'error'         => 'You must read and acknowledge all active NOTAMs before booking a flight.',
                'redirect'      => route('notams'),
                'unread_notams' => true,
            ], 403);
        }

        // Check if user already has an active booking
        $existingBooking = \App\Models\Booking::where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'dispatched'])
            ->latest()
            ->first();

        if ($existingBooking) {
            return response()->json([
                'success' => true,
                'booking_id' => $existingBooking->id,
                'message' => 'You already have an active flight booking. Redirecting to active flight.'
            ]);
        }

        $route = \App\Models\Route::findOrFail($request->route_id);
        
        // Ensure route belongs to tenant
        if ($route->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['error' => 'Unauthorized route.'], 403);
        }

        // Create booking
        $booking = \App\Models\Booking::create([
            'user_id' => $request->user()->id,
            'tenant_id' => $request->user()->tenant_id,
            'route_id' => $route->id,
            'status' => 'pending'
        ]);

        return response()->json(['success' => true, 'booking_id' => $booking->id]);
    }

    public function liveFlights(Request $request, \App\Services\LiveFlightService $service)
    {
        $tenantId = $request->user()?->getActiveTenantId() ?? $request->user()?->tenant_id;
        $flights = $service->getLiveFlights($tenantId);

        return response()->json([
            'count' => count($flights),
            'timestamp' => gmdate('H:i\z'),
            'flights' => $flights,
        ]);
    }
}
