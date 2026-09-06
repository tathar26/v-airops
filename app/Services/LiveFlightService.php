<?php

namespace App\Services;

use App\Models\AcarsActiveFlight;
use App\Models\AcarsPosition;
use App\Models\Booking;
use App\Models\Airport;
use App\Models\Tenant;
use App\Models\Pirep;
use App\Models\AircraftType;
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

        $retentionHours = (int) config('services.acars.live_flight_retention_hours', (int) env('LIVE_FLIGHT_RETENTION_HOURS', 8));
        if ($retentionHours <= 0) {
            $retentionHours = 8;
        }

        $cutoff = \Carbon\Carbon::now()->subHours($retentionHours);

        // Auto-archive stale active flights older than the retention window
        AcarsActiveFlight::where('status', 'active')
            ->where('updated_at', '<', $cutoff)
            ->update(['status' => 'archived']);

        // 1. Query active & recently completed bookings strictly for this tenant within the retention window
        $activeBookings = Booking::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'dispatched', 'in_flight', 'completed'])
            ->where('updated_at', '>=', $cutoff)
            ->with(['user', 'route.aircraftTypes', 'airframe.aircraftType'])
            ->latest('updated_at')
            ->get()
            ->keyBy('user_id');

        // 2. Query active & recently completed ACARS flights strictly for this tenant within the retention window
        $activeAcarsFlights = AcarsActiveFlight::whereIn('status', ['active', 'completed'])
            ->where('updated_at', '>=', $cutoff)
            ->where(function ($q) use ($tenantId, $activeBookings) {
                $q->where('tenant_id', $tenantId);
                if ($activeBookings->isNotEmpty()) {
                    $q->orWhereIn('user_id', $activeBookings->keys());
                }
            })
            ->with('user')
            ->latest('updated_at')
            ->get()
            ->keyBy('user_id');

        // 3. Query recently submitted PIREPs strictly for this tenant within the retention window
        $recentPireps = Pirep::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $cutoff)
            ->with(['airframe.aircraftType', 'route.aircraftTypes'])
            ->latest('id')
            ->get()
            ->groupBy('user_id');

        // Get all unique user IDs with active activity strictly within this tenant
        $activeUserIds = $activeBookings->keys()
            ->merge($activeAcarsFlights->keys())
            ->merge($recentPireps->keys())
            ->unique();

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
        foreach ($recentPireps as $uPireps) {
            $p = $uPireps->first();
            $pLog = is_array($p->flight_log) ? $p->flight_log : (json_decode($p->flight_log ?? '', true) ?? []);
            if (!empty($pLog['origin'])) $neededIcaos->push(strtoupper($pLog['origin']));
            if (!empty($pLog['destination'])) $neededIcaos->push(strtoupper($pLog['destination']));
        }

        $airports = Airport::whereIn('icao', $neededIcaos->unique()->filter())->get()->keyBy('icao');

        // Process each active pilot (strictly 1 entry per pilot)
        foreach ($activeUserIds as $userId) {
            $booking = $activeBookings->get($userId);
            $acars = $activeAcarsFlights->get($userId);
            $pirep = $recentPireps->get($userId)?->first();

            $user = $booking?->user ?? ($acars?->user ?? $pirep?->user);
            if (!$user) continue;

            $sb = $booking?->simbrief_data ?? [];
            $route = $booking?->route ?? $pirep?->route;
            $airframe = $booking?->airframe ?? $pirep?->airframe;

            $pirepLog = is_array($pirep?->flight_log) 
                ? $pirep->flight_log 
                : (json_decode($pirep?->flight_log ?? '', true) ?? []);

            if (empty($sb) && !empty($pirepLog['simbrief_data'])) {
                $sb = $pirepLog['simbrief_data'];
            }

            // ── CALLSIGN & FLIGHT NUMBER ─────────────────────────
            $callsign = $sb['params']['callsign'] 
                ?? ($sb['atc']['callsign'] 
                ?? ($sb['general']['callsign'] 
                ?? ($sb['general']['flight_number'] 
                ?? ($acars?->flight_number 
                ?? ($pirepLog['callsign']
                ?? ($pirepLog['flight_number']
                ?? ($route?->callsign 
                ?? ($route?->flight_number ?? 'FL101'))))))));

            $flightNum = $sb['general']['flight_number'] 
                ?? ($sb['params']['flight_number'] 
                ?? ($acars?->flight_number 
                ?? ($pirepLog['flight_number']
                ?? ($route?->flight_number ?? $callsign))));

            // ── ORIGIN & DESTINATION ─────────────────────────────
            $depIcao = strtoupper($sb['origin']['icao_code'] 
                ?? ($sb['general']['origin'] 
                ?? ($acars?->origin_icao 
                ?? ($pirepLog['origin']
                ?? ($route?->departure_icao ?? 'EGLL')))));

            $arrIcao = strtoupper($sb['destination']['icao_code'] 
                ?? ($sb['general']['destination'] 
                ?? ($acars?->destination_icao 
                ?? ($pirepLog['destination']
                ?? ($route?->arrival_icao ?? 'LFPG')))));

            $depAirport = $airports->get($depIcao);
            $arrAirport = $airports->get($arrIcao);

            $depLat = $depAirport?->lat ?? 51.5074;
            $depLon = $depAirport?->lon ?? -0.1278;
            $arrLat = $arrAirport?->lat ?? 48.8566;
            $arrLon = $arrAirport?->lon ?? 2.3522;

            // ── AIRCRAFT & REGISTRATION ──────────────────────────
            $aircraftReg = $airframe?->registration 
                ?? ($sb['aircraft']['reg'] 
                ?? ($sb['aircraft']['registration'] ?? ''));

            $aircraftCode = $airframe?->aircraftType?->code 
                ?? ($sb['aircraft']['icao_code'] 
                ?? ($sb['aircraft']['icaocode']
                ?? ($acars?->aircraft_type 
                ?? ($pirep?->atc_model
                ?? ($pirepLog['aircraft_type'] ?? null)))));

            if (!$aircraftCode && $route) {
                $aircraftCode = $route->aircraftTypes?->first()?->code;
            }

            $aircraftName = $airframe?->aircraftType?->name 
                ?? ($sb['aircraft']['name'] ?? null);

            if (!$aircraftName && $aircraftCode) {
                $dbAircraftType = AircraftType::where('tenant_id', $tenantId)
                    ->where('code', $aircraftCode)
                    ->first() 
                    ?? AircraftType::where('code', $aircraftCode)->first();
                $aircraftName = $dbAircraftType?->name;
            }

            if (!$aircraftName) {
                $aircraftName = $pirep?->aircraft_title 
                    ?? ($pirepLog['aircraft_title'] 
                    ?? ($acars?->aircraft_type ?? ($aircraftCode ?? '')));
            }

            if (!empty($aircraftName) && !empty($aircraftReg)) {
                $aircraftDisplay = ($aircraftName === $aircraftReg) ? $aircraftName : ($aircraftName . ' - ' . $aircraftReg);
            } elseif (!empty($aircraftName)) {
                $aircraftDisplay = $aircraftName;
            } elseif (!empty($aircraftCode) && !empty($aircraftReg)) {
                $aircraftDisplay = $aircraftCode . ' - ' . $aircraftReg;
            } elseif (!empty($aircraftCode)) {
                $aircraftDisplay = $aircraftCode;
            } elseif (!empty($aircraftReg)) {
                $aircraftDisplay = $aircraftReg;
            } else {
                $aircraftDisplay = 'N/A';
            }

            // ── NETWORK ──────────────────────────────────────────
            $network = $sb['general']['network'] 
                ?? ($sb['network'] 
                ?? ($user->pilotProfiles()->where('tenant_id', $tenantId)->first()?->preferred_network ?? 'Offline'));

            // ── TELEMETRY & LIVE POSITION FROM acars_positions TABLE ──
            $userFlightIds = AcarsActiveFlight::where('user_id', $userId)
                ->whereIn('status', ['active', 'completed'])
                ->where('updated_at', '>=', $cutoff)
                ->pluck('id');
            if ($booking?->id) {
                $userFlightIds->push($booking->id);
            }
            $userFlightIds = $userFlightIds->filter()->unique();
            $latestPos = null;

            if ($userFlightIds->isNotEmpty()) {
                $latestPos = AcarsPosition::whereIn('flight_id', $userFlightIds)
                    ->where('created_at', '>=', $cutoff)
                    ->latest('id')
                    ->first();
            }

            // Strictly hide flight from the live map until pilot has started logging with ACARS
            if (!$latestPos) {
                continue;
            }

            // Live telemetry from Pegasus / ACARS client
            $lat = (float) $latestPos->latitude;
            $lon = (float) $latestPos->longitude;
            $alt = (int) $latestPos->altitude_ft;
            $speed = (int) $latestPos->ground_speed_kt;
            $heading = (int) $latestPos->heading_deg;

            if ($acars?->status === 'completed' || $booking?->status === 'completed' || in_array(strtoupper(trim($latestPos->flight_phase)), ['ARRIVED', 'PARKED', 'GATE_ARRIVAL', 'COMPLETED', 'SHUTDOWN'])) {
                $status = 'Arrived';
            } else {
                $status = $this->formatFlightPhase($latestPos->flight_phase);
            }

            // ── FLIGHT LEVEL ─────────────────────────────────────
            if ($status === 'Preflight' || $status === 'Pushback' || $status === 'Taxiing') {
                $rawAlt = (int) ($sb['general']['initial_altitude'] ?? ($sb['general']['cruise_altitude'] ?? ($sb['params']['fl'] ?? ($acars?->planned_altitude ?? 36000))));
                $flNumber = ($rawAlt > 0 && $rawAlt < 1000) ? $rawAlt : (int) floor($rawAlt / 100);
                $flightLevel = 'FL' . str_pad((string) $flNumber, 3, '0', STR_PAD_LEFT);
            } else {
                $flNumber = (int) floor($alt / 100);
                $flightLevel = 'FL' . str_pad((string) $flNumber, 3, '0', STR_PAD_LEFT);
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
            'ARRIVED', 'PARKED', 'GATE_ARRIVAL', 'COMPLETED', 'SHUTDOWN' => 'Arrived',
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
