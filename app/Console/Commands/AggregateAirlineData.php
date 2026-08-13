<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ProcessAirlineDataJob;
use App\Jobs\FetchExternalRouteDataJob;

class AggregateAirlineData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:aggregate-airline-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates flight and aircraft data from all airlines into the central repository';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data aggregation...');

        // Clean up legacy 3-character IATA rows from previous runs
        $pruned = \App\Models\SystemGlobalFlight::whereRaw('CHAR_LENGTH(departure_icao) < 4 OR CHAR_LENGTH(arrival_icao) < 4')->delete();
        if ($pruned > 0) {
            $this->info("Pruned {$pruned} legacy 3-letter IATA rows.");
        }

        $tenants = Tenant::all();
        $jobs = [];

        foreach ($tenants as $tenant) {
            $jobs[] = new ProcessAirlineDataJob($tenant->id);
        }

        // Also add the external fetch jobs to the batch
        $jobs[] = new \App\Jobs\FetchExternalAirportDataJob();
        $jobs[] = new \App\Jobs\FetchExternalAircraftDataJob();
        $jobs[] = new \App\Jobs\FetchExternalAirlineDataJob();
        $jobs[] = new FetchExternalRouteDataJob();
        $jobs[] = new \App\Jobs\FetchJontyRoutesJob();

        $batch = Bus::batch($jobs)
            ->name('Aggregate Global Network Data')
            ->dispatch();

        $this->info("Dispatched batch {$batch->id} with " . count($jobs) . " jobs.");
    }
}
