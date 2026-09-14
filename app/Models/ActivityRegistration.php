<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityRegistration extends Model
{
    protected $table = 'activity_registrations';

    protected $fillable = [
        'activity_id',
        'user_id',
        'tenant_id',
        'status',
        'points_awarded',
        'team_id',
        'slot_id',
        'registered_at',
        'completed_at',
    ];

    protected $casts = [
        'points_awarded' => 'integer',
        'registered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ActivityTeam::class, 'team_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ActivitySlot::class, 'slot_id');
    }

    public function legProgress(): HasMany
    {
        return $this->hasMany(ActivityLegProgress::class, 'registration_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getCompletedLegsCountAttribute(): int
    {
        return $this->legProgress()->where('is_valid', true)->count();
    }

    public function getTotalLegsCountAttribute(): int
    {
        return $this->activity ? $this->activity->legs()->count() : 0;
    }

    public function getCompletionPercentageAttribute(): float
    {
        $total = $this->getTotalLegsCountAttribute();
        if ($total <= 0) {
            return 0.0;
        }

        return min(100.0, round(($this->getCompletedLegsCountAttribute() / $total) * 100, 1));
    }

    public function getNextIncompleteLeg(): ?ActivityLeg
    {
        if (!$this->activity) {
            return null;
        }

        $completedLegIds = $this->legProgress()
            ->where('is_valid', true)
            ->pluck('activity_leg_id')
            ->toArray();

        return $this->activity->legs()
            ->whereNotIn('id', $completedLegIds)
            ->orderBy('sequence', 'asc')
            ->first();
    }

    public function markAsCompleted(int $points = 0): void
    {
        $this->status = 'completed';
        $this->completed_at = Carbon::now('UTC');
        $this->points_awarded += $points;
        $this->save();
    }
}
