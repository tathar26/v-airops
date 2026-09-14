<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityTeam extends Model
{
    protected $table = 'activity_teams';

    protected $fillable = [
        'activity_id',
        'name',
        'count_type',
        'target_value',
        'current_value',
        'filters',
    ];

    protected $casts = [
        'target_value' => 'integer',
        'current_value' => 'integer',
        'filters' => 'array',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(ActivityContribution::class, 'team_id');
    }

    public function getPercentageAttribute(): float
    {
        if ($this->target_value <= 0) {
            return 0.0;
        }

        return min(100.0, round(($this->current_value / $this->target_value) * 100, 1));
    }

    public function getActualPercentageAttribute(): float
    {
        if ($this->target_value <= 0) {
            return 0.0;
        }

        return round(($this->current_value / $this->target_value) * 100, 1);
    }

    public function isTargetReached(): bool
    {
        return $this->current_value >= $this->target_value;
    }
}
