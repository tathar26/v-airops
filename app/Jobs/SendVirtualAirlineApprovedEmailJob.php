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

class SendVirtualAirlineApprovedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 30;

    public Tenant $tenant;
    public User $owner;

    /**
     * Create a new job instance.
     */
    public function __construct(Tenant $tenant, User $owner)
    {
        $this->tenant = $tenant;
        $this->owner = $owner;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Sending approval email for Virtual Airline '{$this->tenant->name}' to owner {$this->owner->email}");

        try {
            $dashboardUrl = url('/dashboard');

            Mail::send('emails.airline-approved', [
                'tenant' => $this->tenant,
                'owner' => $this->owner,
                'dashboardUrl' => $dashboardUrl,
            ], function ($message) {
                $message->to($this->owner->email)
                    ->subject("🎉 Your Virtual Airline '{$this->tenant->name}' has been Approved!");
            });

            Log::info("Successfully delivered approval email to {$this->owner->email}");
        } catch (Exception $e) {
            Log::error("Failed sending VA approval email to {$this->owner->email}: " . $e->getMessage());
            throw $e;
        }
    }
}
