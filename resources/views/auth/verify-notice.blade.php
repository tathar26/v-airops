<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Required | Virtual Airline Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Outfit', sans-serif; } </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-[#0A1835]">

    <div class="w-full max-w-md space-y-6 text-center">
        <!-- Brand / Icon -->
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-[#0F224A] border border-[#142954] text-[#21A19D] mb-2 shadow-xl">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>

        <h1 class="text-3xl font-extrabold text-white tracking-tight">Check Your Email</h1>
        <p class="text-slate-400 text-sm leading-relaxed">
            We sent a verification link to your registered email address. Please click the link in the message to activate your pilot account.
        </p>

        <!-- Flash Status Messages -->
        @if (session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-semibold flex items-center justify-center space-x-2">
                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error') || $errors->any())
            <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-semibold flex items-center justify-center space-x-2">
                <svg class="w-4 h-4 text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('error') ?? $errors->first() }}</span>
            </div>
        @endif

        <div class="bg-[#0F224A] border border-[#142954] rounded-2xl p-6 shadow-2xl space-y-4">
            @if (!empty($email) || session('email_sent'))
                <div class="p-3 rounded-lg bg-[#060E22] border border-[#142954] text-left">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Recipient Email</div>
                    <div class="text-xs font-mono text-[#21A19D] truncate mt-0.5">{{ $email ?? session('email_sent') }}</div>
                </div>
            @endif

            <p class="text-xs text-slate-400">
                Didn't receive the email? Check your spam folder or request a new link below (available once every 5 minutes).
            </p>

            <!-- Resend Button with 5-Min Realtime Countdown -->
            <div x-data="{
                timeLeft: {{ (int) ($cooldownSeconds ?? session('cooldown_seconds', 0)) }},
                timer: null,
                init() {
                    if (this.timeLeft > 0) {
                        this.timer = setInterval(() => {
                            if (this.timeLeft > 0) {
                                this.timeLeft--;
                            } else {
                                clearInterval(this.timer);
                            }
                        }, 1000);
                    }
                },
                get formattedTime() {
                    const m = Math.floor(this.timeLeft / 60);
                    const s = this.timeLeft % 60;
                    return `${m}:${s < 10 ? '0' : ''}${s}`;
                }
            }" class="w-full">
                <form method="POST" action="{{ route('auth.verify.resend') }}" class="space-y-3">
                    @csrf
                    @if (empty($email) && !session('email_sent'))
                        <div class="text-left">
                            <input type="email" name="email" required placeholder="Enter your registered email" class="w-full bg-[#060E22] border border-[#142954] rounded-lg px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-[#21A19D] transition">
                        </div>
                    @else
                        <input type="hidden" name="email" value="{{ $email ?? session('email_sent') }}">
                    @endif

                    <button type="submit" 
                        :disabled="timeLeft > 0"
                        :class="timeLeft > 0 ? 'opacity-60 cursor-not-allowed bg-navy-950 text-slate-400 border border-navy-700' : 'bg-[#21A19D] hover:bg-[#1C8C88] text-white shadow-md transition-colors'"
                        class="w-full py-3.5 px-4 rounded-lg font-bold text-xs tracking-wide transition-all flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" :class="{ 'animate-spin': timeLeft > 0 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span x-text="timeLeft > 0 ? 'Resend Available in ' + formattedTime : 'Resend Verification Email'"></span>
                    </button>
                </form>
            </div>

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

            <a href="{{ route('login') }}" class="inline-block w-full py-3 px-4 rounded-2xl bg-slate-800/60 hover:bg-slate-800 text-slate-300 hover:text-white text-xs font-semibold transition-all border border-slate-800">
                Return to Login
            </a>
        </div>
    </div>

</body>
</html>
