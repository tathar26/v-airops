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
        
        $this->tenant = Tenant::create(['name' => 'Demo VA', 'domain' => 'demo.vops.test']);
        
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
        // 1. Get or Dispatch Active Flight
        $response = $this->actingAs($this->user)->getJson('/api/v1/flights/active');
        $response->assertStatus(200);
        $flightId = $response->json('id');
        $this->assertNotNull($flightId);

        // 2. Position Ping
        $response = $this->actingAs($this->user)->postJson('/api/v1/acars/position', [
            'flight_id' => $flightId,
            'latitude' => 51.47,
            'longitude' => -0.45,
            'altitude_ft' => 10000,
            'ground_speed_kt' => 250,
            'indicated_airspeed_kt' => 240,
            'vertical_speed_fpm' => 1500,
            'pitch_deg' => 2.5,
            'bank_deg' => 0.0,
            'heading_deg' => 270,
            'fuel_qty_kg' => 6000.0,
            'flight_phase' => 'climb',
        ]);
        $response->assertStatus(200);

        // 3. Submit PIREP
        $response = $this->actingAs($this->user)->postJson('/api/v1/pireps/submit', [
            'flight_id' => $flightId,
            'block_time_minutes' => 75,
            'fuel_used_kg' => 1800.0,
            'touchdown_fpm' => -150.0,
        ]);
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('acars_pireps', [
            'flight_id' => $flightId,
            'touchdown_fpm' => -150.0,
        ]);
    }
}
