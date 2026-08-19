<?php

namespace Tests\Feature;

use App\Jobs\PurgeUnflownBookingsJob;
use App\Models\Booking;
use App\Models\Route;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeUnflownBookingsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_job_deletes_unflown_bookings_older_than_24_hours(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.vops.test',
            'icao' => 'TST',
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $route = Route::create([
            'tenant_id' => $tenant->id,
            'flight_number' => 'TST101',
            'departure_icao' => 'EGLL',
            'arrival_icao' => 'LFPG',
            'block_time' => '01:15:00',
        ]);

        // Old booking (>24h old)
        $oldBooking = Booking::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'route_id' => $route->id,
            'status' => 'pending',
        ]);
        $oldBooking->timestamps = false;
        $oldBooking->created_at = now()->subHours(25);
        $oldBooking->save();

        // Recent booking (<24h old)
        $recentBooking = Booking::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'route_id' => $route->id,
            'status' => 'dispatched',
        ]);

        PurgeUnflownBookingsJob::dispatchSync();

        $this->assertDatabaseMissing('bookings', ['id' => $oldBooking->id]);
        $this->assertDatabaseHas('bookings', ['id' => $recentBooking->id]);
    }

    public function test_artisan_command_purges_unflown_bookings(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.vops.test',
            'icao' => 'TST',
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $route = Route::create([
            'tenant_id' => $tenant->id,
            'flight_number' => 'TST101',
            'departure_icao' => 'EGLL',
            'arrival_icao' => 'LFPG',
            'block_time' => '01:15:00',
        ]);

        $oldBooking = Booking::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'route_id' => $route->id,
            'status' => 'pending',
        ]);
        $oldBooking->timestamps = false;
        $oldBooking->created_at = now()->subHours(30);
        $oldBooking->save();

        $this->artisan('bookings:purge-unflown')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('bookings', ['id' => $oldBooking->id]);
    }
}
