<?php

namespace App\Console\Commands;

use App\Jobs\PurgeUnflownBookingsJob;
use Illuminate\Console\Command;

class PurgeUnflownBookingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:purge-unflown {--hours=24 : Age limit in hours after which unflown bookings are removed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove flight bookings that have not been flown and submitted within the specified timeframe (default: 24 hours)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $this->info("Dispatching PurgeUnflownBookingsJob for bookings older than {$hours} hours...");

        PurgeUnflownBookingsJob::dispatchSync($hours);

        $this->info("Successfully processed unflown booking purge!");
        return Command::SUCCESS;
    }
}
