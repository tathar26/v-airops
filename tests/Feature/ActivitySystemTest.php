<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Activity;
use App\Models\ActivityLeg;
use App\Models\ActivityWave;
use App\Models\ActivitySlot;
use App\Models\ActivityRegistration;
use App\Models\ActivityLegProgress;
use App\Models\ActivityContribution;
use App\Models\Pirep;
use App\Services\ActivityService;
use Livewire\Livewire;
use App\Livewire\Admin\ActivityManager;
use App\Livewire\Pilot\ActivitiesList;
use App\Livewire\Pilot\ActivityDetail;
use App\Livewire\Pilot\CuratedRosters;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivitySystemTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;
    protected User $pilotUser;
    protected ActivityService $activityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Atlantic Airways',
            'icao' => 'AAR',
            'domain' => 'atlantic.v-ops.local',
        ]);

        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_system_admin' => true,
        ]);

        $this->pilotUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_system_admin' => false,
        ]);

        $this->activityService = new ActivityService();
    }

    public function test_admin_can_render_activity_manager(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ActivityManager::class)
            ->assertStatus(200)
            ->assertSee('Activity Operations')
            ->set('type', 'tour')
            ->set('name', 'Mediterranean Grand Tour')
            ->set('description', 'A sun-drenched multi-leg flight across Southern Europe.')
            ->call('saveActivity')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activities', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Mediterranean Grand Tour',
            'type' => 'tour',
        ]);
    }

    public function test_pilot_can_browse_published_activities(): void
    {
        $activity = Activity::create([
            'tenant_id'   => $this->tenant->id,
            'name'        => 'Alpine Ski Run',
            'slug'        => 'alpine-ski-run',
            'type'        => 'event',
            'is_active'   => true,
            'start_at'    => now()->subDay(),
            'end_at'      => now()->addDays(7),
            'description' => 'Winter fly-in to Geneva and Zurich.',
            'author_id'   => $this->adminUser->id,
        ]);

        $this->actingAs($this->pilotUser);

        Livewire::test(ActivitiesList::class)
            ->assertStatus(200)
            ->assertSee('Alpine Ski Run');
    }

    public function test_pilot_can_register_and_unregister_for_activity(): void
    {
        $activity = Activity::create([
            'tenant_id'             => $this->tenant->id,
            'name'                  => 'Transatlantic Flyout',
            'slug'                  => 'transatlantic-flyout',
            'type'                  => 'event',
            'is_active'             => true,
            'start_at'              => now()->subDay(),
            'end_at'                => now()->addDays(7),
            'registration_required' => true,
            'author_id'             => $this->adminUser->id,
        ]);

        $this->actingAs($this->pilotUser);

        Livewire::test(ActivityDetail::class, ['activity' => $activity])
            ->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activity_registrations', [
            'activity_id' => $activity->id,
            'user_id'     => $this->pilotUser->id,
            'status'      => 'active',
        ]);

        Livewire::test(ActivityDetail::class, ['activity' => $activity])
            ->call('unregister')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('activity_registrations', [
            'activity_id' => $activity->id,
            'user_id'     => $this->pilotUser->id,
        ]);
    }

    public function test_tour_sequential_leg_progression_via_pirep(): void
    {
        $tour = Activity::create([
            'tenant_id'             => $this->tenant->id,
            'name'                  => 'Island Hopper Tour',
            'slug'                  => 'island-hopper-tour',
            'type'                  => 'tour',
            'is_active'             => true,
            'start_at'              => now()->subDay(),
            'end_at'                => now()->addDays(7),
            'registration_required' => true,
            'points_value'          => 500,
            'point_award_mode'      => 'all_on_completion',
            'author_id'             => $this->adminUser->id,
        ]);

        $leg1 = ActivityLeg::create([
            'activity_id' => $tour->id,
            'sequence'    => 1,
            'dep_icao'    => 'LPPT',
            'arr_icao'    => 'LPMA',
        ]);

        $leg2 = ActivityLeg::create([
            'activity_id' => $tour->id,
            'sequence'    => 2,
            'dep_icao'    => 'LPMA',
            'arr_icao'    => 'LPPS',
        ]);

        // Register pilot
        ActivityRegistration::create([
            'tenant_id'     => $this->tenant->id,
            'activity_id'   => $tour->id,
            'user_id'       => $this->pilotUser->id,
            'status'        => 'active',
            'registered_at' => now(),
        ]);

        // Flight 1 matching leg 1
        $pirep1 = Pirep::create([
            'tenant_id'            => $this->tenant->id,
            'user_id'              => $this->pilotUser->id,
            'origin_airport'       => 'LPPT',
            'destination_airport'  => 'LPMA',
            'departure_airport_id' => 1,
            'arrival_airport_id'   => 2,
            'status'               => 'approved',
            'score'                => 98,
            'flight_time'          => 95,
            'distance'             => 520,
            'passengers'           => 140,
            'cargo_weight'         => 2000,
            'off_block_time'       => now()->subHours(3),
            'on_block_time'        => now()->subHours(1),
        ]);

        $this->activityService->evaluatePirep($pirep1);

        $this->assertDatabaseHas('activity_leg_progress', [
            'activity_id'     => $tour->id,
            'activity_leg_id' => $leg1->id,
            'user_id'         => $this->pilotUser->id,
            'status'          => 'completed',
            'pirep_id'        => $pirep1->id,
        ]);

        // Flight 2 matching leg 2
        $pirep2 = Pirep::create([
            'tenant_id'            => $this->tenant->id,
            'user_id'              => $this->pilotUser->id,
            'origin_airport'       => 'LPMA',
            'destination_airport'  => 'LPPS',
            'departure_airport_id' => 2,
            'arrival_airport_id'   => 3,
            'status'               => 'approved',
            'score'                => 100,
            'flight_time'          => 30,
            'distance'             => 45,
            'passengers'           => 50,
            'cargo_weight'         => 500,
            'off_block_time'       => now()->subMinutes(50),
            'on_block_time'        => now()->subMinutes(20),
        ]);

        $this->activityService->evaluatePirep($pirep2);

        $this->assertDatabaseHas('activity_leg_progress', [
            'activity_id'     => $tour->id,
            'activity_leg_id' => $leg2->id,
            'user_id'         => $this->pilotUser->id,
            'status'          => 'completed',
            'pirep_id'        => $pirep2->id,
        ]);

        // Check registration completion status
        $reg = ActivityRegistration::where('activity_id', $tour->id)
            ->where('user_id', $this->pilotUser->id)
            ->first();

        $this->assertEquals('completed', $reg->status);
        $this->assertNotNull($reg->completed_at);
        $this->assertEquals(500, $reg->points_awarded);
    }

    public function test_community_goal_tracks_contributions(): void
    {
        $goal = Activity::create([
            'tenant_id'         => $this->tenant->id,
            'name'              => 'Million Passenger Challenge',
            'slug'              => 'million-passenger-challenge',
            'type'              => 'community_goal',
            'is_active'         => true,
            'start_at'          => now()->subDay(),
            'end_at'            => now()->addDays(7),
            'community_metric'  => 'passengers',
            'community_target'  => 1000000,
            'community_current' => 0,
            'author_id'         => $this->adminUser->id,
        ]);

        $pirep = Pirep::create([
            'tenant_id'            => $this->tenant->id,
            'user_id'              => $this->pilotUser->id,
            'origin_airport'       => 'EGLL',
            'destination_airport'  => 'KJFK',
            'departure_airport_id' => 1,
            'arrival_airport_id'   => 2,
            'status'               => 'approved',
            'passengers'           => 280,
            'score'                => 95,
            'flight_time'          => 450,
            'distance'             => 3450,
            'off_block_time'       => now()->subHours(8),
            'on_block_time'        => now()->subHours(1),
        ]);

        $this->activityService->evaluatePirep($pirep);

        $this->assertDatabaseHas('activity_contributions', [
            'activity_id'   => $goal->id,
            'user_id'       => $this->pilotUser->id,
            'pirep_id'      => $pirep->id,
            'metric_value'  => 280,
        ]);

        $goal->refresh();
        $this->assertEquals(280, $goal->community_current);
    }

    public function test_slotted_event_slot_booking(): void
    {
        $event = Activity::create([
            'tenant_id'               => $this->tenant->id,
            'name'                    => 'London City Flyout',
            'slug'                    => 'london-city-flyout',
            'type'                    => 'slotted_event',
            'is_active'               => true,
            'start_at'                => now()->subDay(),
            'end_at'                  => now()->addDays(7),
            'slotted_callsign_system' => 'username_a',
            'author_id'               => $this->adminUser->id,
        ]);

        $wave = ActivityWave::create([
            'activity_id'           => $event->id,
            'name'                  => 'Wave Alpha',
            'start_time'            => now()->addDay()->setHour(18)->setMinute(0),
            'end_time'              => now()->addDay()->setHour(19)->setMinute(0),
            'slot_interval_minutes' => 15,
        ]);

        $slot = ActivitySlot::create([
            'activity_id'      => $event->id,
            'activity_wave_id' => $wave->id,
            'slot_time'        => now()->addDay()->setHour(18)->setMinute(15),
            'status'           => 'available',
        ]);

        $this->actingAs($this->pilotUser);

        Livewire::test(ActivityDetail::class, ['activity' => $event])
            ->call('claimSlot', $slot->id, 'vatsim')
            ->assertHasNoErrors();

        $slot->refresh();
        $this->assertEquals('booked', $slot->status);
        $this->assertEquals($this->pilotUser->id, $slot->user_id);
        $this->assertEquals('vatsim', $slot->network);
        $this->assertNotEmpty($slot->assigned_callsign);
    }
}
