<?php

namespace App\Livewire\Pilot;

use Livewire\Component;
use App\Models\Booking;
use App\Models\Airframe;
use App\Models\Airport;
use App\Models\User;
use App\Services\SimBriefService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Dispatch extends Component
{
    public Booking $booking;

    // SimBrief Integration
    public $simbrief_username = '';
    public $is_loading_simbrief = false;

    // Aircraft & Callsign
    public $airframe_id;
    public $callsign;
    public $flight_number;
    public $dispatch_via_simbrief = true;

    // Schedule & Route
    public $departure_date;
    public $departure_time;
    public $routing = '';
    public $flight_level = '';
    public $cost_index = 4;

    // Alternates
    public $auto_find_alternates = true;
    public $num_alternates = 2;
    public $alternate_1 = '';
    public $alternate_2 = '';

    // Payload
    public $passengers = 170;
    public $passengers_max = 186;
    public $hold_bags = 152;
    public $hold_bags_max = 170;
    public $estimated_zfw = 61626;

    // Network & Co-pilot
    public $network = 'Offline';
    public $copilot_user_id = null;

    // UI States
    public $showSectionAircraft = true;
    public $showSectionSchedule = true;
    public $showSectionAlternates = true;
    public $showSectionPayload = true;
    public $showSectionNetwork = true;
    public $showRouteDetails = false;
    public $showOfpView = false;

    public function mount(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $this->booking = $booking->load(['route.aircraftTypes', 'airframe.aircraftType', 'tenant', 'user']);
        $simData = $booking->simbrief_data ?? [];

        // Pilot SimBrief Username
        $profile = auth()->user()->pilotProfiles()->first();
        $this->simbrief_username = $simData['simbrief_username'] ?? ($profile->simbrief_username ?? '');

        // If booking is already dispatched or has OFP generated, show OFP View
        if ($booking->status === 'dispatched' || isset($simData['weights'])) {
            $this->showOfpView = true;
        }

        // Aircraft & Callsign Defaults
        $this->airframe_id = $booking->airframe_id;
        if (!$this->airframe_id) {
            $firstAirframe = Airframe::where('tenant_id', auth()->user()->tenant_id)->first();
            $this->airframe_id = $firstAirframe ? $firstAirframe->id : null;
        }

        $route = $booking->route;
        $tenant = $booking->tenant ?? auth()->user()->tenant;
        $defaultIcao = $tenant->icao ?? 'EZY';

        $this->callsign = $simData['general']['callsign'] 
            ?? ($simData['callsign'] 
            ?? ($route?->callsign 
            ?? ($route?->operator ? $route->operator . ($route->flight_number ?: rand(100, 999)) : ($defaultIcao . rand(100, 999) . 'HZ'))));

        $this->flight_number = $simData['general']['flight_number'] 
            ?? ($simData['flight_number'] 
            ?? ($route?->flight_number ?? $this->callsign));
        $this->dispatch_via_simbrief = $simData['dispatch_via_simbrief'] ?? true;

        // Schedule & Route Defaults
        $this->departure_date = $simData['departure_date'] ?? date('Y-m-d');
        $this->departure_time = $simData['departure_time'] ?? date('H:i', strtotime('+30 minutes'));
        $this->routing = $simData['general']['route'] ?? ($simData['routing'] ?? ($booking->route->route_string ?? ''));
        $this->flight_level = $simData['general']['initial_altitude'] ?? ($simData['flight_level'] ?? '');
        $this->cost_index = $simData['general']['cost_index'] ?? ($simData['cost_index'] ?? 4);

        // Alternates Defaults
        $this->auto_find_alternates = $simData['auto_find_alternates'] ?? true;
        $this->num_alternates = $simData['num_alternates'] ?? 2;
        $this->alternate_1 = $simData['general']['alternate'] ?? ($simData['alternate_1'] ?? '');
        $this->alternate_2 = $simData['general']['alternate2'] ?? ($simData['alternate_2'] ?? '');

        // Auto-find default alternates if empty
        if (empty($this->alternate_1)) {
            $this->findDefaultAlternates();
        }

        // Payload Defaults
        $this->passengers = $simData['weights']['pax_count'] ?? ($simData['passengers'] ?? 170);
        $this->hold_bags = $simData['weights']['bag_count'] ?? ($simData['hold_bags'] ?? 152);
        $this->recalculateZfw();

        // Network Defaults
        $this->network = $simData['network'] ?? ($profile->preferred_network ?? 'Offline');
        $this->copilot_user_id = $simData['copilot_user_id'] ?? null;

        // Auto-fetch if returning from SimBrief generation or explicitly requested
        if (request()->has('auto_fetch') && !empty($this->simbrief_username)) {
            $this->fetchLiveSimbriefOfp();
        }
    }

    public function findDefaultAlternates()
    {
        $arrIcao = $this->booking->route->arrival_icao;
        $depIcao = $this->booking->route->departure_icao;

        $nearby = Airport::where('icao', '!=', $arrIcao)
            ->where('icao', '!=', $depIcao)
            ->where(function($q) use ($arrIcao) {
                $prefix = substr($arrIcao, 0, 2);
                $q->where('icao', 'like', $prefix . '%');
            })
            ->limit(5)
            ->get();

        if ($nearby->count() >= 1) {
            $this->alternate_1 = $nearby[0]->icao;
        }
        if ($nearby->count() >= 2) {
            $this->alternate_2 = $nearby[1]->icao;
        }
    }

    public function updatedPassengers()
    {
        $this->passengers = max(0, min((int)$this->passengers, $this->passengers_max));
        $this->recalculateZfw();
    }

    public function updatedHoldBags()
    {
        $this->hold_bags = max(0, min((int)$this->hold_bags, $this->hold_bags_max));
        $this->recalculateZfw();
    }

    public function generatePassengers()
    {
        $this->passengers = rand((int)($this->passengers_max * 0.7), $this->passengers_max);
        $this->generateHoldBags();
    }

    public function generateHoldBags()
    {
        $this->hold_bags = rand((int)($this->passengers * 0.75), (int)min($this->passengers * 1.0, $this->hold_bags_max));
        $this->recalculateZfw();
    }

    public function recalculateZfw()
    {
        $paxWeight = $this->passengers * 84;
        $bagWeight = $this->hold_bags * 15;
        $oew = 42500;
        $this->estimated_zfw = $oew + $paxWeight + $bagWeight;
    }

    /**
     * Check if a fetched SimBrief OFP matches the current booking parameters (Callsign, Departure ICAO, Arrival ICAO).
     */
    public function isOfpMatchingBooking(array $liveOfp): bool
    {
        // Extract Origin ICAO safely from any SimBrief schema structure
        $ofpOrig = $liveOfp['general']['origin'] ?? ($liveOfp['origin']['icao_code'] ?? ($liveOfp['origin'] ?? ''));
        if (is_array($ofpOrig)) {
            $ofpOrig = $ofpOrig['icao_code'] ?? $ofpOrig['icao'] ?? '';
        }
        $ofpOrig = strtoupper(trim((string)$ofpOrig));

        // Extract Destination ICAO safely
        $ofpDest = $liveOfp['general']['destination'] ?? ($liveOfp['destination']['icao_code'] ?? ($liveOfp['destination'] ?? ''));
        if (is_array($ofpDest)) {
            $ofpDest = $ofpDest['icao_code'] ?? $ofpDest['icao'] ?? '';
        }
        $ofpDest = strtoupper(trim((string)$ofpDest));

        $bookingDep = strtoupper(trim($this->booking->route->departure_icao));
        $bookingArr = strtoupper(trim($this->booking->route->arrival_icao));

        // 1. Validate Departure & Arrival Airports
        if (empty($ofpOrig) || empty($ofpDest) || $ofpOrig !== $bookingDep || $ofpDest !== $bookingArr) {
            Log::info("SimBrief OFP Mismatch: Origin/Destination mismatch (OFP: '{$ofpOrig}'-'{$ofpDest}', Booking: '{$bookingDep}'-'{$bookingArr}')");
            return false;
        }

        // 2. Validate Callsign / Flight Number
        $ofpCallsign = strtoupper(trim((string)($liveOfp['atc']['callsign'] ?? $liveOfp['general']['callsign'] ?? $liveOfp['callsign'] ?? '')));
        $ofpFltNum = strtoupper(trim((string)($liveOfp['general']['flight_number'] ?? $liveOfp['flight_number'] ?? '')));
        $targetCallsign = strtoupper(trim($this->callsign));
        $targetFltNum = strtoupper(trim($this->flight_number));

        $ofpNum = preg_replace('/[^0-9]/', '', $ofpCallsign . $ofpFltNum);
        $targetNum = preg_replace('/[^0-9]/', '', $targetCallsign . $targetFltNum);

        $callsignMatches = (
            $ofpCallsign === $targetCallsign ||
            $ofpFltNum === $targetFltNum ||
            (!empty($targetNum) && !empty($ofpNum) && $ofpNum === $targetNum) ||
            (isset($liveOfp['params']['static_id']) && $liveOfp['params']['static_id'] === 'VOPS-' . $this->booking->id)
        );

        if (!$callsignMatches) {
            Log::info("SimBrief OFP Mismatch: Callsign mismatch (OFP CS: '{$ofpCallsign}', OFP Flt: '{$ofpFltNum}', Target CS: '{$targetCallsign}', Target Flt: '{$targetFltNum}')");
            return false;
        }

        return true;
    }

    /**
     * Auto-polling background check (called every 3s via wire:poll while in loading state)
     */
    public function checkLiveSimbriefOfp()
    {
        if ($this->showOfpView || empty(trim($this->simbrief_username))) {
            return;
        }

        $simbriefService = new SimBriefService();
        $liveOfp = $simbriefService->fetchLiveOfp($this->simbrief_username);

        if ($liveOfp && $this->isOfpMatchingBooking($liveOfp)) {
            $liveOfp['simbrief_username'] = trim($this->simbrief_username);

            $this->booking->update([
                'airframe_id' => $this->airframe_id,
                'simbrief_data' => $liveOfp,
                'status' => 'dispatched'
            ]);

            $this->booking->refresh();
            $this->is_loading_simbrief = false;
            $this->showOfpView = true;
            session()->flash('message', 'Real SimBrief OFP imported automatically!');
        }
    }

    /**
     * Manual trigger to fetch live SimBrief OFP
     */
    public function fetchLiveSimbriefOfp()
    {
        if (empty(trim($this->simbrief_username))) {
            session()->flash('error', 'Please enter your SimBrief Username or Pilot ID to fetch live OFP.');
            return;
        }

        $simbriefService = new SimBriefService();
        $liveOfp = $simbriefService->fetchLiveOfp($this->simbrief_username);

        if ($liveOfp && $this->isOfpMatchingBooking($liveOfp)) {
            $profile = auth()->user()->pilotProfiles()->first();
            if ($profile) {
                $profile->update(['simbrief_username' => trim($this->simbrief_username)]);
            }

            $liveOfp['simbrief_username'] = trim($this->simbrief_username);

            $this->booking->update([
                'airframe_id' => $this->airframe_id,
                'simbrief_data' => $liveOfp,
                'status' => 'dispatched'
            ]);

            $this->booking->refresh();
            $this->is_loading_simbrief = false;
            $this->showOfpView = true;
            session()->flash('message', 'Successfully imported live OFP from SimBrief!');
        } else {
            if ($liveOfp && !$this->isOfpMatchingBooking($liveOfp)) {
                $ofpOrig = $liveOfp['general']['origin'] ?? ($liveOfp['origin']['icao_code'] ?? '');
                $ofpDest = $liveOfp['general']['destination'] ?? ($liveOfp['destination']['icao_code'] ?? '');
                $ofpCs = $liveOfp['atc']['callsign'] ?? ($liveOfp['general']['callsign'] ?? '');
                session()->flash('error', "The latest SimBrief OFP ({$ofpCs}: {$ofpOrig}→{$ofpDest}) does not match this booking ({$this->callsign}: {$this->booking->route->departure_icao}→{$this->booking->route->arrival_icao}). Please click 'Generate Flight' on SimBrief first.");
            } else {
                session()->flash('error', "Could not fetch live OFP for SimBrief user '{$this->simbrief_username}'. Make sure you generated an OFP on SimBrief first.");
            }
        }
    }

    public function generateOfpData()
    {
        $selectedAirframe = Airframe::with('aircraftType')->find($this->airframe_id);

        $dispatchParams = [
            'type' => $selectedAirframe ? $selectedAirframe->aircraftType->code : ($this->booking->route->aircraftType->code ?? 'A20N'),
            'reg' => $selectedAirframe ? $selectedAirframe->registration : 'HB-AYE',
            'callsign' => strtoupper($this->callsign),
            'flight_number' => strtoupper($this->flight_number),
            'orig' => $this->booking->route->departure_icao,
            'dest' => $this->booking->route->arrival_icao,
            'altn' => strtoupper($this->alternate_1),
            'altn2' => strtoupper($this->alternate_2),
            'route' => $this->routing,
            'fl' => $this->flight_level,
            'ci' => $this->cost_index,
            'passengers' => $this->passengers,
            'hold_bags' => $this->hold_bags,
            'estimated_zfw' => $this->estimated_zfw,
            'distance' => $this->booking->route->distance ?? 374,
            'departure_date' => $this->departure_date,
            'departure_time' => $this->departure_time,
            'dispatch_via_simbrief' => $this->dispatch_via_simbrief,
            'network' => $this->network,
            'copilot_user_id' => $this->copilot_user_id,
            'simbrief_username' => trim($this->simbrief_username),
        ];

        $simbriefService = new SimBriefService();
        $ofpPayload = $simbriefService->generateOrFetchOfp($dispatchParams, $this->simbrief_username);

        $mergedData = array_merge($ofpPayload, $dispatchParams);

        $this->booking->update([
            'airframe_id' => $this->airframe_id,
            'simbrief_data' => $mergedData,
            'status' => 'dispatched'
        ]);

        $this->booking->refresh();
    }

    public function createBooking()
    {
        $this->generateOfpData();
        
        if ($this->dispatch_via_simbrief) {
            $this->is_loading_simbrief = true;
            $this->dispatch('open-simbrief-custom-popup');
            session()->flash('message', 'SimBrief opened with pre-filled flight options! Generating OFP...');
        } else {
            $this->showOfpView = true;
        }
    }

    public function dispatchSimbriefPopup()
    {
        $this->generateOfpData();
        $this->is_loading_simbrief = true;
        $this->dispatch('open-simbrief-custom-popup');
    }

    public function cancelLoadingState()
    {
        $this->is_loading_simbrief = false;
    }

    public function cancelBooking()
    {
        $userId = $this->booking->user_id;

        // Clean up and cancel active ACARS flights for this user
        \App\Models\AcarsActiveFlight::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $this->booking->delete();
        session()->flash('message', 'Booking cancelled successfully.');
        return redirect()->route('flight-centre.index');
    }

    public function editDispatch()
    {
        $this->showOfpView = false;
        $this->is_loading_simbrief = false;
    }

    public function render()
    {
        $fleet = Airframe::with('aircraftType')->where('tenant_id', auth()->user()->tenant_id)->get();
        $selectedAirframe = Airframe::with('aircraftType')->find($this->airframe_id);

        $copilots = User::where('tenant_id', auth()->user()->tenant_id)
            ->where('id', '!=', auth()->id())
            ->get();

        $callsignCode = strtoupper(trim($this->callsign));
        $flightNumInput = strtoupper(trim($this->flight_number));

        // Extract numeric digits for SimBrief fltnum
        $fltNumDigits = preg_replace('/[^0-9]/', '', $flightNumInput);
        if (empty($fltNumDigits)) {
            $fltNumDigits = preg_replace('/[^0-9]/', '', $callsignCode) ?: '101';
        }

        // Extract airline code (2-letter IATA if present in flight number like U2, FR, DS, or 3-letter ICAO from operator/callsign)
        $airlineCode = '';
        if (preg_match('/^([A-Z0-9]{2})[0-9]+$/i', $flightNumInput, $matches)) {
            // e.g. U28161 -> U2, FR2605 -> FR, DS1181 -> DS
            $airlineCode = strtoupper($matches[1]);
        } elseif ($this->booking->route?->operator) {
            $airlineCode = strtoupper($this->booking->route->operator);
        } elseif (strlen($callsignCode) >= 3 && ctype_alpha(substr($callsignCode, 0, 3))) {
            $airlineCode = substr($callsignCode, 0, 3);
        } else {
            $airlineCode = $this->booking->tenant->icao ?? 'VOPS';
        }

        $regCode = $selectedAirframe ? $selectedAirframe->registration : ($this->booking->airframe?->registration ?? 'HB-AYE');
        $typeCode = $selectedAirframe?->aircraftType?->code ?? ($this->booking->airframe?->aircraftType?->code ?? ($this->booking->route?->aircraftTypes?->first()?->code ?? 'A320'));
        $dateCode = date('dMY', strtotime($this->departure_date ?: date('Y-m-d')));
        $depH = (int)date('H', strtotime($this->departure_time ?: date('H:i')));
        $depM = (int)date('i', strtotime($this->departure_time ?: date('H:i')));

        // Navigraph SimBrief Dispatch Redirect Parameters
        $simbriefParams = [
            'airline' => $airlineCode,
            'fltnum' => $fltNumDigits,
            'callsign' => $callsignCode,
            'type' => $typeCode,
            'orig' => $this->booking->route?->departure_icao ?? 'EGLL',
            'dest' => $this->booking->route?->arrival_icao ?? 'LFPG',
            'date' => $dateCode,
            'deph' => $depH,
            'depm' => $depM,
            'steh' => 1,
            'stem' => 30,
            'reg' => $regCode,
            'route' => $this->routing,
            'fl' => $this->flight_level ?: 'AUTO',
            'civalue' => $this->cost_index ?: 4,
            'altn' => strtoupper($this->alternate_1),
            'altn_count' => (int)$this->num_alternates,
            'altn_1_id' => strtoupper($this->alternate_1),
            'altn_2_id' => strtoupper($this->alternate_2),
            'pax' => (int)$this->passengers,
            'cargo' => round(($this->hold_bags * 15) / 1000, 1),
            'units' => 'KGS',
            'planformat' => auth()->user()->pilotProfiles()->first()->simbrief_ofp_format ?? 'LIDO',
            'static_id' => 'VOPS-' . $this->booking->id,
        ];

        // Navigraph SimBrief Custom URL
        $simbriefPopupUrl = 'https://dispatch.simbrief.com/options/custom?' . http_build_query($simbriefParams);

        return view('livewire.pilot.dispatch', [
            'fleet' => $fleet,
            'selectedAirframe' => $selectedAirframe,
            'copilots' => $copilots,
            'simbriefParams' => $simbriefParams,
            'simbriefPopupUrl' => $simbriefPopupUrl,
        ])->layout('layouts.app');
    }
}
