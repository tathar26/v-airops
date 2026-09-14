<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLegProgress extends Model
{
    protected $table = 'activity_leg_progress';

    protected $fillable = [
        'registration_id',
        'activity_leg_id',
        'pirep_id',
        'is_valid',
        'completed_at',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ActivityRegistration::class, 'registration_id');
    }

    public function leg(): BelongsTo
    {
        return $this->belongsTo(ActivityLeg::class, 'activity_leg_id');
    }

    public function pirep(): BelongsTo
    {
        return $this->belongsTo(Pirep::class, 'pirep_id');
    }
}
