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
     * Handle the Pirep "updated" event.
     */
    public function updated(Pirep $pirep): void
    {
        // If the status was just changed to Accepted or Complete, score it
        if ($pirep->isDirty('status') && in_array($pirep->status, ['Accepted', 'Complete'])) {
            $this->scoringService->processPirep($pirep);
        }
    }
}
