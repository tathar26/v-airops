<?php

namespace App\Models\Concerns;

use App\Models\Notam;
use App\Models\NotamUserRead;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasAirlineNotams
{
    /**
     * User's acknowledged NOTAM reads.
     */
    public function notamReads(): HasMany
    {
        return $this->hasMany(NotamUserRead::class, 'user_id');
    }

    /**
     * Check if the user has any unread, active, non-expired NOTAMs for an airline.
     */
    public function hasUnreadNotams(int|Tenant|null $airline = null): bool
    {
        return $this->getUnreadNotamsCount($airline) > 0;
    }

    /**
     * Get count of unread, active, non-expired NOTAMs for an airline.
     */
    public function getUnreadNotamsCount(int|Tenant|null $airline = null): int
    {
        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return 0;
        }

        return Notam::forTenant($airlineId)
            ->active()
            ->whereDoesntHave('reads', function ($q) {
                $q->where('user_id', $this->id);
            })
            ->count();
    }

    /**
     * Get the unread, active, non-expired NOTAM models for an airline.
     */
    public function getUnreadNotams(int|Tenant|null $airline = null): Collection
    {
        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return new Collection();
        }

        return Notam::forTenant($airlineId)
            ->active()
            ->whereDoesntHave('reads', function ($q) {
                $q->where('user_id', $this->id);
            })
            ->orderByRaw("CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 ELSE 4 END")
            ->orderByDesc('posted_at')
            ->get();
    }
}
