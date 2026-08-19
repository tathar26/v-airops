<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendNewVirtualAirlinePendingApprovalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 30;

    public Tenant $tenant;
    public ?User $creator;
    public string $baseHubIcao;

    /**
     * Create a new job instance.
     */
    public function __construct(Tenant $tenant, ?User $creator, string $baseHubIcao)
    {
        $this->tenant = $tenant;
        $this->creator = $creator;
        $this->baseHubIcao = $baseHubIcao;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Processing pending approval email notification for Virtual Airline '{$this->tenant->name}' ({$this->tenant->icao})");

        try {
            // Find all System Admins (Master Admin role)
            $admins = User::role('Master Admin')->get();

            if ($admins->isEmpty()) {
                // Fallback to first user or env admin email
                $admins = User::where('id', 1)->get();
            }

            $adminUrl = url('/admin');

            foreach ($admins as $admin) {
                Mail::send('emails.new-airline-pending-approval', [
                    'tenant' => $this->tenant,
                    'creator' => $this->creator,
                    'baseHubIcao' => $this->baseHubIcao,
                    'admin' => $admin,
                    'adminUrl' => $adminUrl,
                ], function ($message) use ($admin) {
                    $message->to($admin->email)
                        ->subject("[Action Required] New Virtual Airline Created: {$this->tenant->name} ({$this->tenant->icao})");
                });

                Log::info("Dispatched pending VA approval email notification to admin: {$admin->email}");
            }
        } catch (Exception $e) {
            Log::error("Failed sending pending VA approval email for {$this->tenant->name}: " . $e->getMessage());
            throw $e;
        }
    }
}
