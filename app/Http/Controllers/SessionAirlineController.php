<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionAirlineController extends Controller
{
    /**
     * Show Slack-style workspace selection page for multi-airline pilots.
     */
    public function showSelectActiveAirlinePage()
    {
        $user = Auth::user();
        $userAirlines = $user->userAirlines()->with('tenant')->get();

        // If user belongs to 0 airlines, send to onboarding
        if ($userAirlines->isEmpty()) {
            return redirect()->route('onboarding.select-airline');
        }

        return view('auth.select-active-airline', [
            'user' => $user,
            'userAirlines' => $userAirlines,
            'activeAirlineId' => session('active_airline_id'),
        ]);
    }

    /**
     * Set active airline context in session (from Slack-style screen or Top-Right Header Switcher).
     */
    public function switchActiveAirline(Request $request)
    {
        $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
        ]);

        $user = Auth::user();
        $tenantId = (int) $request->input('tenant_id');

        // Verify user actually belongs to this requested airline
        $enrolled = $user->userAirlines()->where('tenant_id', $tenantId)->first();

        if (!$enrolled) {
            return back()->withErrors(['tenant_id' => 'You are not enrolled as a pilot for this Virtual Airline.']);
        }

        // Set session active context
        session(['active_airline_id' => $tenantId]);
        $user->tenant_id = $tenantId;
        $user->save();

        // If JSON / AJAX request (from top right dropdown)
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'active_airline_id' => $tenantId,
                'airline_name' => $enrolled->tenant->name,
                'callsign' => $enrolled->callsign,
                'redirect_url' => url('/dashboard'),
            ]);
        }

        return redirect()->intended('/dashboard')->with('success', "Active airline context switched to {$enrolled->tenant->name} ({$enrolled->callsign}).");
    }
}
