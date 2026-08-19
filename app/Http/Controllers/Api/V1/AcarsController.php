<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FlightEventRequest;
use App\Http\Requests\Api\V1\TelemetryPingRequest;
use App\Jobs\ProcessTelemetryPingJob;
use App\Models\AcarsActiveFlight;
use App\Models\AcarsEvent;
use App\Models\AcarsPosition;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AcarsController extends Controller
{
    public function position(TelemetryPingRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['timestamp'] = $payload['timestamp'] ?? Carbon::now();

        // Always create position record synchronously so live radar and flight parameters update in real-time
        $ping = AcarsPosition::create($payload);

        // Also dispatch background job for scoring and safety checks if queue is enabled
        if (in_array(config('queue.default'), ['redis', 'database'])) {
            try {
                ProcessTelemetryPingJob::dispatch($payload);
            } catch (\Exception $e) {
                // Non-blocking fallback
            }
        }

        return response()->json([
            'status' => 'success',
            'ping_id' => (int) $ping->id,
        ]);
    }

    public function event(FlightEventRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['timestamp'] = Carbon::now();

        $event = AcarsEvent::create($payload);

        return response()->json([
            'status' => 'recorded',
            'event_id' => $event->id,
        ]);
    }
}
