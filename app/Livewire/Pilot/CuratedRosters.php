<?php

namespace App\Livewire\Pilot;

use App\Models\Activity;
use App\Models\ActivityRegistration;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class CuratedRosters extends Component
{
    use WithPagination;

    public function register(int $rosterId)
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        $roster = Activity::where('tenant_id', $tenantId)
            ->where('type', 'curated_roster')
            ->where('is_active', true)
            ->findOrFail($rosterId);

        ActivityRegistration::firstOrCreate(
            [
                'activity_id' => $roster->id,
                'user_id'     => $user->id,
            ],
            [
                'tenant_id'     => $tenantId,
                'status'        => 'in_progress',
                'registered_at' => Carbon::now('UTC'),
            ]
        );

        session()->flash('roster_message', "Curated Roster [{$roster->name}] added to your flight schedule!");
    }

    public function unregister(int $rosterId)
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        ActivityRegistration::where('activity_id', $rosterId)
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->delete();

        session()->flash('roster_message', 'Roster removed from your schedule.');
    }

    public function render()
    {
        $user = auth()->user();
        $tenantId = $user ? ($user->getActiveTenantId() ?? $user->tenant_id) : 0;
        $now = Carbon::now('UTC');

        $rosters = Activity::where('tenant_id', $tenantId)
            ->where('type', 'curated_roster')
            ->where('is_active', true)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->with(['legs.aircraftType', 'legs.airframe'])
            ->withCount('legs')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $userRegistrations = ActivityRegistration::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->with(['legProgress'])
            ->get()
            ->keyBy('activity_id');

        return view('livewire.pilot.curated-rosters', [
            'rosters'           => $rosters,
            'userRegistrations' => $userRegistrations,
        ])->layout('layouts.app');
    }
}
