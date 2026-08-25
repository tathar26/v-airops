<?php

namespace App\Jobs;

use App\Models\AcarsActiveFlight;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PurgeUnflownBookingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of hours after which an unflown booking is considered expired.
     *
     * @var int
     */
    public int $hours;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $hours = null)
    {
        $this->hours = $hours ?? (int) config('services.acars.live_flight_retention_hours', (int) env('LIVE_FLIGHT_RETENTION_HOURS', 8));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoff = Carbon::now()->subHours($this->hours);

        $expiredQuery = Booking::where('created_at', '<=', $cutoff)
            ->whereIn('status', ['pending', 'dispatched']);

        $count = $expiredQuery->count();

        if ($count > 0) {
            $expiredQuery->delete();
            Log::info("PurgeUnflownBookingsJob: Successfully removed {$count} unflown booking(s) older than {$this->hours} hours (created before {$cutoff->toIso8601String()}).");
        } else {
            Log::info("PurgeUnflownBookingsJob: No unflown bookings older than {$this->hours} hours found.");
        }

        // Auto-archive active ACARS flights older than 24h
        $staleFlightCount = AcarsActiveFlight::where('status', 'active')
            ->where('updated_at', '<=', $cutoff)
            ->update(['status' => 'archived']);

        if ($staleFlightCount > 0) {
            Log::info("PurgeUnflownBookingsJob: Successfully archived {$staleFlightCount} stale active ACARS flight(s) older than {$this->hours} hours.");
        }
    }
}
