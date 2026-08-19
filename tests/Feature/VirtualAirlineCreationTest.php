<?php

namespace Tests\Feature;

use App\Jobs\SendNewVirtualAirlinePendingApprovalEmailJob;
use App\Jobs\SendVirtualAirlineApprovedEmailJob;
use App\Models\Airport;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Rank;
use App\Models\TenantHub;
use App\Livewire\MasterAdminDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VirtualAirlineCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Master Admin']);
        Role::firstOrCreate(['name' => 'VA Owner']);
        Role::firstOrCreate(['name' => 'Pilot']);

        // Create test airports for base hubs
        Airport::firstOrCreate(
            ['icao' => 'EGLL'],
            ['name' => 'London Heathrow', 'lat' => 51.4700, 'lon' => -0.4543]
        );
        Airport::firstOrCreate(
            ['icao' => 'EHAM'],
            ['name' => 'Amsterdam Schiphol', 'lat' => 52.3105, 'lon' => 4.7683]
        );
    }

    public function test_master_admin_can_create_virtual_airline_via_livewire(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Master Admin');

        Livewire::actingAs($admin)
            ->test(MasterAdminDashboard::class)
            ->call('openCreateVaModal')
            ->assertSet('showCreateVaModal', true)
            ->set('vaName', 'Speedbird Virtual')
            ->set('vaIcao', 'BAW')
            ->set('vaBaseHubIcao', 'EGLL')
            ->set('vaAccentColor', '#00205b')
            ->set('vaBgColor', '#111827')
            ->set('vaSimbriefFormat', 'baw')
            ->call('createVirtualAirline')
            ->assertHasNoErrors()
            ->assertSet('showCreateVaModal', false)
            ->assertSee('Speedbird Virtual');

        $tenant = Tenant::where('icao', 'BAW')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Speedbird Virtual', $tenant->name);
        $this->assertEquals('active', $tenant->status);
        $this->assertTrue((bool) $tenant->is_approved);
        $this->assertNull($tenant->domain);

        // Hub created
        $hub = TenantHub::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($hub);
        $this->assertEquals(1, $hub->is_base);

        // Default Ranks created
        $this->assertEquals(3, Rank::where('tenant_id', $tenant->id)->count());
        $this->assertTrue(Rank::where('tenant_id', $tenant->id)->where('name', 'Cadet')->exists());
        $this->assertTrue(Rank::where('tenant_id', $tenant->id)->where('name', 'First Officer')->exists());
        $this->assertTrue(Rank::where('tenant_id', $tenant->id)->where('name', 'Captain')->exists());
    }

    public function test_duplicate_icao_code_is_rejected_on_va_creation(): void
    {
        Tenant::create([
            'name' => 'Existing EasyJet',
            'icao' => 'EZY',
            'accent_color' => '#f97316',
            'bg_color' => '#1e1e1e',
            'is_approved' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('onboarding.create-va'), [
            'name' => 'Another EasyJet Airline',
            'icao' => 'ezy', // Lowercase duplicate check
            'base_hub_icao' => 'EGLL',
            'accent_color' => '#ff6600',
            'bg_color' => '#111111',
            'default_simbrief_ofp_format' => 'ezy',
        ]);

        $response->assertSessionHasErrors(['icao']);
        $this->assertEquals(1, Tenant::whereRaw('UPPER(icao) = ?', ['EZY'])->count());
    }

    public function test_pilot_creating_va_dispatches_admin_pending_approval_email_job(): void
    {
        Queue::fake();

        $user = User::factory()->create(['tenant_id' => null]);
        $user->assignRole('Pilot');

        $response = $this->actingAs($user)->post(route('onboarding.create-va'), [
            'name' => 'Royal Dutch Virtual',
            'icao' => 'KLM',
            'base_hub_icao' => 'EHAM',
            'accent_color' => '#00a1de',
            'bg_color' => '#0f172a',
            'default_simbrief_ofp_format' => 'lido',
        ]);

        $response->assertRedirect(route('dashboard'));

        $tenant = Tenant::where('icao', 'KLM')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('pending', $tenant->status);
        $this->assertFalse((bool) $tenant->is_approved);
        $this->assertEquals($user->id, $tenant->created_by);

        Queue::assertPushed(SendNewVirtualAirlinePendingApprovalEmailJob::class, function ($job) use ($tenant, $user) {
            return $job->tenant->id === $tenant->id && $job->creator->id === $user->id;
        });
    }

    public function test_master_admin_can_approve_pending_virtual_airline(): void
    {
        Queue::fake();

        $creator = User::factory()->create(['email' => 'creator@airline.test']);
        $tenant = Tenant::create([
            'name' => 'Lufthansa Virtual',
            'icao' => 'DLH',
            'accent_color' => '#ffcc00',
            'bg_color' => '#001b3d',
            'is_approved' => false,
            'status' => 'pending',
            'created_by' => $creator->id,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Master Admin');

        Livewire::actingAs($admin)
            ->test(MasterAdminDashboard::class)
            ->call('approveVirtualAirline', $tenant->id)
            ->assertSee('approved successfully');

        $tenantFresh = $tenant->fresh();
        $this->assertEquals('active', $tenantFresh->status);
        $this->assertTrue((bool) $tenantFresh->is_approved);
        $this->assertEquals($admin->id, $tenantFresh->approved_by);
        $this->assertNotNull($tenantFresh->approved_at);

        Queue::assertPushed(SendVirtualAirlineApprovedEmailJob::class, function ($job) use ($tenant, $creator) {
            return $job->tenant->id === $tenant->id && $job->owner->id === $creator->id;
        });
    }

    public function test_virtual_airline_creation_validates_required_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('onboarding.create-va'), [
            'name' => '',
            'icao' => '',
            'base_hub_icao' => 'INVALID_CODE',
            'accent_color' => '',
            'bg_color' => '',
            'default_simbrief_ofp_format' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'icao', 'base_hub_icao', 'accent_color', 'bg_color', 'default_simbrief_ofp_format']);
    }

    public function test_user_can_switch_active_airline(): void
    {
        $tenant1 = Tenant::create(['name' => 'Airline One', 'icao' => 'ONE', 'accent_color' => '#f97316', 'bg_color' => '#1e1e1e', 'is_approved' => true, 'status' => 'active']);
        $tenant2 = Tenant::create(['name' => 'Airline Two', 'icao' => 'TWO', 'accent_color' => '#00a1de', 'bg_color' => '#0f172a', 'is_approved' => true, 'status' => 'active']);

        $user = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user->userAirlines()->create(['tenant_id' => $tenant1->id, 'callsign' => 'ONE1001', 'rank' => 'Cadet']);
        $user->userAirlines()->create(['tenant_id' => $tenant2->id, 'callsign' => 'TWO1001', 'rank' => 'Cadet']);

        $response = $this->actingAs($user)->post(route('session.switch-airline'), [
            'tenant_id' => $tenant2->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($tenant2->id, session('active_airline_id'));
        $this->assertEquals($tenant2->id, $user->fresh()->tenant_id);
    }
}
