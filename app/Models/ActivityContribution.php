<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityContribution extends Model
{
    protected $table = 'activity_contributions';

    protected $fillable = [
        'activity_id',
        'team_id',
        'user_id',
        'pirep_id',
        'metric_value',
        'points_awarded',
    ];

    protected $casts = [
        'metric_value' => 'integer',
        'points_awarded' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ActivityTeam::class, 'team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pirep(): BelongsTo
    {
        return $this->belongsTo(Pirep::class, 'pirep_id');
    }
}
