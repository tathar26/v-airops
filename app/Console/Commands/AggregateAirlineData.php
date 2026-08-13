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

        $tenants = Tenant::all();
        $jobs = [];

        foreach ($tenants as $tenant) {
            $jobs[] = new ProcessAirlineDataJob($tenant->id);
        }

        // Also add the external fetch jobs to the batch
        $jobs[] = new \App\Jobs\FetchExternalAirlineDataJob();
        $jobs[] = new FetchExternalRouteDataJob();
        $jobs[] = new \App\Jobs\FetchJontyRoutesJob();

        $batch = Bus::batch($jobs)
            ->name('Aggregate Global Network Data')
            ->dispatch();

        $this->info("Dispatched batch {$batch->id} with " . count($jobs) . " jobs.");
    }
}
