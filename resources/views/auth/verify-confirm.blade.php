<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email Address | Virtual Airline Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Outfit', sans-serif; } </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-black">

    <div class="w-full max-w-md space-y-6 text-center">

        @if ($status === 'valid')
            <!-- Valid Token State: Human Confirmation Required -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-sky-500/10 border border-sky-500/20 text-sky-400 mb-2 shadow-xl shadow-sky-500/10">
                <svg class="w-10 h-10 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>

            <h1 class="text-3xl font-extrabold text-white tracking-tight">Confirm Email Verification</h1>
            <p class="text-slate-400 text-sm leading-relaxed">
                Welcome aboard, <strong class="text-white">{{ $user->first_name ?? $user->name }}</strong>! Click the button below to confirm your email and activate your pilot account.
            </p>

            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5">
                <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 text-left">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Account Email</div>
                    <div class="text-sm font-mono text-sky-400 truncate mt-0.5">{{ $user->email }}</div>
                </div>

                <form method="POST" action="{{ route('auth.verify.confirm') }}" class="space-y-3">
                    @csrf
                    @if (isset($token))
                        <input type="hidden" name="token" value="{{ $token }}">
                    @endif
                    @if (isset($routeId) && isset($routeHash))
                        <input type="hidden" name="route_id" value="{{ $routeId }}">
                        <input type="hidden" name="route_hash" value="{{ $routeHash }}">
                    @endif

                    <button type="submit" class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white font-bold text-base shadow-xl shadow-sky-500/20 hover:shadow-sky-500/30 transition-all transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-sky-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Activate Pilot Account</span>
                    </button>
                </form>

                <p class="text-xs text-slate-500">
                    🔒 Protected against automated spam filters & Safe Links scanners.
                </p>
            </div>

        @elseif ($status === 'already_verified')
            <!-- Already Verified State -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 mb-2 shadow-xl shadow-emerald-500/10">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h1 class="text-3xl font-extrabold text-white tracking-tight">Already Verified!</h1>
            <p class="text-slate-400 text-sm leading-relaxed">
                Your email address <span class="text-emerald-400 font-mono">({{ $user->email }})</span> is already verified. You can sign in to your pilot portal directly.
            </p>

            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <a href="{{ route('login') }}" class="inline-block w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white font-semibold text-sm shadow-lg shadow-sky-500/20 transition-all">
                    Proceed to Login
                </a>
            </div>

        @elseif ($status === 'expired')
            <!-- Expired Token State -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mb-2 shadow-xl shadow-amber-500/10">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <h1 class="text-3xl font-extrabold text-white tracking-tight">Link Expired</h1>
            <p class="text-slate-400 text-sm leading-relaxed">
                This verification link has expired (links are valid for 24 hours). You can request a fresh verification link below.
            </p>

            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <form method="POST" action="{{ route('auth.verify.resend') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ $user->email ?? '' }}">
                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white font-semibold text-sm shadow-lg shadow-sky-500/20 transition-all flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Request New Verification Email</span>
                    </button>
                </form>

                <a href="{{ route('login') }}" class="inline-block w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold transition-all">
                    Return to Login
                </a>
            </div>

        @else
            <!-- Invalid / Missing Token State -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-rose-500/10 border border-rose-500/20 text-rose-400 mb-2 shadow-xl shadow-rose-500/10">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <h1 class="text-3xl font-extrabold text-white tracking-tight">Invalid Verification Link</h1>
            <p class="text-slate-400 text-sm leading-relaxed">
                {{ $message ?? 'This verification token was not found or has already been used.' }}
            </p>

            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <a href="{{ route('login') }}" class="inline-block w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold transition-all">
                    Return to Login
                </a>
            </div>
        @endif

    </div>

</body>
</html>
