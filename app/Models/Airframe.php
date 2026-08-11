<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Airframe extends Model
{
    use BelongsToTenant;

    protected $fillable = ['registration', 'name', 'aircraft_type_id', 'tenant_id'];

    public function aircraftType()
    {
        return $this->belongsTo(AircraftType::class);
    }
}
