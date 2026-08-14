<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcarsActiveFlight extends Model
{
    use HasFactory;

    protected $table = 'acars_active_flights';

    protected $fillable = [
        'user_id',
        'flight_number',
        'origin_icao',
        'destination_icao',
        'route',
        'aircraft_type',
        'planned_altitude',
        'planned_fuel_kg',
        'planned_zfw_kg',
        'simbrief_ofp_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'planned_altitude' => 'integer',
            'planned_fuel_kg' => 'float',
            'planned_zfw_kg' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(AcarsPosition::class, 'flight_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AcarsEvent::class, 'flight_id');
    }

    public function pirep(): HasOne
    {
        return $this->hasOne(AcarsPirep::class, 'flight_id');
    }
}
