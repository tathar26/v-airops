<?php

namespace App\Livewire\Pilot;

use Livewire\Component;
use App\Models\PilotProfile;
use App\Models\Pirep;
use App\Models\UserStatistic;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class Preferences extends Component
{
    public $useImperial;
    public $preferHonorary;
    public $preferredNetwork;
    public $simbriefFormat;
    public $simbriefUsername;

    public $resetPassword = '';
    public $deletePassword = '';

    public $showResetModal = false;
    public $showDeleteModal = false;

    public $simbriefFormats = [];
    public $tenantDefaultSimbriefFormat = 'lido';

    public function mount()
    {
        $profile = PilotProfile::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first();

        if ($profile) {
            $this->useImperial = $profile->use_imperial_units;
            $this->preferHonorary = $profile->prefer_honorary_rank;
            $this->preferredNetwork = $profile->preferred_network;
            $this->simbriefFormat = $profile->simbrief_ofp_format;
            $this->simbriefUsername = $profile->simbrief_username;
        }

        $tenant = auth()->user()->tenant;
        if ($tenant && $tenant->default_simbrief_ofp_format) {
            $this->tenantDefaultSimbriefFormat = $tenant->default_simbrief_ofp_format;
        }

        // Fetch simbrief formats and cache for 24 hours
        $this->simbriefFormats = Cache::remember('simbrief_formats', 86400, function () {
            try {
                $response = Http::timeout(5)->get('http://www.simbrief.com/api/inputs.list.json');
                if ($response->successful()) {
                    $layouts = $response->json('layouts');
                    $formats = [];
                    foreach ($layouts as $key => $layout) {
                        $formats[$key] = $layout['name_long'];
                    }
                    asort($formats);
                    return $formats;
                }
            } catch (\Exception $e) {
                // Fallback list if API fails
            }

            return [
                'lido' => 'LIDO - SimBrief Default',
                'ryr' => 'RYR - Ryanair',
                'aal' => 'AAL - American Airlines',
                'baw' => 'BAW - British Airways',
                'dal' => 'DAL - Delta Air Lines',
                'dlh' => 'DLH - Lufthansa',
                'ezy' => 'EZY - easyJet',
                'swa' => 'SWA - Southwest Airlines',
                'ual' => 'UAL - United Airlines',
            ];
        });
    }

    public function savePreferences()
    {
        $profile = PilotProfile::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first();

        if ($profile) {
            $profile->update([
                'use_imperial_units' => $this->useImperial,
                'prefer_honorary_rank' => $this->preferHonorary,
                'preferred_network' => $this->preferredNetwork ?: null,
                'simbrief_ofp_format' => $this->simbriefFormat ?: null,
                'simbrief_username' => $this->simbriefUsername ? trim($this->simbriefUsername) : null,
            ]);

            session()->flash('message', 'Preferences saved successfully.');
        }
    }

    public function confirmReset()
    {
        $this->resetErrorBag();
        $this->resetPassword = '';
        $this->dispatch('confirming-reset-account');
        $this->showResetModal = true;
    }

    public function resetAccount()
    {
        $user = Auth::user();

        if (!Hash::check($this->resetPassword, $user->password)) {
            throw ValidationException::withMessages([
                'resetPassword' => [__('This password does not match our records.')],
            ]);
        }

        $tenantId = $user->tenant_id;

        // Delete PIREPs for this VA
        Pirep::where('user_id', $user->id)->where('tenant_id', $tenantId)->delete();
        
        // Zero out PilotProfile
        PilotProfile::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->update([
                'flight_time' => 0,
                'points' => 0,
            ]);

        // Zero out UserStatistics
        UserStatistic::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->update([
                'total_flights' => 0,
                'total_flight_time' => 0,
                'total_passengers' => 0,
                'total_freight' => 0,
                'total_block_fuel' => 0,
                'avg_landing_rate' => null,
                'aircraft_types_json' => null,
                'callsigns_json' => null,
                'networks_json' => null,
                'takeoffs_json' => null,
                'simulators_json' => null,
                'events_json' => null,
                'route_types_json' => null,
                'landings_json' => null,
                'flights_per_month_json' => null,
                'landing_rate_history_json' => null,
                'logbook_json' => null,
            ]);

        $this->showResetModal = false;
        session()->flash('message', 'Account statistics have been reset.');
        return redirect()->route('dashboard');
    }

    public function confirmDelete()
    {
        $this->resetErrorBag();
        $this->deletePassword = '';
        $this->dispatch('confirming-delete-account');
        $this->showDeleteModal = true;
    }

    public function deleteAccount()
    {
        $user = Auth::user();

        if (!Hash::check($this->deletePassword, $user->password)) {
            throw ValidationException::withMessages([
                'deletePassword' => [__('This password does not match our records.')],
            ]);
        }

        $tenantId = $user->tenant_id;

        // Delete PIREPs for this VA
        Pirep::where('user_id', $user->id)->where('tenant_id', $tenantId)->delete();
        
        // Delete UserStatistics for this VA
        UserStatistic::where('user_id', $user->id)->where('tenant_id', $tenantId)->delete();

        // Delete PilotProfile for this VA
        PilotProfile::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->delete();

        // Check if user has any other profiles
        $otherProfiles = PilotProfile::where('user_id', $user->id)->count();

        if ($otherProfiles === 0) {
            // Completely delete user if they are in no other VAs
            Auth::logout();
            $user->delete();
            return redirect('/');
        } else {
            $newProfile = PilotProfile::where('user_id', $user->id)->first();
            $user->update(['tenant_id' => $newProfile->tenant_id]);
            $this->showDeleteModal = false;
            session()->flash('message', 'Pilot account deleted for this Virtual Airline.');
            return redirect()->route('dashboard');
        }
    }

    public function render()
    {
        return view('livewire.pilot.preferences')->layout('layouts.app');
    }
}
