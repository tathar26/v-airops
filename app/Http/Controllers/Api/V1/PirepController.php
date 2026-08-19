<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PirepSubmitRequest;
use App\Models\AcarsActiveFlight;
use App\Models\AcarsEvent;
use App\Models\AcarsPirep;
use App\Models\Booking;
use App\Models\Pirep;
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

        // 1. Evaluate flight performance through comprehensive Scoring Engine & Failure Rules
        $dbEvents = AcarsEvent::where('flight_id', $flightId)->get();

        $evalData = [
            'touchdown_fpm' => (float) $request->input('touchdown_fpm'),
            'touchdown_gforce' => (float) $request->input('touchdown_gforce', 1.0),
            'gear_up_landing' => (bool) $request->input('gear_up_landing', false),
            'midair_refuel_detected' => (bool) $request->input('midair_refuel_detected', false),
            'sim_rate_max' => (float) $request->input('sim_rate_max', 1.0),
            'bounce_count' => (int) $request->input('bounce_count', 0),
            'block_time_minutes' => (int) $request->input('block_time_minutes'),
            'prep_time_minutes' => (int) $request->input('prep_time_minutes', 25),
            'fuel_used_kg' => (float) $request->input('fuel_used_kg', 0.0),
            'landing_fuel_kg' => (float) $request->input('landing_fuel_kg', 3000.0),
            'origin_icao' => $flight->origin_icao,
            'destination_icao' => $flight->destination_icao,
            'actual_destination_icao' => $request->input('actual_destination_icao', $flight->destination_icao),
            'network_connected' => $request->input('network_connected', 'OFFLINE'),
            'shared_cockpit' => (bool) $request->input('shared_cockpit', false),
            'engine_start_interval_seconds' => (int) $request->input('engine_start_interval_seconds', 60),
            'engines_shutdown_clean' => (bool) $request->input('engines_shutdown_clean', true),
            'engine_warmup_seconds' => (int) $request->input('engine_warmup_seconds', 180),
            'engine_cooldown_seconds' => (int) $request->input('engine_cooldown_seconds', 180),
            'flaps_retracted_before_parking' => (bool) $request->input('flaps_retracted_before_parking', true),
            'flaps_retracted_too_early' => (bool) $request->input('flaps_retracted_too_early', false),
            'takeoff_flaps_set' => (bool) $request->input('takeoff_flaps_set', true),
            'scheduled_time_minutes' => (int) ($flight->scheduled_time_minutes ?? $request->input('block_time_minutes')),
            'average_time_minutes' => (int) ($flight->average_time_minutes ?? $request->input('block_time_minutes')),
        ];

        $evalResult = $this->scoringService->evaluatePirepData($evalData);

        // 2. Atomically persist PIREP, update flight status, clean up booking, and update pilot stats
        $pirep = DB::transaction(function () use ($request, $flight, $user, $evalResult, $dbEvents) {
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
                'landing_grade' => $evalResult['landing_grade'],
                'total_score' => $evalResult['points_awarded'],
                'flight_log_json' => ['events_count' => $dbEvents->count(), 'failure_reasons' => $evalResult['failure_reasons']],
                'penalties_json' => $evalResult['penalties'],
            ]);

            // Mark active flight as completed so it is removed from active radar
            $flight->update(['status' => 'completed']);

            // Find and clean up active booking for this user
            $booking = Booking::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'dispatched', 'in_flight'])
                ->latest('id')
                ->first();

            $tenantId = $booking?->tenant_id ?? ($user->getActiveTenantId() ?? $user->tenant_id);

            // Also create main VA Pirep record for tenant statistics
            if ($tenantId) {
                $sb = $booking?->simbrief_data ?? [];
                $callsignVal = $sb['params']['callsign'] 
                    ?? ($sb['atc']['callsign'] 
                    ?? ($flight->flight_number 
                    ?? ($booking?->route?->callsign 
                    ?? 'SVK101')));

                $flightNumVal = $sb['general']['flight_number'] 
                    ?? ($sb['params']['fltnum'] 
                    ?? ($booking?->route?->flight_number 
                    ?? $callsignVal));

                $routeString = $flight->route 
                    ?? ($sb['general']['route'] 
                    ?? ($booking?->route?->route_string 
                    ?? 'DIRECT'));

                Pirep::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'route_id' => $booking?->route_id,
                    'airframe_id' => $booking?->airframe_id,
                    'status' => $evalResult['status'],
                    'flight_time' => $evalResult['hours_awarded'],
                    'fuel_used' => (float) $request->input('fuel_used_kg', 0.0),
                    'touchdown_rate_fpm' => (int) round($request->input('touchdown_fpm')),
                    'landing_g' => (float) $request->input('touchdown_gforce', 1.0),
                    'points_awarded' => $evalResult['points_awarded'],
                    'flight_log' => [
                        'flight_id' => $flight->id,
                        'callsign' => strtoupper($callsignVal),
                        'flight_number' => strtoupper($flightNumVal),
                        'origin' => strtoupper($flight->origin_icao),
                        'destination' => strtoupper($flight->destination_icao),
                        'route' => $routeString,
                        'aircraft_type' => $flight->aircraft_type,
                        'planned_altitude' => $flight->planned_altitude,
                        'planned_fuel_kg' => $flight->planned_fuel_kg,
                        'planned_zfw_kg' => $flight->planned_zfw_kg,
                        'touchdown_fpm' => (float) $request->input('touchdown_fpm'),
                        'touchdown_gforce' => (float) $request->input('touchdown_gforce', 1.0),
                        'landing_grade' => $evalResult['landing_grade'],
                        'eval_status' => $evalResult['status'],
                        'failure_reasons' => $evalResult['failure_reasons'],
                        'penalties' => $evalResult['penalties'],
                        'bonuses' => $evalResult['bonuses'],
                        'block_off_time' => $request->input('block_off_time'),
                        'block_on_time' => $request->input('block_on_time'),
                        'block_time_minutes' => (int) $request->input('block_time_minutes'),
                        'fuel_used_kg' => (float) $request->input('fuel_used_kg', 0.0),
                        'simbrief_data' => $sb,
                    ],
                    'created_at' => Carbon::now(),
                ]);
            }

            // Update pilot's current location to flight arrival airport
            $destIcao = strtoupper($request->input('actual_destination_icao') 
                ?? ($flight->destination_icao 
                ?? ($booking?->route?->arrival_icao 
                ?? null)));

            if ($destIcao && $tenantId) {
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
                        ['user_id' => $user->id, 'tenant_id' => $tenantId],
                        ['flight_time' => 0, 'points' => 0]
                    );
                    $profile->current_airport_id = $arrivalAirport->id;
                    $profile->save();
                }
            }

            if ($booking) {
                $booking->delete();
            }

            // Immediately trigger accurate statistic and rank recalculation for this pilot
            \App\Jobs\RecalculatePilotStatistics::dispatchSync($user->id);

            return $newPirep;
        });

        return response()->json([
            'pirep_id' => $pirep->id,
            'flight_id' => $pirep->flight_id,
            'touchdown_fpm' => (float) $pirep->touchdown_fpm,
            'touchdown_gforce' => (float) $pirep->touchdown_gforce,
            'landing_grade' => $pirep->landing_grade,
            'total_score' => $pirep->total_score,
            'penalties_applied' => $evalResult['penalties'] ?? [],
            'submitted_at' => $pirep->submitted_at->toISOString(),
        ]);
    }
}
