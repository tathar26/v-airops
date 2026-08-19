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
        ]);

        // Build verification link
        $verificationUrl = route('auth.verify', ['token' => $verificationToken]);

        // Dispatch queued email job
        \App\Jobs\SendVerificationEmailJob::dispatch($user, $verificationUrl);

        return redirect()->route('auth.verify-notice')->with('email_sent', $user->email);
    }

    /**
     * Show verification pending notice page.
     */
    public function showVerifyNotice()
    {
        return view('auth.verify-notice');
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

            if ($user->verification_token_expires_at && $user->verification_token_expires_at->isPast()) {
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

            if ($user->verification_token_expires_at && $user->verification_token_expires_at->isPast()) {
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
            Auth::logout();
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
