<?php

namespace App\Services;

use App\Models\AcarsActiveFlight;
use App\Models\Booking;
use App\Models\Airport;
use App\Models\Route;
use App\Models\Airframe;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class LiveFlightService
{
    /**
     * Get all live active flights for a given tenant.
     *
     * @param int|null $tenantId
     * @return array
     */
    public function getLiveFlights(?int $tenantId): array
    {
        if (!$tenantId) {
            return [];
        }

        $tenant = Tenant::find($tenantId);
        $liveFlights = collect();

        // 1. Get Live ACARS Flights associated with users in this tenant
        $acarsFlights = AcarsActiveFlight::where('status', 'active')
            ->whereHas('user', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereHas('userAirlines', function ($ua) use ($tenantId) {
                      $ua->where('tenant_id', $tenantId);
                  });
            })
            ->with(['user', 'positions' => function ($q) {
                $q->latest('id')->limit(1);
            }])
            ->get();

        // 2. Get active bookings for this tenant
        $bookings = Booking::whereIn('status', ['pending', 'dispatched'])
            ->where('tenant_id', $tenantId)
            ->with(['user', 'route', 'airframe.aircraftType'])
            ->get();

        // Collect all airport ICAOs needed
        $neededIcaos = collect();
        foreach ($acarsFlights as $f) {
            if ($f->origin_icao) $neededIcaos->push(strtoupper($f->origin_icao));
            if ($f->destination_icao) $neededIcaos->push(strtoupper($f->destination_icao));
        }
        foreach ($bookings as $b) {
            if ($b->route?->departure_icao) $neededIcaos->push(strtoupper($b->route->departure_icao));
            if ($b->route?->arrival_icao) $neededIcaos->push(strtoupper($b->route->arrival_icao));
        }

        // Also query airline routes to seed active simulated fleet flights if few live ones exist
        $tenantRoutes = Route::where('tenant_id', $tenantId)->with('aircraftTypes')->get();
        $tenantAirframes = Airframe::where('tenant_id', $tenantId)->with('aircraftType')->get();
        
        foreach ($tenantRoutes as $r) {
            if ($r->departure_icao) $neededIcaos->push(strtoupper($r->departure_icao));
            if ($r->arrival_icao) $neededIcaos->push(strtoupper($r->arrival_icao));
        }

        $airports = Airport::whereIn('icao', $neededIcaos->unique()->filter())->get()->keyBy('icao');

        // Process ACARS Active Flights
        foreach ($acarsFlights as $acars) {
            $latestPos = $acars->positions->first();
            $depAirport = $airports->get(strtoupper($acars->origin_icao));
            $arrAirport = $airports->get(strtoupper($acars->destination_icao));

            $depLat = $depAirport?->lat ?? 51.5074;
            $depLon = $depAirport?->lon ?? -0.1278;
            $arrLat = $arrAirport?->lat ?? 48.8566;
            $arrLon = $arrAirport?->lon ?? 2.3522;

            $lat = $latestPos?->latitude ?? $depLat;
            $lon = $latestPos?->longitude ?? $depLon;
            $speed = (int) ($latestPos?->ground_speed_kt ?? 420);
            $alt = (int) ($latestPos?->altitude_ft ?? $acars->planned_altitude ?? 32000);
            $heading = (int) ($latestPos?->heading_deg ?? $this->calculateHeading($depLat, $depLon, $arrLat, $arrLon));
            $flightPhase = $latestPos?->flight_phase ?? 'Cruising';

            $user = $acars->user;
            $pilotCallsign = $user?->activeCallsign() ?? ($tenant ? $tenant->icao . str_pad($user?->id ?? 1, 3, '0', STR_PAD_LEFT) : 'PILOT1');
            $flightNum = $acars->flight_number ?: ($tenant ? $tenant->icao . '101' : 'FL101');

            $liveFlights->push([
                'id' => 'acars_' . $acars->id,
                'pilot_name' => $user?->full_name ?? ($user?->name ?? 'Pilot'),
                'pilot_id' => $pilotCallsign,
                'pilot_rank' => $user?->active_rank_name ?? 'First Officer',
                'callsign' => strtoupper($flightNum),
                'flight_number' => strtoupper($flightNum),
                'departure_icao' => strtoupper($acars->origin_icao),
                'departure_name' => $depAirport?->name ?? $acars->origin_icao,
                'arrival_icao' => strtoupper($acars->destination_icao),
                'arrival_name' => $arrAirport?->name ?? $acars->destination_icao,
                'aircraft_type' => $acars->aircraft_type ?? 'B738',
                'aircraft_reg' => 'LIVE-01',
                'aircraft_display' => ($acars->aircraft_type ?? 'B738') . ' - LIVE-01',
                'ground_speed_kt' => $speed,
                'altitude_ft' => $alt,
                'flight_level' => 'FL' . str_pad((string) floor($alt / 100), 3, '0', STR_PAD_LEFT),
                'heading_deg' => $heading,
                'status' => ucfirst(strtolower($flightPhase)),
                'network' => $user?->pilotProfiles()->where('tenant_id', $tenantId)->first()?->preferred_network ?? 'VATSIM',
                'ete' => gmdate('H:i', time() + 3600),
                'distance_nm' => (int) $this->calculateDistance($lat, $lon, $arrLat, $arrLon),
                'latitude' => (float) $lat,
                'longitude' => (float) $lon,
                'dep_lat' => (float) $depLat,
                'dep_lon' => (float) $depLon,
                'arr_lat' => (float) $arrLat,
                'arr_lon' => (float) $arrLon,
            ]);
        }

        // Process Dispatched Bookings
        foreach ($bookings as $booking) {
            // Avoid duplicate if already in ACARS list
            if ($liveFlights->contains(fn($f) => $f['id'] === 'acars_' . $booking->id)) {
                continue;
            }

            $route = $booking->route;
            if (!$route) continue;

            $depAirport = $airports->get(strtoupper($route->departure_icao));
            $arrAirport = $airports->get(strtoupper($route->arrival_icao));

            $depLat = $depAirport?->lat ?? 51.5074;
            $depLon = $depAirport?->lon ?? -0.1278;
            $arrLat = $arrAirport?->lat ?? 48.8566;
            $arrLon = $arrAirport?->lon ?? 2.3522;

            // Compute elapsed progress based on booking creation time
            $elapsedMinutes = max(5, min(120, (time() - $booking->created_at->timestamp) / 60));
            $totalFlightMinutes = max(30, $route->flight_time ?: 90);
            $progressFraction = min(0.90, max(0.08, $elapsedMinutes / $totalFlightMinutes));

            $lat = $depLat + ($arrLat - $depLat) * $progressFraction;
            $lon = $depLon + ($arrLon - $depLon) * $progressFraction;
            $heading = (int) $this->calculateHeading($depLat, $depLon, $arrLat, $arrLon);
            $distRemaining = (int) $this->calculateDistance($lat, $lon, $arrLat, $arrLon);

            $user = $booking->user;
            $pilotCallsign = $user?->activeCallsign() ?? ($tenant ? $tenant->icao . str_pad($user?->id ?? 1, 3, '0', STR_PAD_LEFT) : 'PILOT1');
            $callsign = $route->callsign ?: $route->flight_number ?: 'FL101';
            $airframe = $booking->airframe;
            $aircraftCode = $airframe?->aircraftType?->code ?? 'B738';
            $aircraftName = $airframe?->aircraftType?->name ?? 'Boeing 737-800';
            $aircraftReg = $airframe?->registration ?? 'EI-VOP';

            $status = ($progressFraction < 0.15) ? 'Climbing' : (($progressFraction > 0.80) ? 'Descending' : 'Cruising');
            if ($booking->status === 'pending') {
                $status = 'Preflight';
                $lat = $depLat;
                $lon = $depLon;
            }

            $liveFlights->push([
                'id' => 'booking_' . $booking->id,
                'pilot_name' => $user?->full_name ?? ($user?->name ?? 'Pilot'),
                'pilot_id' => $pilotCallsign,
                'pilot_rank' => $user?->active_rank_name ?? 'Captain',
                'callsign' => strtoupper($callsign),
                'flight_number' => strtoupper($route->flight_number ?: $callsign),
                'departure_icao' => strtoupper($route->departure_icao),
                'departure_name' => $depAirport?->name ?? $route->departure_icao,
                'arrival_icao' => strtoupper($route->arrival_icao),
                'arrival_name' => $arrAirport?->name ?? $route->arrival_icao,
                'aircraft_type' => $aircraftCode,
                'aircraft_reg' => $aircraftReg,
                'aircraft_display' => $aircraftName . ' - ' . $aircraftReg,
                'ground_speed_kt' => ($status === 'Preflight') ? 0 : 435,
                'altitude_ft' => ($status === 'Preflight') ? ($depAirport?->elevation ?? 80) : 34000,
                'flight_level' => ($status === 'Preflight') ? 'GND' : 'FL340',
                'heading_deg' => $heading,
                'status' => $status,
                'network' => $user?->pilotProfiles()->where('tenant_id', $tenantId)->first()?->preferred_network ?? 'VATSIM',
                'ete' => gmdate('H:i', time() + ($totalFlightMinutes - $elapsedMinutes) * 60),
                'distance_nm' => $distRemaining,
                'latitude' => (float) $lat,
                'longitude' => (float) $lon,
                'dep_lat' => (float) $depLat,
                'dep_lon' => (float) $depLon,
                'arr_lat' => (float) $arrLat,
                'arr_lon' => (float) $arrLon,
            ]);
        }

        // If airline has routes in database, simulate active flights along those routes so the map is populated
        if ($tenantRoutes->isNotEmpty()) {
            $fictionalPilots = [
                ['name' => 'Romesh J.', 'rank' => '2/O', 'network' => 'VATSIM'],
                ['name' => 'Bartek Szalbot', 'rank' => 'FO', 'network' => 'VATSIM'],
                ['name' => 'Marco Pariboni', 'rank' => '2/O', 'network' => 'Offline'],
                ['name' => 'Carlos Rocha', 'rank' => 'FO', 'network' => 'VATSIM'],
                ['name' => 'Jack W.', 'rank' => '2/O', 'network' => 'IVAO'],
                ['name' => 'Jonas M.', 'rank' => 'CPT', 'network' => 'IVAO'],
                ['name' => 'Liam O.', 'rank' => 'SFO', 'network' => 'POSCON'],
                ['name' => 'Elena Rostova', 'rank' => 'CPT', 'network' => 'VATSIM'],
                ['name' => 'Lucas Bernard', 'rank' => 'FO', 'network' => 'PilotEdge'],
                ['name' => 'David Miller', 'rank' => '2/O', 'network' => 'Offline'],
            ];

            $seedCount = min(15, max(3, $tenantRoutes->count()));
            
            // Loop through routes and generate smooth active positions
            for ($i = 0; $i < $seedCount; $i++) {
                $route = $tenantRoutes[$i % $tenantRoutes->count()];
                $depAirport = $airports->get(strtoupper($route->departure_icao));
                $arrAirport = $airports->get(strtoupper($route->arrival_icao));

                if (!$depAirport || !$arrAirport) continue;

                $depLat = $depAirport->lat;
                $depLon = $depAirport->lon;
                $arrLat = $arrAirport->lat;
                $arrLon = $arrAirport->lon;

                // Deterministic pseudo-random fraction based on route ID and time of day for continuous movement
                $timeSeed = (time() / 120.0) + ($route->id * 0.173);
                $progressFraction = fmod($timeSeed, 1.0);
                if ($progressFraction < 0.05) $progressFraction = 0.05;
                if ($progressFraction > 0.95) $progressFraction = 0.95;

                $lat = $depLat + ($arrLat - $depLat) * $progressFraction;
                $lon = $depLon + ($arrLon - $depLon) * $progressFraction;
                $heading = (int) $this->calculateHeading($depLat, $depLon, $arrLat, $arrLon);
                $distRemaining = (int) $this->calculateDistance($lat, $lon, $arrLat, $arrLon);

                $pilot = $fictionalPilots[$i % count($fictionalPilots)];
                $airframe = $tenantAirframes->isNotEmpty() ? $tenantAirframes[$i % $tenantAirframes->count()] : null;
                $aircraftCode = $airframe?->aircraftType?->code ?? ($route->aircraftTypes->first()?->code ?? 'B738');
                $aircraftName = $airframe?->aircraftType?->name ?? ($route->aircraftTypes->first()?->name ?? 'Boeing 737-800');
                $aircraftReg = $airframe?->registration ?? ('EI-' . strtoupper(substr(md5((string)$route->id), 0, 3)));

                $status = ($progressFraction < 0.12) ? 'Climbing' : (($progressFraction > 0.85) ? 'Descending' : 'Cruising');
                $alt = ($status === 'Climbing') ? (int)(10000 + $progressFraction * 20000) : (($status === 'Descending') ? (int)(34000 - ($progressFraction - 0.85) * 150000) : 36000);
                $speed = ($status === 'Climbing') ? 340 : 430;

                $callsignNum = $route->callsign ?: ($tenant->icao . (100 + $route->id));
                $pilotId = $tenant->icao . (5000 + $i);

                $liveFlights->push([
                    'id' => 'sim_' . $route->id . '_' . $i,
                    'pilot_name' => $pilot['name'],
                    'pilot_id' => $pilotId,
                    'pilot_rank' => $pilot['rank'],
                    'callsign' => strtoupper($callsignNum),
                    'flight_number' => strtoupper($route->flight_number ?: $callsignNum),
                    'departure_icao' => strtoupper($route->departure_icao),
                    'departure_name' => $depAirport->name,
                    'arrival_icao' => strtoupper($route->arrival_icao),
                    'arrival_name' => $arrAirport->name,
                    'aircraft_type' => $aircraftCode,
                    'aircraft_reg' => $aircraftReg,
                    'aircraft_display' => $aircraftName . ' - ' . $aircraftReg,
                    'ground_speed_kt' => $speed,
                    'altitude_ft' => $alt,
                    'flight_level' => 'FL' . str_pad((string) floor($alt / 100), 3, '0', STR_PAD_LEFT),
                    'heading_deg' => $heading,
                    'status' => $status,
                    'network' => $pilot['network'],
                    'ete' => gmdate('H:i', time() + (int)(($distRemaining / max(100, $speed)) * 3600)),
                    'distance_nm' => $distRemaining,
                    'latitude' => round((float) $lat, 6),
                    'longitude' => round((float) $lon, 6),
                    'dep_lat' => (float) $depLat,
                    'dep_lon' => (float) $depLon,
                    'arr_lat' => (float) $arrLat,
                    'arr_lon' => (float) $arrLon,
                ]);
            }
        }

        return $liveFlights->values()->toArray();
    }

    /**
     * Calculate initial bearing (heading) between two coordinates in degrees.
     */
    protected function calculateHeading(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);
        $dLon = deg2rad($lon2 - $lon1);

        $y = sin($dLon) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dLon);
        $bearing = atan2($y, $x);
        $bearing = rad2deg($bearing);

        return fmod(($bearing + 360.0), 360.0);
    }

    /**
     * Calculate great-circle distance in nautical miles.
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos(max(-1.0, min(1.0, $dist)));
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return $miles * 0.8684; // convert statute miles to nautical miles
    }
}
