<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcarsPosition extends Model
{
    use HasFactory;

    protected $table = 'acars_positions';

    protected $fillable = [
        'flight_id',
        'timestamp',
        'latitude',
        'longitude',
        'altitude_ft',
        'ground_speed_kt',
        'indicated_airspeed_kt',
        'vertical_speed_fpm',
        'pitch_deg',
        'bank_deg',
        'heading_deg',
        'fuel_qty_kg',
        'flight_phase',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'altitude_ft' => 'float',
            'ground_speed_kt' => 'float',
            'indicated_airspeed_kt' => 'float',
            'vertical_speed_fpm' => 'float',
            'pitch_deg' => 'float',
            'bank_deg' => 'float',
            'heading_deg' => 'float',
            'fuel_qty_kg' => 'float',
        ];
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(AcarsActiveFlight::class, 'flight_id');
    }
}
