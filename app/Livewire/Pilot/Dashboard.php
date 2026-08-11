<?php

namespace App\Livewire\Pilot;

use Livewire\Component;
use App\Models\Pirep;
use App\Models\PilotProfile;
use App\Models\Rank;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        
        $profile = PilotProfile::firstOrCreate(
            ['user_id' => $user->id, 'tenant_id' => $tenantId],
            ['flight_time' => 0, 'points' => 0]
        );

        $pirepsCount = Pirep::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->count();

        $acceptedPireps = Pirep::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->where('status', 'Accepted')
            ->count();

        $rejectedPireps = Pirep::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['Rejected', 'Invalidated'])
            ->count();

        $nextRank = Rank::where('tenant_id', $tenantId)
            ->where(function($query) use ($profile) {
                $query->where('min_hours', '>', floor($profile->flight_time / 60))
                      ->orWhere('min_points', '>', $profile->points);
            })
            ->orderBy('min_hours', 'asc')
            ->orderBy('min_points', 'asc')
            ->first();

        return view('livewire.pilot.dashboard', [
            'user' => $user,
            'profile' => $profile,
            'pirepsCount' => $pirepsCount,
            'acceptedPireps' => $acceptedPireps,
            'rejectedPireps' => $rejectedPireps,
            'nextRank' => $nextRank,
        ])->layout('layouts.app');
    }
}

