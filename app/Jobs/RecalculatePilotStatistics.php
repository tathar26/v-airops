<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Pirep;
use App\Models\UserStatistic;
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
     * Execute the job.
     */
    public function handle(): void
    {
        $profiles = \App\Models\PilotProfile::where('user_id', $this->userId)->get();

        foreach ($profiles as $profile) {
            $tenantId = $profile->tenant_id;
            
            $pireps = Pirep::where('user_id', $this->userId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'Accepted')
                ->with(['route', 'airframe.aircraftType'])
                ->get();

            if ($pireps->isEmpty()) {
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

            $total_flights = $pireps->count();
            $total_flight_time = $pireps->sum('flight_time');
            $total_passengers = $pireps->sum('passengers');
            $total_freight = $pireps->sum('freight');
            $total_block_fuel = $pireps->sum('block_fuel');
            
            $landing_rates = $pireps->pluck('touchdown_rate_fpm')->filter();
            $avg_landing_rate = $landing_rates->count() > 0 ? (int) $landing_rates->avg() : null;

            // Groupings
            $aircraft_types_json = $pireps->groupBy(function($p) {
                return $p->airframe?->aircraftType?->name ?? 'Unknown';
            })->map->count()->toArray();

            $callsigns_json = $pireps->groupBy(function($p) {
                // Extract the prefix (letters) from flight_number, e.g., DEMO101 -> DEMO
                preg_match('/^[A-Za-z]+/', $p->route?->flight_number ?? '', $matches);
                return $matches[0] ?? 'Unknown';
            })->map->count()->toArray();

            $networks_json = $pireps->groupBy('network')->map->count()->toArray();
            $simulators_json = $pireps->groupBy('simulator')->map->count()->toArray();

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
                return $p->route?->route_type ?? 'Unknown';
            })->map->count()->toArray();

            // Flights per month (e.g. "Aug 26" -> count)
            $flights_per_month_json = $pireps->groupBy(function($p) {
                return $p->created_at->format('M y');
            })->map->count()->toArray();

            // Landing rate history (array of objects { date, fpm })
            $landing_rate_history_json = $pireps->map(function($p) {
                return [
                    'date' => $p->created_at->format('M y'), // or Y-m-d
                    'fpm' => $p->touchdown_rate_fpm
                ];
            })->values()->toArray();

            // Logbook Table Aggregation
            $logbook = [];
            $aircraftGroups = $pireps->groupBy(function($p) {
                return $p->airframe?->aircraftType?->name ?? 'Unknown';
            });

            foreach ($aircraftGroups as $type => $groupPireps) {
                $fpm_vals = $groupPireps->pluck('touchdown_rate_fpm')->filter();
                
                $logbook[] = [
                    'type' => $type,
                    'flights' => $groupPireps->count(),
                    'passengers' => $groupPireps->sum('passengers'),
                    'freight' => $groupPireps->sum('freight'),
                    'air_time' => $groupPireps->sum('flight_time'),
                    'fuel_used' => $groupPireps->sum('block_fuel'),
                    'takeoffs_day' => $groupPireps->where('is_day_takeoff', true)->count(),
                    'takeoffs_night' => $groupPireps->where('is_day_takeoff', false)->count(),
                    'landings_day' => $groupPireps->where('is_day_landing', true)->count(),
                    'landings_night' => $groupPireps->where('is_day_landing', false)->count(),
                    'avg_fpm' => $fpm_vals->count() > 0 ? (int) $fpm_vals->avg() : 0,
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
