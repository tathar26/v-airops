<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Pirep;
use App\Models\Airport;
use App\Models\UserStatistic;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculatePilotStatistics implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Determine whether a given timestamp at an airport is daytime.
     * Uses PHP date_sun_info() when airport coordinates are available,
     * or solar time estimation based on ICAO region longitude.
     */
    public static function determineDaytime(int $timestamp, ?string $icao): bool
    {
        $icao = strtoupper(trim((string)$icao));
        $airport = !empty($icao) ? Airport::where('icao', $icao)->first() : null;

        if ($airport && (abs((float)$airport->lat) > 0.01 || abs((float)$airport->lon) > 0.01)) {
            $lat = (float) $airport->lat;
            $lon = (float) $airport->lon;

            $sun = @date_sun_info($timestamp, $lat, $lon);
            if (is_array($sun)) {
                // Check civil twilight (standard aviation definition of day/night)
                if (isset($sun['civil_twilight_begin'], $sun['civil_twilight_end']) 
                    && is_int($sun['civil_twilight_begin']) && is_int($sun['civil_twilight_end'])) {
                    return ($timestamp >= $sun['civil_twilight_begin'] && $timestamp <= $sun['civil_twilight_end']);
                }
                // Fallback to sunrise / sunset
                if (isset($sun['sunrise'], $sun['sunset']) 
                    && is_int($sun['sunrise']) && is_int($sun['sunset'])) {
                    return ($timestamp >= $sun['sunrise'] && $timestamp <= $sun['sunset']);
                }
            }

            // Polar day / night or astronomical anomaly fallback: approximate solar angle via lon
            $solarHour = fmod((gmdate('G', $timestamp) + (gmdate('i', $timestamp) / 60.0) + ($lon / 15.0) + 24.0), 24.0);
            return ($solarHour >= 6.5 && $solarHour <= 19.5);
        }

        // Region-based longitude approximation if airport coordinates are unseeded (0, 0)
        $firstChar = substr($icao, 0, 1);
        $estLon = match ($firstChar) {
            'K', 'C', 'M' => -95.0, // North America
            'S' => -60.0,           // South America
            'E', 'L', 'B' => 10.0,  // Europe
            'U' => 40.0,            // Eastern Europe / Russia
            'O' => 50.0,            // Middle East
            'V' => 80.0,            // South Asia / India
            'Z', 'R' => 115.0,      // East Asia / Japan
            'Y', 'N' => 140.0,      // Australia / Pacific
            'D', 'F', 'G', 'H' => 20.0, // Africa
            default => 0.0
        };

        $solarHour = fmod((gmdate('G', $timestamp) + (gmdate('i', $timestamp) / 60.0) + ($estLon / 15.0) + 24.0), 24.0);
        return ($solarHour >= 6.5 && $solarHour <= 19.5);
    }

    /**
     * Normalize simulator names to canonical, clean categories.
     */
    public static function normalizeSimulator(?string $sim): string
    {
        if (empty($sim) || strtolower($sim) === 'unknown') {
            return 'MSFS 2020';
        }

        $simUpper = strtoupper(trim($sim));
        if (str_contains($simUpper, '2024')) {
            return 'MSFS 2024';
        }
        if (str_contains($simUpper, 'MSFS') || str_contains($simUpper, 'FLIGHT SIMULATOR')) {
            return 'MSFS 2020';
        }
        if (str_contains($simUpper, 'X-PLANE') || str_contains($simUpper, 'XP')) {
            if (str_contains($simUpper, '11')) return 'X-Plane 11';
            return 'X-Plane 12';
        }
        if (str_contains($simUpper, 'P3D') || str_contains($simUpper, 'PREPAR3D')) {
            return 'Prepar3D';
        }
        if (str_contains($simUpper, 'FSX')) {
            return 'FSX';
        }

        return trim($sim);
    }

    /**
     * Normalize network names.
     */
    public static function normalizeNetwork(?string $net, ?string $fallback = 'Offline'): string
    {
        $net = trim((string)($net ?: $fallback));
        $netUpper = strtoupper($net);
        if (str_contains($netUpper, 'VATSIM')) return 'VATSIM';
        if (str_contains($netUpper, 'IVAO')) return 'IVAO';
        if (str_contains($netUpper, 'POSCON')) return 'POSCON';
        if (str_contains($netUpper, 'OFFLINE')) return 'Offline';
        if (empty($net) || strtolower($net) === 'unknown') return 'Offline';
        return ucfirst(strtolower($net));
    }

    /**
     * Extract clean callsign prefix (e.g. EZY, SVK, BAW).
     */
    public static function extractCallsignPrefix(Pirep $p): string
    {
        $raw = $p->flight_log['callsign'] 
            ?? ($p->route?->callsign 
            ?? ($p->flight_log['flight_number'] 
            ?? ($p->route?->flight_number 
            ?? ($p->tenant?->icao ?? 'Unknown'))));

        $raw = strtoupper(trim((string)$raw));

        // Detect IATA designators with digits like U2 (easyJet) -> map to EZY
        if (preg_match('/^U2/i', $raw)) {
            return 'EZY';
        }

        // Standard 3 or 4 letter ICAO airline prefix (e.g. SVK, EZY, BAW, VOPS)
        if (preg_match('/^[A-Z]{2,4}/', $raw, $matches)) {
            return $matches[0];
        }

        // Alphanumeric 2-char IATA code (e.g. W6 for Wizz Air)
        if (preg_match('/^[A-Z0-9]{2}/', $raw, $matches)) {
            return $matches[0];
        }

        if (preg_match('/^[A-Z]+/i', $raw, $matches)) {
            return $matches[0];
        }

        return 'Unknown';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $profiles = \App\Models\PilotProfile::where('user_id', $this->userId)->get();

        foreach ($profiles as $profile) {
            $tenantId = $profile->tenant_id;
            
            // Accepted, Complete, and Rejected PIREPs award flight time hours.
            // Invalidated PIREPs award no hours and no points.
            $validPireps = Pirep::where('user_id', $this->userId)
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['Accepted', 'accepted', 'Complete', 'complete', 'Approved', 'approved', 'Rejected', 'rejected'])
                ->with(['route', 'airframe.aircraftType', 'tenant'])
                ->get();

            if ($validPireps->isEmpty()) {
                $profile->flight_time = 0;
                $profile->points = 0;
                $profile->save();

                UserStatistic::updateOrCreate([
                    'user_id' => $this->userId,
                    'tenant_id' => $tenantId
                ], [
                    'total_flights' => 0,
                    'total_flight_time' => 0,
                    'total_passengers' => 0,
                    'total_freight' => 0,
                    'total_block_fuel' => 0,
                    'avg_landing_rate' => null,
                ]);
                continue;
            }

            // 1. Data Healing & Backfill for each PIREP
            foreach ($validPireps as $p) {
                $dirty = false;
                $fLog = is_array($p->flight_log) ? $p->flight_log : [];
                $sb = is_array($fLog['simbrief_data'] ?? null) ? $fLog['simbrief_data'] : [];

                $destIcao = $p->route?->arrival_icao ?? ($fLog['destination'] ?? null);
                $originIcao = $p->route?->departure_icao ?? ($fLog['origin'] ?? null);

                // Determine takeoff & landing timestamps
                $createdTs = $p->created_at ? $p->created_at->timestamp : time();
                $flightMins = (int) ($p->flight_time ?: ($fLog['block_time_minutes'] ?? 60));

                $takeoffTs = !empty($fLog['block_off_time']) ? strtotime($fLog['block_off_time']) : ($createdTs - ($flightMins * 60));
                $landingTs = !empty($fLog['block_on_time']) ? strtotime($fLog['block_on_time']) : $createdTs;

                $computedDayTakeoff = self::determineDaytime($takeoffTs, $originIcao);
                $computedDayLanding = self::determineDaytime($landingTs, $destIcao);

                if ($p->is_day_takeoff !== $computedDayTakeoff) {
                    $p->is_day_takeoff = $computedDayTakeoff;
                    $dirty = true;
                }

                if ($p->is_day_landing !== $computedDayLanding) {
                    $p->is_day_landing = $computedDayLanding;
                    $dirty = true;
                }

                // Backfill passengers from simbrief_data or flight_log if 0 / null
                if (empty($p->passengers)) {
                    $pax = (int) ($sb['weights']['pax_count'] ?? ($sb['params']['pax'] ?? ($sb['passengers'] ?? ($fLog['passengers'] ?? 0))));
                    if ($pax > 0) {
                        $p->passengers = $pax;
                        $dirty = true;
                    }
                }

                // Backfill freight/cargo from simbrief_data or flight_log if 0 / null
                if (empty($p->freight)) {
                    $cargo = (int) round((float)($sb['weights']['cargo'] ?? ($sb['cargo_kg'] ?? ($fLog['cargo_kg'] ?? 0))));
                    if ($cargo > 0) {
                        $p->freight = $cargo;
                        $dirty = true;
                    }
                }

                // Backfill fuel_used and block_fuel
                $fuelUsed = (float) ($p->fuel_used ?: ($p->block_fuel ?: ($fLog['fuel_used_kg'] ?? 0.0)));
                if ($p->fuel_used != $fuelUsed) {
                    $p->fuel_used = $fuelUsed;
                    $dirty = true;
                }
                if ($p->block_fuel != (int) round($fuelUsed)) {
                    $p->block_fuel = (int) round($fuelUsed);
                    $dirty = true;
                }

                // Normalize network
                $netVal = self::normalizeNetwork($p->network ?: ($fLog['network'] ?? ($sb['network'] ?? null)), $profile->preferred_network ?? 'Offline');
                if ($p->network !== $netVal) {
                    $p->network = $netVal;
                    $dirty = true;
                }

                // Normalize simulator
                $simVal = self::normalizeSimulator($p->simulator ?: ($fLog['simulator'] ?? null));
                if ($p->simulator !== $simVal) {
                    $p->simulator = $simVal;
                    $dirty = true;
                }

                if ($dirty) {
                    $p->save();
                }
            }

            $pireps = $validPireps;
            $total_flights = $pireps->count();
            $total_flight_time = (int) $pireps->sum('flight_time');

            // Only Accepted/Complete/Approved PIREPs award points (Rejected awards hours but 0 points)
            $acceptedPireps = $pireps->filter(function($p) {
                return in_array(strtolower($p->status), ['accepted', 'complete', 'approved']);
            });
            $total_points = (int) $acceptedPireps->sum('points_awarded');
            
            $total_passengers = (int) $pireps->sum('passengers');
            $total_freight = (int) $pireps->sum('freight');
            $total_block_fuel = (int) round($pireps->sum('fuel_used') ?: $pireps->sum('block_fuel'));
            
            // Update PilotProfile hours and points
            $profile->flight_time = $total_flight_time;
            $profile->points = $total_points;

            // Automatically upgrade rank if qualified based on hours
            $qualifyingRank = \App\Models\Rank::where('tenant_id', $tenantId)
                ->where('min_hours', '<=', floor($total_flight_time / 60))
                ->orderBy('min_hours', 'desc')
                ->first();

            if ($qualifyingRank) {
                $profile->rank_id = $qualifyingRank->id;
            }

            // Sync pilot location to the arrival airport of the latest PIREP
            $latestPirep = $pireps->sortByDesc('created_at')->first();
            if ($latestPirep) {
                $destIcao = null;
                if ($latestPirep->route && $latestPirep->route->arrival_icao) {
                    $destIcao = strtoupper($latestPirep->route->arrival_icao);
                } elseif (is_array($latestPirep->flight_log) && !empty($latestPirep->flight_log['destination'])) {
                    $destIcao = strtoupper($latestPirep->flight_log['destination']);
                }

                if ($destIcao) {
                    $arrivalAirport = Airport::where('icao', $destIcao)->first();
                    if (!$arrivalAirport) {
                        $arrivalAirport = Airport::create([
                            'icao' => $destIcao,
                            'name' => $destIcao,
                            'lat' => 0.0,
                            'lon' => 0.0,
                        ]);
                    }
                    if ($arrivalAirport) {
                        $profile->current_airport_id = $arrivalAirport->id;
                    }
                }
            }

            $profile->save();
            
            $landing_rates = $pireps->pluck('touchdown_rate_fpm')->filter();
            $avg_landing_rate = $landing_rates->count() > 0 ? (int) round($landing_rates->avg()) : null;

            // Groupings
            $aircraft_types_json = $pireps->groupBy(function($p) {
                return $p->airframe?->aircraftType?->name ?? 'Unknown';
            })->map->count()->toArray();

            $callsigns_json = $pireps->groupBy(function($p) {
                return self::extractCallsignPrefix($p);
            })->map->count()->toArray();

            $networks_json = $pireps->groupBy(function($p) use ($profile) {
                return self::normalizeNetwork($p->network, $profile->preferred_network ?? 'Offline');
            })->map->count()->toArray();

            $simulators_json = $pireps->groupBy(function($p) {
                return self::normalizeSimulator($p->simulator);
            })->map->count()->toArray();

            $takeoffs_json = [
                'Day' => $pireps->where('is_day_takeoff', true)->count(),
                'Night' => $pireps->where('is_day_takeoff', false)->count(),
            ];

            $landings_json = [
                'Day' => $pireps->where('is_day_landing', true)->count(),
                'Night' => $pireps->where('is_day_landing', false)->count(),
            ];

            $events_json = [
                'Event' => $pireps->where('is_event', true)->count(),
                'Non-Event' => $pireps->where('is_event', false)->count(),
            ];

            $route_types_json = $pireps->groupBy(function($p) {
                return $p->route?->route_type ?? 'Scheduled';
            })->map->count()->toArray();

            // Flights per month (e.g. "Aug 26" -> count)
            $flights_per_month_json = $pireps->sortBy('created_at')->groupBy(function($p) {
                return $p->created_at->format('M y');
            })->map->count()->toArray();

            // Landing rate history (array of objects { date, fpm })
            // Chronologically sorted, labeled with flight date and callsign for clear distinction
            $landing_rate_history_json = $pireps->sortBy('created_at')->map(function($p) {
                $fpm = (int) ($p->touchdown_rate_fpm ?: ($p->flight_log['touchdown_fpm'] ?? 0));
                $cs = $p->flight_log['callsign'] 
                    ?? ($p->route?->callsign 
                    ?? ($p->flight_log['flight_number'] ?? ''));
                
                $label = $p->created_at->format('d M');
                if (!empty($cs)) {
                    $label .= ' (' . $cs . ')';
                }

                return [
                    'date' => $label,
                    'fpm' => $fpm
                ];
            })->values()->toArray();

            // Logbook Table Aggregation
            $logbook = [];
            $aircraftGroups = $pireps->groupBy(function($p) {
                return $p->airframe?->aircraftType?->name ?? 'Unknown';
            });

            foreach ($aircraftGroups as $type => $groupPireps) {
                $fpm_vals = $groupPireps->pluck('touchdown_rate_fpm')->filter();
                $groupFuel = (int) round($groupPireps->sum('fuel_used') ?: $groupPireps->sum('block_fuel'));
                
                $logbook[] = [
                    'type' => $type,
                    'flights' => $groupPireps->count(),
                    'passengers' => (int) $groupPireps->sum('passengers'),
                    'freight' => (int) $groupPireps->sum('freight'),
                    'air_time' => (int) $groupPireps->sum('flight_time'),
                    'fuel_used' => $groupFuel,
                    'takeoffs_day' => $groupPireps->where('is_day_takeoff', true)->count(),
                    'takeoffs_night' => $groupPireps->where('is_day_takeoff', false)->count(),
                    'landings_day' => $groupPireps->where('is_day_landing', true)->count(),
                    'landings_night' => $groupPireps->where('is_day_landing', false)->count(),
                    'avg_fpm' => $fpm_vals->count() > 0 ? (int) round($fpm_vals->avg()) : 0,
                ];
            }

            UserStatistic::updateOrCreate([
                'user_id' => $this->userId,
                'tenant_id' => $tenantId
            ], [
                'total_flights' => $total_flights,
                'total_flight_time' => $total_flight_time,
                'total_passengers' => $total_passengers,
                'total_freight' => $total_freight,
                'total_block_fuel' => $total_block_fuel,
                'avg_landing_rate' => $avg_landing_rate,
                'aircraft_types_json' => $aircraft_types_json,
                'callsigns_json' => $callsigns_json,
                'networks_json' => $networks_json,
                'takeoffs_json' => $takeoffs_json,
                'simulators_json' => $simulators_json,
                'events_json' => $events_json,
                'route_types_json' => $route_types_json,
                'landings_json' => $landings_json,
                'flights_per_month_json' => $flights_per_month_json,
                'landing_rate_history_json' => $landing_rate_history_json,
                'logbook_json' => $logbook,
            ]);
        }
    }
}
