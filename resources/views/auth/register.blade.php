<!DOCTYPE html>
<html lang="en" class="h-full bg-[#0A1835] text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <x-google-analytics />
    <title>Pilot Registration | V-Air Ops Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-[#0A1835] overflow-x-hidden">

    <div class="w-full max-w-md space-y-6 my-auto">
        <!-- Logo / Brand Header -->
        <div class="text-center space-y-2">
            <a href="/" class="inline-block">
                <img src="{{ asset('images/v-air-ops-logo.png') }}" alt="V-Air Ops" class="h-20 w-auto object-contain mx-auto">
            </a>
            <h1 class="text-2xl font-extrabold tracking-tight text-white">Pilot Registration</h1>
            <p class="text-slate-400 text-sm">Join or create your own virtual airline for free</p>
        </div>

        <!-- Registration Card -->
        <div class="bg-[#0F224A] border border-[#142954] rounded-2xl p-8 shadow-2xl space-y-6">
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Username</label>
                    <div class="relative">
                        <input type="text" name="username" value="{{ old('username') }}" required placeholder="CaptainSmith"
                            class="w-full px-4 py-3 bg-[#060E22] border border-[#142954] rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#21A19D] focus:border-transparent transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required placeholder="John"
                            class="w-full px-4 py-3 bg-[#060E22] border border-[#142954] rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#21A19D] focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="Doe"
                            class="w-full px-4 py-3 bg-[#060E22] border border-[#142954] rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#21A19D] focus:border-transparent transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="pilot@flight-sim.org"
                        class="w-full px-4 py-3 bg-[#060E22] border border-[#142954] rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#21A19D] focus:border-transparent transition-all">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Password</label>
                    <input type="password" name="password" required placeholder="••••••••••••"
                        class="w-full px-4 py-3 bg-[#060E22] border border-[#142954] rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#21A19D] focus:border-transparent transition-all">
                </div>

                <!-- Cloudflare Turnstile Security Widget -->
                <div class="flex justify-center my-4">
                    <div class="cf-turnstile" data-sitekey="{{ config('services.cloudflare.turnstile_site_key') ?: env('CLOUDFLARE_TURNSTILE_SITE_KEY', '1x00000000000000000000AA') }}" data-theme="auto"></div>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-lg bg-[#21A19D] hover:bg-[#1C8C88] text-white font-semibold shadow-md transition-colors active:scale-[0.99]">
                    Create Free Pilot Account
                </button>
            </form>

            <div class="pt-4 border-t border-[#142954] text-center text-xs text-slate-400">
                Already registered? <a href="{{ route('login') }}" class="text-[#21A19D] hover:text-teal-300 font-semibold underline underline-offset-4">Log in here</a>
            </div>
        </div>
    </div>

</body>
</html>
