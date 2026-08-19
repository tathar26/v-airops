<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Pirep;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPirepsAndRecalculateStatsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Execute the job to process pending PIREPs and recalculate stats for all pilots.
     */
    public function handle(): void
    {
        // 1. Find all distinct user IDs who have submitted or accepted PIREPs
        $userIds = Pirep::pluck('user_id')->unique()->filter();

        // Also include all users with pilot profiles
        $profileUserIds = \App\Models\PilotProfile::pluck('user_id')->unique()->filter();
        $allUserIds = $userIds->merge($profileUserIds)->unique();

        foreach ($allUserIds as $userId) {
            try {
                RecalculatePilotStatistics::dispatchSync($userId);
            } catch (\Throwable $e) {
                Log::error("Failed recalculating statistics for User ID: {$userId}: " . $e->getMessage());
            }
        }
    }
}
