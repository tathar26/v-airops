<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserAirline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CallsignGeneratorService
{
    /**
     * Generate a unique callsign for a user joining a specific virtual airline.
     * Prevents race condition collisions using database row locking and retry loops.
     *
     * @param User $user
     * @param Tenant $tenant
     * @param string $initialRank
     * @return UserAirline
     * @throws Exception
     */
    public function assignUserToAirline(User $user, Tenant $tenant, string $initialRank = 'Cadet'): UserAirline
    {
        // Check if user is already enrolled in this airline
        $existing = UserAirline::where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                return DB::transaction(function () use ($user, $tenant, $initialRank) {
                    $prefix = strtoupper($tenant->icao ?: 'VA');
                    $prefixLength = strlen($prefix);

                    // 1. Lock existing callsign records for this airline to prevent concurrent race conditions
                    $lastCallsignRecord = UserAirline::where('tenant_id', $tenant->id)
                        ->lockForUpdate()
                        ->where('callsign', 'LIKE', $prefix . '%')
                        ->get()
                        ->map(function ($record) use ($prefix, $prefixLength) {
                            $numStr = substr($record->callsign, $prefixLength);
                            return is_numeric($numStr) ? (int) $numStr : 0;
                        })
                        ->max();

                    $baseNumber = $tenant->callsign_base ?? 1000;
                    $nextNumber = ($lastCallsignRecord && $lastCallsignRecord >= $baseNumber) 
                        ? $lastCallsignRecord + 1 
                        : $baseNumber + 1;

                    // Format callsign: e.g. RYR1042
                    $generatedCallsign = $prefix . sprintf('%04d', $nextNumber);

                    // Double check uniqueness for safety
                    while (UserAirline::where('tenant_id', $tenant->id)->where('callsign', $generatedCallsign)->exists()) {
                        $nextNumber++;
                        $generatedCallsign = $prefix . sprintf('%04d', $nextNumber);
                    }

                    // 2. Create junction record
                    return UserAirline::create([
                        'user_id' => $user->id,
                        'tenant_id' => $tenant->id,
                        'callsign' => $generatedCallsign,
                        'join_date' => now(),
                        'rank' => $initialRank,
                        'is_active' => true,
                    ]);
                });
            } catch (Exception $e) {
                Log::warning("Callsign generation attempt {$attempt} collision/conflict for User {$user->id} in Tenant {$tenant->id}: " . $e->getMessage());
                if ($attempt >= $maxRetries) {
                    throw new Exception("Unable to assign a unique callsign for airline {$tenant->name}. Please try again.");
                }
                usleep(50000 * $attempt); // Backoff before retrying
            }
        }

        throw new Exception("Failed to generate unique callsign after multiple attempts.");
    }
}
