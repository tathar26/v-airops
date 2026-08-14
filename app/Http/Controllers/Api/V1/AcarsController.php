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

        // Check if queue connection is configured (e.g. redis or database); otherwise synchronous insert
        if (in_array(config('queue.default'), ['redis', 'database'])) {
            ProcessTelemetryPingJob::dispatch($payload);
            $pingId = (int) (AcarsPosition::max('id') ?? 0) + 1;
        } else {
            $ping = AcarsPosition::create($payload);
            $pingId = $ping->id;
        }

        return response()->json([
            'status' => 'success',
            'ping_id' => (int) $pingId,
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
