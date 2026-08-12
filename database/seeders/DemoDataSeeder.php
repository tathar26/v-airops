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

        $a320 = AircraftType::firstOrCreate([
            'tenant_id' => $tenant->id,
            'code' => 'A320',
        ], [
            'name' => 'Airbus A320-200'
        ]);

        $airframe1 = Airframe::firstOrCreate([
            'tenant_id' => $tenant->id,
            'registration' => 'G-DEMO',
        ], [
            'aircraft_type_id' => $b738->id,
            'name' => 'City of London'
        ]);

        $airframe2 = Airframe::firstOrCreate([
            'tenant_id' => $tenant->id,
            'registration' => 'F-DEMO',
        ], [
            'aircraft_type_id' => $a320->id,
            'name' => 'City of Paris'
        ]);

        // 7. Create Multiple Routes
        $routes = [
            ['flight_number' => 'DEMO101', 'departure_icao' => 'EGLL', 'arrival_icao' => 'EHAM', 'block_time' => '01:15:00'],
            ['flight_number' => 'DEMO102', 'departure_icao' => 'EHAM', 'arrival_icao' => 'EDDF', 'block_time' => '01:05:00'],
            ['flight_number' => 'DEMO103', 'departure_icao' => 'EDDF', 'arrival_icao' => 'LFPG', 'block_time' => '01:25:00'],
            ['flight_number' => 'DEMO104', 'departure_icao' => 'LFPG', 'arrival_icao' => 'LEMD', 'block_time' => '02:10:00'],
            ['flight_number' => 'DEMO105', 'departure_icao' => 'LEMD', 'arrival_icao' => 'LIRF', 'block_time' => '02:30:00'],
        ];

        foreach ($routes as $routeData) {
            \App\Models\Route::firstOrCreate([
                'tenant_id' => $tenant->id,
                'flight_number' => $routeData['flight_number'],
            ], [
                'departure_icao' => $routeData['departure_icao'],
                'arrival_icao' => $routeData['arrival_icao'],
                'block_time' => $routeData['block_time']
            ]);
        }

        // 8. Create More Pilots
        $pilots = [];
        for ($i = 1; $i <= 5; $i++) {
            $newPilot = User::firstOrCreate([
                'email' => "pilot{$i}@demo.vops.test",
            ], [
                'name' => "Line Pilot {$i}",
                'password' => bcrypt('password'),
                'tenant_id' => $tenant->id,
            ]);
            $newPilot->assignRole($pilotRole);
            $pilots[] = $newPilot;
        }

        // Add original test pilot to list
        $pilots[] = $pilot;

        // 9. Create PIREPs
        $statuses = ['accepted', 'rejected', 'pending'];
        foreach ($pilots as $p) {
            // Give each pilot 3 PIREPs
            for ($j = 0; $j < 3; $j++) {
                $route = $routes[array_rand($routes)];
                $airframe = rand(0, 1) ? $airframe1 : $airframe2;
                
                \App\Models\Pirep::firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'user_id' => $p->id,
                    'flight_number' => $route['flight_number'] . '-' . $j,
                ], [
                    'departure_icao' => $route['departure_icao'],
                    'arrival_icao' => $route['arrival_icao'],
                    'aircraft_type_id' => $airframe->aircraft_type_id,
                    'airframe_id' => $airframe->id,
                    'status' => $statuses[array_rand($statuses)],
                    'score' => rand(50, 100),
                    'landing_rate' => rand(-50, -500),
                    'flight_time' => rand(60, 180), // minutes
                    'fuel_used' => rand(2000, 8000),
                    'distance' => rand(200, 1500),
                    'submitted_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }
}
