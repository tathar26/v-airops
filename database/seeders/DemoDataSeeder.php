<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\AircraftType;
use App\Models\Airframe;
use App\Models\Route;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles
        $masterAdminRole = Role::firstOrCreate(['name' => 'Master Admin']);
        $vaOwnerRole = Role::firstOrCreate(['name' => 'VA Owner']);
        $pilotRole = Role::firstOrCreate(['name' => 'Pilot']);

        // 2. Create Master Admin
        $admin = User::firstOrCreate([
            'email' => 'admin@vops.test',
        ], [
            'name' => 'System Admin',
            'password' => bcrypt('password'),
            'tenant_id' => null,
        ]);
        $admin->assignRole($masterAdminRole);

        // 3. Create a Demo Tenant
        $tenant = Tenant::firstOrCreate([
            'domain' => 'demo.vops.test',
        ], [
            'name' => 'Demo Virtual Airline',
            'accent_color' => '#f97316' // Orange to match vAMSYS
        ]);

        // 4. Create VA Owner
        $owner = User::firstOrCreate([
            'email' => 'owner@demo.vops.test',
        ], [
            'name' => 'VA Owner',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
        ]);
        $owner->assignRole($vaOwnerRole);

        // 5. Create a Pilot
        $pilot = User::firstOrCreate([
            'email' => 'pilot@demo.vops.test',
        ], [
            'name' => 'Test Pilot',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
        ]);
        $pilot->assignRole($pilotRole);

        // 6. Create Fleet Data
        $b738 = AircraftType::firstOrCreate([
            'tenant_id' => $tenant->id,
            'code' => 'B738',
        ], [
            'name' => 'Boeing 737-800'
        ]);

        Airframe::firstOrCreate([
            'tenant_id' => $tenant->id,
            'registration' => 'G-DEMO',
        ], [
            'aircraft_type_id' => $b738->id,
            'name' => 'City of London'
        ]);

        // 7. Create a Route
        Route::firstOrCreate([
            'tenant_id' => $tenant->id,
            'flight_number' => 'DEMO101',
        ], [
            'departure_icao' => 'EGLL',
            'arrival_icao' => 'EHAM',
            'block_time' => '01:15:00'
        ]);
    }
}
