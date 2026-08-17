<?php

namespace App\Console\Commands;

use App\Jobs\AssignMissingCallsignsJob;
use Illuminate\Console\Command;

class AssignMissingCallsignsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'callsigns:assign-missing {--tenant= : Filter by a specific Tenant/Airline ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process existing users in virtual airlines who do not have a callsign assigned yet';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;

        $this->info('Dispatching AssignMissingCallsignsJob...');

        AssignMissingCallsignsJob::dispatchSync($tenantId);

        $this->info('Successfully processed missing callsigns!');
        return Command::SUCCESS;
    }
}
