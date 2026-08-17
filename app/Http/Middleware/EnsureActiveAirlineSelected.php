<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAirlineSelected
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // 1. Email Verification Guard
        if (!$user->email_verified_at) {
            if (!$request->routeIs('auth.verify-notice', 'auth.verify', 'logout')) {
                return redirect()->route('auth.verify-notice');
            }
        }

        // 2. Airline Enrollment & Active Context Guard
        $userAirlines = $user->userAirlines;
        $count = $userAirlines->count();

        if ($count === 0) {
            if (!$request->routeIs('onboarding.*', 'logout', 'auth.*')) {
                return redirect()->route('onboarding.select-airline');
            }
        } elseif ($count === 1) {
            // Auto-set session if only 1 airline exists
            if (!session()->has('active_airline_id')) {
                session(['active_airline_id' => $userAirlines->first()->tenant_id]);
            }
        } else {
            // >1 Airlines: Check if active_airline_id session is valid
            $activeId = session('active_airline_id');
            $isValidActive = $activeId && $userAirlines->pluck('tenant_id')->contains($activeId);

            if (!$isValidActive) {
                if (!$request->routeIs('session.select-airline', 'session.switch-airline', 'onboarding.*', 'logout', 'auth.*')) {
                    return redirect()->route('session.select-airline');
                }
            }
        }

        return $next($request);
    }
}
