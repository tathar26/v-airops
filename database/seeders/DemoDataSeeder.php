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
            'first_name' => 'System',
            'last_name' => 'Admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
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
            'first_name' => 'VA',
            'last_name' => 'Owner',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'tenant_id' => $tenant->id,
        ]);
        $owner->assignRole($vaOwnerRole);

        // 5. Create a Pilot
        $pilot = User::firstOrCreate([
            'email' => 'pilot@demo.vops.test',
        ], [
            'name' => 'Test Pilot',
            'first_name' => 'Test',
            'last_name' => 'Pilot',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
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

        $createdRoutes = [];
        foreach ($routes as $routeData) {
            $createdRoutes[] = Route::firstOrCreate([
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
                'first_name' => 'Line',
                'last_name' => "Pilot {$i}",
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ]);
            $newPilot->assignRole($pilotRole);
            $pilots[] = $newPilot;
        }

        // Add original test pilot to list
        $pilots[] = $pilot;

        // 9. Create PIREPs
        $statuses = ['Accepted', 'Rejected', 'Submitted'];
        foreach ($pilots as $p) {
            if ($p->pireps()->count() >= 3) {
                continue;
            }

            for ($j = 0; $j < 3; $j++) {
                /** @var Route $route */
                $route = $createdRoutes[array_rand($createdRoutes)];
                $airframe = rand(0, 1) ? $airframe1 : $airframe2;
                
                \App\Models\Pirep::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $p->id,
                    'route_id' => $route->id,
                    'airframe_id' => $airframe->id,
                    'status' => $statuses[array_rand($statuses)],
                    'points_awarded' => rand(50, 100),
                    'touchdown_rate_fpm' => rand(-50, -500),
                    'flight_time' => rand(60, 180), // minutes
                    'fuel_used' => rand(2000, 8000),
                    'distance_nm' => rand(200, 1500),
                    'created_at' => now()->subDays(rand(1, 30))->subHours(rand(1, 12)),
                ]);
            }
        }
    }
}
