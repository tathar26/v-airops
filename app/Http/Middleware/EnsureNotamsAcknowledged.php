<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotamsAcknowledged
{
    /**
     * Handle an incoming request and ensure pilot has acknowledged all active NOTAMs before booking/dispatching.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Resolve active tenant / airline
        $airlineId = session('active_airline_id') ?? $user->getActiveTenantId() ?? $user->tenant_id;

        if ($airlineId && $user->hasUnreadNotams($airlineId)) {
            $message = 'You must read and acknowledge all active NOTAMs before booking a flight.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error'          => $message,
                    'redirect'       => route('notams'),
                    'unread_notams'  => true,
                ], 403);
            }

            session()->flash('warning', $message);
            session()->flash('notam_block', true);

            return redirect()->route('notams');
        }

        return $next($request);
    }
}
