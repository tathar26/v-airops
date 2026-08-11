<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Route;
use App\Models\Pirep;
use App\Models\PilotProfile;
use App\Models\Airframe;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        if (!$tenant) {
            $this->command->warn('No tenant found. Please run DemoDataSeeder first.');
            return;
        }

        // 1. Create 5 Pilots
        $pilots = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = User::firstOrCreate(
                ['email' => "demo_pilot_{$i}@vops.test"],
                [
                    'name' => "Demo Pilot {$i}",
                    'password' => Hash::make('password'),
                    'tenant_id' => $tenant->id,
                ]
            );
            $user->assignRole('Pilot');

            PilotProfile::firstOrCreate(
                ['user_id' => $user->id, 'tenant_id' => $tenant->id],
                ['flight_time' => 0, 'points' => 0]
            );

            $pilots[] = $user;
        }

        // Add the primary pilot@demo.vops.test to the list of pilots if exists
        $primaryPilot = User::where('email', 'pilot@demo.vops.test')->first();
        if ($primaryPilot) {
            $pilots[] = $primaryPilot;
        }

        // 2. Ensure some Routes
        $routes = Route::where('tenant_id', $tenant->id)->get();
        if ($routes->count() < 10) {
            $fictitiousRoutes = [
                ['flight_number' => 'VOPS101', 'dep' => 'EGLL', 'arr' => 'EHAM', 'time' => 50],
                ['flight_number' => 'VOPS102', 'dep' => 'EHAM', 'arr' => 'EDDF', 'time' => 60],
                ['flight_number' => 'VOPS103', 'dep' => 'EDDF', 'arr' => 'LFPG', 'time' => 70],
                ['flight_number' => 'VOPS104', 'dep' => 'LFPG', 'arr' => 'LEMD', 'time' => 120],
                ['flight_number' => 'VOPS105', 'dep' => 'LEMD', 'arr' => 'LPPT', 'time' => 60],
                ['flight_number' => 'VOPS106', 'dep' => 'KJFK', 'arr' => 'EGLL', 'time' => 380],
                ['flight_number' => 'VOPS107', 'dep' => 'OMDB', 'arr' => 'VABB', 'time' => 180],
                ['flight_number' => 'VOPS108', 'dep' => 'YSSY', 'arr' => 'NZAA', 'time' => 190],
                ['flight_number' => 'VOPS109', 'dep' => 'RJAA', 'arr' => 'RKSI', 'time' => 140],
                ['flight_number' => 'VOPS110', 'dep' => 'WSSS', 'arr' => 'VTBS', 'time' => 135],
            ];

            foreach ($fictitiousRoutes as $r) {
                // Convert minutes to HH:MM:SS
                $hours = floor($r['time'] / 60);
                $minutes = $r['time'] % 60;
                $blockTimeStr = sprintf('%02d:%02d:00', $hours, $minutes);

                Route::firstOrCreate(
                    [
                        'flight_number' => $r['flight_number'],
                        'tenant_id' => $tenant->id
                    ],
                    [
                        'departure_icao' => $r['dep'],
                        'arrival_icao' => $r['arr'],
                        'block_time' => $blockTimeStr,
                    ]
                );
            }
            $routes = Route::where('tenant_id', $tenant->id)->get();
        }

        // 3. Ensure some Airframes
        $airframe = Airframe::where('tenant_id', $tenant->id)->first();
        if (!$airframe) {
            $this->command->warn('No airframes found. Skipping PIREP generation.');
            return;
        }

        // 4. Generate 50 PIREPs
        $this->command->info('Generating 50 PIREPs...');

        for ($i = 0; $i < 50; $i++) {
            $pilot = $pilots[array_rand($pilots)];
            $route = $routes->random();

            // Convert block time string (HH:MM:SS) to minutes
            $timeParts = explode(':', $route->block_time);
            $blockMinutes = 0;
            if (count($timeParts) >= 2) {
                $blockMinutes = ((int)$timeParts[0] * 60) + (int)$timeParts[1];
            } else {
                $blockMinutes = 60; // fallback
            }

            // Simulate flight time roughly around block time +/- 10 mins
            $flightTime = max(10, $blockMinutes + rand(-10, 10)); 
            
            // Generate some random landing rates
            $landingRate = rand(-800, -50); 
            
            $pirep = Pirep::create([
                'user_id' => $pilot->id,
                'tenant_id' => $tenant->id,
                'airframe_id' => $airframe->id,
                'route_id' => $route->id,
                'flight_time' => $flightTime,
                'touchdown_rate_fpm' => $landingRate,
                'status' => 'Submitted',
                'points_awarded' => 0,
                'created_at' => Carbon::now()->subDays(rand(0, 30)), 
            ]);

            // Update status to 'Accepted' which will trigger the PirepObserver to award points and rank
            // For a small percentage, set as Rejected for realistic stats
            $isRejected = (rand(1, 100) <= 5); // 5% rejected
            
            $pirep->update([
                'status' => $isRejected ? 'Rejected' : 'Accepted'
            ]);
        }

        $this->command->info('Demo Dashboard Data Seeded Successfully!');
    }
}
