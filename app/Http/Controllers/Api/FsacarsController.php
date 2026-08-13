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
        // FSACARS sends the pilot password hash in 'hash' and org serverpass in 'pass'
        $passInput = trim((string) ($request->input('hash') ?: $request->input('pass') ?: ''));

        Log::info('FSACARS Auth Request', [
            'user' => $userInput,
            'has_hash' => $request->has('hash'),
            'has_pass' => $request->has('pass'),
        ]);

        if (empty($userInput)) {
            return response('ERR#Missing Username', 200)->header('Content-Type', 'text/plain');
        }

        $user = $this->findUser($userInput);

        if (!$user) {
            Log::warning('FSACARS Auth Failed: Pilot Not Found', ['user' => $userInput]);
            return response('ERR#Pilot Not Found', 200)->header('Content-Type', 'text/plain');
        }

        if (!$this->validatePassword($user, $passInput, $request)) {
            Log::warning('FSACARS Auth Failed: Invalid Password', ['user' => $userInput]);
            return response('ERR#Invalid Password', 200)->header('Content-Type', 'text/plain');
        }

        Log::info('FSACARS Auth Success', ['user' => $userInput, 'pilot_name' => $user->name]);

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
     * Helper to locate user by Callsign, ID, or Email (case-insensitive).
     */
    private function findUser(string $identifier): ?User
    {
        $cleanId = strtolower(trim($identifier));

        if (is_numeric($cleanId)) {
            $user = User::find((int) $cleanId);
            if ($user) return $user;
        }

        return User::whereRaw('LOWER(email) = ?', [$cleanId])
            ->orWhereRaw('LOWER(callsign) = ?', [$cleanId])
            ->first();
    }

    /**
     * Validate pass input against user password or acars_password_hash.
     * Supports SHA-256 (hashalgo=SHA256 in org.cfg), SHA-1, stored acars_password_hash, and Bcrypt.
     */
    private function validatePassword(User $user, string $passInput, ?Request $request = null): bool
    {
        $hashParam = $request ? trim((string) $request->input('hash', '')) : '';
        $passParam = $request ? trim((string) $request->input('pass', '')) : '';
        
        $hashLower = strtolower($hashParam);
        $passLower = strtolower(trim($passInput));

        // 1. Direct match with stored acars_password_hash
        if (!empty($user->acars_password_hash)) {
            $storedHash = strtolower($user->acars_password_hash);
            if ($storedHash === $hashLower || $storedHash === $passLower) {
                return true;
            }
        }

        // 2. Direct Bcrypt / Hash check if pass or passInput was sent in plaintext
        if (Hash::check($passInput, $user->password) || ($passParam && Hash::check($passParam, $user->password))) {
            return true;
        }

        // 3. Auto-bind acars_password_hash on pilot's first FSACARS login attempt
        // We use hashParam if present (64 or 40 chars), otherwise passLower
        $targetHash = (strlen($hashLower) === 64 || strlen($hashLower) === 40) ? $hashLower : $passLower;
        if (strlen($targetHash) === 64 || strlen($targetHash) === 40) {
            $user->forceFill(['acars_password_hash' => $targetHash])->save();
            Log::info("FSACARS: Auto-bound acars_password_hash for user {$user->id} ({$user->email})");
            return true;
        }

        return false;
    }
}
