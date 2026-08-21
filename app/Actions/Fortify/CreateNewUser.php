<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $turnstileSecret = config('services.cloudflare.turnstile_secret_key') ?: env('CLOUDFLARE_TURNSTILE_SECRET_KEY');
        if ($turnstileSecret && isset($input['cf-turnstile-response'])) {
            try {
                $turnstileVerify = \Illuminate\Support\Facades\Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $turnstileSecret,
                    'response' => $input['cf-turnstile-response'],
                ]);
                if (!$turnstileVerify->successful() || !$turnstileVerify->json('success')) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cf-turnstile-response' => ['Cloudflare Turnstile verification failed. Please try again.'],
                    ]);
                }
            } catch (\Illuminate\Validation\ValidationException $ve) {
                throw $ve;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Cloudflare Turnstile verification error in Fortify: ' . $e->getMessage());
            }
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
