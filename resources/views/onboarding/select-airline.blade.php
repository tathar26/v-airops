<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airline Selection Onboarding | Virtual Airline Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Outfit', sans-serif; } </style>
</head>
<body class="min-h-full bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-black p-6 md:p-12">

    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Header -->
        <div class="text-center space-y-3">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-sky-500/10 border border-sky-500/20 text-sky-400 text-xs font-semibold uppercase tracking-widest">
                <span class="w-2 h-2 rounded-full bg-sky-400 animate-ping"></span>
                First-Time Onboarding
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-white">Select Your Virtual Airline(s)</h1>
            <p class="text-slate-400 text-sm md:text-base max-w-xl mx-auto">
                Welcome, <span class="text-white font-semibold">{{ $user->name }}</span>! Choose one or more airlines to join. The platform will automatically assign your unique pilot callsign upon joining.
            </p>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm text-center">
                {{ session('success') }}
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('onboarding.join') }}" method="POST" class="space-y-8">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($airlines as $airline)
                    @php
                        $isAlreadyJoined = in_array($airline->id, $joinedAirlineIds);
                    @endphp
                    <label class="relative block cursor-pointer group">
                        <input type="checkbox" name="airline_ids[]" value="{{ $airline->id }}" {{ $isAlreadyJoined ? 'checked disabled' : '' }} class="peer sr-only">
                        
                        <div class="h-full p-6 rounded-3xl bg-slate-900/80 border border-slate-800 peer-checked:border-sky-500 peer-checked:bg-slate-900 peer-checked:ring-2 peer-checked:ring-sky-500/30 transition-all duration-200 hover:border-slate-700 shadow-xl flex flex-col justify-between space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-4">
                                    @if ($airline->logo_path)
                                        <img src="{{ asset('storage/' . $airline->logo_path) }}" alt="{{ $airline->name }}" class="w-12 h-12 object-contain rounded-xl bg-slate-950 p-2 border border-slate-800">
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-800 to-slate-950 flex items-center justify-center font-bold text-sky-400 border border-slate-700">
                                            {{ strtoupper($airline->icao ?: 'VA') }}
                                        </div>
                                    @endif

                                    <div>
                                        <h3 class="text-lg font-bold text-white group-hover:text-sky-400 transition-colors">{{ $airline->name }}</h3>
                                        <p class="text-xs text-slate-400 font-mono">ICAO: {{ strtoupper($airline->icao ?: 'VA') }}</p>
                                    </div>
                                </div>

                                <div class="w-6 h-6 rounded-full border border-slate-700 peer-checked:border-sky-500 peer-checked:bg-sky-500 flex items-center justify-center transition-all">
                                    <svg class="w-4 h-4 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between text-xs">
                                <span class="text-slate-400">Callsign Preview</span>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-sky-400 font-mono font-semibold">
                                    {{ strtoupper($airline->icao ?: 'VA') }}1042 (Auto-Generated)
                                </span>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-center pt-4">
                <button type="submit"
                    class="py-4 px-10 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white font-bold shadow-xl shadow-sky-500/25 hover:shadow-sky-500/40 transition-all duration-200 transform hover:-translate-y-0.5">
                    Confirm & Complete Onboarding →
                </button>
            </div>
        </form>
    </div>

</body>
</html>
