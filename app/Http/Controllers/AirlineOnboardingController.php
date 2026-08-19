<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\UserAirline;
use App\Services\CallsignGeneratorService;
use App\Services\VirtualAirlineCreationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AirlineOnboardingController extends Controller
{
    protected CallsignGeneratorService $callsignService;
    protected VirtualAirlineCreationService $vaService;

    public function __construct(
        CallsignGeneratorService $callsignService,
        VirtualAirlineCreationService $vaService
    ) {
        $this->callsignService = $callsignService;
        $this->vaService = $vaService;
    }

    /**
     * Show the first-time airline selection onboarding page.
     */
    public function showSelectionPage()
    {
        $user = Auth::user();
        $availableAirlines = Tenant::approved()->get();
        
        // Get existing airline IDs user belongs to
        $joinedAirlineIds = $user->userAirlines()->pluck('tenant_id')->toArray();

        $simbriefFormats = [
            'lido' => 'LIDO - SimBrief Default',
            'ryr'  => 'RYR - Ryanair',
            'aal'  => 'AAL - American Airlines',
            'baw'  => 'BAW - British Airways',
            'dal'  => 'DAL - Delta Air Lines',
            'dlh'  => 'DLH - Lufthansa',
            'ezy'  => 'EZY - easyJet',
            'swa'  => 'SWA - Southwest Airlines',
            'ual'  => 'UAL - United Airlines',
        ];

        return view('onboarding.select-airline', [
            'user' => $user,
            'airlines' => $availableAirlines,
            'joinedAirlineIds' => $joinedAirlineIds,
            'simbriefFormats' => $simbriefFormats,
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
            $activeTenantId = (int) $newlyJoined[0]->tenant_id;
            session(['active_airline_id' => $activeTenantId]);
            $user->tenant_id = $activeTenantId;
            $user->save();
        }

        if ($user->roles->isEmpty()) {
            $pilotRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Pilot']);
            $user->assignRole($pilotRole);
        }

        return redirect()->intended('/dashboard')->with('success', 'Welcome aboard! Your unique pilot callsigns have been assigned.');
    }

    /**
     * Process creation of a new Virtual Airline by a pilot directly from onboarding.
     */
    public function createAirline(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icao' => ['required', 'string', 'min:2', 'max:4', 'alpha'],
            'base_hub_icao' => ['required', 'string', 'size:4', 'alpha'],
            'accent_color' => ['required', 'string', 'max:7'],
            'bg_color' => ['required', 'string', 'max:7'],
            'default_simbrief_ofp_format' => ['required', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:1024'],
        ], [
            'name.required' => 'The Virtual Airline name is required.',
            'icao.required' => 'The Airline ICAO code is required (e.g. EZY, BAW).',
            'base_hub_icao.required' => 'A Base Hub airport ICAO code is required (e.g. EGLL, KJFK).',
            'base_hub_icao.size' => 'The Base Hub ICAO code must be exactly 4 characters.',
        ]);

        $user = Auth::user();

        try {
            $tenant = $this->vaService->createVirtualAirline(
                $validated,
                $user,
                $request->file('logo')
            );

            return redirect()->route('dashboard')->with('success', "Virtual Airline '{$tenant->name}' ({$tenant->icao}) created successfully! It has been submitted for System Administrator approval and an email notification was sent to the platform owner.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput()->with('show_create_modal', true);
        } catch (\Throwable $e) {
            return back()->withErrors(['general' => 'Failed to create Virtual Airline: ' . $e->getMessage()])->withInput()->with('show_create_modal', true);
        }
    }
}
