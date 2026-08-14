<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcarsEvent extends Model
{
    use HasFactory;

    protected $table = 'acars_events';

    protected $fillable = [
        'flight_id',
        'timestamp',
        'event_type',
        'description',
        'severity',
        'penalty_points',
        'telemetry_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'penalty_points' => 'integer',
            'telemetry_snapshot' => 'array',
        ];
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(AcarsActiveFlight::class, 'flight_id');
    }
}
