<?php

namespace App\Livewire\Pilot;

use App\Models\Activity;
use App\Models\ActivityRegistration;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ActivitiesList extends Component
{
    use WithPagination;

    public string $tab = 'active'; // active, my, events, tours, community
    public string $search = '';

    public function setTab(string $newTab)
    {
        $this->tab = $newTab;
        $this->resetPage();
    }

    public function register(int $activityId)
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        $activity = Activity::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->findOrFail($activityId);

        if (!$activity->isRegistrationOpen()) {
            session()->flash('activity_error', 'Registration is currently closed for this activity.');
            return;
        }

        ActivityRegistration::firstOrCreate(
            [
                'activity_id' => $activity->id,
                'user_id'     => $user->id,
            ],
            [
                'tenant_id'     => $tenantId,
                'status'        => 'in_progress',
                'registered_at' => Carbon::now('UTC'),
            ]
        );

        session()->flash('activity_success', "You have registered for [{$activity->name}]! Good luck, Captain!");
    }

    public function unregister(int $activityId)
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        $registration = ActivityRegistration::where('activity_id', $activityId)
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($registration) {
            $registration->delete();
            session()->flash('activity_success', 'You have been unregistered from the activity.');
        }
    }

    public function render()
    {
        $user = auth()->user();
        $tenantId = $user ? ($user->getActiveTenantId() ?? $user->tenant_id) : 0;
        $now = Carbon::now('UTC');

        $query = Activity::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('show_from')->orWhere('show_from', '<=', $now);
            })
            ->withCount(['legs', 'registrations']);

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        if ($this->tab === 'my') {
            $query->whereHas('registrations', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($this->tab === 'events') {
            $query->whereIn('type', ['event', 'slotted_event', 'focus_airport']);
        } elseif ($this->tab === 'tours') {
            $query->whereIn('type', ['tour', 'roster']);
        } elseif ($this->tab === 'community') {
            $query->whereIn('type', ['community_goal', 'community_challenge']);
        } else {
            // 'active': currently ongoing or upcoming within 14 days
            $query->where('end_at', '>=', $now);
        }

        $activities = $query->orderBy('start_at', 'asc')->paginate(9);

        // Fetch pilot registrations mapped by activity_id for quick badge checks
        $userRegistrations = ActivityRegistration::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->with(['legProgress'])
            ->get()
            ->keyBy('activity_id');

        return view('livewire.pilot.activities-list', [
            'activities'        => $activities,
            'userRegistrations' => $userRegistrations,
        ])->layout('layouts.app');
    }
}
