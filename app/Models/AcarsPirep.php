<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcarsPirep extends Model
{
    use HasFactory;

    protected $table = 'acars_pireps';

    protected $fillable = [
        'flight_id',
        'user_id',
        'submitted_at',
        'block_off_time',
        'block_on_time',
        'block_time_minutes',
        'fuel_used_kg',
        'touchdown_fpm',
        'touchdown_gforce',
        'landing_grade',
        'total_score',
        'flight_log_json',
        'penalties_json',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'block_off_time' => 'datetime',
            'block_on_time' => 'datetime',
            'block_time_minutes' => 'integer',
            'fuel_used_kg' => 'float',
            'touchdown_fpm' => 'float',
            'touchdown_gforce' => 'float',
            'total_score' => 'integer',
            'flight_log_json' => 'array',
            'penalties_json' => 'array',
        ];
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(AcarsActiveFlight::class, 'flight_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
