<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CustomAuthController extends Controller
{
    /**
     * Show registration form.
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Handle user registration and dispatch verification email.
     */
    public function register(Request $request)
    {
        $turnstileSecret = config('services.cloudflare.turnstile_secret_key') ?: env('CLOUDFLARE_TURNSTILE_SECRET_KEY');
        if ($turnstileSecret) {
            $turnstileToken = $request->input('cf-turnstile-response');
            if (!$turnstileToken) {
                return back()->withErrors([
                    'cf-turnstile-response' => 'Please complete the Cloudflare Turnstile security check.',
                ])->withInput();
            }

            try {
                $turnstileVerify = \Illuminate\Support\Facades\Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $turnstileSecret,
                    'response' => $turnstileToken,
                    'remoteip' => $request->ip(),
                ]);

                if (!$turnstileVerify->successful() || !$turnstileVerify->json('success')) {
                    return back()->withErrors([
                        'cf-turnstile-response' => 'Cloudflare Turnstile verification failed. Please try again.',
                    ])->withInput();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Cloudflare Turnstile verification error: ' . $e->getMessage());
            }
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,name'],
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $verificationToken = Str::random(64);

        $user = User::create([
            'name' => $validated['username'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'verification_token' => $verificationToken,
            'verification_token_expires_at' => now()->addHours(24),
            'verification_email_sent_at' => now(),
        ]);

        // Auto-assign default Pilot role
        $pilotRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Pilot']);
        $user->assignRole($pilotRole);

        // Build verification link
        $verificationUrl = route('auth.verify', ['token' => $verificationToken]);

        // Dispatch queued email job
        \App\Jobs\SendVerificationEmailJob::dispatch($user, $verificationUrl);

        return redirect()->route('auth.verify-notice')
            ->with('email_sent', $user->email)
            ->with('cooldown_seconds', 300)
            ->with('success', 'A verification email has been dispatched to your address.');
    }

    /**
     * Show verification pending notice page.
     */
    public function showVerifyNotice(Request $request)
    {
        $email = session('email_sent') ?? $request->query('email') ?? Auth::user()?->email;

        $user = null;
        if ($email) {
            $user = User::where('email', strtolower($email))->first();
        } elseif (app()->environment('local') || config('app.debug')) {
            $user = User::whereNull('email_verified_at')->whereNotNull('verification_token')->latest('id')->first();
        }

        $canResend = true;
        $cooldownSeconds = 0;

        if ($user) {
            $canResend = $user->canResendVerificationEmail();
            $cooldownSeconds = $user->verificationResendCooldownSeconds();
        }

        $devVerificationUrl = null;
        if ((app()->environment('local') || config('app.debug')) && $user && $user->verification_token) {
            $devVerificationUrl = route('auth.verify', ['token' => $user->verification_token]);
        }

        return view('auth.verify-notice', [
            'email' => $email ?? $user?->email,
            'user' => $user,
            'canResend' => $canResend,
            'cooldownSeconds' => $cooldownSeconds,
            'devVerificationUrl' => $devVerificationUrl,
        ]);
    }

    /**
     * Resend verification email with a 5-minute cooldown check.
     */
    public function resendVerificationEmail(Request $request)
    {
        $email = $request->input('email') ?? session('email_sent') ?? Auth::user()?->email;

        if (!$email) {
            $request->validate([
                'email' => ['required', 'email', 'exists:users,email'],
            ]);
            $email = $request->input('email');
        }

        $user = User::where('email', strtolower($email))->first();

        if (!$user) {
            return redirect()->route('auth.verify-notice')
                ->withErrors(['email' => 'No account found with this email address.']);
        }

        if ($user->email_verified_at) {
            return redirect()->route('login')
                ->with('status', 'Your account is already verified! You may log in.');
        }

        // 5-minute cooldown enforcement
        if (!$user->canResendVerificationEmail()) {
            $remainingSeconds = $user->verificationResendCooldownSeconds();
            $remainingMinutes = ceil($remainingSeconds / 60);

            return redirect()->route('auth.verify-notice')
                ->with('email_sent', $user->email)
                ->with('cooldown_seconds', $remainingSeconds)
                ->with('error', "Please wait {$remainingMinutes} minute(s) before requesting another verification email.");
        }

        // Generate fresh single-use token & stamp new sent time
        $verificationToken = Str::random(64);
        $user->update([
            'verification_token' => $verificationToken,
            'verification_token_expires_at' => now()->addHours(24),
            'verification_email_sent_at' => now(),
        ]);

        $verificationUrl = route('auth.verify', ['token' => $verificationToken]);

        \App\Jobs\SendVerificationEmailJob::dispatch($user, $verificationUrl);

        return redirect()->route('auth.verify-notice')
            ->with('email_sent', $user->email)
            ->with('cooldown_seconds', 300)
            ->with('success', 'A new verification email has been dispatched to your email address!');
    }

    /**
     * Display email verification prompt landing page (safe for Office 365 / Spam filters).
     */
    public function showVerifyPrompt(Request $request)
    {
        $token = $request->query('token') ?? $request->input('token');

        if ($token) {
            $user = User::where('verification_token', $token)->first();

            if (!$user) {
                return view('auth.verify-confirm', [
                    'status' => 'invalid',
                    'message' => 'This verification token was not found or has already been used.',
                ]);
            }

            if ($user->email_verified_at) {
                return view('auth.verify-confirm', [
                    'status' => 'already_verified',
                    'user' => $user,
                ]);
            }

            $isExpired = $user->verification_token_expires_at && \Carbon\Carbon::parse($user->verification_token_expires_at)->isPast();
            if ($isExpired) {
                return view('auth.verify-confirm', [
                    'status' => 'expired',
                    'user' => $user,
                ]);
            }

            return view('auth.verify-confirm', [
                'status' => 'valid',
                'user' => $user,
                'token' => $token,
            ]);
        }

        $id = $request->route('id') ?? $request->input('route_id');
        $hash = $request->route('hash') ?? $request->input('route_hash');

        if ($id && $hash) {
            $user = User::find($id);

            if ($user && hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                if ($user->hasVerifiedEmail()) {
                    return view('auth.verify-confirm', [
                        'status' => 'already_verified',
                        'user' => $user,
                    ]);
                }

                return view('auth.verify-confirm', [
                    'status' => 'valid',
                    'user' => $user,
                    'routeId' => $id,
                    'routeHash' => $hash,
                ]);
            }
        }

        return view('auth.verify-confirm', [
            'status' => 'invalid',
            'message' => 'Invalid or missing verification parameters.',
        ]);
    }

    /**
     * Process human POST confirmation to verify email and activate user account.
     */
    public function confirmVerifyEmail(Request $request)
    {
        $token = $request->input('token') ?? $request->query('token');

        if ($token) {
            $user = User::where('verification_token', $token)->first();

            if (!$user) {
                return redirect()->route('login')->withErrors(['verification' => 'Verification token not found or already verified.']);
            }

            $isExpired = $user->verification_token_expires_at && \Carbon\Carbon::parse($user->verification_token_expires_at)->isPast();
            if ($isExpired) {
                return redirect()->route('login')->withErrors(['verification' => 'Verification token has expired. Please request a new verification link.']);
            }

            $user->email_verified_at = now();
            $user->verification_token = null;
            $user->verification_token_expires_at = null;
            $user->save();

            Auth::login($user);

            return redirect()->route('onboarding.select-airline')->with('success', 'Email verified successfully! Please select your first Virtual Airline to get started.');
        }

        $id = $request->input('route_id') ?? $request->route('id');
        $hash = $request->input('route_hash') ?? $request->route('hash');

        if ($id && $hash) {
            $user = User::find($id);

            if ($user && hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                if (!$user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }

                Auth::login($user);
                return redirect()->route('onboarding.select-airline')->with('success', 'Email verified successfully! Please select your first Virtual Airline to get started.');
            }
        }

        return redirect()->route('login')->withErrors(['verification' => 'Invalid verification request.']);
    }

    /**
     * Fallback alias for direct email verification.
     */
    public function verifyEmail(Request $request)
    {
        return $this->showVerifyPrompt($request);
    }

    /**
     * Handle user login & initial routing guard.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        // 1. Verification Guard
        if (!$user->email_verified_at) {
            if (Auth::guard() instanceof \Illuminate\Contracts\Auth\StatefulGuard) {
                Auth::guard()->logout();
            } else {
                Auth::guard('web')->logout();
            }
            return redirect()->route('auth.verify-notice')->withErrors([
                'verification' => 'Your email address is unverified. Please check your inbox for the verification link.',
            ]);
        }

        // 2. Multi-Airline Session Routing Guard
        $userAirlines = $user->userAirlines()->with('tenant')->get();
        $airlineCount = $userAirlines->count();

        if ($airlineCount === 0) {
            return redirect()->route('onboarding.select-airline');
        }

        if ($airlineCount === 1) {
            $singleAirline = $userAirlines->first();
            session(['active_airline_id' => $singleAirline->tenant_id]);
            return redirect()->intended('/dashboard');
        }

        // >1 Airlines -> Redirect to Slack-Style Active Airline Selector
        return redirect()->route('session.select-airline');
    }
}
