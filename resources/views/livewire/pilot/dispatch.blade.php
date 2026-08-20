<div class="dispatch-console max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 text-white font-sans" @if($is_loading_simbrief && !$showOfpView) wire:poll.3s="checkLiveSimbriefOfp" @endif>
    
    @if (session()->has('message'))
        <div class="p-4 bg-green-500/20 border border-green-500 text-green-100 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-red-500/20 border border-red-500 text-red-100 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if (session()->has('info'))
        <div class="p-4 bg-blue-500/20 border border-blue-500 text-blue-100 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ session('info') }}</span>
        </div>
    @endif

    @php
        $safeStr = function($val, $fallback = '') {
            if (is_null($val)) return $fallback;
            if (is_array($val)) {
                if (empty($val)) return $fallback;
                if (isset($val[0]) && is_string($val[0])) return implode("\n", $val);
                if (isset($val['metar']) && is_string($val['metar'])) return $val['metar'];
                if (isset($val['taf']) && is_string($val['taf'])) return $val['taf'];
                $flat = [];
                array_walk_recursive($val, function($a) use (&$flat) {
                    if (is_scalar($a)) $flat[] = (string)$a;
                });
                return !empty($flat) ? implode(' ', $flat) : $fallback;
            }
            if (is_scalar($val)) {
                $s = trim((string)$val);
                return $s !== '' ? $s : $fallback;
            }
            return $fallback;
        };

        $safeNum = function($val, $fallback = 0) {
            if (is_null($val)) return $fallback;
            if (is_numeric($val)) return (float)$val;
            if (is_array($val)) {
                $first = reset($val);
                return is_numeric($first) ? (float)$first : $fallback;
            }
            return $fallback;
        };

        $sbData = $booking->simbrief_data ?? [];
        
        // Origin / Destination
        $headerDep = $safeStr($sbData['origin']['icao_code'] ?? ($sbData['general']['origin'] ?? ($booking->route?->departure_icao ?? 'EGLL')));
        $headerArr = $safeStr($sbData['destination']['icao_code'] ?? ($sbData['general']['destination'] ?? ($booking->route?->arrival_icao ?? 'LFPG')));
        
        // Distance
        $headerDist = (int) $safeNum($sbData['general']['route_distance'] ?? ($sbData['general']['air_distance'] ?? ($booking->route?->distance ?? 374)));
        
        // Callsign / Flight Number
        $headerCallsign = strtoupper($safeStr($sbData['params']['callsign'] ?? ($sbData['atc']['callsign'] ?? ($sbData['general']['callsign'] ?? ($callsign ?? 'FL101')))));
        
        // Time Parsing Functions
        $formatTime = function($val) {
            if (empty($val) || is_array($val)) return null;
            $str = trim((string)$val);
            if (is_numeric($str)) {
                $num = (int)$str;
                if ($num > 1000000) {
                    return date('H:i', $num) . 'z';
                }
                if (strlen($str) === 4) {
                    return substr($str, 0, 2) . ':' . substr($str, 2, 2) . 'z';
                }
            }
            if (preg_match('/(\d{2}:\d{2})/', $str, $matches)) {
                return $matches[1] . 'z';
            }
            return $str;
        };

        $formatEte = function($val) {
            if (empty($val) || is_array($val)) return null;
            if (is_numeric($val)) {
                $s = (int)$val;
                if ($s > 0) {
                    return sprintf('%02d:%02d', floor($s / 3600), floor(($s % 3600) / 60));
                }
            }
            $str = trim((string)$val);
            if (preg_match('/(\d{1,2}:\d{2})/', $str, $matches)) {
                return $matches[1];
            }
            return $str;
        };

        // ETD (Estimated Time of Departure)
        $rawEtd = $sbData['times']['est_out'] 
            ?? ($sbData['times']['sched_out'] 
            ?? ($sbData['times']['est_off'] 
            ?? ($sbData['times']['sched_off'] 
            ?? ($sbData['times']['orig_etd'] 
            ?? ($sbData['general']['etd'] 
            ?? ($sbData['general']['std'] 
            ?? ($departure_time ?: date('H:i'))))))));
        $headerEtd = $formatTime($rawEtd) ?? ($departure_time ? $departure_time . 'z' : date('H:i') . 'z');

        // ETE (Estimated Time Enroute)
        $rawEte = $sbData['times']['est_time_enroute'] 
            ?? ($sbData['times']['sched_time_enroute'] 
            ?? ($sbData['general']['est_time_enroute'] 
            ?? ($sbData['times']['est_block'] 
            ?? ($booking->route?->flight_time ? ($booking->route->flight_time * 60) : 5400))));
        $headerEte = $formatEte($rawEte) ?? '01:30';

        // ETA (Estimated Time of Arrival)
        $rawEta = $sbData['times']['est_in'] 
            ?? ($sbData['times']['sched_in'] 
            ?? ($sbData['times']['est_on'] 
            ?? ($sbData['times']['sched_on'] 
            ?? ($sbData['times']['dest_eta'] 
            ?? ($sbData['general']['eta'] 
            ?? ($sbData['general']['sta'] ?? null))))));
        $headerEta = $formatTime($rawEta);
        if (!$headerEta) {
            $cleanEtd = str_replace('z', '', $headerEtd);
            $eteParts = explode(':', $headerEte);
            $eteMinutes = ((int)($eteParts[0] ?? 1) * 60) + (int)($eteParts[1] ?? 30);
            $headerEta = date('H:i', strtotime($cleanEtd . ' +' . $eteMinutes . ' minutes')) . 'z';
        }
        
        // Operator
        $headerOperator = $safeStr($sbData['general']['icao_airline'] 
            ?? ($sbData['general']['airline'] 
            ?? ($booking->route?->operator 
            ?? ($booking->tenant?->name ?? 'V-Air Ops Airline'))));
            
        // Airframe / Registration
        $headerReg = $safeStr($sbData['aircraft']['reg'] 
            ?? ($sbData['general']['registration']
            ?? ($selectedAirframe ? $selectedAirframe->registration : ($booking->airframe?->registration ?? 'HB-AYE'))));
        $headerType = $safeStr($sbData['aircraft']['icao_code'] 
            ?? ($sbData['aircraft']['icaocode']
            ?? ($sbData['general']['aircraft_type']
            ?? ($selectedAirframe ? $selectedAirframe->aircraftType->code : ($booking->airframe?->aircraftType->code ?? ($booking->route?->aircraftTypes?->first()?->code ?? 'A320'))))));

        // OFP Layout format
        $headerOfpLayout = strtoupper($safeStr($sbData['general']['ofp_layout'] ?? ($sbData['params']['planformat'] ?? ($ofp_format ?: ($resolvedFormat ?? 'LIDO')))));
    @endphp

    @if($is_loading_simbrief && !$showOfpView)
        <!-- FULL SCREEN SIMBRIEF GENERATION LOADING PAGE -->
        <div class="bg-[#12161F] border border-blue-500/30 rounded-2xl p-8 sm:p-12 shadow-2xl space-y-8 text-center my-6 relative overflow-hidden">
            <!-- Background Glow & Radar Pulse -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Radar Scanner Pulse -->
            <div class="relative w-36 h-36 mx-auto flex items-center justify-center">
                <div class="absolute inset-0 rounded-full bg-blue-500/15 animate-ping"></div>
                <div class="absolute inset-2 rounded-full border-2 border-dashed border-blue-400/40 animate-spin" style="animation-duration: 8s;"></div>
                <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-blue-700 via-indigo-700 to-purple-800 border-2 border-blue-400 flex items-center justify-center text-4xl shadow-2xl shadow-blue-500/50">
                    ✈️
                </div>
            </div>

            <!-- Header & Guidance -->
            <div class="space-y-3 max-w-xl mx-auto">
                <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center justify-center gap-3">
                    Generating & Syncing SimBrief OFP...
                </h2>
                <p class="text-sm text-gray-300 leading-relaxed">
                    SimBrief has been opened in a new tab with your pre-filled options. Click <strong class="text-tenant-accent font-bold">Generate Flight</strong> on SimBrief. V-Air Ops is waiting to fetch your official flight plan.
                </p>
            </div>

            <!-- Active Flight Parameters Card -->
            <div class="inline-flex flex-wrap items-center justify-center gap-4 bg-black/50 border border-white/10 px-6 py-3.5 rounded-2xl text-xs font-mono text-gray-300 shadow-inner">
                <div class="flex items-center gap-1.5">
                    <span class="text-gray-500 uppercase font-bold text-[10px]">Flight:</span>
                    <strong class="text-white text-sm font-sans">{{ $headerDep }} ➔ {{ $headerArr }}</strong>
                </div>
                <span class="text-gray-600">|</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-gray-500 uppercase font-bold text-[10px]">Callsign:</span>
                    <strong class="text-tenant-accent font-mono">{{ strtoupper($headerCallsign) }}</strong>
                </div>
                <span class="text-gray-600">|</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-gray-500 uppercase font-bold text-[10px]">Airframe:</span>
                    <strong class="text-white">{{ $headerReg }} ({{ $headerType }})</strong>
                </div>
                <span class="text-gray-600">|</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-gray-500 uppercase font-bold text-[10px]">SimBrief ID:</span>
                    <strong class="text-blue-400 font-mono">{{ $simbrief_username ?: 'Not configured' }}</strong>
                </div>
            </div>

            <!-- Auto-Polling Live Status Box -->
            <div class="max-w-md mx-auto p-5 bg-[#181D29] border border-blue-500/30 rounded-xl space-y-3 shadow-lg">
                <div class="flex items-center justify-between text-xs text-gray-300">
                    <span class="flex items-center gap-2 text-blue-300 font-bold">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-400 animate-pulse"></span>
                        Auto-checking SimBrief API every 3s...
                    </span>
                    <span class="text-gray-400 font-mono text-[11px]">Validating Callsign & Route</span>
                </div>
                <div class="w-full bg-gray-800 h-2 rounded-full overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 via-indigo-400 to-purple-500 h-full w-full animate-pulse"></div>
                </div>
                <p class="text-[11px] text-gray-400 text-left">
                    🔒 <strong class="text-gray-200">Strict Match Verification:</strong> V-Air Ops ensures older SimBrief OFPs won't be matched unless origin (<span class="text-white font-mono">{{ $booking->route->departure_icao }}</span>), destination (<span class="text-white font-mono">{{ $booking->route->arrival_icao }}</span>), and callsign (<span class="text-white font-mono">{{ strtoupper($callsign) }}</span>) strictly match!
                </p>
            </div>

            <!-- Action Controls -->
            <div class="flex flex-wrap items-center justify-center gap-4 text-xs pt-2">
                <button wire:click="fetchLiveSimbriefOfp" class="px-6 py-3 bg-tenant-accent hover:opacity-90 text-white font-extrabold rounded-xl shadow-lg transition flex items-center gap-2">
                    📥 Force Fetch OFP Now
                </button>

                <a href="{!! $simbriefPopupUrl !!}" target="_blank" class="px-6 py-3 bg-[#1C212E] hover:bg-[#283042] border border-blue-400/40 text-white font-bold rounded-xl shadow transition flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Re-open SimBrief Generator Tab
                </a>

                <button wire:click="cancelLoadingState" class="px-6 py-3 bg-red-500/20 hover:bg-red-500/30 text-red-300 font-bold rounded-xl transition border border-red-500/30">
                    ✏️ Cancel & Edit Parameters
                </button>
            </div>
        </div>
    @else
        <!-- Top Flight Header -->
        <div class="rounded-2xl p-6 shadow-xl space-y-4 border" style="background-color: var(--tenant-card-bg, #12161F); border-color: var(--tenant-input-border, rgba(255,255,255,0.12)); color: var(--tenant-card-text, #ffffff);">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl sm:text-3xl font-extrabold" style="color: var(--tenant-card-text, #ffffff);">
                        {{ $headerDep }} <span class="opacity-50 font-normal">→</span> {{ $headerArr }}
                    </h1>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-black/30 text-slate-200 border border-white/10 font-mono">
                        {{ $headerDist }} nm
                    </span>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-black/40 text-tenant-accent border border-tenant-accent/40 font-mono tracking-wide">
                        {{ $headerCallsign }}
                    </span>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-black/40 text-amber-300 border border-amber-500/30 font-mono flex items-center gap-1.5 shadow-sm">
                        ✈️ {{ $headerReg }} ({{ $headerType }})
                    </span>
                    @if($showOfpView || $booking->status === 'dispatched')
                        <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Dispatched & Active
                        </span>
                    @else
                        <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-400 border border-amber-500/30">
                            Pending Dispatch
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-3 text-xs">
                    <button wire:click="cancelBooking" class="px-3.5 py-2 bg-red-500/20 border border-red-500/40 rounded-xl text-red-300 hover:text-white hover:bg-red-500/40 font-semibold transition flex items-center gap-1">
                        Cancel Booking
                    </button>
                    <a href="https://www.flightradar24.com/data/flights/{{ strtolower($headerCallsign) }}" target="_blank" class="px-3.5 py-2 bg-black/30 border border-white/15 rounded-xl text-slate-200 hover:text-white hover:bg-black/50 font-semibold transition">
                        Flight Radar 24
                    </a>
                    <button wire:click="$toggle('showRouteDetails')" class="px-3 py-2 text-slate-300 hover:text-white transition flex items-center gap-1 font-medium">
                        Route details {{ $showRouteDetails ? '∧' : '∨' }}
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-6 text-sm pt-3 border-t" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                <div><span style="color: var(--tenant-card-muted, #94a3b8);" class="uppercase text-xs font-bold mr-1.5">ETD</span> <span class="font-bold font-mono text-emerald-400">{{ $headerEtd }}</span></div>
                <div><span style="color: var(--tenant-card-muted, #94a3b8);" class="uppercase text-xs font-bold mr-1.5">ETA</span> <span class="font-bold font-mono text-sky-400">{{ $headerEta }}</span></div>
                <div><span style="color: var(--tenant-card-muted, #94a3b8);" class="uppercase text-xs font-bold mr-1.5">ETE</span> <span class="font-bold font-mono" style="color: var(--tenant-card-text, #ffffff);">{{ $headerEte }}</span></div>
                <div><span style="color: var(--tenant-card-muted, #94a3b8);" class="uppercase text-xs font-bold mr-1.5">Aircraft</span> <span class="font-bold font-mono text-amber-300">{{ $headerReg }} ({{ $headerType }})</span></div>
                <div><span style="color: var(--tenant-card-muted, #94a3b8);" class="uppercase text-xs font-bold mr-1.5">OFP Layout</span> <span class="font-bold font-mono text-tenant-accent">{{ $headerOfpLayout }}</span></div>
                <div><span style="color: var(--tenant-card-muted, #94a3b8);" class="uppercase text-xs font-bold mr-1.5">Operator</span> <span class="font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $headerOperator }}</span></div>
            </div>

            @if($showRouteDetails)
                <div class="mt-4 p-4 bg-black/50 rounded-xl border border-white/10 space-y-2 text-xs" style="color: var(--tenant-card-text, #cbd5e1);">
                    <p><strong style="color: var(--tenant-card-text, #ffffff);">Departure Airport:</strong> {{ $headerDep }}</p>
                    <p><strong style="color: var(--tenant-card-text, #ffffff);">Arrival Airport:</strong> {{ $headerArr }}</p>
                    <p><strong style="color: var(--tenant-card-text, #ffffff);">Aircraft & Registration:</strong> {{ $headerType }} ({{ $headerReg }})</p>
                    <p><strong style="color: var(--tenant-card-text, #ffffff);">OFP Layout Format:</strong> {{ $headerOfpLayout }}</p>
                    <p><strong style="color: var(--tenant-card-text, #ffffff);">Planned Route:</strong> {{ $safeStr($sbData['general']['route'] ?? ($routing ?: 'Direct / SimBrief Auto-routing')) }}</p>
                </div>
            @endif
        </div>

        @if($showOfpView && (isset($booking->simbrief_data['weights']) || isset($booking->simbrief_data['fuel'])))
            <!-- DISPATCHED FLIGHT / OFP PRESENTATION VIEW -->
            @php $ofp = $booking->simbrief_data; @endphp

            <div x-data="{ currentTab: 'summary' }" class="rounded-2xl p-6 shadow-2xl space-y-6 border" style="background-color: var(--tenant-card-bg, #12161F); border-color: var(--tenant-input-border, rgba(255,255,255,0.12)); color: var(--tenant-card-text, #ffffff);">
                <div class="flex items-center justify-between border-b pb-4 flex-wrap gap-4" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    <div>
                        <h2 class="text-xl font-extrabold flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                            📄 Operational Flight Plan (OFP)
                            @if(!empty($ofp['is_simbrief_live']))
                                <span class="text-xs bg-sky-500/20 text-sky-300 border border-sky-500/40 px-2.5 py-0.5 rounded-full font-bold">SimBrief Live</span>
                            @else
                                <span class="text-xs bg-black/30 text-slate-300 border border-white/10 px-2.5 py-0.5 rounded-full font-medium">Custom OFP</span>
                            @endif
                        </h2>
                        <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">OFP Release for {{ $safeStr($ofp['general']['callsign'] ?? $callsign) }} · Format: <strong>{{ $headerOfpLayout }}</strong> · Aircraft: <strong>{{ $headerReg }} ({{ $headerType }})</strong></p>
                    </div>

                    <div class="flex gap-3 text-xs flex-wrap">
                        <button wire:click="editDispatch" class="px-4 py-2 bg-black/30 hover:bg-black/50 border border-white/15 rounded-xl font-semibold transition" style="color: var(--tenant-card-text, #ffffff);">
                            ✏️ Edit Parameters
                        </button>
                        <a href="{!! $simbriefPopupUrl !!}" target="_blank" class="px-4 py-2 bg-[#1C212E] hover:bg-[#283042] border border-sky-400/40 text-sky-200 rounded-xl transition flex items-center gap-1.5 font-bold shadow-sm">
                            <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            Open SimBrief Options
                        </a>
                    </div>
                </div>

                <!-- Instant Alpine.js Tab Navigation -->
                <div class="flex items-center gap-2 border-b pb-3 text-xs font-semibold overflow-x-auto" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    <button type="button" 
                            @click="currentTab = 'summary'" 
                            class="px-4 py-2.5 rounded-xl transition flex items-center gap-2 text-xs font-bold cursor-pointer"
                            :class="currentTab === 'summary' ? 'bg-tenant-accent text-white shadow-lg' : 'bg-black/25 text-slate-300 hover:text-white hover:bg-black/40 border border-white/10'">
                        📊 Flight Summary
                    </button>
                    <button type="button" 
                            @click="currentTab = 'full_ofp'" 
                            class="px-4 py-2.5 rounded-xl transition flex items-center gap-2 text-xs font-bold cursor-pointer"
                            :class="currentTab === 'full_ofp' ? 'bg-tenant-accent text-white shadow-lg' : 'bg-black/25 text-slate-300 hover:text-white hover:bg-black/40 border border-white/10'">
                        📄 Full SimBrief OFP
                    </button>
                    <button type="button" 
                            @click="currentTab = 'navlog'" 
                            class="px-4 py-2.5 rounded-xl transition flex items-center gap-2 text-xs font-bold cursor-pointer"
                            :class="currentTab === 'navlog' ? 'bg-tenant-accent text-white shadow-lg' : 'bg-black/25 text-slate-300 hover:text-white hover:bg-black/40 border border-white/10'">
                        🧭 Navlog & Fixes
                    </button>
                    <button type="button" 
                            @click="currentTab = 'weather'" 
                            class="px-4 py-2.5 rounded-xl transition flex items-center gap-2 text-xs font-bold cursor-pointer"
                            :class="currentTab === 'weather' ? 'bg-tenant-accent text-white shadow-lg' : 'bg-black/25 text-slate-300 hover:text-white hover:bg-black/40 border border-white/10'">
                        🌤️ Weather & Briefing
                    </button>
                </div>

                <!-- TAB 1: Flight Summary & Cards -->
                <div x-show="currentTab === 'summary'" class="space-y-6">
                    <!-- Weight & Fuel Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-black/35 border border-white/10 p-5 rounded-2xl space-y-3 shadow-inner">
                            <span class="text-xs font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">Estimated Fuel</span>
                            <div class="text-2xl font-black text-tenant-accent font-mono">{{ number_format($safeNum($ofp['fuel']['plan_ramp'] ?? 7100)) }} <span class="text-sm font-normal opacity-70">kg</span></div>
                            <div class="text-xs space-y-1.5 pt-3 border-t border-white/10">
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Trip Burn:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ number_format($safeNum($ofp['fuel']['enroute_burn'] ?? 4200)) }} kg</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Contingency:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ number_format($safeNum($ofp['fuel']['contingency'] ?? 350)) }} kg</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Alternate:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ number_format($safeNum($ofp['fuel']['alternate'] ?? 1100)) }} kg</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Reserve:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ number_format($safeNum($ofp['fuel']['reserve'] ?? 1200)) }} kg</span></div>
                            </div>
                        </div>

                        <div class="bg-black/35 border border-white/10 p-5 rounded-2xl space-y-3 shadow-inner">
                            <span class="text-xs font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">Weights Summary</span>
                            <div class="text-2xl font-black font-mono" style="color: var(--tenant-card-text, #ffffff);">{{ number_format($safeNum($ofp['weights']['est_zfw'] ?? 61626)) }} <span class="text-sm font-normal opacity-70">kg ZFW</span></div>
                            <div class="text-xs space-y-1.5 pt-3 border-t border-white/10">
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">TOW (Takeoff):</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ number_format($safeNum($ofp['weights']['est_tow'] ?? 68476)) }} kg</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">LDW (Landing):</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ number_format($safeNum($ofp['weights']['est_ldw'] ?? 64276)) }} kg</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Payload:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ number_format($safeNum($ofp['weights']['payload'] ?? 16560)) }} kg</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Pax / Bags:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $safeStr($ofp['weights']['pax_count'] ?? 170) }} pax / {{ $safeStr($ofp['weights']['bag_count'] ?? 152) }} bags</span></div>
                            </div>
                        </div>

                        <div class="bg-black/35 border border-white/10 p-5 rounded-2xl space-y-3 shadow-inner">
                            <span class="text-xs font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">Flight Profile</span>
                            @php
                                $rawOfpAlt = (int) $safeNum($ofp['general']['initial_altitude'] ?? ($ofp['general']['cruise_altitude'] ?? ($ofp['params']['fl'] ?? 36000)));
                                $ofpFl = ($rawOfpAlt >= 1000) ? 'FL' . floor($rawOfpAlt / 100) : 'FL' . $rawOfpAlt;
                                $ofpFt = ($rawOfpAlt >= 1000) ? $rawOfpAlt : $rawOfpAlt * 100;
                            @endphp
                            <div class="text-2xl font-black text-sky-400 font-mono">{{ $ofpFl }} <span class="text-xs font-normal opacity-70">({{ number_format($ofpFt) }} ft)</span></div>
                            <div class="text-xs space-y-1.5 pt-3 border-t border-white/10">
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Cost Index:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $safeStr($ofp['general']['cost_index'] ?? 4) }}</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Est. ETE:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $headerEte }}</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Distance:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $safeStr($ofp['general']['air_distance'] ?? 374) }} nm</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Network:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $network }}</span></div>
                            </div>
                        </div>

                        <div class="bg-black/35 border border-white/10 p-5 rounded-2xl space-y-3 shadow-inner">
                            <span class="text-xs font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">Assigned Aircraft</span>
                            <div class="text-xl font-black text-amber-400 font-mono flex items-center gap-2">
                                <span>✈️</span> {{ $headerReg }} <span class="text-xs font-bold text-white px-2 py-0.5 rounded bg-amber-500/20 border border-amber-500/30">{{ $headerType }}</span>
                            </div>
                            <div class="text-xs space-y-1.5 pt-3 border-t border-white/10">
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Airframe:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $selectedAirframe ? $selectedAirframe->registration . ' (' . ($selectedAirframe->aircraftType->name ?? $selectedAirframe->aircraftType->code) . ')' : $headerReg . ' (' . $headerType . ')' }}</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">OFP Format:</span> <span class="font-mono font-bold text-tenant-accent">{{ $headerOfpLayout }}</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Alt 1:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $safeStr($ofp['general']['alternate'] ?? ($alternate_1 ?: 'EDDW')) }}</span></div>
                                <div class="flex justify-between"><span style="color: var(--tenant-card-muted, #94a3b8);">Alt 2:</span> <span class="font-mono font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $safeStr($ofp['general']['alternate2'] ?? ($alternate_2 ?: 'EDHL')) }}</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Routing Banner -->
                    <div class="bg-black/45 p-5 rounded-2xl border border-white/10 space-y-2 shadow-inner">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold uppercase tracking-wider" style="color: var(--tenant-card-text, #ffffff);">ATC Routing String</span>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $safeStr($ofp['general']['route'] ?? $routing) }}')" class="text-tenant-accent font-bold hover:underline">Copy Route</button>
                        </div>
                        <div class="p-4 bg-[#090C12] rounded-xl font-mono text-sm text-emerald-400 tracking-wide break-words border border-white/10 shadow-inner">
                            {{ $safeStr($ofp['general']['route'] ?? ($routing ?: 'DIRECT')) }}
                        </div>
                    </div>

                    <!-- Weather / METAR Briefing Preview -->
                    @if(isset($ofp['weather']))
                        <div class="bg-black/35 p-5 rounded-2xl border border-white/10 space-y-3 shadow-inner">
                            <span class="text-xs font-bold uppercase tracking-wider block" style="color: var(--tenant-card-text, #ffffff);">🌤️ Weather & METAR Briefing</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs font-mono">
                                <div class="p-4 bg-[#090C12] rounded-xl border border-white/10 space-y-1.5 shadow-inner">
                                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Departure ({{ $booking->route->departure_icao }})</span>
                                    <span class="text-slate-200 font-semibold">{{ $safeStr($ofp['weather']['orig_metar'] ?? '', 'No METAR available') }}</span>
                                </div>
                                <div class="p-4 bg-[#090C12] rounded-xl border border-white/10 space-y-1.5 shadow-inner">
                                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Arrival ({{ $booking->route->arrival_icao }})</span>
                                    <span class="text-slate-200 font-semibold">{{ $safeStr($ofp['weather']['dest_metar'] ?? '', 'No METAR available') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- TAB 2: Full Official SimBrief OFP Document Viewer -->
                <div x-show="currentTab === 'full_ofp'" x-cloak class="space-y-4">
                    <div class="flex items-center justify-between bg-black/40 p-4 rounded-xl border border-white/10 text-xs">
                        <div class="flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="font-bold">Official Operational Flight Plan &bull; <strong>{{ $headerOfpLayout }}</strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="const el = document.getElementById('ofp-full-text'); navigator.clipboard.writeText(el.innerText || el.textContent); alert('OFP copied to clipboard!');" class="px-3.5 py-1.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-lg transition">
                                📋 Copy OFP Text
                            </button>
                            <button type="button" onclick="window.print()" class="px-3.5 py-1.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-lg transition">
                                🖨️ Print OFP
                            </button>
                            <a href="{!! $simbriefPopupUrl !!}" target="_blank" class="px-3.5 py-1.5 bg-tenant-accent hover:opacity-90 text-white font-bold rounded-lg transition">
                                ↗ Open in SimBrief
                            </a>
                        </div>
                    </div>

                    <!-- Monospaced OFP Viewer -->
                    <div id="ofp-full-text" class="bg-[#090C12] border border-white/15 rounded-2xl p-6 font-mono text-xs text-slate-200 overflow-x-auto max-h-[700px] leading-relaxed shadow-2xl select-text">
                        @if(!empty($ofp['text']['plan_html']) && is_string($ofp['text']['plan_html']))
                            <div class="ofp-html-content font-mono whitespace-pre-wrap">
                                {!! $ofp['text']['plan_html'] !!}
                            </div>
                        @elseif(!empty($ofp['text']['plan_text']))
                            <pre class="whitespace-pre font-mono text-emerald-300">{{ $safeStr($ofp['text']['plan_text']) }}</pre>
                        @else
                            <!-- Structured LIDO Template Fallback -->
                            <pre class="whitespace-pre font-mono text-slate-200">
================================================================================
                           OPERATIONAL FLIGHT PLAN
================================================================================
RELEASE: {{ strtoupper($callsign) }} / {{ date('dMY') }}   AIRFRAME: {{ $selectedAirframe ? $selectedAirframe->registration : 'HB-AYE' }} ({{ $selectedAirframe ? $selectedAirframe->aircraftType->code : 'A320' }})
ORIGIN:  {{ $booking->route->departure_icao }}               DESTINATION: {{ $booking->route->arrival_icao }}
ALTN:    {{ $safeStr($ofp['general']['alternate'] ?? $alternate_1) }} (ALTN2: {{ $safeStr($ofp['general']['alternate2'] ?? $alternate_2) }})
CRUISE:  {{ $ofpFl ?? 'FL360' }}   COST INDEX: {{ $safeStr($ofp['general']['cost_index'] ?? 4) }}   EST ETE: {{ $safeStr($ofp['general']['est_time_enroute'] ?? '01:30') }}

--------------------------------------------------------------------------------
ATC ROUTE
--------------------------------------------------------------------------------
{{ $booking->route->departure_icao }}/{{ $ofpFl ?? 'FL360' }} {{ $safeStr($ofp['general']['route'] ?? ($routing ?: 'DIRECT')) }} {{ $booking->route->arrival_icao }}

--------------------------------------------------------------------------------
FUEL PLANNING (KGS)
--------------------------------------------------------------------------------
TRIP FUEL:        {{ str_pad(number_format($safeNum($ofp['fuel']['enroute_burn'] ?? 4200)), 8, ' ', STR_PAD_LEFT) }} KG    TIME: {{ $safeStr($ofp['general']['est_time_enroute'] ?? '01:30') }}
CONTINGENCY 5%:   {{ str_pad(number_format($safeNum($ofp['fuel']['contingency'] ?? 350)), 8, ' ', STR_PAD_LEFT) }} KG    TIME: 00:15
ALTERNATE ({{ $safeStr($ofp['general']['alternate'] ?? 'EDDW') }}): {{ str_pad(number_format($safeNum($ofp['fuel']['alternate'] ?? 1100)), 8, ' ', STR_PAD_LEFT) }} KG    TIME: 00:30
FINAL RESERVE:    {{ str_pad(number_format($safeNum($ofp['fuel']['reserve'] ?? 1200)), 8, ' ', STR_PAD_LEFT) }} KG    TIME: 00:30
--------------------------------------------------------------------------------
MIN REQUIRED:     {{ str_pad(number_format($safeNum($ofp['fuel']['enroute_burn'] ?? 4200) + $safeNum($ofp['fuel']['contingency'] ?? 350) + $safeNum($ofp['fuel']['alternate'] ?? 1100) + $safeNum($ofp['fuel']['reserve'] ?? 1200)), 8, ' ', STR_PAD_LEFT) }} KG
BLOCK / RAMP:     {{ str_pad(number_format($safeNum($ofp['fuel']['plan_ramp'] ?? 7100)), 8, ' ', STR_PAD_LEFT) }} KG

--------------------------------------------------------------------------------
WEIGHT SUMMARY (KGS)
--------------------------------------------------------------------------------
EST ZFW:          {{ str_pad(number_format($safeNum($ofp['weights']['est_zfw'] ?? 61626)), 8, ' ', STR_PAD_LEFT) }} KG    MAX ZFW: 62,500 KG
EST TOW:          {{ str_pad(number_format($safeNum($ofp['weights']['est_tow'] ?? 68476)), 8, ' ', STR_PAD_LEFT) }} KG    MAX TOW: 79,000 KG
EST LDW:          {{ str_pad(number_format($safeNum($ofp['weights']['est_ldw'] ?? 64276)), 8, ' ', STR_PAD_LEFT) }} KG    MAX LDW: 66,000 KG
PAYLOAD:          {{ str_pad(number_format($safeNum($ofp['weights']['payload'] ?? 16560)), 8, ' ', STR_PAD_LEFT) }} KG    PAX: {{ $safeStr($ofp['weights']['pax_count'] ?? 170) }}  BAGS: {{ $safeStr($ofp['weights']['bag_count'] ?? 152) }}

--------------------------------------------------------------------------------
WEATHER BRIEFING
--------------------------------------------------------------------------------
DEP METAR: {{ $safeStr($ofp['weather']['orig_metar'] ?? 'N/A') }}
ARR METAR: {{ $safeStr($ofp['weather']['dest_metar'] ?? 'N/A') }}
ALT METAR: {{ $safeStr($ofp['weather']['altn_metar'] ?? 'N/A') }}
================================================================================
                                END OF OFP
================================================================================
                            </pre>
                        @endif
                    </div>
                </div>

                <!-- TAB 3: Navlog Fixes Table -->
                <div x-show="currentTab === 'navlog'" x-cloak class="space-y-4">
                    <div class="bg-black/35 p-5 rounded-2xl border border-white/10 shadow-inner">
                        <h3 class="text-sm font-bold uppercase tracking-wider mb-4" style="color: var(--tenant-card-text, #ffffff);">🧭 Navigation Log & Enroute Waypoints</h3>
                        
                        @php
                            $rawFixes = $ofp['navlog']['fix'] ?? [];
                            $fixes = is_array($rawFixes) ? $rawFixes : [];
                            if (isset($fixes['ident'])) { $fixes = [$fixes]; }
                        @endphp

                        @if(!empty($fixes))
                            <div class="overflow-x-auto">
                                <table class="w-full text-left font-mono text-xs text-slate-200">
                                    <thead class="bg-black/40 text-slate-400 uppercase text-[10px] border-b border-white/10">
                                        <tr>
                                            <th class="py-2.5 px-3 font-bold">Fix / Ident</th>
                                            <th class="py-2.5 px-3 font-bold">Airway</th>
                                            <th class="py-2.5 px-3 font-bold">Freq</th>
                                            <th class="py-2.5 px-3 font-bold">Track (M)</th>
                                            <th class="py-2.5 px-3 font-bold">Dist (NM)</th>
                                            <th class="py-2.5 px-3 font-bold">Altitude</th>
                                            <th class="py-2.5 px-3 font-bold">Wind / Temp</th>
                                            <th class="py-2.5 px-3 font-bold">Fuel Rem (KG)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/5">
                                        @foreach($fixes as $fix)
                                            @php
                                                if (!is_array($fix)) continue;
                                                $fIdent = $safeStr($fix['ident'] ?? '', '--');
                                                $fAirway = $safeStr($fix['via_airway'] ?? ($fix['airway'] ?? 'DCT'), 'DCT');
                                                $fFreq = $safeStr($fix['frequency'] ?? '', '--');
                                                $fTrack = str_pad((string)(int)$safeNum($fix['track_mag'] ?? ($fix['heading_mag'] ?? 0)), 3, '0', STR_PAD_LEFT);
                                                $fDist = (string)$safeNum($fix['distance'] ?? ($fix['stage_distance'] ?? 0));
                                                $fAltFeet = $safeNum($fix['altitude_feet'] ?? 0);
                                                $fAlt = $fAltFeet > 0 ? 'FL' . floor($fAltFeet / 100) : ($ofpFl ?? 'FL360');
                                                $fWindDir = str_pad((string)(int)$safeNum($fix['wind_dir'] ?? 0), 3, '0', STR_PAD_LEFT);
                                                $fWindSpd = (string)$safeNum($fix['wind_spd'] ?? 0);
                                                $fOat = (string)$safeNum($fix['oat'] ?? -45);
                                                $fFuel = $safeNum($fix['fuel_plan_onboard'] ?? ($fix['fuel_rem'] ?? 0));
                                            @endphp
                                            <tr class="hover:bg-white/5 transition">
                                                <td class="py-2.5 px-3 font-bold text-white">{{ $fIdent }}</td>
                                                <td class="py-2.5 px-3 text-sky-400 font-bold">{{ $fAirway }}</td>
                                                <td class="py-2.5 px-3 text-slate-300">{{ $fFreq }}</td>
                                                <td class="py-2.5 px-3 text-amber-300 font-bold">{{ $fTrack }}°</td>
                                                <td class="py-2.5 px-3 text-slate-300">{{ $fDist }} nm</td>
                                                <td class="py-2.5 px-3 text-emerald-400 font-bold">{{ $fAlt }}</td>
                                                <td class="py-2.5 px-3 text-slate-300">{{ $fWindDir }}/{{ $fWindSpd }}kt ({{ $fOat }}°C)</td>
                                                <td class="py-2.5 px-3 text-tenant-accent font-bold">{{ number_format($fFuel) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-6 text-center text-slate-400 space-y-3 font-sans">
                                <p class="text-sm">ATC Route Waypoint Sequence:</p>
                                <div class="p-4 bg-[#090C12] rounded-xl font-mono text-sm text-emerald-400 break-words border border-white/10 shadow-inner">
                                    {{ $safeStr($ofp['general']['route'] ?? ($routing ?: 'DIRECT')) }}
                                </div>
                                <p class="text-xs opacity-70">Waypoints automatically parsed from ATC route string for cruise {{ $ofpFl ?? 'FL360' }}.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- TAB 4: Detailed Weather Briefing -->
                <div x-show="currentTab === 'weather'" x-cloak class="space-y-4 font-mono text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Departure -->
                        <div class="bg-black/35 p-5 rounded-2xl border border-white/10 space-y-3 shadow-inner">
                            <div class="flex items-center justify-between border-b border-white/10 pb-2">
                                <span class="font-bold font-sans text-sm" style="color: var(--tenant-card-text, #ffffff);">🛫 Departure Airport ({{ $booking->route->departure_icao }})</span>
                                <span class="text-sky-400 uppercase text-[10px] font-sans font-bold">METAR & TAF</span>
                            </div>
                            <div class="p-4 bg-[#090C12] rounded-xl border border-white/10 space-y-1.5 shadow-inner">
                                <span class="text-slate-400 text-[10px] uppercase block font-bold">Current METAR</span>
                                <p class="text-slate-200 font-semibold break-words">{{ $safeStr($ofp['weather']['orig_metar'] ?? '', 'No METAR available') }}</p>
                            </div>
                            @if(!empty($ofp['weather']['orig_taf']))
                                <div class="p-4 bg-[#090C12] rounded-xl border border-white/10 space-y-1.5 shadow-inner">
                                    <span class="text-slate-400 text-[10px] uppercase block font-bold">Terminal Aerodrome Forecast (TAF)</span>
                                    <p class="text-slate-200 break-words">{{ $safeStr($ofp['weather']['orig_taf']) }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Arrival -->
                        <div class="bg-black/35 p-5 rounded-2xl border border-white/10 space-y-3 shadow-inner">
                            <div class="flex items-center justify-between border-b border-white/10 pb-2">
                                <span class="font-bold font-sans text-sm" style="color: var(--tenant-card-text, #ffffff);">🛬 Arrival Airport ({{ $booking->route->arrival_icao }})</span>
                                <span class="text-sky-400 uppercase text-[10px] font-sans font-bold">METAR & TAF</span>
                            </div>
                            <div class="p-4 bg-[#090C12] rounded-xl border border-white/10 space-y-1.5 shadow-inner">
                                <span class="text-slate-400 text-[10px] uppercase block font-bold">Current METAR</span>
                                <p class="text-slate-200 font-semibold break-words">{{ $safeStr($ofp['weather']['dest_metar'] ?? '', 'No METAR available') }}</p>
                            </div>
                            @if(!empty($ofp['weather']['dest_taf']))
                                <div class="p-4 bg-[#090C12] rounded-xl border border-white/10 space-y-1.5 shadow-inner">
                                    <span class="text-slate-400 text-[10px] uppercase block font-bold">Terminal Aerodrome Forecast (TAF)</span>
                                    <p class="text-slate-200 break-words">{{ $safeStr($ofp['weather']['dest_taf']) }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Alternates -->
                        <div class="bg-black/35 p-5 rounded-2xl border border-white/10 space-y-3 md:col-span-2 shadow-inner">
                            <div class="flex items-center justify-between border-b border-white/10 pb-2">
                                <span class="font-bold font-sans text-sm" style="color: var(--tenant-card-text, #ffffff);">🔄 Alternate Airports</span>
                                <span class="text-amber-400 uppercase text-[10px] font-sans font-bold">Weather Briefing</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="p-4 bg-[#090C12] rounded-xl border border-white/10 space-y-1.5 shadow-inner">
                                    <span class="text-slate-400 text-[10px] uppercase block font-bold">Primary Alternate ({{ $safeStr($ofp['general']['alternate'] ?? $alternate_1) }})</span>
                                    <p class="text-slate-200 font-semibold break-words">{{ $safeStr($ofp['weather']['altn_metar'] ?? '', 'No METAR available') }}</p>
                                </div>
                                @if(!empty($ofp['general']['alternate2']) || !empty($alternate_2))
                                    <div class="p-4 bg-[#090C12] rounded-xl border border-white/10 space-y-1.5 shadow-inner">
                                        <span class="text-slate-400 text-[10px] uppercase block font-bold">Secondary Alternate ({{ $safeStr($ofp['general']['alternate2'] ?? $alternate_2) }})</span>
                                        <p class="text-slate-200 font-semibold break-words">{{ $safeStr($ofp['weather']['altn2_metar'] ?? '', 'No secondary alternate METAR reported') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- vPilot ACARS Connection Prompt -->
                <div class="bg-gradient-to-r from-blue-900/40 via-indigo-900/40 to-purple-900/40 p-5 rounded-2xl border border-blue-500/30 flex flex-wrap items-center justify-between gap-4 shadow-lg">
                    <div class="space-y-1">
                        <h4 class="font-extrabold text-white text-base flex items-center gap-2">
                            📡 Ready to Fly with vPilot ACARS
                        </h4>
                        <p class="text-xs text-slate-300">Launch your flight simulator and start vPilot ACARS. Your active flight will automatically synchronize.</p>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="cancelBooking" class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-300 text-xs font-bold rounded-xl transition border border-red-500/30">
                            Cancel Flight
                        </button>
                    </div>
                </div>
            </div>
        @else
            <!-- READY TO DISPATCH Main Banner -->
            <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-800 rounded-xl p-6 shadow-2xl space-y-5 border border-blue-400/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-300 animate-pulse"></div>
                        <h2 class="text-lg font-bold text-white tracking-wide uppercase">READY TO DISPATCH</h2>
                    </div>
                    <span class="text-xs font-medium text-blue-200">{{ date('d M Y') }}</span>
                </div>

                <!-- Parameters Pill Summary Bar -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 text-xs bg-black/40 p-4 rounded-xl border border-white/10">
                    <div>
                        <span class="text-blue-200 block text-[10px] uppercase font-semibold">Aircraft</span>
                        <span class="font-bold text-white">{{ $selectedAirframe ? $selectedAirframe->registration : 'HB-AYE' }} · {{ $selectedAirframe ? $selectedAirframe->aircraftType->code : 'A20N' }}</span>
                        <button wire:click="$set('showSectionAircraft', true)" class="text-blue-300 hover:text-white underline block text-[10px] mt-0.5">change</button>
                    </div>

                    <div>
                        <span class="text-blue-200 block text-[10px] uppercase font-semibold">Callsign</span>
                        <span class="font-bold text-white">{{ strtoupper($callsign) }} · Flight {{ strtoupper($flight_number) }}</span>
                    </div>

                    <div>
                        <span class="text-blue-200 block text-[10px] uppercase font-semibold">Departure</span>
                        <span class="font-bold text-white">{{ $departure_date }} {{ $departure_time }}</span>
                        <button wire:click="$set('showSectionSchedule', true)" class="text-blue-300 hover:text-white underline block text-[10px] mt-0.5">adjust</button>
                    </div>

                    <div>
                        <span class="text-blue-200 block text-[10px] uppercase font-semibold">Network</span>
                        <span class="font-bold text-white">{{ $network }} · {{ $copilot_user_id ? 'Co-pilot' : 'Solo' }}</span>
                    </div>

                    <div>
                        <span class="text-blue-200 block text-[10px] uppercase font-semibold">OFP Format</span>
                        <span class="font-bold text-amber-300 uppercase font-mono">{{ $ofp_format ?: ($resolvedFormat ?? 'LIDO') }}</span>
                        <button wire:click="$set('showSectionAircraft', true)" class="text-blue-300 hover:text-white underline block text-[10px] mt-0.5">change</button>
                    </div>

                    <div>
                        <span class="text-blue-200 block text-[10px] uppercase font-semibold">Payload</span>
                        <span class="font-bold text-white">{{ number_format($estimated_zfw) }} kg ZFW</span>
                    </div>

                    <div>
                        <span class="text-blue-200 block text-[10px] uppercase font-semibold">SimBrief</span>
                        <span class="font-bold text-white">{{ $dispatch_via_simbrief ? 'On' : 'Off' }} · {{ $num_alternates }} altn</span>
                        <button wire:click="$set('showSectionAlternates', true)" class="text-blue-300 hover:text-white underline block text-[10px] mt-0.5">adjust</button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-4 pt-2 flex-wrap sm:flex-nowrap">
                    <a href="{!! $simbriefPopupUrl !!}" target="_blank" wire:click="createBooking" class="w-full sm:w-auto flex-1 bg-white text-blue-900 font-extrabold px-6 py-3 rounded-lg shadow-lg hover:bg-gray-100 transition text-center text-sm flex items-center justify-center gap-2">
                        <span>🚀 Confirm Dispatch & Open SimBrief</span>
                    </a>
                    
                    <a href="{!! $simbriefPopupUrl !!}" target="_blank" class="w-full sm:w-auto bg-[#1C212E] hover:bg-[#283042] border border-blue-400/40 text-white font-bold px-6 py-3 rounded-lg shadow transition flex items-center justify-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        SimBrief Options Page
                    </a>
                </div>
            </div>

            <!-- SECTION 1: Aircraft & SimBrief Live Sync -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-lg transition-all">
                <div wire:click="$toggle('showSectionAircraft')" class="px-6 py-4 bg-[#181D29] flex items-center justify-between cursor-pointer border-b border-white/5 hover:bg-[#1E2433]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center font-bold">
                            ✈
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-base">Aircraft & SimBrief Sync</h3>
                            <p class="text-xs text-gray-400">{{ $selectedAirframe ? $selectedAirframe->registration : 'HB-AYE' }} · {{ strtoupper($callsign) }} · {{ strtoupper($flight_number) }} · Layout {{ $ofp_format ?: ($resolvedFormat ?? 'LIDO') }} · SimBrief {{ $dispatch_via_simbrief ? 'on' : 'off' }}</p>
                        </div>
                    </div>
                    <span class="text-gray-400">{{ $showSectionAircraft ? '∧' : '∨' }}</span>
                </div>

                @if($showSectionAircraft)
                    <div class="p-6 space-y-4">
                        <!-- SimBrief Pilot Account Integration -->
                        <div class="p-4 bg-blue-950/40 border border-blue-500/30 rounded-xl space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-blue-300 uppercase tracking-wider">SimBrief Integration & Live Sync</span>
                                <a href="{!! $simbriefPopupUrl !!}" target="_blank" class="text-xs text-blue-400 hover:underline">Open Pre-filled SimBrief Generator</a>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <x-input type="text" wire:model="simbrief_username" class="w-full bg-[#1C212E] border-blue-500/40 text-white text-sm" placeholder="Enter your SimBrief Username or Pilot ID (e.g. 123456)" />
                                </div>
                                <button wire:click="fetchLiveSimbriefOfp" class="px-4 py-2 bg-tenant-accent hover:opacity-90 text-white text-xs font-bold rounded-lg transition flex items-center justify-center gap-2">
                                    📥 Fetch Live OFP from SimBrief
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-400">Enter your Navigraph Alias or SimBrief Pilot ID above to pull your exact live generated OFP directly into V-Ops.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <x-label for="airframe_id" value="{{ __('Aircraft') }}" class="text-white font-medium" />
                                    <div class="flex gap-3 text-xs">
                                        <a href="{{ route('fleet') }}" target="_blank" class="text-tenant-accent hover:underline">Aircraft Picker</a>
                                        <a href="https://www.flightradar24.com" target="_blank" class="text-gray-400 hover:text-white underline">FR24 Lookup</a>
                                    </div>
                                </div>
                                <select id="airframe_id" wire:model.live="airframe_id" class="w-full bg-[#1C212E] border border-gray-700 text-white rounded-lg p-2.5 text-sm focus:border-tenant-accent focus:ring-tenant-accent">
                                    <option value="">Select Airframe...</option>
                                    @foreach($fleet as $airframe)
                                        <option value="{{ $airframe->id }}">{{ $airframe->registration }} | {{ $airframe->aircraftType->name ?? $airframe->aircraftType->code }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-label for="ofp_format" value="{{ __('SimBrief OFP Format / Layout') }}" class="text-white font-medium mb-1" />
                                <select id="ofp_format" wire:model.live="ofp_format" class="w-full bg-[#1C212E] border border-gray-700 text-white rounded-lg p-2.5 text-sm focus:border-tenant-accent focus:ring-tenant-accent font-semibold">
                                    @foreach($availableOfpFormats as $fmtKey => $fmtName)
                                        <option value="{{ $fmtKey }}">{{ $fmtName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-label for="callsign" value="{{ __('Callsign') }}" class="text-white font-medium mb-1" />
                                <x-input id="callsign" type="text" wire:model="callsign" class="w-full bg-[#1C212E] border-gray-700 text-white uppercase text-sm" placeholder="e.g. EZS64HZ" />
                            </div>
                            <div>
                                <x-label for="flight_number" value="{{ __('Flight Number') }}" class="text-white font-medium mb-1" />
                                <x-input id="flight_number" type="text" wire:model="flight_number" class="w-full bg-[#1C212E] border-gray-700 text-white uppercase text-sm" placeholder="e.g. DS1181" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-black/30 rounded-lg border border-white/5">
                            <div>
                                <span class="text-sm font-semibold text-white block">Dispatch via SimBrief</span>
                                <span class="text-xs text-gray-400">Generate an OFP before creating booking</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="dispatch_via_simbrief" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-tenant-accent"></div>
                            </label>
                        </div>
                    </div>
                @endif
            </div>

            <!-- SECTION 2: Schedule & Route -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-lg transition-all">
                <div wire:click="$toggle('showSectionSchedule')" class="px-6 py-4 bg-[#181D29] flex items-center justify-between cursor-pointer border-b border-white/5 hover:bg-[#1E2433]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold">
                            📅
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-base">Schedule & Route</h3>
                            <p class="text-xs text-gray-400">{{ $departure_date }} {{ $departure_time }} · FL {{ $flight_level ?: 'AUTO' }} · CI {{ $cost_index }}</p>
                        </div>
                    </div>
                    <span class="text-gray-400">{{ $showSectionSchedule ? '∧' : '∨' }}</span>
                </div>

                @if($showSectionSchedule)
                    <div class="p-6 space-y-4">
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <x-label value="{{ __('Your Departure Time UTC +0') }}" class="text-white font-medium" />
                                <div class="flex gap-3 text-xs text-tenant-accent">
                                    <button type="button" wire:click="$set('departure_time', '13:25')" class="hover:underline">Route Time</button>
                                    <button type="button" wire:click="$set('departure_time', '{{ date('H:i') }}')" class="hover:underline">Time Setter</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-input type="date" wire:model="departure_date" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" />
                                <x-input type="time" wire:model="departure_time" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" />
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <x-label for="routing" value="{{ __('Routing') }}" class="text-white font-medium" />
                                <button type="button" onclick="navigator.clipboard.writeText('{{ $routing }}')" class="text-xs text-gray-400 hover:text-white underline">Copy</button>
                            </div>
                            <textarea id="routing" wire:model="routing" rows="3" class="w-full bg-[#1C212E] border-gray-700 text-white rounded-lg p-2.5 text-sm font-mono" placeholder="Enter route string or leave empty for SimBrief auto-routing"></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-label for="flight_level" value="{{ __('Flight Level') }}" class="text-white font-medium mb-1" />
                                <x-input id="flight_level" type="text" wire:model="flight_level" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" placeholder="SimBrief will generate one if left empty" />
                            </div>
                            <div>
                                <x-label for="cost_index" value="{{ __('Cost Index') }}" class="text-white font-medium mb-1" />
                                <div class="relative">
                                    <x-input id="cost_index" type="number" wire:model="cost_index" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm pr-10" />
                                    <span class="absolute right-3 top-2.5 text-gray-500">🔒</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- SECTION 3: Alternates -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-lg transition-all">
                <div wire:click="$toggle('showSectionAlternates')" class="px-6 py-4 bg-[#181D29] flex items-center justify-between cursor-pointer border-b border-white/5 hover:bg-[#1E2433]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-yellow-500/20 text-yellow-400 flex items-center justify-center font-bold">
                            🛬
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-base">Alternates</h3>
                            <p class="text-xs text-gray-400">Alternate 1: {{ $alternate_1 ?: 'None' }} · Alternate 2: {{ $alternate_2 ?: 'None' }}</p>
                        </div>
                    </div>
                    <span class="text-gray-400">{{ $showSectionAlternates ? '∧' : '∨' }}</span>
                </div>

                @if($showSectionAlternates)
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between p-3 bg-black/30 rounded-lg border border-white/5">
                            <div>
                                <span class="text-sm font-semibold text-white block">Auto-find Alternates</span>
                                <span class="text-xs text-gray-400">Automatically suggest nearby alternate airports</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="auto_find_alternates" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-tenant-accent"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <x-label for="num_alternates" value="{{ __('Number of Alternates') }}" class="text-white font-medium mb-1" />
                                <select id="num_alternates" wire:model.live="num_alternates" class="w-full bg-[#1C212E] border-gray-700 text-white rounded-lg p-2.5 text-sm">
                                    <option value="1">1 Alternate</option>
                                    <option value="2">2 Alternates</option>
                                    <option value="3">3 Alternates</option>
                                </select>
                            </div>
                            <div>
                                <x-label for="alternate_1" value="{{ __('Alternate 1 (ICAO)') }}" class="text-white font-medium mb-1" />
                                <x-input id="alternate_1" type="text" wire:model="alternate_1" class="w-full bg-[#1C212E] border-gray-700 text-white uppercase text-sm" placeholder="e.g. EDDH" />
                            </div>
                            @if($num_alternates >= 2)
                                <div>
                                    <x-label for="alternate_2" value="{{ __('Alternate 2 (ICAO)') }}" class="text-white font-medium mb-1" />
                                    <x-input id="alternate_2" type="text" wire:model="alternate_2" class="w-full bg-[#1C212E] border-gray-700 text-white uppercase text-sm" placeholder="e.g. EDDW" />
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- SECTION 4: Payload -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-lg transition-all">
                <div wire:click="$toggle('showSectionPayload')" class="px-6 py-4 bg-[#181D29] flex items-center justify-between cursor-pointer border-b border-white/5 hover:bg-[#1E2433]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-green-500/20 text-green-400 flex items-center justify-center font-bold">
                            👥
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-base">Payload</h3>
                            <p class="text-xs text-gray-400">{{ $passengers }} pax · {{ $hold_bags }} bags · ZFW {{ number_format($estimated_zfw) }} kg</p>
                        </div>
                    </div>
                    <span class="text-gray-400">{{ $showSectionPayload ? '∧' : '∨' }}</span>
                </div>

                @if($showSectionPayload)
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <x-label for="passengers" value="Passengers (max {{ $passengers_max }})" class="text-white font-medium" />
                                    <button type="button" wire:click="generatePassengers" class="text-xs text-tenant-accent hover:underline flex items-center gap-1">↻ Generate</button>
                                </div>
                                <x-input id="passengers" type="number" min="0" max="{{ $passengers_max }}" wire:model.live="passengers" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" />
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <x-label for="hold_bags" value="Passengers with Hold Luggage (max {{ $hold_bags_max }})" class="text-white font-medium" />
                                    <button type="button" wire:click="generateHoldBags" class="text-xs text-tenant-accent hover:underline flex items-center gap-1">↻ Generate</button>
                                </div>
                                <x-input id="hold_bags" type="number" min="0" max="{{ $hold_bags_max }}" wire:model.live="hold_bags" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" />
                            </div>
                        </div>

                        <div class="pt-3 border-t border-white/5 flex items-center justify-between text-sm">
                            <span class="text-gray-400">Estimated Zero Fuel Weight (ZFW):</span>
                            <span class="font-bold text-white font-mono text-base">{{ number_format($estimated_zfw) }} kg</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- SECTION 5: Network & Co-pilot -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-lg transition-all">
                <div wire:click="$toggle('showSectionNetwork')" class="px-6 py-4 bg-[#181D29] flex items-center justify-between cursor-pointer border-b border-white/5 hover:bg-[#1E2433]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold">
                            📡
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-base">Network & Co-pilot</h3>
                            <p class="text-xs text-gray-400">{{ $network }} · {{ $copilot_user_id ? 'Shared Cockpit' : 'Solo' }}</p>
                        </div>
                    </div>
                    <span class="text-gray-400">{{ $showSectionNetwork ? '∧' : '∨' }}</span>
                </div>

                @if($showSectionNetwork)
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <x-label for="network" value="{{ __('Network') }}" class="text-white font-medium" />
                                    <div class="flex gap-2 text-xs">
                                        <a href="{{ route('profile.preferences') }}" class="text-tenant-accent hover:underline">Preferred Network</a>
                                    </div>
                                </div>
                                <select id="network" wire:model="network" class="w-full bg-[#1C212E] border-gray-700 text-white rounded-lg p-2.5 text-sm">
                                    <option value="Offline">Offline</option>
                                    <option value="VATSIM">VATSIM</option>
                                    <option value="IVAO">IVAO</option>
                                    <option value="POSCON">POSCON</option>
                                    <option value="PILOTEDGE">PilotEdge</option>
                                </select>
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <x-label for="copilot_user_id" value="{{ __('Shared Cockpit Co-Pilot') }}" class="text-white font-medium" />
                                </div>
                                <select id="copilot_user_id" wire:model="copilot_user_id" class="w-full bg-[#1C212E] border-gray-700 text-white rounded-lg p-2.5 text-sm">
                                    <option value="">None (Solo Flight)</option>
                                    @foreach($copilots as $pilot)
                                        <option value="{{ $pilot->id }}">{{ $pilot->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
