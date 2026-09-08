@php
    $user = Auth::user();
    $userAirlines = $user ? $user->userAirlines()->with('tenant')->get() : collect();
    $activeAirlineId = session('active_airline_id');
    $activeRecord = $userAirlines->firstWhere('tenant_id', $activeAirlineId) ?? $userAirlines->first();
@endphp

@if ($user && $userAirlines->count() > 0)
    <div class="relative inline-block text-left" x-data="{ open: false }">
        <!-- Top Right Header Airline Switcher Button -->
        <button @click="open = !open" @click.away="open = false" type="button"
            class="flex items-center gap-3 px-3.5 py-2 rounded-xl bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 shadow-lg backdrop-blur-md transition-all duration-200 focus:outline-none group">
            
            <!-- Airline Logo / Badge -->
            <div class="w-7 h-7 rounded-lg bg-slate-950 border border-slate-700 flex items-center justify-center text-xs font-black text-sky-400 font-mono overflow-hidden">
                @if ($activeRecord && $activeRecord->tenant->logo_path)
                    <img src="{{ asset('storage/' . $activeRecord->tenant->logo_path) }}" alt="{{ $activeRecord->tenant->name }}" class="w-full h-full object-contain p-0.5">
                @else
                    {{ strtoupper($activeRecord->tenant->icao ?? 'VA') }}
                @endif
            </div>

            <!-- Active Callsign & Airline Info -->
            <div class="text-left hidden sm:block">
                <div class="text-xs font-bold text-white group-hover:text-sky-400 transition-colors flex items-center gap-1.5">
                    <span>{{ $activeRecord->tenant->name ?? 'Select Airline' }}</span>
                    <span class="px-1.5 py-0.2 rounded bg-sky-500/20 text-sky-400 font-mono text-[10px]">{{ $activeRecord->callsign }}</span>
                </div>
                <div class="text-[10px] text-slate-400 font-mono">Active Pilot Session</div>
            </div>

            <!-- Chevron Down Icon -->
            <svg class="w-4 h-4 text-slate-400 group-hover:text-white transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute right-0 mt-2 w-72 rounded-2xl bg-slate-900/95 border border-slate-700 shadow-2xl backdrop-blur-xl z-50 overflow-hidden divide-y divide-slate-800" style="display: none;">
            
            <div class="p-3 bg-slate-950/60">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Switch Active Airline</p>
            </div>

            <div class="py-1 max-h-64 overflow-y-auto">
                @foreach ($userAirlines as $ua)
                    @php
                        $isSelected = ($activeRecord && $activeRecord->tenant_id == $ua->tenant_id);
                    @endphp
                    <form action="{{ route('session.switch-airline') }}" method="POST">
                        @csrf
                        <input type="hidden" name="tenant_id" value="{{ $ua->tenant_id }}">
                        <button type="submit" class="w-full text-left px-4 py-3 hover:bg-slate-800/80 flex items-center justify-between transition-colors group">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-center text-xs font-bold text-sky-400 font-mono">
                                    {{ strtoupper($ua->tenant->icao ?? 'VA') }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-white group-hover:text-sky-400 transition-colors">{{ $ua->tenant->name }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">Callsign: {{ $ua->callsign }}</p>
                                </div>
                            </div>

                            @if ($isSelected)
                                <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50"></span>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>

            <div class="p-2.5 bg-slate-950/60 text-center">
                <a href="{{ route('onboarding.select-airline') }}" class="text-[11px] font-semibold text-sky-400 hover:text-sky-300 transition-colors">
                    + Enroll in New Airline
                </a>
            </div>
        </div>
    </div>
@endif
