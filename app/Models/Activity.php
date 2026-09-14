<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Activity extends Model
{
    protected $table = 'activities';

    protected $fillable = [
        'tenant_id',
        'author_id',
        'name',
        'slug',
        'type',
        'subtype',
        'description',
        'sidebar_title',
        'sidebar_content',
        'image_url',
        'tags',
        'start_at',
        'end_at',
        'show_from',
        'time_leeway_minutes',
        'time_award_scale',
        'restrictions',
        'event_criteria',
        'registration_required',
        'registration_start_at',
        'registration_end_at',
        'points_mode',
        'points_value',
        'point_award_mode',
        'allow_repeat',
        'is_active',
        'community_metric',
        'community_target',
        'community_current',
        'community_completion_points',
        'community_tiered_multipliers',
        'tier_multipliers',
        'slotted_departure_icao',
        'slotted_callsign_system',
        'slotted_generator_pattern',
        'slotted_dispatch_window_before',
        'slotted_dispatch_window_after',
        'slotted_networks',
    ];

    protected $casts = [
        'tags' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'show_from' => 'datetime',
        'time_leeway_minutes' => 'integer',
        'restrictions' => 'array',
        'event_criteria' => 'array',
        'registration_required' => 'boolean',
        'registration_start_at' => 'datetime',
        'registration_end_at' => 'datetime',
        'points_value' => 'integer',
        'allow_repeat' => 'boolean',
        'is_active' => 'boolean',
        'community_target' => 'integer',
        'community_current' => 'integer',
        'community_completion_points' => 'integer',
        'community_tiered_multipliers' => 'boolean',
        'tier_multipliers' => 'array',
        'slotted_dispatch_window_before' => 'integer',
        'slotted_dispatch_window_after' => 'integer',
        'slotted_networks' => 'array',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function legs(): HasMany
    {
        return $this->hasMany(ActivityLeg::class, 'activity_id')->orderBy('sequence', 'asc');
    }

    public function waves(): HasMany
    {
        return $this->hasMany(ActivityWave::class, 'activity_id')->orderBy('position', 'asc');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ActivitySlot::class, 'activity_id')->orderBy('slot_time', 'asc');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(ActivityTeam::class, 'activity_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class, 'activity_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(ActivityContribution::class, 'activity_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('show_from')
                           ->orWhere('show_from', '<=', Carbon::now('UTC'));
                     });
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now('UTC');
        return $query->where('is_active', true)
                     ->where('start_at', '<=', $now)
                     ->where('end_at', '>=', $now);
    }

    // ── Type Checkers ────────────────────────────────────────────────────────

    public function isMultiLeg(): bool
    {
        return in_array($this->type, ['tour', 'roster', 'curated_roster']);
    }

    public function isSlotted(): bool
    {
        return $this->type === 'slotted_event';
    }

    public function isCommunity(): bool
    {
        return in_array($this->type, ['community_goal', 'community_challenge']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'event' => 'Event',
            'slotted_event' => 'Slotted Event',
            'focus_airport' => 'Focus Airport',
            'tour' => 'Tour',
            'roster' => 'Roster',
            'curated_roster' => 'Curated Roster',
            'community_goal' => 'Community Goal',
            'community_challenge' => 'Community Challenge',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getStatusAttribute(): string
    {
        $now = Carbon::now('UTC');
        if (!$this->is_active) {
            return 'Draft';
        }
        if ($now->lt($this->start_at)) {
            return 'Upcoming';
        }
        if ($now->gt($this->end_at)) {
            return 'Ended';
        }
        return 'Active';
    }

    public function isRegistrationOpen(): bool
    {
        if (!$this->registration_required) {
            return true;
        }

        $now = Carbon::now('UTC');

        if ($this->registration_start_at && $now->lt($this->registration_start_at)) {
            return false;
        }

        if ($this->registration_end_at && $now->gt($this->registration_end_at)) {
            return false;
        }

        return $this->getStatusAttribute() !== 'Ended';
    }

    public function isRegistered(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        return $this->registrations()->where('user_id', $user->id)->exists();
    }

    public function getPilotRegistration(?User $user = null): ?ActivityRegistration
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }

        return $this->registrations()->where('user_id', $user->id)->first();
    }

    public function getRegistrationsCountAttribute(): int
    {
        return $this->registrations()->count();
    }

    public function getCompletionsCountAttribute(): int
    {
        return $this->registrations()->where('status', 'completed')->count();
    }

    public function getEstimatedFlightTimeAttribute(): string
    {
        if ($this->isMultiLeg()) {
            $totalMinutes = 0;
            foreach ($this->legs as $leg) {
                if ($leg->route && $leg->route->block_time) {
                    $parts = explode(':', $leg->route->block_time);
                    $totalMinutes += ((int)($parts[0] ?? 0) * 60) + (int)($parts[1] ?? 0);
                } else {
                    // Approximate fallback of 90 mins per leg
                    $totalMinutes += 90;
                }
            }
            $hours = floor($totalMinutes / 60);
            $mins = $totalMinutes % 60;
            return "{$hours}h {$mins}m";
        }

        if ($this->type === 'focus_airport') {
            return 'Varies (Hub Operations)';
        }

        return '1h – 4h';
    }
}
