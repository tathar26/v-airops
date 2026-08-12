<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Route extends Model
{
    use BelongsToTenant;

    protected $fillable = ['flight_number', 'departure_icao', 'arrival_icao', 'block_time', 'route_string', 'tenant_id', 'route_type', 'distance', 'callsign', 'remarks', 'operator'];

    public function aircraftTypes()
    {
        return $this->belongsToMany(AircraftType::class);
    }
}
