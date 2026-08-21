<!DOCTYPE html>
<html lang="en" class="h-full bg-[#0A1835] text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | V-Air Ops Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <h1 class="text-2xl font-extrabold tracking-tight text-white">Reset Password</h1>
            <p class="text-slate-400 text-sm">We'll email you a secure link to reset your password</p>
        </div>

        <!-- Card -->
        <div class="bg-[#0F224A] border border-[#142954] rounded-2xl p-8 shadow-2xl space-y-6">
            
            @session('status')
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-semibold flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ $value }}</span>
                </div>
            @endsession

            @if ($errors->any())
                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
                    <div class="relative">
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="pilot@flight-sim.org"
                            class="w-full px-4 py-3 bg-[#060E22] border border-[#142954] rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#21A19D] focus:border-transparent transition-all">
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 rounded-lg bg-[#21A19D] hover:bg-[#1C8C88] text-white font-semibold shadow-md transition-colors active:scale-[0.99]">
                    Email Password Reset Link
                </button>
            </form>

            <div class="pt-4 border-t border-[#142954] text-center text-xs text-slate-400">
                Remember your password? <a href="{{ route('login') }}" class="text-[#21A19D] hover:text-teal-300 font-semibold underline underline-offset-4">Return to login</a>
            </div>
        </div>
    </div>

</body>
</html>
