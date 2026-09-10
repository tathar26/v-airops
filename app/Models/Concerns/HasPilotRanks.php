<?php

namespace App\Models\Concerns;

use App\Models\PilotProfile;
use App\Models\Rank;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasPilotRanks
{
    public function pilotProfiles(): HasMany
    {
        return $this->hasMany(PilotProfile::class);
    }

    /**
     * Get the pilot profile for an airline context.
     * Checks relation cache first to optimize eager-loaded queries.
     */
    public function getPilotProfile(int|Tenant|null $airline = null): ?PilotProfile
    {
        $airlineId = $this->resolveAirlineId($airline);

        if ($this->relationLoaded('pilotProfiles')) {
            if ($airlineId) {
                $matched = $this->pilotProfiles->firstWhere('tenant_id', $airlineId);
                if ($matched) {
                    return $matched;
                }
            } elseif ($this->pilotProfiles->isNotEmpty()) {
                return $this->pilotProfiles->first();
            }
        }

        if ($airlineId) {
            return $this->pilotProfiles()->where('tenant_id', $airlineId)->first();
        }

        return $this->pilotProfiles()->first();
    }

    /**
     * Auto-calculate the pilot's standard rank based on total flight hours for this airline.
     */
    public function getAutoCalculatedRank(int|Tenant|null $airline = null): ?Rank
    {
        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return null;
        }

        $profile = $this->getPilotProfile($airlineId);
        if ($profile && $profile->rank_id) {
            if ($profile->relationLoaded('rank') && $profile->rank) {
                return $profile->rank;
            }
            $explicitRank = Rank::where('tenant_id', $airlineId)->find($profile->rank_id);
            if ($explicitRank) {
                return $explicitRank;
            }
        }

        // Highest matching regular rank meeting min_hours, min_points, min_bonus_points
        $totalHours = $profile ? floor($profile->flight_time / 60) : 0;
        $totalPoints = $profile ? $profile->points : 0;
        $totalBonus = $profile ? ($profile->bonus_points ?? 0) : 0;

        $calculatedRank = Rank::where('tenant_id', $airlineId)
            ->where('is_honorary', false)
            ->where('min_hours', '<=', $totalHours)
            ->where('min_points', '<=', $totalPoints)
            ->where('min_bonus_points', '<=', $totalBonus)
            ->orderBy('position', 'desc')
            ->orderBy('min_hours', 'desc')
            ->first();

        if ($calculatedRank) {
            return $calculatedRank;
        }

        // Fallback: Lowest regular rank
        return Rank::where('tenant_id', $airlineId)
            ->where('is_honorary', false)
            ->orderBy('position', 'asc')
            ->orderBy('min_hours', 'asc')
            ->first();
    }

    /**
     * Get the pilot's regular rank in the specified airline.
     */
    public function getRankForAirline(int|Tenant|null $airline = null): ?Rank
    {
        return $this->getAutoCalculatedRank($airline);
    }

    /**
     * Get the honorary rank model if assigned in this airline.
     */
    public function getHonoraryRank(int|Tenant|null $airline = null): ?Rank
    {
        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return null;
        }

        $profile = $this->getPilotProfile($airlineId);
        if ($profile && $profile->honorary_rank_id) {
            if ($profile->relationLoaded('honoraryRank') && $profile->honoraryRank) {
                return $profile->honoraryRank;
            }
            return Rank::where('tenant_id', $airlineId)->find($profile->honorary_rank_id);
        }

        return null;
    }

    /**
     * Get the honorary rank string if user holds one in this airline.
     */
    public function getHonoraryRankString(int|Tenant|null $airline = null): ?string
    {
        $honoraryRank = $this->getHonoraryRank($airline);
        if ($honoraryRank) {
            return $honoraryRank->name;
        }

        // Fallback to role-based honorary rank string if present
        $roles = $this->getRolesForAirline($airline);
        foreach ($roles as $role) {
            if ($role->is_staff && !empty($role->honorary_rank_string)) {
                return $role->honorary_rank_string;
            }
        }

        return null;
    }

    /**
     * Master Display Rank Logic:
     * - Returns the Honorary Rank if prefer_honorary_rank is TRUE and the pilot holds an honorary rank.
     * - Otherwise, returns the standard regular rank name.
     */
    public function getDisplayRank(int|Tenant|null $airline = null): string
    {
        $airlineId = $this->resolveAirlineId($airline);
        $profile = $this->getPilotProfile($airlineId);

        $preferHonorary = $profile ? $profile->prefer_honorary_rank : $this->prefer_honorary_rank;

        // 1. If user prefers honorary rank and has one assigned in this airline
        if ($preferHonorary) {
            $honorary = $this->getHonoraryRankString($airlineId);
            if (!empty($honorary)) {
                return $honorary;
            }
        }

        // 2. Fallback to regular rank
        $regularRank = $this->getRankForAirline($airlineId);
        if ($regularRank) {
            return $regularRank->name;
        }

        // 3. Final default
        $userAirline = $this->activeUserAirline();
        if ($userAirline && $userAirline->rank) {
            return $userAirline->rank;
        }

        return 'Cadet';
    }

    public function isDisplayingHonoraryRank(int|Tenant|null $airline = null): bool
    {
        $airlineId = $this->resolveAirlineId($airline);
        $profile = $this->getPilotProfile($airlineId);
        $preferHonorary = $profile ? $profile->prefer_honorary_rank : $this->prefer_honorary_rank;

        return (bool) ($preferHonorary && $this->getHonoraryRankString($airlineId));
    }

    public function getDisplayRankImageUrl(int|Tenant|null $airline = null): string
    {
        $airlineId = $this->resolveAirlineId($airline);
        $profile = $this->getPilotProfile($airlineId);

        if ($profile) {
            return $profile->getDisplayRankImageUrl();
        }

        $rank = $this->getRankForAirline($airlineId);
        return $rank ? $rank->image_url : asset('images/epaulettes/epaulette-01.png');
    }

    /**
     * Get the pilot's active rank name string (uses display rank logic).
     */
    public function getActiveRankNameAttribute(): string
    {
        return $this->getDisplayRank();
    }

    /**
     * Dynamic display rank accessor.
     */
    public function getDisplayRankAttribute(): string
    {
        return $this->getDisplayRank();
    }

    /**
     * Get the pilot's active rank model for the current virtual airline.
     */
    public function getActiveRankAttribute(): ?Rank
    {
        return $this->getAutoCalculatedRank();
    }
}
