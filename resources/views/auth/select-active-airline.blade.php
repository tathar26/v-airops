<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Active Workspace | Virtual Airline Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Outfit', sans-serif; } </style>
</head>
<body class="min-h-full flex items-center justify-center p-6 bg-[#0A1835]">

    <div class="w-full max-w-2xl space-y-8">
        <!-- Brand Header -->
        <div class="text-center space-y-3">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#0F224A] border border-[#142954] text-[#21A19D] shadow-xl mb-1">
                <svg class="w-8 h-8 text-[#21A19D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-extrabold text-white">Choose Your Active Airline</h1>
            <p class="text-slate-400 text-sm">Select the Virtual Airline workspace you want to launch for this session</p>
        </div>

        <!-- Airline Workspace Cards (Slack Style) -->
        <div class="space-y-4">
            @foreach ($userAirlines as $ua)
                @php
                    $tenant = $ua->tenant;
                    $isActive = ($activeAirlineId == $tenant->id);
                @endphp
                <form action="{{ route('session.switch-airline') }}" method="POST">
                    @csrf
                    <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                    
                    <button type="submit" class="w-full text-left group transition-all duration-200">
                        <div class="p-6 rounded-2xl bg-[#0F224A] border {{ $isActive ? 'border-[#21A19D] ring-2 ring-[#21A19D]/30' : 'border-[#142954]' }} hover:border-[#21A19D] hover:bg-[#142954] shadow-xl flex items-center justify-between transition-all">
                            <div class="flex items-center gap-5">
                                @if ($tenant->logo_path)
                                    <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenant->name }}" class="w-14 h-14 object-contain rounded-xl bg-[#060E22] p-2.5 border border-[#142954]">
                                @else
                                    <div class="w-14 h-14 rounded-xl bg-[#060E22] flex items-center justify-center font-extrabold text-[#21A19D] text-lg border border-[#142954]">
                                        {{ strtoupper($tenant->icao ?: 'VA') }}
                                    </div>
                                @endif

                                <div>
                                    <div class="flex items-center gap-3">
                                        <h2 class="text-xl font-bold text-white group-hover:text-[#21A19D] transition-colors">{{ $tenant->name }}</h2>
                                        @if ($isActive)
                                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold">Active Session</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-slate-400 mt-1 font-mono">
                                        <span>Callsign: <strong class="text-[#21A19D]">{{ $ua->callsign }}</strong></span>
                                        <span>•</span>
                                        <span>Rank: <strong class="text-slate-200">{{ $ua->rank }}</strong></span>
                                        <span>•</span>
                                        <span>Joined: {{ $ua->join_date ? $ua->join_date->format('M Y') : 'Active' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 text-[#21A19D] font-semibold text-sm group-hover:translate-x-1 transition-transform">
                                Launch Workspace →
                            </div>
                        </div>
                    </button>
                </form>
            @endforeach
        </div>

        <div class="flex items-center justify-center gap-6 pt-2 text-xs">
            <a href="{{ route('onboarding.select-airline') }}" class="text-[#21A19D] hover:text-teal-300 font-medium transition-colors">
                + Join Another Virtual Airline
            </a>
            <span class="text-slate-700">&bull;</span>
            <a href="{{ route('onboarding.select-airline') }}" class="text-[#6F3B84] hover:text-purple-300 font-medium transition-colors">
                ✨ Create New Virtual Airline
            </a>
        </div>
    </div>

</body>
</html>
