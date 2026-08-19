<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:verify 
                            {user? : Email or username of the user to verify}
                            {--all : Verify all unverified users}
                            {--link : Generate and output verification link instead of direct verification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify a user email or generate verification links for local testing without external email';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('all')) {
            $count = User::whereNull('email_verified_at')->update([
                'email_verified_at' => now(),
                'verification_token' => null,
                'verification_token_expires_at' => null,
            ]);

            $this->info("Successfully verified all ({$count}) unverified user(s).");
            return Command::SUCCESS;
        }

        $identifier = $this->argument('user');

        if (!$identifier) {
            $unverifiedUsers = User::whereNull('email_verified_at')->get();

            if ($unverifiedUsers->isEmpty()) {
                $this->info('All users in the system are already verified.');
                return Command::SUCCESS;
            }

            $choices = $unverifiedUsers->mapWithKeys(function ($user) {
                return [$user->id => "{$user->name} ({$user->email})"];
            })->toArray();

            $choices['all'] = 'Verify ALL unverified users';

            $selected = $this->choice('Select an unverified user to verify or generate link for:', $choices);

            if ($selected === 'Verify ALL unverified users') {
                User::whereNull('email_verified_at')->update([
                    'email_verified_at' => now(),
                    'verification_token' => null,
                    'verification_token_expires_at' => null,
                ]);
                $this->info("Successfully verified all ({$unverifiedUsers->count()}) users.");
                return Command::SUCCESS;
            }

            $userId = array_search($selected, $choices);
            $user = User::find($userId);
        } else {
            $user = User::where('email', $identifier)
                ->orWhere('name', $identifier)
                ->first();

            if (!$user) {
                $this->error("User '{$identifier}' not found in database.");
                return Command::FAILURE;
            }
        }

        if ($this->option('link')) {
            if (!$user->verification_token) {
                $user->verification_token = \Illuminate\Support\Str::random(64);
                $user->verification_token_expires_at = now()->addHours(24);
                $user->save();
            }

            $url = route('auth.verify', ['token' => $user->verification_token]);
            $this->info("Verification URL for {$user->name} ({$user->email}):");
            $this->line("<comment>{$url}</comment>");
            return Command::SUCCESS;
        }

        $user->email_verified_at = now();
        $user->verification_token = null;
        $user->verification_token_expires_at = null;
        $user->save();

        $this->info("User '{$user->name}' ({$user->email}) has been successfully verified.");

        return Command::SUCCESS;
    }
}
