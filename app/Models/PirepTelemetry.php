<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PirepTelemetry extends Model
{
    use HasFactory;

    protected $table = 'pirep_telemetries';

    protected $fillable = [
        'pirep_id',
        'flight_hash',
        'latitude',
        'longitude',
        'altitude_msl',
        'ground_speed_kts',
        'heading',
        'elapsed_seconds',
    ];

    public function pirep()
    {
        return $this->belongsTo(Pirep::class);
    }
}
