<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Rank;
use App\Models\PilotProfile;
use App\Models\Pirep;
use App\Services\RankProgressionService;
use App\Livewire\RankManager;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RankSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Airways',
            'icao' => 'TST',
            'domain' => 'test.v-ops.local',
        ]);

        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_system_admin' => true,
        ]);
    }

    public function test_default_ranks_are_seeded(): void
    {
        RankProgressionService::seedDefaultRanks($this->tenant);

        $this->assertDatabaseHas('ranks', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Cadet',
            'abbreviation' => 'Cdt',
            'is_honorary' => false,
            'is_default' => true,
            'position' => 1,
        ]);

        $this->assertDatabaseHas('ranks', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Staff Team',
            'abbreviation' => 'ST',
            'is_honorary' => true,
            'is_default' => true,
            'position' => 999,
        ]);
    }

    public function test_regular_rank_requires_all_four_milestones(): void
    {
        RankProgressionService::seedDefaultRanks($this->tenant);

        $cadet = Rank::where('tenant_id', $this->tenant->id)->where('name', 'Cadet')->first();

        $firstOfficer = Rank::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'First Officer',
            'abbreviation' => 'FO',
            'position' => 2,
            'min_hours' => 20,
            'min_points' => 1000,
            'min_bonus_points' => 100,
            'min_pireps' => 5,
            'is_honorary' => false,
            'is_default' => false,
            'image_path' => 'images/epaulettes/epaulette-02.png',
        ]);

        $pilot = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $profile = PilotProfile::create([
            'user_id' => $pilot->id,
            'tenant_id' => $this->tenant->id,
            'rank_id' => $cadet->id,
            'flight_time' => 25 * 60, // 25 hours (meets min_hours)
            'points' => 1200,         // meets min_points
            'bonus_points' => 50,     // FAILS: needs 100
        ]);

        // Create 6 accepted pireps
        for ($i = 0; $i < 6; $i++) {
            Pirep::create([
                'user_id' => $pilot->id,
                'tenant_id' => $this->tenant->id,
                'status' => 'accepted',
                'flight_number' => 'TST' . (100 + $i),
                'departure_airport' => 'KJFK',
                'arrival_airport' => 'KBOS',
            ]);
        }

        // Evaluate: should NOT upgrade because bonus_points (50) < 100
        RankProgressionService::evaluatePilotRank($profile);
        $profile->refresh();
        $this->assertEquals($cadet->id, $profile->rank_id);

        // Now meet bonus_points as well
        $profile->bonus_points = 150;
        $profile->save();

        RankProgressionService::evaluatePilotRank($profile);
        $profile->refresh();
        $this->assertEquals($firstOfficer->id, $profile->rank_id);
    }

    public function test_honorary_rank_preference_display(): void
    {
        RankProgressionService::seedDefaultRanks($this->tenant);

        $cadet = Rank::where('tenant_id', $this->tenant->id)->where('name', 'Cadet')->first();
        $staffRank = Rank::where('tenant_id', $this->tenant->id)->where('name', 'Staff Team')->first();

        $pilot = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $profile = PilotProfile::create([
            'user_id' => $pilot->id,
            'tenant_id' => $this->tenant->id,
            'rank_id' => $cadet->id,
            'honorary_rank_id' => $staffRank->id,
            'prefer_honorary_rank' => false,
        ]);

        // When prefer_honorary_rank is false -> display regular rank
        $this->assertEquals('Cadet', $pilot->getDisplayRank($this->tenant->id));
        $this->assertFalse($pilot->isDisplayingHonoraryRank($this->tenant->id));

        // When prefer_honorary_rank is true -> display honorary rank
        $profile->prefer_honorary_rank = true;
        $profile->save();

        $this->assertEquals('Staff Team', $pilot->getDisplayRank($this->tenant->id));
        $this->assertTrue($pilot->isDisplayingHonoraryRank($this->tenant->id));
    }

    public function test_default_ranks_cannot_be_deleted(): void
    {
        RankProgressionService::seedDefaultRanks($this->tenant);
        $cadet = Rank::where('tenant_id', $this->tenant->id)->where('name', 'Cadet')->first();

        $this->actingAs($this->adminUser);

        Livewire::test(RankManager::class)
            ->call('deleteRank', $cadet->id);

        // Cadet must still exist in DB
        $this->assertDatabaseHas('ranks', [
            'id' => $cadet->id,
            'name' => 'Cadet',
        ]);
    }

    public function test_user_active_rank_name_accessor_and_get_rank_for_airline(): void
    {
        RankProgressionService::seedDefaultRanks($this->tenant);
        $cadet = Rank::where('tenant_id', $this->tenant->id)->where('name', 'Cadet')->first();

        $pilot = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        PilotProfile::create([
            'user_id' => $pilot->id,
            'tenant_id' => $this->tenant->id,
            'rank_id' => $cadet->id,
        ]);

        $this->assertEquals($cadet->id, $pilot->getRankForAirline($this->tenant->id)?->id);
        $this->assertEquals('Cadet', $pilot->active_rank_name);
        $this->assertNotEmpty($pilot->getDisplayRankImageUrl($this->tenant->id));
    }

    public function test_rank_manager_can_render_modal_for_default_rank(): void
    {
        RankProgressionService::seedDefaultRanks($this->tenant);
        $cadet = Rank::where('tenant_id', $this->tenant->id)->where('name', 'Cadet')->first();

        $this->actingAs($this->adminUser);

        Livewire::test(RankManager::class)
            ->call('editRank', $cadet->id)
            ->assertSet('is_default', true)
            ->assertSee('Cadet')
            ->assertStatus(200);
    }

    public function test_user_get_pilot_profile_helper(): void
    {
        RankProgressionService::seedDefaultRanks($this->tenant);
        $cadet = Rank::where('tenant_id', $this->tenant->id)->where('name', 'Cadet')->first();

        $pilot = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $profile = PilotProfile::create([
            'user_id' => $pilot->id,
            'tenant_id' => $this->tenant->id,
            'rank_id' => $cadet->id,
        ]);

        // Direct call with tenant id
        $this->assertEquals($profile->id, $pilot->getPilotProfile($this->tenant->id)?->id);

        // Direct call without arguments (resolves via tenant_id)
        $this->assertEquals($profile->id, $pilot->getPilotProfile()?->id);

        // Eager-loaded relation
        $loadedUser = User::with(['pilotProfiles.rank', 'pilotProfiles.honoraryRank'])->find($pilot->id);
        $this->assertTrue($loadedUser->relationLoaded('pilotProfiles'));
        $this->assertEquals($profile->id, $loadedUser->getPilotProfile()?->id);
        $this->assertEquals($profile->id, $loadedUser->getPilotProfile($this->tenant->id)?->id);
    }
}
