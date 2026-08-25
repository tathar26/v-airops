<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAirlinePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission  Granular permission slug (e.g. 'view_routes', 'edit_routes', 'manage_fleet')
     * @param  string|null  $routeParam  Optional name of route parameter holding tenant/airline ID
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $routeParam = null): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // 1. Global System Administrator Override: Always Allow
        if ($user->isSystemAdmin()) {
            return $next($request);
        }

        // 2. Resolve Airline Context
        $airlineId = null;
        if ($routeParam && $request->route($routeParam)) {
            $val = $request->route($routeParam);
            $airlineId = is_object($val) ? ($val->id ?? $val->tenant_id ?? null) : (int) $val;
        } else {
            $airlineId = session('active_airline_id') ?? $user->getActiveTenantId();
        }

        if (!$airlineId) {
            abort(400, 'Virtual airline context is missing.');
        }

        // 3. Evaluate Granular Permission
        if (!$user->hasAirlinePermission($permission, $airlineId)) {
            abort(403, "Forbidden. You do not have the required [{$permission}] permission for this virtual airline.");
        }

        return $next($request);
    }
}
