<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\UserAirline;
use App\Services\CallsignGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AirlineOnboardingController extends Controller
{
    protected CallsignGeneratorService $callsignService;

    public function __construct(CallsignGeneratorService $callsignService)
    {
        $this->callsignService = $callsignService;
    }

    /**
     * Show the first-time airline selection onboarding page.
     */
    public function showSelectionPage()
    {
        $user = Auth::user();
        $availableAirlines = Tenant::all();
        
        // Get existing airline IDs user belongs to
        $joinedAirlineIds = $user->userAirlines()->pluck('tenant_id')->toArray();

        return view('onboarding.select-airline', [
            'user' => $user,
            'airlines' => $availableAirlines,
            'joinedAirlineIds' => $joinedAirlineIds,
        ]);
    }

    /**
     * Process onboarding submission: join selected airlines and auto-generate unique callsigns.
     */
    public function joinAirlines(Request $request)
    {
        $request->validate([
            'airline_ids' => ['required', 'array', 'min:1'],
            'airline_ids.*' => ['required', 'exists:tenants,id'],
        ]);

        $user = Auth::user();
        $selectedIds = $request->input('airline_ids');
        $newlyJoined = [];

        foreach ($selectedIds as $tenantId) {
            $tenant = Tenant::findOrFail($tenantId);
            $userAirline = $this->callsignService->assignUserToAirline($user, $tenant, 'Cadet');
            $newlyJoined[] = $userAirline;
        }

        // Auto set active session context to the first joined airline
        if (count($newlyJoined) > 0) {
            session(['active_airline_id' => $newlyJoined[0]->tenant_id]);
        }

        return redirect()->intended('/dashboard')->with('success', 'Welcome aboard! Your unique pilot callsigns have been assigned.');
    }
}
