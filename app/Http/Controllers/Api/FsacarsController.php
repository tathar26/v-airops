<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fsacars\PirepRequest;
use App\Models\User;
use App\Models\Booking;
use App\Models\Pirep;
use App\Models\PirepTelemetry;
use App\Models\PilotProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FsacarsController extends Controller
{
    /**
     * Authenticate Pilot credentials from FSACARS client.
     */
    public function authenticate(Request $request): Response
    {
        $userInput = trim((string) $request->input('user', ''));
        $passInput = trim((string) $request->input('pass', ''));

        if (empty($userInput)) {
            return response('ERR#Missing Username', 200)->header('Content-Type', 'text/plain');
        }

        $user = $this->findUser($userInput);

        if (!$user) {
            return response('ERR#Pilot Not Found', 200)->header('Content-Type', 'text/plain');
        }

        if (!$this->validatePassword($user, $passInput)) {
            return response('ERR#Invalid Password', 200)->header('Content-Type', 'text/plain');
        }

        // FSACARS expects plaintext OK or OK#PilotName
        return response('OK#' . $user->name, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Fetch Pilot's active flight bid details for FSACARS dispatch.
     */
    public function dispatch(Request $request): Response
    {
        $userInput = trim((string) $request->input('user', ''));
        $user = $this->findUser($userInput);

        if (!$user) {
            return response('ERR#Pilot Not Found', 200)->header('Content-Type', 'text/plain');
        }

        // Find user's active booking
        $booking = Booking::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['route', 'airframe'])
            ->latest()
            ->first();

        if (!$booking || !$booking->route) {
            // Fallback default dispatch string format if no active booking exists
            return response('VOPS101|EGLL|LFPG|A320|DVR L10 WELIN|320|150|3000|11500', 200)
                ->header('Content-Type', 'text/plain');
        }

        $route = $booking->route;
        $simData = $booking->simbrief_data ?? [];

        $fl = $simData['general']['initial_fl'] ?? 320;
        $pax = $simData['general']['passengers'] ?? 150;
        $cargo = $simData['general']['cargo'] ?? 3000;
        $fuel = $simData['fuel']['plan_ramp'] ?? 11500;
        $equip = $booking->airframe?->icao ?? ($simData['aircraft']['icao'] ?? 'A320');
        $routeStr = $route->route_string ?? ($simData['general']['route'] ?? 'DCT');

        // Pipe-delimited payload expected by FSACARS
        // Format: FLIGHT_NUM|DEP_ICAO|ARR_ICAO|EQUIPMENT|ROUTE|FLIGHT_LEVEL|PAX|CARGO_LBS|FUEL_LBS
        $payload = sprintf(
            '%s|%s|%s|%s|%s|%d|%d|%d|%d',
            $route->flight_number,
            $route->departure_icao,
            $route->arrival_icao,
            $equip,
            $routeStr,
            is_numeric($fl) ? (int) $fl : 320,
            (int) $pax,
            (int) $cargo,
            (int) $fuel
        );

        return response($payload, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Store in-flight position telemetry sent by FSACARS.
     */
    public function positionReport(Request $request): Response
    {
        $fhash = $request->input('fhash');

        if (!empty($fhash)) {
            PirepTelemetry::create([
                'flight_hash'      => $fhash,
                'latitude'         => (float) str_replace(',', '.', $request->input('lat2', 0)),
                'longitude'        => (float) str_replace(',', '.', $request->input('lon2', 0)),
                'altitude_msl'     => (int) $request->input('msl', 0),
                'ground_speed_kts' => (int) $request->input('gskts', 0),
                'heading'          => (int) $request->input('hdgtrue', 0),
                'elapsed_seconds'  => (int) $request->input('etime', 0),
            ]);
        }

        // Return legacy #OK# acknowledgment
        return response('#OK#', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Process completed PIREP submission from FSACARS client.
     */
    public function submitPirep(PirepRequest $request): Response
    {
        $userInput = trim((string) $request->input('user'));
        $user = $this->findUser($userInput);

        if (!$user) {
            return response('ERR#Pilot Not Found', 200)->header('Content-Type', 'text/plain');
        }

        // Find active booking for user
        $booking = Booking::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        $fuelStart = (float) $request->input('fuelstart', 0);
        $fuelStop  = (float) $request->input('fuelstop', 0);
        $fuelUsed  = max(0, $fuelStart - $fuelStop);
        $blockHrs  = (float) $request->input('blocktime', 0);
        $flightMins = (int) round($blockHrs * 60);

        // Parse log string or raw payload
        $rawLog = $request->input('log') ?? $request->getContent();

        // Create PIREP record
        $pirep = Pirep::create([
            'tenant_id'            => $user->tenant_id,
            'user_id'              => $user->id,
            'route_id'             => $booking?->route_id,
            'airframe_id'          => $booking?->airframe_id,
            'flight_hash'          => $request->input('fhash'),
            'aircraft_title'       => $request->input('aircraft', 'FSACARS Aircraft'),
            'atc_model'            => $request->input('atcModel'),
            'time_out'             => $request->input('timeout'),
            'time_off'             => $request->input('timeoff'),
            'time_on'              => $request->input('timeon'),
            'time_in'              => $request->input('timein'),
            'block_time'           => $blockHrs,
            'air_time'             => (float) $request->input('airtime', 0),
            'distance_nm'          => (float) $request->input('actualNM', 0),
            'fuel_start'           => $fuelStart,
            'fuel_stop'            => $fuelStop,
            'fuel_used'            => $fuelUsed,
            'takeoff_weight'       => (int) $request->input('takeoffLBS'),
            'landing_weight'       => (int) $request->input('landingLBS'),
            'touchdown_rate_fpm'   => (int) $request->input('landingFPM', 0),
            'landing_g'            => (float) $request->input('landingG', 0),
            'landing_kts'          => (float) $request->input('landingKTS', 0),
            'landing_flight_rules' => $request->input('landingFR', 'VFR'),
            'has_crashed'          => (bool) $request->input('crashed', 0),
            'overspeed_count'      => (int) $request->input('overspeed', 0),
            'pause_count'          => (int) $request->input('pause', 0),
            'slew_count'           => (int) $request->input('slew', 0),
            'stall_count'          => (int) $request->input('stall', 0),
            'simulator'            => $request->input('fsver'),
            'raw_acars_log'        => $rawLog,
            'flight_log'           => is_array($rawLog) ? $rawLog : explode("\n", (string) $rawLog),
            'status'               => 'filed',
            'flight_time'          => $flightMins,
            'points_awarded'       => max(10, $flightMins),
        ]);

        // Link position reports to this PIREP
        PirepTelemetry::where('flight_hash', $pirep->flight_hash)
            ->update(['pirep_id' => $pirep->id]);

        // Update pilot profile stats
        $profile = PilotProfile::firstOrCreate(
            ['user_id' => $user->id, 'tenant_id' => $user->tenant_id],
            ['flight_time' => 0, 'points' => 0]
        );
        $profile->increment('flight_time', $flightMins);
        $profile->increment('points', $pirep->points_awarded);

        // Mark booking as completed if it exists
        if ($booking) {
            $booking->update(['status' => 'completed']);
        }

        // Return legacy acknowledgment string required by FSACARS client
        return response('#RXOK#', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Helper to locate user by Callsign, ID, or Email.
     */
    private function findUser(string $identifier): ?User
    {
        if (is_numeric($identifier)) {
            $user = User::find((int) $identifier);
            if ($user) return $user;
        }

        return User::where('callsign', $identifier)
            ->orWhere('email', $identifier)
            ->first();
    }

    /**
     * Validate pass input against user password or acars_password_hash.
     * Supports SHA-256 (hashalgo=SHA256 in org.cfg), SHA-1, stored acars_password_hash, and Bcrypt.
     */
    private function validatePassword(User $user, string $passInput): bool
    {
        $passInputLower = strtolower($passInput);

        // 1. Direct match with stored acars_password_hash
        if (!empty($user->acars_password_hash) && strtolower($user->acars_password_hash) === $passInputLower) {
            return true;
        }

        // 2. Match SHA-256 hash (from org.cfg hashalgo=SHA256)
        if ($passInputLower === hash('sha256', $user->email)) {
            return true;
        }

        // 3. Match SHA-1 hash (default FSACARS hash)
        if ($passInputLower === sha1($user->email)) {
            return true;
        }

        // 4. Bcrypt or Hash check if passInput was sent in plaintext
        if (Hash::check($passInput, $user->password)) {
            return true;
        }

        return false;
    }
}
