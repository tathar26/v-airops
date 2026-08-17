<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendVerificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 30;

    public User $user;
    public string $verificationUrl;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Processing queued verification email for User #{$this->user->id} ({$this->user->email})");

        try {
            Mail::raw(
                "Welcome to Virtual Airline Operations, {$this->user->name}!\n\nPlease click the following link to verify your account:\n{$this->verificationUrl}\n\nThis link will expire in 24 hours.",
                function ($message) {
                    $message->to($this->user->email)
                        ->subject('Verify Your Virtual Airline Pilot Account');
                }
            );

            Log::info("Successfully delivered verification email to {$this->user->email}");
        } catch (Exception $e) {
            Log::error("Failed sending verification email to {$this->user->email}: " . $e->getMessage());
            throw $e; // Re-throw to trigger retry / failed_jobs recording
        }
    }
}
