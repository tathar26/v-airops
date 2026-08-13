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

        $operatorCode = $booking->route->operator ?? 'EZS';
        $this->callsign = $simData['general']['callsign'] ?? ($simData['callsign'] ?? ($booking->route->callsign ?? ($operatorCode . rand(100, 999) . 'HZ')));
        $this->flight_number = $simData['general']['flight_number'] ?? ($simData['flight_number'] ?? ($booking->route->flight_number ?? ('DS' . rand(1000, 9999))));
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
        $profile = auth()->user()->pilotProfiles()->first();
        $this->network = $simData['network'] ?? ($profile->preferred_network ?? 'Offline');
        $this->copilot_user_id = $simData['copilot_user_id'] ?? null;
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
        ];

        $simbriefService = new SimBriefService();
        $profile = auth()->user()->pilotProfiles()->first();
        $ofpPayload = $simbriefService->generateOrFetchOfp($dispatchParams, $profile->simbrief_username ?? null);

        // Merge parameters into simbrief_data
        $mergedData = array_merge($ofpPayload, $dispatchParams);

        $this->booking->update([
            'airframe_id' => $this->airframe_id,
            'simbrief_data' => $mergedData,
            'status' => 'dispatched'
        ]);

        $this->booking->refresh();
        $this->showOfpView = true;
    }

    public function createBooking()
    {
        $this->generateOfpData();
        session()->flash('message', 'Flight successfully dispatched! Your OFP has been generated.');
    }

    public function dispatchSimbriefPopup()
    {
        $this->generateOfpData();
        $this->dispatch('open-simbrief-popup');
    }

    public function cancelBooking()
    {
        $this->booking->delete();
        session()->flash('message', 'Booking cancelled successfully.');
        return redirect()->route('flight-centre.index');
    }

    public function editDispatch()
    {
        $this->showOfpView = false;
    }

    public function render()
    {
        $fleet = Airframe::with('aircraftType')->where('tenant_id', auth()->user()->tenant_id)->get();
        $selectedAirframe = Airframe::with('aircraftType')->find($this->airframe_id);

        $copilots = User::where('tenant_id', auth()->user()->tenant_id)
            ->where('id', '!=', auth()->id())
            ->get();

        $simbriefParams = [
            'type' => $selectedAirframe ? $selectedAirframe->aircraftType->code : ($this->booking->route->aircraftType->code ?? 'A20N'),
            'orig' => $this->booking->route->departure_icao,
            'dest' => $this->booking->route->arrival_icao,
            'callsign' => strtoupper($this->callsign),
            'fltnum' => strtoupper($this->flight_number),
            'airline' => substr(strtoupper($this->callsign), 0, 3),
            'reg' => $selectedAirframe ? $selectedAirframe->registration : 'HB-AYE',
            'date' => date('dMy', strtotime($this->departure_date)),
            'deptime' => str_replace(':', '', $this->departure_time),
            'route' => $this->routing,
            'fl' => $this->flight_level,
            'ci' => $this->cost_index,
            'altn' => strtoupper($this->alternate_1),
            'altn2' => strtoupper($this->alternate_2),
            'pax' => $this->passengers,
            'bag' => $this->hold_bags,
            'units' => 'KGS',
            'planformat' => auth()->user()->pilotProfiles()->first()->simbrief_ofp_format ?? 'lido',
            'static_id' => 'VOPS-' . $this->booking->id,
        ];

        return view('livewire.pilot.dispatch', [
            'fleet' => $fleet,
            'selectedAirframe' => $selectedAirframe,
            'copilots' => $copilots,
            'simbriefParams' => $simbriefParams,
        ])->layout('layouts.app');
    }
}
