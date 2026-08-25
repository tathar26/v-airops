<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notam extends Model
{
    use HasFactory;

    protected $table = 'notams';

    protected $fillable = [
        'tenant_id',
        'author_id',
        'title',
        'body',
        'priority',
        'category',
        'expires_at',
        'posted_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'posted_at'  => 'datetime',
        'is_active'  => 'boolean',
    ];

    /* =========================================================================
     | Relationships
     |======================================================================== */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function airline(): BelongsTo
    {
        return $this->tenant();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NotamUserRead::class, 'notam_id');
    }

    public function readUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notam_user_reads', 'notam_id', 'user_id')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /* =========================================================================
     | Scopes
     |======================================================================== */

    /**
     * Scope to only active and non-expired NOTAMs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope query to a specific virtual airline / tenant.
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /* =========================================================================
     | Helper Methods & Accessors
     |======================================================================== */

    /**
     * Check if a specific user has read/acknowledged this NOTAM.
     */
    public function isReadBy(User|int $user): bool
    {
        $userId = is_object($user) ? $user->id : (int) $user;

        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Mark this NOTAM as read by a user.
     */
    public function markAsReadBy(User|int $user): NotamUserRead
    {
        $userId = is_object($user) ? $user->id : (int) $user;

        return NotamUserRead::firstOrCreate([
            'notam_id'  => $this->id,
            'user_id'   => $userId,
        ], [
            'tenant_id' => $this->tenant_id,
            'read_at'   => now(),
        ]);
    }

    /**
     * Check if this NOTAM is permanent (never expires).
     */
    public function isPermanent(): bool
    {
        return is_null($this->expires_at);
    }

    /**
     * Check if this NOTAM is expired.
     */
    public function isExpired(): bool
    {
        return !is_null($this->expires_at) && $this->expires_at->isPast();
    }

    /**
     * Get CSS badge class based on priority.
     */
    public function getPriorityBadgeClass(): string
    {
        return match (strtolower($this->priority)) {
            'high'   => 'bg-red-500/20 text-red-300 border-red-500/30',
            'medium' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
            'low'    => 'bg-sky-500/20 text-sky-300 border-sky-500/30',
            default  => 'bg-gray-700/50 text-gray-300 border-white/10',
        };
    }
}
