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
        $callsign = strtoupper($request->input('callsign'));
        $user = User::where('callsign', $callsign)->first();

        // Auto-seed default test pilot if database is newly initialized
        if (!$user && $callsign === 'SVK101') {
            $user = User::create([
                'callsign' => 'SVK101',
                'name' => 'Skyvex Test Pilot',
                'email' => 'svk101@vops.local',
                'password' => Hash::make($request->input('password')),
            ]);
        }

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'detail' => 'Invalid callsign or password'
            ], 401);
        }

        // Revoke older tokens for clean session management
        $user->tokens()->where('name', 'vPilot-ACARS-Token')->delete();
        $token = $user->createToken('vPilot-ACARS-Token')->plainTextToken;

        $pilotProfile = $user->pilotProfiles()->first();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'pilot' => [
                'id' => $user->id,
                'callsign' => $user->callsign,
                'name' => $user->name,
                'rank' => $pilotProfile?->rank?->name ?? 'Captain',
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
            'callsign' => $user->callsign,
            'name' => $user->name,
            'rank' => $pilotProfile?->rank?->name ?? 'Captain',
            'total_flights' => $user->pireps()->count(),
            'total_hours' => round((float) ($pilotProfile?->flight_time ?? 0.0), 2),
        ]);
    }
}
