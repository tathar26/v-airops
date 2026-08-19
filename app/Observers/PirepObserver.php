<?php

namespace App\Observers;

use App\Models\Pirep;
use App\Services\PirepScoringService;

class PirepObserver
{
    protected $scoringService;

    public function __construct(PirepScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * Handle the Pirep "created" event.
     */
    public function created(Pirep $pirep): void
    {
        $this->updatePilotLocation($pirep);

        if (in_array(strtolower($pirep->status), ['accepted', 'complete', 'approved'])) {
            $this->scoringService->processPirep($pirep);
        }
    }

    /**
     * Handle the Pirep "updated" event.
     */
    public function updated(Pirep $pirep): void
    {
        $this->updatePilotLocation($pirep);

        // If the status was just changed to Accepted or Complete, score it
        if ($pirep->isDirty('status') && in_array(strtolower($pirep->status), ['accepted', 'complete', 'approved'])) {
            $this->scoringService->processPirep($pirep);
        }
    }

    /**
     * Automatically update the pilot's current location to the flight arrival airport.
     */
    protected function updatePilotLocation(Pirep $pirep): void
    {
        $destIcao = null;
        if ($pirep->route && $pirep->route->arrival_icao) {
            $destIcao = strtoupper($pirep->route->arrival_icao);
        } elseif (is_array($pirep->flight_log) && !empty($pirep->flight_log['destination'])) {
            $destIcao = strtoupper($pirep->flight_log['destination']);
        }

        if ($destIcao) {
            $arrivalAirport = \App\Models\Airport::where('icao', $destIcao)->first();
            if (!$arrivalAirport) {
                $arrivalAirport = \App\Models\Airport::create([
                    'icao' => $destIcao,
                    'name' => $destIcao,
                    'lat' => 0.0,
                    'lon' => 0.0,
                ]);
            }
            if ($arrivalAirport) {
                $profile = \App\Models\PilotProfile::firstOrCreate(
                    ['user_id' => $pirep->user_id, 'tenant_id' => $pirep->tenant_id],
                    ['flight_time' => 0, 'points' => 0]
                );
                $profile->current_airport_id = $arrivalAirport->id;
                $profile->save();
            }
        }
    }
}
