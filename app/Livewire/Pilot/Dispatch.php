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
    public int $bookingId;

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

    // OFP Format / Layout
    public $ofp_format = '';

    // UI States
    public $showSectionAircraft = true;
    public $showSectionSchedule = true;
    public $showSectionAlternates = true;
    public $showSectionPayload = true;
    public $showSectionNetwork = true;
    public $showRouteDetails = false;
    public $showOfpView = false;

    public function getBookingProperty(): ?Booking
    {
        return Booking::with(['route.aircraftTypes', 'airframe.aircraftType', 'tenant', 'user'])->find($this->bookingId);
    }

    public function mount(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $this->bookingId = $booking->id;
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

        // OFP Layout Format
        $this->ofp_format = strtoupper((string)($simData['general']['ofp_layout'] 
            ?? ($simData['planformat'] 
            ?? ($profile?->simbrief_ofp_format 
            ?? ($tenant?->default_simbrief_ofp_format ?? 'LIDO')))));

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

            $this->activateFlightFromBooking();
            $this->booking->refresh();
            $this->is_loading_simbrief = false;
            $this->showOfpView = true;
            session()->flash('message', 'Real SimBrief OFP imported automatically! Flight is now active in ACARS.');
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

            $this->activateFlightFromBooking();
            $this->booking->refresh();
            $this->is_loading_simbrief = false;
            $this->showOfpView = true;
            session()->flash('message', 'Successfully imported live OFP from SimBrief! Flight is now active in ACARS.');
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

    /**
     * Automatically create and activate ACARS active flight for the user and tenant
     */
    private function activateFlightFromBooking()
    {
        $this->booking->refresh();
        $this->booking->load(['route', 'airframe.aircraftType', 'tenant']);

        $sb = $this->booking->simbrief_data ?? [];
        $targetFlightNum = $sb['params']['callsign'] 
            ?? ($sb['atc']['callsign'] 
            ?? ($sb['general']['flight_number'] 
            ?? ($this->booking->route?->callsign 
            ?? ($this->booking->route?->flight_number 
            ?? ($this->booking->tenant?->icao ? $this->booking->tenant->icao . '101' : 'SVK101')))));

        $targetDep = $sb['origin']['icao_code'] ?? ($sb['general']['origin'] ?? ($this->booking->route?->departure_icao ?? 'EGLL'));
        $targetArr = $sb['destination']['icao_code'] ?? ($sb['general']['destination'] ?? ($this->booking->route?->arrival_icao ?? 'LFPG'));
        $targetAircraft = $this->booking->airframe?->aircraftType?->code ?? ($sb['aircraft']['icao_code'] ?? ($this->booking->route?->aircraftTypes?->first()?->code ?? 'A320'));
        $targetOfpId = (string) ($sb['params']['ofp_id'] ?? ($sb['general']['ofp_id'] ?? $this->booking->id));

        $rawAlt = (int) ($sb['general']['initial_altitude'] ?? ($sb['general']['cruise_altitude'] ?? ($sb['params']['fl'] ?? ($this->booking->route?->flight_level ?? 36000))));
        $plannedAltitude = ($rawAlt > 0 && $rawAlt < 1000) ? $rawAlt * 100 : $rawAlt;

        $plannedFuel = (float) ($sb['fuel']['plan_ramp'] ?? ($sb['fuel']['ramp'] ?? ($sb['fuel']['plan_takeoff'] ?? 6500.0)));
        $plannedZfw = (float) ($sb['weights']['est_zfw'] ?? ($sb['weights']['zfw'] ?? 58000.0));
        $routeString = $this->booking->route?->route_string ?? ($sb['general']['route'] ?? 'DIRECT');

        // Archive previous active flights for this pilot
        \App\Models\AcarsActiveFlight::where('user_id', $this->booking->user_id)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        // Create or activate the ACARS flight for this tenant
        \App\Models\AcarsActiveFlight::create([
            'user_id' => $this->booking->user_id,
            'tenant_id' => $this->booking->tenant_id,
            'flight_number' => strtoupper($targetFlightNum),
            'origin_icao' => strtoupper($targetDep),
            'destination_icao' => strtoupper($targetArr),
            'route' => $routeString,
            'aircraft_type' => strtoupper($targetAircraft),
            'planned_altitude' => $plannedAltitude,
            'planned_fuel_kg' => $plannedFuel,
            'planned_zfw_kg' => $plannedZfw,
            'simbrief_ofp_id' => $targetOfpId,
            'status' => 'active',
        ]);
    }

    public function generateOfpData()
    {
        $selectedAirframe = Airframe::with('aircraftType')->find($this->airframe_id);
        $targetTypeCode = $selectedAirframe?->aircraftType?->code ?? ($this->booking->route->aircraftType->code ?? ($this->booking->route->aircraftTypes?->first()?->code ?? 'A20N'));
        $targetRegCode = $selectedAirframe ? $selectedAirframe->registration : ($this->booking->airframe?->registration ?? 'HB-AYE');
        $targetPlanFormat = strtoupper((string)($this->ofp_format ?: (auth()->user()->pilotProfiles()->first()?->simbrief_ofp_format ?: ($this->booking->tenant?->default_simbrief_ofp_format ?? 'LIDO'))));

        $dispatchParams = [
            'type' => $targetTypeCode,
            'reg' => $targetRegCode,
            'airframe_id' => $this->airframe_id,
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
            'planformat' => $targetPlanFormat,
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

        $this->activateFlightFromBooking();
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

        $profile = auth()->user()->pilotProfiles()->first();
        $tenant = $this->booking->tenant ?? auth()->user()->tenant;
        $resolvedFormat = strtoupper((string)($this->ofp_format ?: ($profile?->simbrief_ofp_format ?: ($tenant?->default_simbrief_ofp_format ?? 'LIDO'))));

        $availableOfpFormats = [
            'LIDO' => 'LIDO (Standard IATA / European)',
            'EZY' => 'EZY (easyJet)',
            'BAW' => 'BAW (British Airways)',
            'DLH' => 'DLH (Lufthansa)',
            'AFR' => 'AFR (Air France)',
            'KLM' => 'KLM (Royal Dutch Airlines)',
            'RYR' => 'RYR (Ryanair)',
            'AAL' => 'AAL (American Airlines)',
            'DAL' => 'DAL (Delta Air Lines)',
            'UAL' => 'UAL (United Airlines)',
            'SWA' => 'SWA (Southwest Airlines)',
            'ACA' => 'ACA (Air Canada)',
            'QFA' => 'QFA (Qantas)',
            'UAE' => 'UAE (Emirates)',
            'THY' => 'THY (Turkish Airlines)',
            'WZZ' => 'WZZ (Wizz Air)',
            'SAS' => 'SAS (Scandinavian Airlines)',
            'FIN' => 'FIN (Finnair)',
            'VOZ' => 'VOZ (Virgin Australia)',
            'VIR' => 'VIR (Virgin Atlantic)',
            'JBU' => 'JBU (JetBlue)',
        ];

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
            'planformat' => $resolvedFormat,
            'static_id' => 'VOPS-' . $this->booking->id,
        ];

        // Navigraph SimBrief Custom URL
        $simbriefPopupUrl = 'https://dispatch.simbrief.com/options/custom?' . http_build_query($simbriefParams);

        return view('livewire.pilot.dispatch', [
            'booking' => $this->booking,
            'fleet' => $fleet,
            'selectedAirframe' => $selectedAirframe,
            'copilots' => $copilots,
            'availableOfpFormats' => $availableOfpFormats,
            'resolvedFormat' => $resolvedFormat,
            'simbriefParams' => $simbriefParams,
            'simbriefPopupUrl' => $simbriefPopupUrl,
        ])->layout('layouts.app');
    }
}
