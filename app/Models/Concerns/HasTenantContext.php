<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Models\TenantHub;

trait HasTenantContext
{
    /**
     * Helper to resolve active tenant/airline ID.
     */
    public function resolveAirlineId(int|Tenant|null $airline = null): ?int
    {
        if ($airline instanceof Tenant) {
            return (int) $airline->id;
        }

        if (is_numeric($airline)) {
            return (int) $airline;
        }

        return session('active_airline_id') ?? $this->tenant_id;
    }

    public function getTenantIdAttribute($value)
    {
        if ($value) {
            return (int) $value;
        }

        $sessionActive = session('active_airline_id');
        if ($sessionActive) {
            return (int) $sessionActive;
        }

        $firstEnrolled = $this->userAirlines()->first();
        return $firstEnrolled ? (int) $firstEnrolled->tenant_id : null;
    }

    public function getActiveTenantId(): ?int
    {
        return $this->tenant_id ? (int) $this->tenant_id : null;
    }

    public function activeUserAirline()
    {
        $activeTenantId = session('active_airline_id') ?? $this->tenant_id;
        if (!$activeTenantId) {
            return $this->userAirlines()->first();
        }

        return $this->userAirlines()->where('tenant_id', $activeTenantId)->first();
    }

    /**
     * Get user's formatted full name (e.g. Glenn Satory).
     */
    public function getFullNameAttribute(): string
    {
        $first = trim($this->first_name ?? '');
        $last = trim($this->last_name ?? '');

        if ($first !== '' || $last !== '') {
            return trim("{$first} {$last}");
        }

        return $this->name ?? 'Pilot';
    }

    /**
     * Get the active pilot callsign for the current virtual airline.
     */
    public function activeCallsign(): ?string
    {
        $activeRecord = $this->activeUserAirline();
        if ($activeRecord && $activeRecord->callsign) {
            return $activeRecord->callsign;
        }

        return $this->callsign ?: 'No Callsign';
    }

    /**
     * Get the pilot's current location ICAO airport code for the active airline.
     */
    public function getCurrentLocationIcaoAttribute(): string
    {
        $tenantId = $this->getActiveTenantId() ?? $this->tenant_id;
        if ($tenantId) {
            $profile = $this->getPilotProfile($tenantId);
            if ($profile) {
                return $profile->current_location_icao;
            }

            $baseHub = TenantHub::with('airport')->where('tenant_id', $tenantId)->where('is_base', true)->first();
            if ($baseHub && $baseHub->airport) {
                return $baseHub->airport->icao;
            }
        }

        return 'EGLL';
    }
}
