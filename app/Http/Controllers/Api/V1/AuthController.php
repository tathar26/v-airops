<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = trim((string) ($request->input('email') ?: $request->input('callsign')));
        $password = (string) $request->input('password');

        if (empty($identifier)) {
            return response()->json([
                'detail' => 'Email or callsign is required'
            ], 400);
        }

        // Query user by email OR callsign (case-insensitive)
        $user = User::where(function ($query) use ($identifier) {
            $query->whereRaw('LOWER(email) = ?', [strtolower($identifier)])
                  ->orWhereRaw('LOWER(callsign) = ?', [strtolower($identifier)]);
        })->first();

        // Auto-seed demo pilot if database is newly initialized and demo account is used
        $demoIdentifiers = ['pilot@v-ops.com', 'demo@skyvex.com', 'svk101', 'pilot@example.com'];
        if (!$user && in_array(strtolower($identifier), $demoIdentifiers)) {
            $user = User::create([
                'email' => str_contains($identifier, '@') ? $identifier : 'pilot@v-ops.com',
                'callsign' => !str_contains($identifier, '@') ? strtoupper($identifier) : 'SVK101',
                'name' => 'Demo Pilot',
                'password' => Hash::make($password),
            ]);
        }

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'detail' => 'Invalid email/callsign or password'
            ], 401);
        }

        // Revoke previous tokens for clean session management
        $user->tokens()->where('name', 'vPilot-ACARS-Token')->delete();
        $token = $user->createToken('vPilot-ACARS-Token')->plainTextToken;

        $pilotProfile = $user->pilotProfiles()->first();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'pilot' => [
                'id' => $user->id,
                'email' => $user->email,
                'callsign' => $user->callsign ?? 'PILOT',
                'name' => $user->name,
                'rank' => $pilotProfile?->rank?->name ?? 'Senior Captain',
                'total_flights' => $user->pireps()->count(),
                'total_hours' => round((float) ($pilotProfile?->flight_time ?? 0.0), 2),
            ]
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $pilotProfile = $user->pilotProfiles()->first();

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'callsign' => $user->callsign ?? 'PILOT',
            'name' => $user->name,
            'rank' => $pilotProfile?->rank?->name ?? 'Senior Captain',
            'total_flights' => $user->pireps()->count(),
            'total_hours' => round((float) ($pilotProfile?->flight_time ?? 0.0), 2),
        ]);
    }
}
