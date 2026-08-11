<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\AircraftType;
use App\Models\Airframe;
use App\Models\Route;

class AcarsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::create(['name' => 'Demo VA', 'domain' => 'demo.vamsys.test']);
        
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        
        $this->aircraftType = AircraftType::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'B738',
            'name' => 'Boeing 737-800'
        ]);
        
        $this->airframe = Airframe::create([
            'tenant_id' => $this->tenant->id,
            'aircraft_type_id' => $this->aircraftType->id,
            'registration' => 'G-VAMS'
        ]);
        
        $this->route = Route::create([
            'tenant_id' => $this->tenant->id,
            'flight_number' => 'VAM123',
            'departure_icao' => 'EGLL',
            'arrival_icao' => 'EHAM',
            'block_time' => '01:15:00'
        ]);
    }

    public function test_acars_full_flight_flow()
    {
        // 1. Connect
        $response = $this->actingAs($this->user)->postJson('/api/acars/connect', [
            'route_id' => $this->route->id,
            'airframe_id' => $this->airframe->id,
        ]);
        
        $response->assertStatus(200);
        $pirepId = $response->json('pirep_id');
        $this->assertNotNull($pirepId);

        // 2. Telemetry
        $response = $this->actingAs($this->user)->postJson("/api/acars/{$pirepId}/telemetry", [
            'lat' => 51.47,
            'lon' => -0.45,
            'alt' => 10000,
            'gs' => 250,
            'phase' => 'climb'
        ]);
        
        $response->assertStatus(200);

        // 3. File PIREP
        $response = $this->actingAs($this->user)->postJson("/api/acars/{$pirepId}/file", [
            'block_fuel' => 5000,
            'zfw' => 55000,
            'touchdown_rate_fpm' => -150,
            'cost_index' => 15,
        ]);
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('pireps', [
            'id' => $pirepId,
            'status' => 'filed',
            'block_fuel' => 5000,
            'touchdown_rate_fpm' => -150
        ]);
    }
}
