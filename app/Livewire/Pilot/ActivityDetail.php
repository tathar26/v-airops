<?php

namespace App\Livewire\Pilot;

use App\Models\Activity;
use App\Models\ActivityContribution;
use App\Models\ActivityLeg;
use App\Models\ActivityRegistration;
use App\Models\ActivitySlot;
use App\Models\ActivityWave;
use App\Models\Route;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ActivityDetail extends Component
{
    public int $activityId;
    public string $selectedNetwork = 'vatsim';

    public function mount(int $activity)
    {
        $this->activityId = $activity;
    }

    public function register()
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        $act = Activity::where('tenant_id', $tenantId)->findOrFail($this->activityId);

        if (!$act->isRegistrationOpen()) {
            session()->flash('detail_error', 'Registration is currently closed for this activity.');
            return;
        }

        ActivityRegistration::firstOrCreate(
            [
                'activity_id' => $act->id,
                'user_id'     => $user->id,
            ],
            [
                'tenant_id'     => $tenantId,
                'status'        => 'in_progress',
                'registered_at' => Carbon::now('UTC'),
            ]
        );

        session()->flash('detail_success', 'You are now registered for this activity!');
    }

    public function unregister()
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        $reg = ActivityRegistration::where('activity_id', $this->activityId)
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($reg) {
            // Also release booked slot if slotted event
            if ($reg->slot_id) {
                $slot = ActivitySlot::find($reg->slot_id);
                if ($slot && $slot->user_id === $user->id) {
                    $slot->user_id = null;
                    $slot->booked_at = null;
                    $slot->callsign_suffix = null;
                    $slot->save();
                }
            }

            $reg->delete();
            session()->flash('detail_success', 'You have been unregistered.');
        }
    }

    public function bookSlot(int $slotId)
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        $slot = ActivitySlot::where('activity_id', $this->activityId)->findOrFail($slotId);

        if ($slot->isBooked() && $slot->user_id !== $user->id) {
            session()->flash('detail_error', 'This departure slot has already been claimed by another pilot.');
            return;
        }

        // Release any existing slot for this activity
        ActivitySlot::where('activity_id', $this->activityId)
            ->where('user_id', $user->id)
            ->update([
                'user_id'         => null,
                'booked_at'       => null,
                'callsign_suffix' => null,
            ]);

        // Generate callsign suffix based on event system
        $activity = $slot->activity;
        $suffix = match ($activity->slotted_callsign_system) {
            'username_a' => ltrim((string)$user->id, '0'),
            'username_b' => substr((string)$user->id, -2) . strtoupper(substr($user->name, 0, 2)),
            'generator'  => 'VA' . rand(10, 99),
            default      => (string)$user->id,
        };

        $slot->user_id = $user->id;
        $slot->booked_at = Carbon::now('UTC');
        $slot->callsign_suffix = $suffix;
        $slot->save();

        // Ensure registration exists and links slot
        $reg = ActivityRegistration::firstOrCreate(
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

        $reg->slot_id = $slot->id;
        $reg->save();

        session()->flash('detail_success', "Slot reserved at {$slot->slot_time->format('H:i')}z! Assigned Callsign Suffix: [{$suffix}].");
    }

    public function releaseSlot(int $slotId)
    {
        $user = auth()->user();
        $slot = ActivitySlot::where('activity_id', $this->activityId)->findOrFail($slotId);

        if ($slot->user_id === $user->id) {
            $slot->user_id = null;
            $slot->booked_at = null;
            $slot->callsign_suffix = null;
            $slot->save();

            $reg = ActivityRegistration::where('activity_id', $this->activityId)
                ->where('user_id', $user->id)
                ->first();
            if ($reg) {
                $reg->slot_id = null;
                $reg->save();
            }

            session()->flash('detail_success', 'Slot reservation released.');
        }
    }

    public function render()
    {
        $user = auth()->user();
        $tenantId = $user ? ($user->getActiveTenantId() ?? $user->tenant_id) : 0;

        $activity = Activity::where('tenant_id', $tenantId)
            ->with(['legs.route', 'legs.airframe', 'legs.aircraftType', 'waves.slots.user', 'teams.contributions'])
            ->findOrFail($this->activityId);

        $registration = ActivityRegistration::where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->with(['legProgress.pirep'])
            ->first();

        // Completed legs map
        $completedLegsMap = [];
        if ($registration) {
            foreach ($registration->legProgress as $prog) {
                if ($prog->is_valid) {
                    $completedLegsMap[$prog->activity_leg_id] = $prog;
                }
            }
        }

        // Leaderboard for community goals
        $topContributors = [];
        if ($activity->type === 'community_goal') {
            $topContributors = ActivityContribution::where('activity_id', $activity->id)
                ->with('user')
                ->select('user_id', DB::raw('SUM(metric_value) as total_metric'), DB::raw('SUM(points_awarded) as total_points'))
                ->groupBy('user_id')
                ->orderBy('total_metric', 'desc')
                ->take(10)
                ->get();
        }

        // Available departure slots filtered by selected network
        $networkSlots = [];
        if ($activity->type === 'slotted_event') {
            $networkSlots = ActivitySlot::where('activity_id', $activity->id)
                ->where('network', $this->selectedNetwork)
                ->with(['wave', 'user'])
                ->orderBy('slot_time', 'asc')
                ->get()
                ->groupBy('wave_id');
        }

        return view('livewire.pilot.activity-detail', [
            'activity'         => $activity,
            'registration'     => $registration,
            'completedLegsMap' => $completedLegsMap,
            'topContributors'  => $topContributors,
            'networkSlots'     => $networkSlots,
        ])->layout('layouts.app');
    }
}
