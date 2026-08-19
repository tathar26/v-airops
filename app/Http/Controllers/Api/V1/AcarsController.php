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
        $user = $request->user();

        // Ensure telemetry position strictly belongs to the authenticated pilot's active flight
        if ($user) {
            $flight = AcarsActiveFlight::where('id', $payload['flight_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$flight) {
                $userFlight = AcarsActiveFlight::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();

                if ($userFlight) {
                    $payload['flight_id'] = $userFlight->id;
                } else {
                    return response()->json([
                        'status' => 'error',
                        'detail' => 'No active flight found for authenticated pilot'
                    ], 404);
                }
            }
        }

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
        $user = $request->user();

        // Ensure event strictly belongs to authenticated pilot's active flight
        if ($user) {
            $flight = AcarsActiveFlight::where('id', $payload['flight_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$flight) {
                $userFlight = AcarsActiveFlight::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();

                if ($userFlight) {
                    $payload['flight_id'] = $userFlight->id;
                } else {
                    return response()->json([
                        'status' => 'error',
                        'detail' => 'No active flight found for authenticated pilot'
                    ], 404);
                }
            }
        }

        $event = AcarsEvent::create($payload);

        return response()->json([
            'status' => 'recorded',
            'event_id' => $event->id,
        ]);
    }
}
