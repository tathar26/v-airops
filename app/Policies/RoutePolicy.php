<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Route;

class RoutePolicy
{
    /**
     * Pre-authorization check: Global System Administrators bypass all airline restrictions.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view routes for the active airline.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAirlinePermission('view_routes');
    }

    /**
     * Determine whether the user can create routes for the active airline.
     */
    public function create(User $user): bool
    {
        return $user->hasAirlinePermission('create_routes');
    }

    /**
     * Determine whether the user can update the given route.
     */
    public function update(User $user, Route $route): bool
    {
        return $user->hasAirlinePermission('edit_routes', $route->tenant_id);
    }

    /**
     * Determine whether the user can delete the given route.
     */
    public function delete(User $user, Route $route): bool
    {
        return $user->hasAirlinePermission('delete_routes', $route->tenant_id);
    }
}
