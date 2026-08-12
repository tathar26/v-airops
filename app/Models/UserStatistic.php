<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStatistic extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'total_flights',
        'total_flight_time',
        'total_passengers',
        'total_freight',
        'total_block_fuel',
        'avg_landing_rate',
        'aircraft_types_json',
        'callsigns_json',
        'networks_json',
        'takeoffs_json',
        'simulators_json',
        'events_json',
        'route_types_json',
        'landings_json',
        'flights_per_month_json',
        'landing_rate_history_json',
        'logbook_json',
    ];

    protected $casts = [
        'total_flights' => 'integer',
        'total_flight_time' => 'integer',
        'total_passengers' => 'integer',
        'total_freight' => 'integer',
        'total_block_fuel' => 'integer',
        'avg_landing_rate' => 'integer',
        'aircraft_types_json' => 'array',
        'callsigns_json' => 'array',
        'networks_json' => 'array',
        'takeoffs_json' => 'array',
        'simulators_json' => 'array',
        'events_json' => 'array',
        'route_types_json' => 'array',
        'landings_json' => 'array',
        'flights_per_month_json' => 'array',
        'landing_rate_history_json' => 'array',
        'logbook_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
