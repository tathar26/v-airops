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
    public $airplane_profile = 'default';
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
    public $passengers = null;
    public $passengers_max = 186;
    public $hold_bags = null;
    public $hold_bags_max = 170;
    public $estimated_zfw = 61626;

    // Network & Co-pilot
    public $network = 'Offline';
    public $copilot_user_id = null;

    // OFP Format / Layout
    public $ofp_format = '';

    // SimBrief FMS Downloads
    public $selectedFmsFormat = 'mfs';

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
        $this->simbrief_username = $profile?->simbrief_username ?? ($simData['simbrief_username'] ?? '');

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

        $this->airplane_profile = $simData['airplane_profile'] ?? 'default';

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
        $this->dispatch_via_simbrief = !empty($this->simbrief_username) ? ($simData['dispatch_via_simbrief'] ?? true) : false;

        // Normalize existing SimBrief data if present
        $simDataUpdated = false;
        if (!empty($simData['params']['ofp_layout']) && ($simData['general']['ofp_layout'] ?? 'LIDO') === 'LIDO' && strtoupper($simData['params']['ofp_layout']) !== 'LIDO') {
            $simData['general']['ofp_layout'] = strtoupper($simData['params']['ofp_layout']);
            $simDataUpdated = true;
        }
        if (empty($simData['general']['alternate']) && !empty($simData['alternate'])) {
            if (is_array($simData['alternate'])) {
                if (isset($simData['alternate'][0]['icao_code'])) {
                    $simData['general']['alternate'] = strtoupper($simData['alternate'][0]['icao_code']);
                    $simDataUpdated = true;
                }
                if (isset($simData['alternate'][1]['icao_code'])) {
                    $simData['general']['alternate2'] = strtoupper($simData['alternate'][1]['icao_code']);
                    $simDataUpdated = true;
                }
            }
        }
        if ($simDataUpdated) {
            $booking->update(['simbrief_data' => $simData]);
        }

        // Schedule & Route Defaults
        $this->departure_date = $simData['departure_date'] ?? date('Y-m-d');
        $this->departure_time = $simData['departure_time'] ?? date('H:i', strtotime('+30 minutes'));
        $this->routing = $simData['general']['route'] ?? ($simData['routing'] ?? ($booking->route->route_string ?? ''));
        $this->flight_level = $simData['general']['initial_altitude'] ?? ($simData['flight_level'] ?? '');
        $this->cost_index = $simData['general']['cost_index'] ?? ($simData['cost_index'] ?? 4);

        // OFP Layout Format
        $this->ofp_format = strtoupper((string)(
            $simData['params']['ofp_layout']
            ?? ($simData['general']['ofp_layout'] 
            ?? ($simData['params']['planformat'] 
            ?? ($simData['planformat'] 
            ?? ($profile?->simbrief_ofp_format 
            ?? ($tenant?->default_simbrief_ofp_format ?? 'LIDO')))))
        ));

        // Alternates Defaults - If already dispatched with live OFP, populate from OFP; otherwise keep empty when auto_find_alternates is true so SimBrief chooses
        $this->auto_find_alternates = $simData['auto_find_alternates'] ?? true;
        $this->num_alternates = $simData['num_alternates'] ?? 2;
        $hasDispatchedOfp = ($booking->status === 'dispatched' || $this->showOfpView);

        $this->alternate_1 = ($hasDispatchedOfp || !$this->auto_find_alternates)
            ? ($simData['general']['alternate'] ?? ($simData['alternate_1'] ?? ($simData['alternate'][0]['icao_code'] ?? '')))
            : '';
        $this->alternate_2 = ($hasDispatchedOfp || !$this->auto_find_alternates)
            ? ($simData['general']['alternate2'] ?? ($simData['alternate_2'] ?? ($simData['alternate'][1]['icao_code'] ?? '')))
            : '';

        // Payload Defaults - empty by default so SimBrief automatically calculates load
        $this->passengers = $simData['weights']['pax_count'] ?? ($simData['passengers'] ?? null);
        $this->hold_bags = $simData['weights']['bag_count'] ?? ($simData['hold_bags'] ?? null);

        $profiles = $this->availableAirplaneProfiles;
        $activeProf = $profiles[$this->airplane_profile] ?? ($profiles['default'] ?? null);
        if ($activeProf) {
            $this->passengers_max = $activeProf['max_pax'] ?? 186;
            $this->hold_bags_max = $activeProf['max_bags'] ?? 170;
        }
        $this->recalculateZfw();

        // Network Defaults
        $this->network = $simData['network'] ?? ($profile->preferred_network ?? 'Offline');
        $this->copilot_user_id = $simData['copilot_user_id'] ?? null;

        // Auto-fetch if returning from SimBrief generation or explicitly requested
        if (request()->has('auto_fetch') && !empty($this->simbrief_username)) {
            $this->fetchLiveSimbriefOfp();
        }
    }

    public function updatedDispatchViaSimbrief($value)
    {
        if ($value) {
            $profile = auth()->user()->pilotProfiles()->first();
            $simId = $profile?->simbrief_username;
            if (empty(trim((string)$simId))) {
                $this->dispatch_via_simbrief = false;
                session()->flash('error', 'You must first set your SimBrief Username or Pilot ID in your Account Settings / Preferences before enabling Dispatch via SimBrief.');
                return;
            }
            $this->simbrief_username = trim($simId);
        }
    }

    public function updatedAutoFindAlternates($value)
    {
        if ($value) {
            $this->alternate_1 = '';
            $this->alternate_2 = '';
        }
    }

    public function updatedAirframeId($value)
    {
        $this->airplane_profile = 'default';
        $profiles = $this->availableAirplaneProfiles;
        $def = $profiles['default'] ?? null;
        if ($def) {
            $this->passengers_max = $def['max_pax'];
            $this->hold_bags_max = $def['max_bags'];
            $this->passengers = null;
            $this->hold_bags = null;
            $this->recalculateZfw();
        }
    }

    public function updatedAirplaneProfile($profileId)
    {
        $profiles = $this->availableAirplaneProfiles;
        $profile = $profiles[$profileId] ?? ($profiles['default'] ?? null);
        if ($profile) {
            $this->passengers_max = $profile['max_pax'] ?? 186;
            $this->hold_bags_max = $profile['max_bags'] ?? 170;
            if ($profileId === 'default') {
                $this->passengers = null;
                $this->hold_bags = null;
            } else {
                $this->passengers = (int)round(($this->passengers_max) * 0.9);
                $this->hold_bags = (int)round(($this->passengers) * 0.85);
            }
            $this->recalculateZfw();
        }
    }

    public function getAvailableAirplaneProfilesProperty(): array
    {
        $selectedAirframe = Airframe::with('aircraftType')->find($this->airframe_id);
        $rawCode = $selectedAirframe?->aircraftType?->code 
            ?? ($this->booking->airframe?->aircraftType?->code 
            ?? ($this->booking->route?->aircraftTypes?->first()?->code ?? 'A320'));
        $code = strtoupper(trim((string)$rawCode));

        $defaultOption = [
            'default' => [
                'id' => 'default',
                'name' => 'Default SimBrief (' . $code . ' Auto Load)',
                'max_pax' => 180,
                'max_bags' => 180,
                'oew' => 42500,
                'type' => $code,
            ],
        ];

        // Fetch live profiles directly from SimBrief API
        try {
            $simbriefService = app(SimBriefService::class);
            $liveAirframes = $simbriefService->getAirframesForType($code);
            if (!empty($liveAirframes)) {
                return array_merge($defaultOption, $liveAirframes);
            }
        } catch (\Throwable $e) {
            Log::warning('SimBrief live airframe fetch failed: ' . $e->getMessage());
        }

        $catalogue = [
            'A320' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 180, 'max_bags' => 180, 'oew' => 42500, 'type' => 'A320'],
                'fenix_a320_cfm' => ['name' => 'Fenix A320-200 (CFM56)', 'max_pax' => 180, 'max_bags' => 180, 'oew' => 42500, 'type' => 'A320'],
                'fenix_a320_iae' => ['name' => 'Fenix A320-200 (IAE V2500)', 'max_pax' => 180, 'max_bags' => 180, 'oew' => 42500, 'type' => 'A320'],
                'fbw_a320neo' => ['name' => 'FlyByWire A320neo (LEAP-1A)', 'max_pax' => 186, 'max_bags' => 186, 'oew' => 44300, 'type' => 'A20N'],
                'toliss_a320' => ['name' => 'ToLiss A320 (CEO / NEO)', 'max_pax' => 180, 'max_bags' => 180, 'oew' => 42500, 'type' => 'A320'],
                'inibuilds_a320neo' => ['name' => 'iniBuilds A320neo (v2)', 'max_pax' => 186, 'max_bags' => 186, 'oew' => 44300, 'type' => 'A20N'],
                'ff_a320' => ['name' => 'Flight Factor A320 Ultimate', 'max_pax' => 180, 'max_bags' => 180, 'oew' => 42500, 'type' => 'A320'],
                'latinvfr_a320' => ['name' => 'LatinVFR A320 CEO', 'max_pax' => 180, 'max_bags' => 180, 'oew' => 42500, 'type' => 'A320'],
            ],
            'A321' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 220, 'max_bags' => 220, 'oew' => 47000, 'type' => 'A321'],
                'fenix_a321_cfm' => ['name' => 'Fenix A321-200 (CFM)', 'max_pax' => 220, 'max_bags' => 220, 'oew' => 47000, 'type' => 'A321'],
                'fenix_a321_iae' => ['name' => 'Fenix A321-200 (IAE)', 'max_pax' => 220, 'max_bags' => 220, 'oew' => 47000, 'type' => 'A321'],
                'toliss_a321' => ['name' => 'ToLiss A321 (CEO & NEO)', 'max_pax' => 220, 'max_bags' => 220, 'oew' => 47000, 'type' => 'A321'],
                'inibuilds_a321neo' => ['name' => 'iniBuilds A321neo', 'max_pax' => 230, 'max_bags' => 230, 'oew' => 47500, 'type' => 'A21N'],
            ],
            'A319' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 150, 'max_bags' => 150, 'oew' => 40800, 'type' => 'A319'],
                'fenix_a319_cfm' => ['name' => 'Fenix A319-100 (CFM)', 'max_pax' => 150, 'max_bags' => 150, 'oew' => 40800, 'type' => 'A319'],
                'fenix_a319_iae' => ['name' => 'Fenix A319-100 (IAE)', 'max_pax' => 150, 'max_bags' => 150, 'oew' => 40800, 'type' => 'A319'],
                'toliss_a319' => ['name' => 'ToLiss A319', 'max_pax' => 150, 'max_bags' => 150, 'oew' => 40800, 'type' => 'A319'],
            ],
            'B738' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 189, 'max_bags' => 189, 'oew' => 41400, 'type' => 'B738'],
                'pmdg_738' => ['name' => 'PMDG 737-800', 'max_pax' => 189, 'max_bags' => 189, 'oew' => 41400, 'type' => 'B738'],
                'zibo_738' => ['name' => 'Zibo Mod 737-800', 'max_pax' => 189, 'max_bags' => 189, 'oew' => 41400, 'type' => 'B738'],
                'ifly_738_max' => ['name' => 'iFly 737 MAX 8', 'max_pax' => 189, 'max_bags' => 189, 'oew' => 45070, 'type' => 'B38M'],
                'pmdg_738_max' => ['name' => 'PMDG 737 MAX 8', 'max_pax' => 189, 'max_bags' => 189, 'oew' => 45070, 'type' => 'B38M'],
            ],
            'B737' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 149, 'max_bags' => 149, 'oew' => 38100, 'type' => 'B737'],
                'pmdg_737' => ['name' => 'PMDG 737-700', 'max_pax' => 149, 'max_bags' => 149, 'oew' => 38100, 'type' => 'B737'],
                'pmdg_736' => ['name' => 'PMDG 737-600', 'max_pax' => 123, 'max_bags' => 123, 'oew' => 36378, 'type' => 'B736'],
                'pmdg_739' => ['name' => 'PMDG 737-900ER', 'max_pax' => 215, 'max_bags' => 215, 'oew' => 44676, 'type' => 'B739'],
            ],
            'B777' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 396, 'max_bags' => 396, 'oew' => 167800, 'type' => 'B77W'],
                'pmdg_77w' => ['name' => 'PMDG 777-300ER', 'max_pax' => 396, 'max_bags' => 396, 'oew' => 167800, 'type' => 'B77W'],
                'ff_772' => ['name' => 'Flight Factor 777-200ER', 'max_pax' => 312, 'max_bags' => 312, 'oew' => 142900, 'type' => 'B772'],
            ],
            'B787' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 290, 'max_bags' => 290, 'oew' => 128800, 'type' => 'B789'],
                'kuro_788' => ['name' => 'Kuro 787-8', 'max_pax' => 248, 'max_bags' => 248, 'oew' => 119950, 'type' => 'B788'],
                'horizon_789' => ['name' => 'Horizon Simulations 787-9', 'max_pax' => 290, 'max_bags' => 290, 'oew' => 128800, 'type' => 'B789'],
                'asobo_78x' => ['name' => 'Asobo 787-10', 'max_pax' => 330, 'max_bags' => 330, 'oew' => 135500, 'type' => 'B78X'],
            ],
            'A330' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 287, 'max_bags' => 287, 'oew' => 137000, 'type' => 'A339'],
                'headwind_a339' => ['name' => 'Headwind A330-900neo', 'max_pax' => 287, 'max_bags' => 287, 'oew' => 137000, 'type' => 'A339'],
                'inibuilds_a330' => ['name' => 'iniBuilds A330-300', 'max_pax' => 287, 'max_bags' => 287, 'oew' => 124500, 'type' => 'A333'],
                'aerosoft_a330' => ['name' => 'Aerosoft A330-300', 'max_pax' => 287, 'max_bags' => 287, 'oew' => 124500, 'type' => 'A333'],
            ],
            'CRJ' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 90, 'max_bags' => 90, 'oew' => 21800, 'type' => 'CRJ9'],
                'aerosoft_crj7' => ['name' => 'Aerosoft CRJ-700', 'max_pax' => 70, 'max_bags' => 70, 'oew' => 20000, 'type' => 'CRJ7'],
                'aerosoft_crj9' => ['name' => 'Aerosoft CRJ-900', 'max_pax' => 90, 'max_bags' => 90, 'oew' => 21800, 'type' => 'CRJ9'],
                'aerosoft_crjx' => ['name' => 'Aerosoft CRJ-1000', 'max_pax' => 100, 'max_bags' => 100, 'oew' => 23180, 'type' => 'CRJX'],
            ],
            'EJET' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 100, 'max_bags' => 100, 'oew' => 28000, 'type' => 'E190'],
                'fss_e170' => ['name' => 'FlightSim Studio E-Jets 170', 'max_pax' => 76, 'max_bags' => 76, 'oew' => 21140, 'type' => 'E170'],
                'fss_e175' => ['name' => 'FlightSim Studio E-Jets 175', 'max_pax' => 88, 'max_bags' => 88, 'oew' => 21810, 'type' => 'E175'],
                'fss_e190' => ['name' => 'FlightSim Studio E-Jets 190', 'max_pax' => 100, 'max_bags' => 100, 'oew' => 28080, 'type' => 'E190'],
                'fss_e195' => ['name' => 'FlightSim Studio E-Jets 195', 'max_pax' => 122, 'max_bags' => 122, 'oew' => 28970, 'type' => 'E195'],
            ],
            'ATR' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 72, 'max_bags' => 72, 'oew' => 13500, 'type' => 'AT76'],
                'ms_atr72' => ['name' => 'Microsoft / Hans Hartmann ATR 72-600', 'max_pax' => 72, 'max_bags' => 72, 'oew' => 13500, 'type' => 'AT76'],
                'ms_atr42' => ['name' => 'Microsoft / Hans Hartmann ATR 42-600', 'max_pax' => 48, 'max_bags' => 48, 'oew' => 11550, 'type' => 'AT46'],
            ],
            'MD11' => [
                'default' => ['name' => 'Default SimBrief (Auto Load)', 'max_pax' => 298, 'max_bags' => 298, 'oew' => 128800, 'type' => 'MD11'],
                'tfdi_md11' => ['name' => 'TFDi Design MD-11', 'max_pax' => 298, 'max_bags' => 298, 'oew' => 128800, 'type' => 'MD11'],
            ],
        ];

        if (str_contains($code, '321') || $code === 'A21N') {
            return $catalogue['A321'];
        }
        if (str_contains($code, '319') || $code === 'A19N') {
            return $catalogue['A319'];
        }
        if (str_contains($code, '320') || $code === 'A20N') {
            return $catalogue['A320'];
        }
        if (str_contains($code, '738') || str_contains($code, '800') || str_contains($code, '38M') || str_contains($code, 'MAX8')) {
            return $catalogue['B738'];
        }
        if (str_contains($code, '737') || str_contains($code, '736') || str_contains($code, '739')) {
            return $catalogue['B737'];
        }
        if (str_contains($code, '777') || str_contains($code, '77W') || str_contains($code, '772') || str_contains($code, '773') || str_contains($code, '77L')) {
            return $catalogue['B777'];
        }
        if (str_contains($code, '787') || str_contains($code, '788') || str_contains($code, '789') || str_contains($code, '78X')) {
            return $catalogue['B787'];
        }
        if (str_contains($code, '330') || str_contains($code, '339') || str_contains($code, '333') || str_contains($code, '332')) {
            return $catalogue['A330'];
        }
        if (str_contains($code, 'CRJ')) {
            return $catalogue['CRJ'];
        }
        if (str_contains($code, 'E17') || str_contains($code, 'E19') || str_contains($code, 'EJET') || str_contains($code, '295')) {
            return $catalogue['EJET'];
        }
        if (str_contains($code, 'ATR') || str_contains($code, 'AT7') || str_contains($code, 'AT4')) {
            return $catalogue['ATR'];
        }
        if (str_contains($code, 'MD11') || str_contains($code, 'M11')) {
            return $catalogue['MD11'];
        }

        return [
            'default' => ['name' => 'Default SimBrief (' . $code . ' Auto Load)', 'max_pax' => 180, 'max_bags' => 180, 'oew' => 42500, 'type' => $code],
        ];
    }

    public function updatedPassengers()
    {
        if ($this->passengers !== null && $this->passengers !== '') {
            $this->passengers = max(0, min((int)$this->passengers, $this->passengers_max));
        }
        $this->recalculateZfw();
    }

    public function updatedHoldBags()
    {
        if ($this->hold_bags !== null && $this->hold_bags !== '') {
            $this->hold_bags = max(0, min((int)$this->hold_bags, $this->hold_bags_max));
        }
        $this->recalculateZfw();
    }

    public function generatePassengers()
    {
        $this->passengers = rand((int)($this->passengers_max * 0.7), $this->passengers_max);
        $this->generateHoldBags();
    }

    public function generateHoldBags()
    {
        $pax = (int)($this->passengers ?: $this->passengers_max);
        $this->hold_bags = rand((int)($pax * 0.75), (int)min($pax * 1.0, $this->hold_bags_max));
        $this->recalculateZfw();
    }

    public function clearPassengers()
    {
        $this->passengers = null;
        $this->recalculateZfw();
    }

    public function clearHoldBags()
    {
        $this->hold_bags = null;
        $this->recalculateZfw();
    }

    public function recalculateZfw()
    {
        $profiles = $this->availableAirplaneProfiles;
        $profile = $profiles[$this->airplane_profile] ?? ($profiles['default'] ?? null);
        $oew = $profile['oew'] ?? 42500;

        $paxWeight = !empty($this->passengers) ? ((int)$this->passengers * 84) : 0;
        $bagWeight = !empty($this->hold_bags) ? ((int)$this->hold_bags * 15) : 0;
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

            if (!empty($liveOfp['general']['ofp_layout'])) {
                $this->ofp_format = strtoupper($liveOfp['general']['ofp_layout']);
            }
            if (!empty($liveOfp['general']['alternate'])) {
                $this->alternate_1 = strtoupper($liveOfp['general']['alternate']);
            }
            if (!empty($liveOfp['general']['alternate2'])) {
                $this->alternate_2 = strtoupper($liveOfp['general']['alternate2']);
            }

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

            if (!empty($liveOfp['general']['ofp_layout'])) {
                $this->ofp_format = strtoupper($liveOfp['general']['ofp_layout']);
            }
            if (!empty($liveOfp['general']['alternate'])) {
                $this->alternate_1 = strtoupper($liveOfp['general']['alternate']);
            }
            if (!empty($liveOfp['general']['alternate2'])) {
                $this->alternate_2 = strtoupper($liveOfp['general']['alternate2']);
            }

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
        $profiles = $this->availableAirplaneProfiles;
        $profile = $profiles[$this->airplane_profile] ?? ($profiles['default'] ?? null);

        $targetTypeCode = $profile['type'] ?? ($selectedAirframe?->aircraftType?->code ?? ($this->booking->route->aircraftType->code ?? ($this->booking->route->aircraftTypes?->first()?->code ?? 'A20N')));
        $targetRegCode = $selectedAirframe ? $selectedAirframe->registration : ($this->booking->airframe?->registration ?? 'HB-AYE');
        $targetPlanFormat = strtoupper((string)($this->ofp_format ?: (auth()->user()->pilotProfiles()->first()?->simbrief_ofp_format ?: ($this->booking->tenant?->default_simbrief_ofp_format ?? 'LIDO'))));

        $dispatchParams = [
            'type' => $targetTypeCode,
            'reg' => $targetRegCode,
            'airframe_id' => $this->airframe_id,
            'airplane_profile' => $this->airplane_profile,
            'airplane_profile_name' => $profile['name'] ?? 'Default Profile',
            'callsign' => strtoupper($this->callsign),
            'flight_number' => strtoupper($this->flight_number),
            'orig' => $this->booking->route->departure_icao,
            'dest' => $this->booking->route->arrival_icao,
            'altn' => $this->auto_find_alternates ? '' : strtoupper($this->alternate_1),
            'altn2' => $this->auto_find_alternates ? '' : strtoupper($this->alternate_2),
            'auto_find_alternates' => $this->auto_find_alternates,
            'num_alternates' => $this->num_alternates,
            'route' => $this->routing,
            'fl' => $this->flight_level,
            'ci' => $this->cost_index,
            'passengers' => !empty($this->passengers) ? (int)$this->passengers : null,
            'hold_bags' => !empty($this->hold_bags) ? (int)$this->hold_bags : null,
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

    public function cancelAndRebook()
    {
        $userId = $this->booking->user_id;

        // Clean up and cancel active ACARS flights for this user
        \App\Models\AcarsActiveFlight::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $this->booking->delete();
        session()->flash('message', 'Booking cancelled. You can now choose your route options again.');
        return redirect()->route('flight-centre.index');
    }

    public function editDispatch()
    {
        $this->showOfpView = false;
        $this->is_loading_simbrief = false;
    }

    public function returnToOfp()
    {
        if (!empty($this->booking->simbrief_data)) {
            $this->showOfpView = true;
        }
    }

    public function downloadFmsFile(?string $formatKey = null)
    {
        $key = $formatKey ?: $this->selectedFmsFormat;
        $downloads = $this->availableFmsDownloads;
        $target = $downloads[$key] ?? (reset($downloads) ?: null);

        if ($target && !empty($target['url'])) {
            return redirect()->away($target['url']);
        }
    }

    public function getAvailableFmsDownloadsProperty(): array
    {
        $sb = $this->booking->simbrief_data ?? [];
        $downloads = $sb['fms_downloads'] ?? [];
        $directory = $downloads['directory'] ?? 'https://www.simbrief.com/ofp/flightplans/';

        $results = [];
        foreach ($downloads as $key => $val) {
            if ($key === 'directory' || !is_array($val)) {
                continue;
            }
            $link = $val['link'] ?? '';
            $name = $val['name'] ?? strtoupper($key);
            if (empty($link)) {
                continue;
            }

            $url = str_starts_with($link, 'http') ? $link : rtrim($directory, '/') . '/' . ltrim($link, '/');
            $results[$key] = [
                'key' => $key,
                'name' => $name,
                'link' => $link,
                'url' => $url,
            ];
        }

        if (empty($results)) {
            $results['pdf'] = [
                'key' => 'pdf',
                'name' => 'PDF Document',
                'url' => 'https://www.simbrief.com/ofp/flightplans/',
            ];
            $results['mfs'] = [
                'key' => 'mfs',
                'name' => 'FS2020 / FS2024 (.pln)',
                'url' => 'https://www.simbrief.com/ofp/flightplans/',
            ];
            $results['xp9'] = [
                'key' => 'xp9',
                'name' => 'X-Plane 11/12 (.fms)',
                'url' => 'https://www.simbrief.com/ofp/flightplans/',
            ];
            $results['pmr'] = [
                'key' => 'pmr',
                'name' => 'PMDG (.rte)',
                'url' => 'https://www.simbrief.com/ofp/flightplans/',
            ];
        }

        return $results;
    }

    public function getVatsimPrefileUrlProperty(): string
    {
        $sb = $this->booking->simbrief_data ?? [];

        // 1. Direct official SimBrief VATSIM link (battle-tested, includes complete ICAO raw string & fuel_time)
        if (!empty($sb['prefile']['vatsim']['link'])) {
            return html_entity_decode((string)$sb['prefile']['vatsim']['link']);
        }

        // 2. Clean single-line ICAO flight plan string if OFP text is available
        if (!empty($sb['atc']['flightplan_text'])) {
            $cleanRaw = trim((string)preg_replace('/\s+/', ' ', (string)$sb['atc']['flightplan_text']));
            $fuelTime = '0330';
            if (isset($sb['times']['est_endurance'])) {
                $fuelTime = str_replace(':', '', (string)$sb['times']['est_endurance']);
            }
            return 'https://my.vatsim.net/pilots/flightplan?raw=' . urlencode($cleanRaw) . '&fuel_time=' . urlencode($fuelTime);
        }

        // 3. Clean fallback query parameters for manual bookings prior to SimBrief generation
        $cs = strtoupper(trim((string)($sb['general']['callsign'] ?? ($this->callsign ?? ''))));
        $acType = strtoupper(trim((string)($sb['aircraft']['icao_code'] ?? ($sb['general']['aircraft_type'] ?? ($this->booking->route?->aircraftTypes?->first()?->code ?? 'A320')))));
        $dep = strtoupper(trim((string)($sb['general']['origin'] ?? ($sb['origin']['icao_code'] ?? ($this->booking->route?->departure_icao ?? '')))));
        $arr = strtoupper(trim((string)($sb['general']['destination'] ?? ($sb['destination']['icao_code'] ?? ($this->booking->route?->arrival_icao ?? '')))));
        $alt = strtoupper(trim((string)($sb['general']['alternate'] ?? ($this->alternate_1 ?? ''))));
        $alt2 = strtoupper(trim((string)($sb['general']['alternate2'] ?? ($this->alternate_2 ?? ''))));
        $route = trim((string)($sb['general']['route'] ?? ($this->routing ?? '')));
        
        $rawAlt = (int)($sb['general']['initial_altitude'] ?? 36000);
        $altitude = ($rawAlt > 0 && $rawAlt < 1000) ? $rawAlt * 100 : $rawAlt;

        $tas = (int)($sb['general']['cruise_tas'] ?? 450);
        $depTime = str_replace(':', '', (string)($this->departure_time ?: date('Hi')));
        $ete = (string)($sb['general']['est_time_enroute'] ?? '01:30');
        $eteClean = str_replace(':', '', $ete);
        
        $endurance = '0330';
        if (isset($sb['times']['est_endurance'])) {
            $endurance = str_replace(':', '', (string)$sb['times']['est_endurance']);
        }

        $params = [
            'callsign' => $cs,
            'aircraft' => $acType,
            'dep' => $dep,
            'arr' => $arr,
            'alt' => $alt,
            'alt2' => $alt2,
            'route' => $route,
            'altitude' => $altitude,
            'tas' => $tas,
            'deptime' => $depTime,
            'enroute' => $eteClean,
            'fuel' => $endurance,
            'remarks' => $sb['general']['dx_rmk'][0] ?? ($sb['general']['dx_rmk'] ?? 'VOPS / SIMBRIEF OFP'),
        ];

        return 'https://my.vatsim.net/pilots/flightplan?' . http_build_query($params);
    }

    public function getIvaoPrefileUrlProperty(): string
    {
        $sb = $this->booking->simbrief_data ?? [];
        if (!empty($sb['prefile']['ivao']['link'])) {
            return $sb['prefile']['ivao']['link'];
        }

        $cs = strtoupper(trim((string)($sb['general']['callsign'] ?? ($this->callsign ?? ''))));
        $acType = strtoupper(trim((string)($sb['aircraft']['icao_code'] ?? ($sb['general']['aircraft_type'] ?? ($this->booking->route?->aircraftTypes?->first()?->code ?? 'A320')))));
        $dep = strtoupper(trim((string)($sb['general']['origin'] ?? ($sb['origin']['icao_code'] ?? ($this->booking->route?->departure_icao ?? '')))));
        $arr = strtoupper(trim((string)($sb['general']['destination'] ?? ($sb['destination']['icao_code'] ?? ($this->booking->route?->arrival_icao ?? '')))));
        $alt = strtoupper(trim((string)($sb['general']['alternate'] ?? ($this->alternate_1 ?? ''))));
        $route = trim((string)($sb['general']['route'] ?? ($this->routing ?? '')));
        
        $rawAlt = (int)($sb['general']['initial_altitude'] ?? 36000);
        $fl = ($rawAlt >= 1000) ? 'F' . str_pad((int)floor($rawAlt / 100), 3, '0', STR_PAD_LEFT) : 'F' . str_pad($rawAlt, 3, '0', STR_PAD_LEFT);
        $tas = 'N' . str_pad((int)($sb['general']['cruise_tas'] ?? 450), 4, '0', STR_PAD_LEFT);

        $depTime = str_replace(':', '', (string)($this->departure_time ?: date('Hi')));
        $ete = (string)($sb['general']['est_time_enroute'] ?? '01:30');
        $eteClean = str_replace(':', '', $ete);

        $endurance = '0330';
        if (isset($sb['times']['est_endurance'])) {
            $endurance = str_replace(':', '', (string)$sb['times']['est_endurance']);
        }

        $params = [
            'callsign' => $cs,
            'origin' => $dep,
            'destination' => $arr,
            'alternate' => $alt,
            'route' => $route,
            'aircraft' => $acType,
            'cruisingSpeed' => $tas,
            'cruisingLevel' => $fl,
            'eet' => $eteClean,
            'endurance' => $endurance,
            'remarks' => 'VOPS / SIMBRIEF OFP',
        ];

        return 'https://fpl.ivao.aero/create?' . http_build_query($params);
    }

    public function getPosconPrefileUrlProperty(): string
    {
        $sb = $this->booking->simbrief_data ?? [];
        if (!empty($sb['prefile']['poscon']['link'])) {
            return $sb['prefile']['poscon']['link'];
        }

        return 'https://hq.poscon.net/';
    }

    public function getRouteComparisonStatsProperty(): array
    {
        $tenantId = $this->booking->tenant_id;
        $routeId = $this->booking->route_id;

        $pireps = \App\Models\Pirep::where('tenant_id', $tenantId)
            ->where('status', 'accepted')
            ->when($routeId, function ($q) use ($routeId) {
                $q->where('route_id', $routeId);
            })
            ->get();

        $count = $pireps->count();
        if ($count === 0) {
            $pireps = \App\Models\Pirep::where('tenant_id', $tenantId)->where('status', 'accepted')->limit(20)->get();
            $count = $pireps->count();
        }

        if ($count > 0) {
            $avgLanding = (int) round($pireps->avg('touchdown_rate_fpm') ?: -144);
            $avgFuel = (int) round($pireps->avg('fuel_used') ?: 2828);
            $avgSecs = (int) round($pireps->avg('flight_time') ?: 5176);
            $avgPts = (int) round($pireps->avg('points_awarded') ?: 167);
            $avgPax = (int) round($pireps->avg('passengers') ?: 164);
            $avgCargo = (int) round($pireps->avg('freight') ?: 0);
        } else {
            $avgLanding = -144;
            $avgFuel = 2828;
            $avgSecs = 5176;
            $avgPts = 167;
            $avgPax = 164;
            $avgCargo = 0;
        }

        $hrs = floor($avgSecs / 3600);
        $mins = floor(($avgSecs % 3600) / 60);
        $secs = $avgSecs % 60;
        $flightTimeFmt = sprintf('%02d:%02d:%02d', $hrs, $mins, $secs);

        return [
            'count' => $count ?: 5,
            'landing_rate' => $avgLanding,
            'fuel_used' => $avgFuel,
            'flight_time' => $flightTimeFmt,
            'points' => $avgPts,
            'passengers' => $avgPax,
            'freight' => $avgCargo,
        ];
    }

    public function getRouteWaypointsProperty(): array
    {
        $sb = $this->booking->simbrief_data ?? [];
        $waypoints = [];

        $origLat = (float)($sb['origin']['pos_lat'] ?? 0);
        $origLon = (float)($sb['origin']['pos_long'] ?? 0);
        $origIcao = strtoupper(trim((string)($sb['origin']['icao_code'] ?? ($this->booking->route?->departure_icao ?? ''))));
        $origName = $sb['origin']['name'] ?? $origIcao;

        $destLat = (float)($sb['destination']['pos_lat'] ?? 0);
        $destLon = (float)($sb['destination']['pos_long'] ?? 0);
        $destIcao = strtoupper(trim((string)($sb['destination']['icao_code'] ?? ($this->booking->route?->arrival_icao ?? ''))));
        $destName = $sb['destination']['name'] ?? $destIcao;

        if ($origLat === 0.0 && $origLon === 0.0 && !empty($origIcao)) {
            $ap = Airport::where('icao', $origIcao)->first();
            if ($ap) {
                $origLat = (float)$ap->lat;
                $origLon = (float)$ap->lon;
            }
        }
        if ($destLat === 0.0 && $destLon === 0.0 && !empty($destIcao)) {
            $ap = Airport::where('icao', $destIcao)->first();
            if ($ap) {
                $destLat = (float)$ap->lat;
                $destLon = (float)$ap->lon;
            }
        }

        if ($origLat != 0.0 || $origLon != 0.0) {
            $waypoints[] = [
                'ident' => $origIcao,
                'name' => $origName,
                'lat' => $origLat,
                'lon' => $origLon,
                'type' => 'departure',
            ];
        }

        // Support both direct indexed array (SimBrief JSON v2) and XML-nested array (navlog.fix)
        $fixes = [];
        if (!empty($sb['navlog'])) {
            if (isset($sb['navlog']['fix'])) {
                $fixes = isset($sb['navlog']['fix'][0]) ? $sb['navlog']['fix'] : [$sb['navlog']['fix']];
            } elseif (is_array($sb['navlog'])) {
                $fixes = isset($sb['navlog'][0]) ? $sb['navlog'] : [$sb['navlog']];
            }
        }

        $destAdded = false;
        foreach ($fixes as $fix) {
            if (!is_array($fix)) continue;

            $lat = (float)($fix['pos_lat'] ?? ($fix['lat'] ?? 0));
            $lon = (float)($fix['pos_long'] ?? ($fix['lon'] ?? ($fix['long'] ?? 0)));
            $ident = strtoupper(trim((string)($fix['ident'] ?? '')));

            if ($lat == 0.0 && $lon == 0.0) continue;
            if ($ident === $origIcao) continue;

            // Check if fix is the arrival airport
            if ($ident === $destIcao || (($fix['type'] ?? '') === 'apt' && abs($lat - $destLat) < 0.1 && abs($lon - $destLon) < 0.1)) {
                $waypoints[] = [
                    'ident' => $destIcao,
                    'name' => $destName,
                    'lat' => $destLat ?: $lat,
                    'lon' => $destLon ?: $lon,
                    'type' => 'arrival',
                ];
                $destAdded = true;
                continue;
            }

            $fixType = 'waypoint';
            if ($ident === 'TOC') {
                $fixType = 'toc';
            } elseif ($ident === 'TOD') {
                $fixType = 'tod';
            } elseif (($fix['type'] ?? '') === 'vor') {
                $fixType = 'vor';
            } elseif (($fix['type'] ?? '') === 'ndb') {
                $fixType = 'ndb';
            }

            $waypoints[] = [
                'ident' => $ident,
                'name' => $fix['name'] ?? $ident,
                'lat' => $lat,
                'lon' => $lon,
                'alt' => (int)($fix['altitude_feet'] ?? 0),
                'stage' => $fix['stage'] ?? '',
                'airway' => $fix['via_airway'] ?? '',
                'type' => $fixType,
            ];
        }

        if (!$destAdded && ($destLat != 0.0 || $destLon != 0.0)) {
            $waypoints[] = [
                'ident' => $destIcao,
                'name' => $destName,
                'lat' => $destLat,
                'lon' => $destLon,
                'type' => 'arrival',
            ];
        }

        return $waypoints;
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
        $baseTypeCode = $selectedAirframe?->aircraftType?->code ?? ($this->booking->airframe?->aircraftType?->code ?? ($this->booking->route?->aircraftTypes?->first()?->code ?? 'A320'));
        $dateCode = date('dMY', strtotime($this->departure_date ?: date('Y-m-d')));
        $depH = (int)date('H', strtotime($this->departure_time ?: date('H:i')));
        $depM = (int)date('i', strtotime($this->departure_time ?: date('H:i')));

        $profile = auth()->user()->pilotProfiles()->first();
        $tenant = $this->booking->tenant ?? auth()->user()->tenant;
        $resolvedFormat = strtoupper((string)($this->ofp_format ?: ($profile?->simbrief_ofp_format ?: ($tenant?->default_simbrief_ofp_format ?? 'LIDO'))));

        $availableAirplaneProfiles = $this->availableAirplaneProfiles;
        $selectedAirplaneProfile = $availableAirplaneProfiles[$this->airplane_profile] ?? ($availableAirplaneProfiles['default'] ?? null);
        $targetTypeCode = $selectedAirplaneProfile['type'] ?? $baseTypeCode;

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
            'type' => $targetTypeCode,
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
            'altn_count' => (int)$this->num_alternates,
            'units' => 'KGS',
            'planformat' => $resolvedFormat,
            'navlog' => 1,
            'detailed_navlog' => 1,
            'static_id' => 'VOPS-' . $this->booking->id,
        ];

        // Alternates handling: only pass if not auto_find_alternates
        if (!$this->auto_find_alternates && !empty($this->alternate_1)) {
            $simbriefParams['altn'] = strtoupper($this->alternate_1);
            $simbriefParams['altn_1_id'] = strtoupper($this->alternate_1);
        }
        if (!$this->auto_find_alternates && !empty($this->alternate_2)) {
            $simbriefParams['altn_2_id'] = strtoupper($this->alternate_2);
        }

        // Passenger & Load handling: if empty, send AUTO so SimBrief chooses
        if (!empty($this->passengers) && (int)$this->passengers > 0) {
            $simbriefParams['pax'] = (int)$this->passengers;
        } else {
            $simbriefParams['pax'] = 'AUTO';
        }

        if (!empty($this->hold_bags) && (int)$this->hold_bags > 0) {
            $simbriefParams['cargo'] = round(($this->hold_bags * 15) / 1000, 1);
        } else {
            $simbriefParams['cargo'] = 'AUTO';
        }

        // Navigraph SimBrief Custom URL
        $simbriefPopupUrl = 'https://dispatch.simbrief.com/options/custom?' . http_build_query($simbriefParams);

        // Flight Expiry Calculation (24h standard booking window)
        $expiryTs = strtotime((string)($this->booking->created_at ?: now())) + (24 * 3600);
        $diffSecs = max(0, $expiryTs - time());
        $diffH = floor($diffSecs / 3600);
        $diffM = floor(($diffSecs % 3600) / 60);
        $flightExpiryString = date('jS M y H:iz', $expiryTs) . " ({$diffH} hours {$diffM} minutes from now)";

        return view('livewire.pilot.dispatch', [
            'booking' => $this->booking,
            'fleet' => $fleet,
            'selectedAirframe' => $selectedAirframe,
            'availableAirplaneProfiles' => $availableAirplaneProfiles,
            'copilots' => $copilots,
            'availableOfpFormats' => $availableOfpFormats,
            'resolvedFormat' => $resolvedFormat,
            'simbriefParams' => $simbriefParams,
            'simbriefPopupUrl' => $simbriefPopupUrl,
            'vatsimPrefileUrl' => $this->vatsimPrefileUrl,
            'ivaoPrefileUrl' => $this->ivaoPrefileUrl,
            'posconPrefileUrl' => $this->posconPrefileUrl,
            'availableFmsDownloads' => $this->availableFmsDownloads,
            'routeComparisonStats' => $this->routeComparisonStats,
            'routeWaypoints' => $this->routeWaypoints,
            'flightExpiryString' => $flightExpiryString,
        ])->layout('layouts.app');
    }
}
