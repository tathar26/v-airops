<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Required | Virtual Airline Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Outfit', sans-serif; } </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-black">

    <div class="w-full max-w-md space-y-6 text-center">
        <!-- Animated Icon -->
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mb-2 shadow-xl shadow-amber-500/10">
            <svg class="w-10 h-10 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>

        <h1 class="text-3xl font-extrabold text-white">Check Your Email</h1>
        <p class="text-slate-400 text-sm leading-relaxed">
            We sent a verification link to your registered email address. Please click the link in the message to activate your pilot account and proceed to airline onboarding.
        </p>

        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            @if (session('email_sent'))
                <p class="text-xs text-sky-400 font-mono bg-sky-500/10 p-2.5 rounded-xl border border-sky-500/20">
                    Sent to: {{ session('email_sent') }}
                </p>
            @endif

            <p class="text-xs text-slate-500">
                Didn't receive the email? Check your spam folder or verify your email address.
            </p>

            @if (isset($devVerificationUrl) && $devVerificationUrl)
                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-left space-y-2">
                    <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-amber-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Local Dev Mode (Email Simulation)</span>
                    </div>
                    <p class="text-xs text-amber-200/80 leading-relaxed">
                        External emails are disabled in local dev. You can test the button-based verification flow directly:
                    </p>
                    <a href="{{ $devVerificationUrl }}" class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/40 text-amber-200 text-xs font-semibold transition-all">
                        <span>Open Verification Landing Page &rarr;</span>
                    </a>
                </div>
            @endif

            <a href="{{ route('login') }}" class="inline-block w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold transition-all">
                Return to Login
            </a>
        </div>
    </div>

</body>
</html>
