<?php

namespace App\Services;

use App\Models\AcarsActiveFlight;
use App\Models\AcarsPosition;
use App\Models\Booking;
use App\Models\Airport;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class LiveFlightService
{
    /**
     * Get all real active flights currently being flown or dispatched for a given tenant.
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

        // 1. Query active bookings for this tenant (status: pending, dispatched, in_flight)
        $activeBookings = Booking::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'dispatched', 'in_flight'])
            ->with(['user', 'route', 'airframe.aircraftType'])
            ->latest('updated_at')
            ->get()
            ->keyBy('user_id');

        // 2. Query active ACARS flights for users belonging to this tenant
        $activeAcarsFlights = AcarsActiveFlight::where('status', 'active')
            ->whereHas('user', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereHas('userAirlines', function ($ua) use ($tenantId) {
                      $ua->where('tenant_id', $tenantId);
                  });
            })
            ->with('user')
            ->latest('updated_at')
            ->get()
            ->keyBy('user_id');

        // Get all unique user IDs with active activity
        $activeUserIds = $activeBookings->keys()->merge($activeAcarsFlights->keys())->unique();

        if ($activeUserIds->isEmpty()) {
            return [];
        }

        // Collect all airport ICAOs needed to load coordinates in one query
        $neededIcaos = collect();
        foreach ($activeBookings as $b) {
            $sb = $b->simbrief_data ?? [];
            $dep = $sb['origin']['icao_code'] ?? ($sb['general']['origin'] ?? $b->route?->departure_icao);
            $arr = $sb['destination']['icao_code'] ?? ($sb['general']['destination'] ?? $b->route?->arrival_icao);
            if ($dep) $neededIcaos->push(strtoupper($dep));
            if ($arr) $neededIcaos->push(strtoupper($arr));
        }
        foreach ($activeAcarsFlights as $a) {
            if ($a->origin_icao) $neededIcaos->push(strtoupper($a->origin_icao));
            if ($a->destination_icao) $neededIcaos->push(strtoupper($a->destination_icao));
        }

        $airports = Airport::whereIn('icao', $neededIcaos->unique()->filter())->get()->keyBy('icao');

        // Process each active pilot (strictly 1 entry per pilot)
        foreach ($activeUserIds as $userId) {
            $booking = $activeBookings->get($userId);
            $acars = $activeAcarsFlights->get($userId);

            $user = $booking?->user ?? $acars?->user;
            if (!$user) continue;

            $sb = $booking?->simbrief_data ?? [];
            $route = $booking?->route;
            $airframe = $booking?->airframe;

            // ── CALLSIGN & FLIGHT NUMBER ─────────────────────────
            $callsign = $sb['params']['callsign'] 
                ?? ($sb['atc']['callsign'] 
                ?? ($sb['general']['callsign'] 
                ?? ($sb['general']['flight_number'] 
                ?? ($acars?->flight_number 
                ?? ($route?->callsign 
                ?? ($route?->flight_number ?? 'FL101'))))));

            $flightNum = $sb['general']['flight_number'] 
                ?? ($sb['params']['flight_number'] 
                ?? ($acars?->flight_number 
                ?? ($route?->flight_number ?? $callsign)));

            // ── ORIGIN & DESTINATION ─────────────────────────────
            $depIcao = strtoupper($sb['origin']['icao_code'] 
                ?? ($sb['general']['origin'] 
                ?? ($acars?->origin_icao 
                ?? ($route?->departure_icao ?? 'EGLL'))));

            $arrIcao = strtoupper($sb['destination']['icao_code'] 
                ?? ($sb['general']['destination'] 
                ?? ($acars?->destination_icao 
                ?? ($route?->arrival_icao ?? 'LFPG'))));

            $depAirport = $airports->get($depIcao);
            $arrAirport = $airports->get($arrIcao);

            $depLat = $depAirport?->lat ?? 51.5074;
            $depLon = $depAirport?->lon ?? -0.1278;
            $arrLat = $arrAirport?->lat ?? 48.8566;
            $arrLon = $arrAirport?->lon ?? 2.3522;

            // ── AIRCRAFT & REGISTRATION ──────────────────────────
            $aircraftReg = $airframe?->registration 
                ?? ($sb['aircraft']['reg'] 
                ?? ($sb['aircraft']['registration'] ?? 'G-DEMO'));

            $aircraftCode = $airframe?->aircraftType?->code 
                ?? ($sb['aircraft']['icao_code'] 
                ?? ($acars?->aircraft_type 
                ?? ($route?->aircraftTypes?->first()?->code ?? 'B738')));

            $aircraftName = $airframe?->aircraftType?->name 
                ?? ($sb['aircraft']['name'] ?? 'Boeing 737-800');

            $aircraftDisplay = $aircraftName . ($aircraftReg ? ' - ' . $aircraftReg : '');

            // ── NETWORK ──────────────────────────────────────────
            $network = $sb['general']['network'] 
                ?? ($sb['network'] 
                ?? ($user->pilotProfiles()->where('tenant_id', $tenantId)->first()?->preferred_network ?? 'Offline'));

            // ── TELEMETRY & LIVE POSITION FROM acars_positions TABLE ──
            $userFlightIds = AcarsActiveFlight::where('user_id', $userId)->pluck('id');
            $latestPos = null;

            if ($userFlightIds->isNotEmpty()) {
                $latestPos = AcarsPosition::whereIn('flight_id', $userFlightIds)
                    ->latest('id')
                    ->first();
            }

            if ($latestPos) {
                // Live telemetry from Pegasus / ACARS client
                $lat = (float) $latestPos->latitude;
                $lon = (float) $latestPos->longitude;
                $alt = (int) $latestPos->altitude_ft;
                $speed = (int) $latestPos->ground_speed_kt;
                $heading = (int) $latestPos->heading_deg;
                $status = $this->formatFlightPhase($latestPos->flight_phase);
            } else {
                // Dispatched / Preflight / Boarding on ground at departure airport
                $lat = (float) $depLat;
                $lon = (float) $depLon;
                $alt = (int) ($depAirport?->elevation ?? 0);
                $speed = 0;
                $heading = (int) $this->calculateHeading($depLat, $depLon, $arrLat, $arrLon);
                $status = 'Preflight';
            }

            // ── FLIGHT LEVEL ─────────────────────────────────────
            if ($status === 'Preflight' || $status === 'Pushback' || $status === 'Taxiing') {
                $plannedFl = (int) ($sb['general']['initial_altitude'] ?? ($sb['params']['fl'] ?? ($acars?->planned_altitude ?? 34000)));
                $flightLevel = 'FL' . str_pad((string) floor($plannedFl / 100), 3, '0', STR_PAD_LEFT);
            } else {
                $flightLevel = 'FL' . str_pad((string) floor($alt / 100), 3, '0', STR_PAD_LEFT);
            }

            // ── DISTANCE & ETE ───────────────────────────────────
            $routeDist = (int) ($sb['general']['route_distance'] ?? ($sb['general']['air_distance'] ?? $this->calculateDistance($depLat, $depLon, $arrLat, $arrLon)));
            $remainingDist = (int) $this->calculateDistance($lat, $lon, $arrLat, $arrLon);

            $eteString = '--:--';
            if (!empty($sb['times']['est_time_enroute'])) {
                $eteSeconds = (int) $sb['times']['est_time_enroute'];
                $eteString = sprintf('%02d:%02d', floor($eteSeconds / 3600), floor(($eteSeconds % 3600) / 60));
            } elseif (!empty($sb['times']['est_in']) && !empty($sb['times']['est_out'])) {
                $eteString = date('H:i', strtotime($sb['times']['est_in']));
            } elseif ($route?->flight_time) {
                $eteString = sprintf('%02d:%02d', floor($route->flight_time / 60), $route->flight_time % 60);
            }

            $pilotCallsign = $user->activeCallsign() 
                ?: ($tenant ? $tenant->icao . str_pad($user->id, 3, '0', STR_PAD_LEFT) : 'PILOT' . $user->id);

            $liveFlights->push([
                'id' => 'flight_' . ($booking?->id ?? $acars?->id ?? $user->id),
                'pilot_name' => $user->full_name ?: $user->name,
                'pilot_id' => $pilotCallsign,
                'pilot_rank' => $user->active_rank_name ?: 'Captain',
                'callsign' => strtoupper($callsign),
                'flight_number' => strtoupper($flightNum),
                'departure_icao' => $depIcao,
                'departure_name' => $depAirport?->name ?? $depIcao,
                'arrival_icao' => $arrIcao,
                'arrival_name' => $arrAirport?->name ?? $arrIcao,
                'aircraft_type' => $aircraftCode,
                'aircraft_reg' => $aircraftReg,
                'aircraft_display' => $aircraftDisplay,
                'ground_speed_kt' => $speed,
                'altitude_ft' => $alt,
                'flight_level' => $flightLevel,
                'heading_deg' => $heading,
                'status' => $status,
                'network' => $network,
                'ete' => $eteString,
                'distance_nm' => ($status === 'Preflight') ? $routeDist : $remainingDist,
                'latitude' => round($lat, 6),
                'longitude' => round($lon, 6),
                'dep_lat' => (float) $depLat,
                'dep_lon' => (float) $depLon,
                'arr_lat' => (float) $arrLat,
                'arr_lon' => (float) $arrLon,
            ]);
        }

        return $liveFlights->values()->toArray();
    }

    /**
     * Format raw flight phase from acars_positions table into human-readable standard phase.
     */
    public function formatFlightPhase(?string $phase): string
    {
        if (!$phase) {
            return 'Preflight';
        }

        $upper = strtoupper(trim($phase));

        return match ($upper) {
            'PREFLIGHT', 'BOARDING', 'PLANNING' => 'Preflight',
            'PUSHBACK', 'PUSH_BACK', 'ENGINE_START', 'STARTING' => 'Pushback',
            'TAXI', 'TAXI_OUT', 'TAXIING', 'TAXI_IN', 'TAXI_TO_GATE' => 'Taxiing',
            'TAKEOFF', 'TAKEOFF_RUN', 'ROTATION' => 'Takeoff',
            'CLIMB', 'CLIMBING', 'INITIAL_CLIMB' => 'Climbing',
            'CRUISE', 'CRUISING', 'ENROUTE', 'LEVEL' => 'Cruising',
            'DESCENT', 'DESCENDING' => 'Descending',
            'APPROACH', 'APPROACHING', 'FINAL', 'FINAL_APPROACH' => 'Approach',
            'LANDING', 'TOUCHDOWN', 'LANDED' => 'Landed',
            'PARKED', 'GATE_ARRIVAL', 'COMPLETED', 'SHUTDOWN' => 'Parked',
            default => ucwords(str_replace('_', ' ', strtolower($phase))),
        };
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

        return (int) fmod(($bearing + 360.0), 360.0);
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

        return (int) ($miles * 0.8684);
    }
}
