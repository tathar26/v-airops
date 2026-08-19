<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Airframe;
use App\Models\Route;
use App\Models\Pirep;
use App\Models\PilotProfile;
use Illuminate\Support\Facades\Auth;

class TenantDashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;
        
        $fleetCount = Airframe::where('tenant_id', $tenantId)->count();
        $routeCount = Route::where('tenant_id', $tenantId)->count();
        $recentPireps = Pirep::with(['user', 'route', 'airframe'])
                            ->where('tenant_id', $tenantId)
                            ->orderBy('created_at', 'desc')
                            ->take(5)
                            ->get();
        $totalPireps = Pirep::where('tenant_id', $tenantId)->count();

        $profile = PilotProfile::firstOrCreate(
            ['user_id' => $user->id, 'tenant_id' => $tenantId],
            ['flight_time' => 0, 'points' => 0]
        );

        $userPireps = Pirep::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->get();

        $userAcceptedPireps = $userPireps->where('status', 'Accepted')->count();
        $userPendingPireps = $userPireps->where('status', 'Pending')->count();
        $userRejectedPireps = $userPireps->whereIn('status', ['Rejected', 'Invalidated'])->count();
        $userTotalPireps = $userPireps->count();

        $flightTimeHours = floor($profile->flight_time / 60);
        $flightTimeMins = $profile->flight_time % 60;

        $liveFlightService = app(\App\Services\LiveFlightService::class);
        $liveFlights = $liveFlightService->getLiveFlights($tenantId);

        return view('livewire.tenant-dashboard', [
            'user' => $user,
            'profile' => $profile,
            'fleetCount' => $fleetCount,
            'routeCount' => $routeCount,
            'totalPireps' => $totalPireps,
            'recentPireps' => $recentPireps,
            'userTotalPireps' => $userTotalPireps,
            'userAcceptedPireps' => $userAcceptedPireps,
            'userPendingPireps' => $userPendingPireps,
            'userRejectedPireps' => $userRejectedPireps,
            'flightTimeHours' => $flightTimeHours,
            'flightTimeMins' => $flightTimeMins,
            'callsign' => $user->activeCallsign(),
            'rankName' => $user->active_rank_name,
            'currentLocation' => $profile->current_location_icao,
            'liveFlights' => $liveFlights,
            'liveFlightsCount' => count($liveFlights),
        ]);
    }
}
