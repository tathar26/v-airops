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
        $tenantId = $user->tenant_id ?? session('active_airline_id') ?? $user->userAirlines()->first()?->tenant_id;
        
        if (!$tenantId) {
            return redirect()->route('onboarding.select-airline');
        }

        $profile = PilotProfile::firstOrCreate(
            ['user_id' => $user->id, 'tenant_id' => $tenantId],
            ['flight_time' => 0, 'points' => 0]
        );

        $pirepsCount = Pirep::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->count();

        $acceptedPireps = Pirep::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['accepted', 'Accepted', 'complete', 'Complete', 'approved', 'Approved'])
            ->count();

        $rejectedPireps = Pirep::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['rejected', 'Rejected', 'invalidated', 'Invalidated'])
            ->count();

        $currentRank = $profile->rank ?? Rank::where('tenant_id', $tenantId)->regular()->orderBy('position')->first();
        $currentPosition = $currentRank?->position ?? 0;

        $nextRank = Rank::where('tenant_id', $tenantId)
            ->regular()
            ->where('position', '>', $currentPosition)
            ->orderBy('position', 'asc')
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

