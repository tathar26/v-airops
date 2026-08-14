<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PirepSubmitRequest;
use App\Models\AcarsActiveFlight;
use App\Models\AcarsEvent;
use App\Models\AcarsPirep;
use App\Services\Acars\ScoringService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PirepController extends Controller
{
    public function __construct(private readonly ScoringService $scoringService) {}

    public function submit(PirepSubmitRequest $request): JsonResponse
    {
        $user = $request->user();
        $flightId = (int) $request->input('flight_id');

        $flight = AcarsActiveFlight::where('id', $flightId)
            ->where('user_id', $user->id)
            ->first();

        if (!$flight) {
            return response()->json(['detail' => 'Flight not found or unauthorized'], 404);
        }

        if (AcarsPirep::where('flight_id', $flightId)->exists()) {
            return response()->json(['detail' => 'PIREP already submitted for this flight'], 400);
        }

        // 1. Evaluate touchdown performance
        $touchdownFpm = (float) $request->input('touchdown_fpm');
        $landingGradeResult = $this->scoringService->evaluateLandingGrade($touchdownFpm);

        $penaltiesApplied = [];
        $totalPenaltyPoints = $landingGradeResult['penalty'];

        if ($landingGradeResult['penalty'] > 0) {
            $penaltiesApplied[] = [
                'category' => 'Landing Rate',
                'description' => sprintf('Touchdown rate of %.1f FPM (%s)', $touchdownFpm, $landingGradeResult['grade']),
                'points_deducted' => $landingGradeResult['penalty'],
            ];
        }

        // 2. Aggregate logged events and deduct points
        $dbEvents = AcarsEvent::where('flight_id', $flightId)->get();
        foreach ($dbEvents as $ev) {
            $pts = $ev->penalty_points > 0 ? $ev->penalty_points : 10;
            $totalPenaltyPoints += $pts;
            $penaltiesApplied[] = [
                'category' => $ev->event_type,
                'description' => $ev->description,
                'points_deducted' => $pts,
            ];
        }

        $totalScore = max(0, 100 - $totalPenaltyPoints);

        // 3. Atomically persist PIREP, update flight status, and calculate pilot stats
        $pirep = DB::transaction(function () use ($request, $flight, $user, $landingGradeResult, $totalScore, $penaltiesApplied, $dbEvents) {
            $newPirep = AcarsPirep::create([
                'flight_id' => $flight->id,
                'user_id' => $user->id,
                'submitted_at' => Carbon::now(),
                'block_off_time' => $request->input('block_off_time'),
                'block_on_time' => $request->input('block_on_time'),
                'block_time_minutes' => (int) $request->input('block_time_minutes'),
                'fuel_used_kg' => (float) $request->input('fuel_used_kg', 0.0),
                'touchdown_fpm' => (float) $request->input('touchdown_fpm'),
                'touchdown_gforce' => (float) $request->input('touchdown_gforce', 1.0),
                'landing_grade' => $landingGradeResult['grade'],
                'total_score' => $totalScore,
                'flight_log_json' => ['events_count' => $dbEvents->count()],
                'penalties_json' => $penaltiesApplied,
            ]);

            // Update flight status
            $flight->update(['status' => 'completed']);

            // Update pilot profile hours
            $profile = $user->pilotProfiles()->first();
            if ($profile) {
                $additionalHours = round($request->input('block_time_minutes') / 60.0, 2);
                $profile->flight_time = ($profile->flight_time ?? 0.0) + $additionalHours;
                $profile->save();
            }

            return $newPirep;
        });

        return response()->json([
            'pirep_id' => $pirep->id,
            'flight_id' => $pirep->flight_id,
            'touchdown_fpm' => (float) $pirep->touchdown_fpm,
            'touchdown_gforce' => (float) $pirep->touchdown_gforce,
            'landing_grade' => $pirep->landing_grade,
            'total_score' => $pirep->total_score,
            'penalties_applied' => $penaltiesApplied,
            'submitted_at' => $pirep->submitted_at->toISOString(),
        ]);
    }
}
