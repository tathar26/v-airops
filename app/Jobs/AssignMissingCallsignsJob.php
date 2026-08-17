<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserAirline;
use App\Services\CallsignGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AssignMissingCallsignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Optional tenant ID filter. If provided, processes only pilots for that airline.
     *
     * @var int|null
     */
    protected ?int $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $tenantId = null)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle(CallsignGeneratorService $callsignService): void
    {
        Log::info("Starting AssignMissingCallsignsJob processing...");

        $processedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        // 1. Process legacy users directly linked to a tenant via users.tenant_id who missing user_airlines entries
        $usersQuery = User::whereNotNull('tenant_id');
        if ($this->tenantId) {
            $usersQuery->where('tenant_id', $this->tenantId);
        }

        $usersQuery->chunk(100, function ($users) use ($callsignService, &$processedCount, &$skippedCount, &$failedCount) {
            foreach ($users as $user) {
                $tenant = Tenant::find($user->tenant_id);
                if (!$tenant) {
                    $skippedCount++;
                    continue;
                }

                // Check if user already has a valid callsign record in user_airlines
                $userAirline = UserAirline::where('user_id', $user->id)
                    ->where('tenant_id', $tenant->id)
                    ->first();

                if ($userAirline && !empty($userAirline->callsign)) {
                    $skippedCount++;
                    continue;
                }

                try {
                    // Check if legacy user already has a callsign string in users table
                    if ($user->callsign && !empty(trim($user->callsign))) {
                        $legacyCallsign = strtoupper(trim($user->callsign));

                        // Ensure legacy callsign isn't taken in this airline
                        $exists = UserAirline::where('tenant_id', $tenant->id)
                            ->where('callsign', $legacyCallsign)
                            ->where('user_id', '!=', $user->id)
                            ->exists();

                        if (!$exists) {
                            UserAirline::updateOrCreate(
                                ['user_id' => $user->id, 'tenant_id' => $tenant->id],
                                [
                                    'callsign' => $legacyCallsign,
                                    'join_date' => $user->created_at ?? now(),
                                    'rank' => 'Pilot',
                                    'is_active' => true,
                                ]
                            );
                            $processedCount++;
                            Log::info("Preserved legacy callsign {$legacyCallsign} for User #{$user->id} in Tenant #{$tenant->id}");
                            continue;
                        }
                    }

                    // Otherwise, generate a fresh unique collision-free callsign
                    $assignedRecord = $callsignService->assignUserToAirline($user, $tenant, 'Pilot');
                    
                    // Sync callsign to user table if blank
                    if (empty($user->callsign)) {
                        $user->callsign = $assignedRecord->callsign;
                        $user->save();
                    }

                    $processedCount++;
                    Log::info("Assigned new callsign {$assignedRecord->callsign} to User #{$user->id} in Tenant #{$tenant->id}");
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Failed to assign callsign for User #{$user->id} in Tenant #{$tenant->id}: " . $e->getMessage());
                }
            }
        });

        // 2. Process records in user_airlines table where callsign is NULL or empty
        $orphanedAirlinesQuery = UserAirline::where(function ($query) {
            $query->whereNull('callsign')->orWhere('callsign', '');
        });

        if ($this->tenantId) {
            $orphanedAirlinesQuery->where('tenant_id', $this->tenantId);
        }

        $orphanedAirlinesQuery->chunk(100, function ($userAirlines) use ($callsignService, &$processedCount, &$failedCount) {
            foreach ($userAirlines as $ua) {
                $user = User::find($ua->user_id);
                $tenant = Tenant::find($ua->tenant_id);

                if (!$user || !$tenant) {
                    continue;
                }

                try {
                    $assignedRecord = $callsignService->assignUserToAirline($user, $tenant, $ua->rank ?: 'Pilot');
                    $processedCount++;
                    Log::info("Fixed empty callsign record #{$ua->id} with callsign {$assignedRecord->callsign} for User #{$user->id}");
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Failed to repair empty callsign record #{$ua->id}: " . $e->getMessage());
                }
            }
        });

        Log::info("AssignMissingCallsignsJob completed. Processed: {$processedCount}, Skipped: {$skippedCount}, Failed: {$failedCount}");
    }
}
