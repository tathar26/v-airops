<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemGlobalFlight extends Model
{
    protected $table = 'system_global_flights';

    protected $fillable = [
        'original_tenant_id',
        'flight_number',
        'operator',
        'departure_icao',
        'arrival_icao',
        'block_time',
        'route_type',
        'distance',
        'aircraft_types',
        'route_hash',
    ];
}
