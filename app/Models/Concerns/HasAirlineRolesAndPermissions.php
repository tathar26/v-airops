<?php

namespace App\Models\Concerns;

use App\Models\AirlineRole;
use App\Models\Tenant;
use App\Models\UserAirlineRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait HasAirlineRolesAndPermissions
{
    /**
     * User's assigned roles across all virtual airlines.
     */
    public function airlineRoles(): HasMany
    {
        return $this->hasMany(UserAirlineRole::class, 'user_id');
    }

    /**
     * Check if the user is a Global System Administrator.
     */
    public function isSystemAdmin(): bool
    {
        return (bool) $this->is_system_admin || $this->hasRole('Master Admin');
    }

    /**
     * Check if the user has a specific permission within an airline context.
     * GLOBAL SYSTEM ADMINISTRATORS AUTOMATICALLY BYPASS THIS CHECK.
     */
    public function hasAirlinePermission(string $permission, int|Tenant|null $airline = null): bool
    {
        // 1. Global System Administrator Override: Always allow
        if ($this->isSystemAdmin()) {
            return true;
        }

        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return false;
        }

        // 2. VA Creator / Owner automatic full access for their airline
        $tenant = Tenant::find($airlineId);
        if ($tenant && ($tenant->created_by === $this->id || $this->hasRole('VA Owner'))) {
            return true;
        }

        // 3. Evaluate permissions across user's assigned roles for this airline
        $roles = $this->getRolesForAirline($airlineId);
        foreach ($roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user holds a specific role in an airline context.
     */
    public function hasAirlineRole(string|array $roleSlug, int|Tenant|null $airline = null): bool
    {
        if ($this->isSystemAdmin()) {
            return true;
        }

        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return false;
        }

        $slugs = is_array($roleSlug) ? $roleSlug : [$roleSlug];
        $userRoles = $this->getRolesForAirline($airlineId);

        return $userRoles->whereIn('slug', $slugs)->isNotEmpty();
    }

    /**
     * Retrieve all active roles assigned to this user within a specific airline.
     */
    public function getRolesForAirline(int|Tenant|null $airline = null): Collection
    {
        $airlineId = $this->resolveAirlineId($airline);
        if (!$airlineId) {
            return collect();
        }

        return AirlineRole::with('permissions')
            ->where('tenant_id', $airlineId)
            ->whereIn('id', function ($query) use ($airlineId) {
                $query->select('role_id')
                    ->from('user_airline_roles')
                    ->where('user_id', $this->id)
                    ->where('tenant_id', $airlineId);
            })
            ->get();
    }

    /**
     * Assign an airline-scoped role to this user.
     */
    public function assignAirlineRole(AirlineRole|int $role, int|Tenant|null $airline = null): void
    {
        $roleModel = is_numeric($role) ? AirlineRole::findOrFail($role) : $role;
        $airlineId = $this->resolveAirlineId($airline) ?? $roleModel->tenant_id;

        UserAirlineRole::firstOrCreate([
            'user_id'   => $this->id,
            'tenant_id' => $airlineId,
            'role_id'   => $roleModel->id,
        ]);
    }

    /**
     * Remove an airline-scoped role from this user.
     */
    public function removeAirlineRole(AirlineRole|int $role, int|Tenant|null $airline = null): void
    {
        $roleId = is_object($role) ? $role->id : (int) $role;
        $airlineId = $this->resolveAirlineId($airline) ?? ($role instanceof AirlineRole ? $role->tenant_id : null);

        $query = UserAirlineRole::where('user_id', $this->id)->where('role_id', $roleId);
        if ($airlineId) {
            $query->where('tenant_id', $airlineId);
        }
        $query->delete();
    }
}
