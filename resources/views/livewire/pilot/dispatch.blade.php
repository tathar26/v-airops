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
        $headerOfpLayout = strtoupper($safeStr(
            $sbData['params']['ofp_layout'] 
            ?? ($sbData['general']['ofp_layout'] 
            ?? ($sbData['params']['planformat'] 
            ?? ($ofp_format ?: ($resolvedFormat ?? 'LIDO'))))
        ));
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
                <div class="w-24 h-24 rounded-full bg-[#0F224A] border-2 border-[#21A19D] flex items-center justify-center text-4xl shadow-2xl shadow-blue-500/50">
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
                    <div class="bg-[#21A19D] h-full w-full animate-pulse"></div>
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
    @if($showOfpView && (isset($booking->simbrief_data['weights']) || isset($booking->simbrief_data['fuel'])))
        <!-- DISPATCHED FLIGHT / OFP DASHBOARD VIEW -->
        @php 
            $ofp = $booking->simbrief_data ?? [];
            $ofpAlt1 = $safeStr($ofp['general']['alternate'] ?? ($ofp['alternate'][0]['icao_code'] ?? ($ofp['alternate']['icao_code'] ?? ($alternate_1 ?: 'None'))));
            $ofpAlt2 = $safeStr($ofp['general']['alternate2'] ?? ($ofp['alternate'][1]['icao_code'] ?? ($alternate_2 ?: 'None')));

            $tlrTakeoffRwy = $ofp['tlr']['takeoff']['runway'] ?? [];
            if (isset($tlrTakeoffRwy[0])) $tlrTakeoffRwy = $tlrTakeoffRwy[0];

            $tlrLandingRwy = $ofp['tlr']['landing']['runway'] ?? [];
            if (isset($tlrLandingRwy[0])) $tlrLandingRwy = $tlrLandingRwy[0];
        @endphp

        <!-- TOP ROUTE MAP BANNER -->
        <div class="relative w-full rounded-2xl overflow-hidden border shadow-2xl" style="height: 360px; background-color: #090C12; border-color: var(--tenant-input-border, rgba(255,255,255,0.12));" x-data="dispatchRouteMap(@js($routeWaypoints))" wire:ignore>
            <div x-ref="mapContainer" class="w-full h-full"></div>
            <!-- Map Overlay Badges -->
            <div class="absolute top-4 left-4 z-[400] flex items-center gap-2">
                <span class="px-3.5 py-1.5 rounded-xl bg-black/75 backdrop-blur-md text-xs font-mono font-bold text-white border border-white/10 shadow-lg flex items-center gap-2">
                    <span class="text-tenant-accent">✈</span> {{ $headerDep }} ➔ {{ $headerArr }} <span class="text-gray-400">({{ $headerDist }} NM)</span>
                </span>
            </div>
            <div class="absolute top-4 right-4 z-[400] flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-bold backdrop-blur-md flex items-center gap-1.5 shadow-lg">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Dispatched & Active
                </span>
            </div>
        </div>

        <!-- FLIGHT EXPIRY NOTICE BAR -->
        <div class="rounded-xl px-5 py-3 text-xs sm:text-sm text-gray-300 border font-medium flex items-center gap-2 shadow-sm" style="background-color: var(--tenant-card-bg, #12161F); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
            <span>🕒</span>
            <span>Please start your flight before <strong class="text-white">{{ $flightExpiryString }}</strong>.</span>
        </div>

        <!-- 2-COLUMN MAIN CONTENT GRID -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- LEFT MAIN COLUMN (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- CARD 1: FLIGHT INFORMATION -->
                <div class="rounded-2xl p-6 shadow-xl space-y-6 border" style="background-color: var(--tenant-card-bg, #12161F); border-color: var(--tenant-input-border, rgba(255,255,255,0.12)); color: var(--tenant-card-text, #ffffff);">
                    <div class="flex items-center justify-between border-b pb-3" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        <h2 class="text-xs font-extrabold uppercase tracking-wider text-tenant-accent" style="color: var(--tenant-accent, #21A19D);">
                            FLIGHT INFORMATION
                        </h2>
                    </div>

                    <!-- Row 1: Callsign | Flight Number + Booking | Route Number -->
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">CALLSIGN | FLIGHT NUMBER</div>
                            <div class="text-2xl sm:text-3xl font-black text-white font-mono tracking-tight mt-0.5">
                                {{ $headerCallsign }} | {{ $booking->route->flight_number ?? $headerCallsign }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">BOOKING | ROUTE NUMBER</div>
                            <div class="text-lg sm:text-xl font-black text-white font-mono tracking-tight mt-0.5">
                                #{{ $booking->id }} | #{{ $booking->route_id ?? $booking->route->id ?? '1' }}
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Departure ➔ Arrival with Center Airplane Graphic -->
                    <div class="grid grid-cols-12 items-center gap-2 pt-2">
                        <div class="col-span-4 space-y-0.5">
                            <div class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">DEPARTURE</div>
                            <div class="text-xl sm:text-2xl font-black text-white font-mono">
                                {{ $headerDep }} / {{ $booking->route?->departureAirport?->iata_code ?? '---' }}
                            </div>
                            <div class="text-xs text-gray-300 font-medium truncate">
                                {{ $booking->route?->departureAirport?->name ?? $booking->route?->departureAirport?->city ?? $headerDep }}
                            </div>
                        </div>

                        <div class="col-span-4 flex items-center justify-center px-2">
                            <div class="w-full flex items-center">
                                <div class="h-[1px] bg-white/20 flex-1"></div>
                                <div class="px-3 text-gray-400 transform rotate-90 text-lg">✈</div>
                                <div class="h-[1px] bg-white/20 flex-1"></div>
                            </div>
                        </div>

                        <div class="col-span-4 space-y-0.5 text-right">
                            <div class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">ARRIVAL</div>
                            <div class="text-xl sm:text-2xl font-black text-white font-mono">
                                {{ $headerArr }} / {{ $booking->route?->arrivalAirport?->iata_code ?? '---' }}
                            </div>
                            <div class="text-xs text-gray-300 font-medium truncate">
                                {{ $booking->route?->arrivalAirport?->name ?? $booking->route?->arrivalAirport?->city ?? $headerArr }}
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: STD, DURATION, DISTANCE, STA -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t text-xs" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">STD</span>
                            <span class="text-base font-bold text-white font-mono">{{ $booking->route->std ?? $headerEtd }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">DURATION</span>
                            <span class="text-base font-bold text-white font-mono">{{ $headerEte }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">DISTANCE</span>
                            <span class="text-base font-bold text-white font-mono">{{ $headerDist }} NM</span>
                        </div>
                        <div class="sm:text-right">
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">STA</span>
                            <span class="text-base font-bold text-white font-mono">{{ $booking->route->sta ?? $headerEta }}</span>
                        </div>
                    </div>

                    <!-- Row 4: ETD & ETA -->
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">ETD</span>
                            <span class="text-base font-bold text-emerald-400 font-mono">{{ $headerEtd }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">ETA</span>
                            <span class="text-base font-bold text-sky-400 font-mono">{{ $headerEta }}</span>
                        </div>
                    </div>

                    <!-- Row 5: METARs & Quick Links -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t text-xs" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        <!-- Departure METAR -->
                        <div class="space-y-3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">METAR</span>
                                <p class="font-mono text-xs text-gray-200 bg-black/40 p-3.5 rounded-xl border border-white/5 break-words min-h-[56px] leading-relaxed">
                                    {{ $safeStr($ofp['weather']['orig_metar'] ?? '', 'No METAR available for ' . $headerDep) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('airports') }}?search={{ $headerDep }}" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-gray-200 border border-white/10 font-medium text-xs transition">
                                    Airport Information
                                </a>
                                <a href="https://metar-taf.com/{{ $headerDep }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-gray-200 border border-white/10 font-medium text-xs transition">
                                    METAR-TAF.com
                                </a>
                            </div>
                        </div>

                        <!-- Arrival METAR -->
                        <div class="space-y-3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider block text-right md:text-left" style="color: var(--tenant-card-muted, #94a3b8);">METAR</span>
                                <p class="font-mono text-xs text-gray-200 bg-black/40 p-3.5 rounded-xl border border-white/5 break-words min-h-[56px] leading-relaxed">
                                    {{ $safeStr($ofp['weather']['dest_metar'] ?? '', 'No METAR available for ' . $headerArr) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap md:justify-end">
                                <a href="{{ route('airports') }}?search={{ $headerArr }}" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-gray-200 border border-white/10 font-medium text-xs transition">
                                    Airport Information
                                </a>
                                <a href="https://metar-taf.com/{{ $headerArr }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-gray-200 border border-white/10 font-medium text-xs transition">
                                    METAR-TAF.com
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: PILOT INFORMATION -->
                <div class="rounded-2xl p-6 shadow-xl space-y-6 border" style="background-color: var(--tenant-card-bg, #12161F); border-color: var(--tenant-input-border, rgba(255,255,255,0.12)); color: var(--tenant-card-text, #ffffff);">
                    <div class="flex items-center justify-between border-b pb-3" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        <h2 class="text-xs font-extrabold uppercase tracking-wider text-tenant-accent" style="color: var(--tenant-accent, #21A19D);">
                            PILOT INFORMATION
                        </h2>
                    </div>

                    <!-- Row 1: Aircraft, Cost Index, Flight Level, Passengers, Luggage, Freight -->
                    <div class="grid grid-cols-2 sm:grid-cols-6 gap-4 text-xs">
                        <div class="sm:col-span-1 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">AIRCRAFT</span>
                            <div class="text-base font-black text-white font-mono">{{ $headerReg }}</div>
                            <div class="text-[11px] text-gray-400">{{ $booking->airline->icao ?? 'VA' }} {{ $headerType }} | {{ $headerType }}</div>
                            <div class="pt-2 flex items-center gap-1.5 flex-wrap">
                                <a href="{{ route('fleet') }}" class="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-gray-300 border border-white/10 text-[11px] font-medium transition">
                                    Aircraft Information
                                </a>
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">COST INDEX</span>
                            <div class="text-base font-black text-white font-mono mt-1">{{ $safeStr($ofp['general']['cost_index'] ?? 4) }}</div>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">FLIGHT LEVEL</span>
                            <div class="text-base font-black text-white font-mono mt-1">
                                @php
                                    $rawOfpAlt = (int)$safeNum($ofp['general']['initial_altitude'] ?? ($ofp['general']['cruise_altitude'] ?? 38000));
                                    $flDisplay = ($rawOfpAlt >= 1000) ? floor($rawOfpAlt / 100) : $rawOfpAlt;
                                @endphp
                                {{ $flDisplay }}
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">PASSENGERS</span>
                            <div class="text-base font-black text-white font-mono mt-1">{{ $safeStr($ofp['weights']['pax_count'] ?? ($booking->passengers ?: 169)) }}</div>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">LUGGAGE</span>
                            <div class="text-base font-black text-white font-mono mt-1">
                                {{ number_format($safeNum($ofp['weights']['bag_weight'] ?? ($ofp['weights']['cargo'] ?? 3603))) }} kg
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">FREIGHT</span>
                            <div class="text-base font-black text-white font-mono mt-1">
                                {{ number_format($safeNum($ofp['weights']['freight'] ?? ($booking->cargo ?: 0))) }} kg
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Route Type & Network -->
                    <div class="grid grid-cols-2 sm:grid-cols-6 gap-4 pt-4 border-t text-xs" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        <div class="sm:col-span-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">ROUTE TYPE</span>
                            <div class="text-sm font-bold text-white mt-1">{{ ucfirst($booking->route->flight_type ?? 'Scheduled') }}</div>
                        </div>
                        <div class="sm:col-span-3 text-right">
                            <span class="text-[10px] font-bold uppercase tracking-wider block" style="color: var(--tenant-card-muted, #94a3b8);">NETWORK</span>
                            <div class="text-sm font-black font-mono text-tenant-accent mt-1">{{ strtoupper($network) }}</div>
                        </div>
                    </div>

                    <!-- Row 3: Route -->
                    <div class="pt-4 border-t text-xs space-y-2" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">ROUTE</span>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $safeStr($ofp['general']['route'] ?? ($routing ?: 'DIRECT')) }}'); alert('Route copied to clipboard!');" class="text-tenant-accent font-bold hover:underline text-[11px]">
                                Copy Route
                            </button>
                        </div>
                        <div class="p-3.5 bg-black/40 rounded-xl font-mono text-xs text-white font-semibold tracking-wide break-words border border-white/5 shadow-inner">
                            {{ $safeStr($ofp['general']['route'] ?? ($routing ?: 'DIRECT')) }}
                        </div>
                        @if(!empty($ofp['general']['remarks']) || !empty($booking->route->remarks))
                            <div class="pt-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">REMARKS</span>
                                <p class="text-xs text-gray-300 font-mono">{{ $safeStr($ofp['general']['remarks'] ?? $booking->route->remarks) }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- CARD 3: SIMBRIEF OFP SUMMARY ACCORDION -->
                <div x-data="{ openSummary: true }" class="rounded-2xl border overflow-hidden shadow-xl" style="background-color: var(--tenant-card-bg, #12161F); border-color: var(--tenant-input-border, rgba(255,255,255,0.12));">
                    <div @click="openSummary = !openSummary" class="p-5 flex items-center justify-between cursor-pointer border-b hover:bg-white/5 transition" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                            <span>SIMBRIEF OFP SUMMARY</span>
                        </h2>
                        <span class="text-gray-400 transform transition-transform text-xs" :class="openSummary ? 'rotate-180' : ''">∨</span>
                    </div>

                    <div x-show="openSummary" class="p-6 space-y-6">
                        <!-- Dispatcher Remarks Notification -->
                        <div class="p-4 rounded-xl bg-sky-950/40 border border-sky-500/20 text-xs flex items-start gap-3">
                            <span class="text-sky-400 text-base">ℹ️</span>
                            <div>
                                <span class="font-bold text-white block text-xs">Dispatcher Remarks</span>
                                <span class="text-sky-200/90 font-mono">{{ $safeStr($ofp['general']['dispatcher_remarks'] ?? ($ofp['remarks'] ?? 'NONE')) }}</span>
                            </div>
                        </div>

                        <!-- Runway Sub-card -->
                        <div class="p-4 rounded-xl bg-black/30 border border-white/10 space-y-3">
                            <h3 class="text-xs font-bold text-gray-300 uppercase tracking-wider">Runway</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs font-mono">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Departure</span>
                                    <span class="text-white font-bold">RWY {{ $safeStr($ofp['origin']['plan_rwy'] ?? '17R') }} {{ !empty($ofp['tlr']['takeoff']['conditions']['surface_condition']) ? ucfirst($ofp['tlr']['takeoff']['conditions']['surface_condition']) : 'Dry' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Headwind | Crosswind</span>
                                    <span class="text-white font-bold">{{ $safeStr($tlrTakeoffRwy['headwind_component'] ?? '4') }} | {{ $safeStr($tlrTakeoffRwy['crosswind_component'] ?? '4') }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Arrival</span>
                                    <span class="text-white font-bold">RWY {{ $safeStr($ofp['destination']['plan_rwy'] ?? '26L') }} {{ !empty($ofp['tlr']['landing']['conditions']['surface_condition']) ? ucfirst($ofp['tlr']['landing']['conditions']['surface_condition']) : 'Wet' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Headwind | Crosswind</span>
                                    <span class="text-white font-bold">{{ $safeStr($tlrLandingRwy['headwind_component'] ?? '8') }} | {{ $safeStr($tlrLandingRwy['crosswind_component'] ?? '2') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- TLR Sub-card -->
                        <div class="p-4 rounded-xl bg-black/30 border border-white/10 space-y-3">
                            <h3 class="text-xs font-bold text-gray-300 uppercase tracking-wider">TLR</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-8 gap-3 text-xs font-mono">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Flaps</span>
                                    <span class="text-white font-bold">{{ $safeStr($tlrTakeoffRwy['flap_setting'] ?? '1') }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">De-Rate</span>
                                    <span class="text-white font-bold">{{ $safeStr($tlrTakeoffRwy['thrust_setting'] ?? 'FLEX') }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Temp</span>
                                    <span class="text-white font-bold">{{ $safeStr($tlrTakeoffRwy['flex_temperature'] ?? '67') }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">V1</span>
                                    <span class="text-white font-bold">{{ $safeStr($tlrTakeoffRwy['speeds_v1'] ?? '156') }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">VR</span>
                                    <span class="text-white font-bold">{{ $safeStr($tlrTakeoffRwy['speeds_vr'] ?? '156') }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">V2</span>
                                    <span class="text-white font-bold">{{ $safeStr($tlrTakeoffRwy['speeds_v2'] ?? '157') }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Bleeds</span>
                                    <span class="text-white font-bold">{{ $safeStr($tlrTakeoffRwy['bleed_setting'] ?? 'ON') }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Anti-ice</span>
                                    <span class="text-white font-bold">{{ $safeStr($tlrTakeoffRwy['anti_ice_setting'] ?? 'OFF') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Sub-card -->
                        <div class="p-4 rounded-xl bg-black/30 border border-white/10 space-y-3">
                            <h3 class="text-xs font-bold text-gray-300 uppercase tracking-wider">Performance</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs font-mono">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Cruise Profile</span>
                                    <span class="text-white font-bold">CI {{ $safeStr($ofp['general']['cost_index'] ?? 4) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Climb Profile</span>
                                    <span class="text-white font-bold">{{ $safeStr($ofp['general']['climb_profile'] ?? '250/300/78') }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Descent Profile</span>
                                    <span class="text-white font-bold">{{ $safeStr($ofp['general']['descent_profile'] ?? '78/300/250') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fuel Sub-card (12 items) -->
                        <div class="p-4 rounded-xl bg-black/30 border border-white/10 space-y-3">
                            <h3 class="text-xs font-bold text-gray-300 uppercase tracking-wider">Fuel</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-6 gap-y-4 gap-x-2 text-xs font-mono">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Tank Capacity</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['tank_capacity'] ?? 19117)) }} kg</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Block</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['plan_ramp'] ?? 5329)) }} kg</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Minimum Takeoff</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['min_takeoff'] ?? ($safeNum($ofp['fuel']['plan_takeoff'] ?? 5156)))) }} kg</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Planned Takeoff</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['plan_takeoff'] ?? 5156)) }} kg</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Trip</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['enroute_burn'] ?? 2519)) }} kg</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Contingency</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['contingency'] ?? 181)) }} kg</span>
                                </div>

                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Alternate</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['alternate'] ?? ($ofp['alternate'][0]['burn'] ?? 1541))) }} kg</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Reserve</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['reserve'] ?? 915)) }} kg</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Taxi</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['taxi'] ?? 173)) }} kg</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">ETOPS</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['etops'] ?? 0)) }} kg</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Extra</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['extra'] ?? 0)) }} kg</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-400 block font-sans">Planned Landing</span>
                                    <span class="text-white font-bold">{{ number_format($safeNum($ofp['fuel']['plan_landing'] ?? 2637)) }} kg</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 4: SIMBRIEF OFP FULL TEXT ACCORDION -->
                <div x-data="{ openOfp: false }" class="rounded-2xl border overflow-hidden shadow-xl" style="background-color: var(--tenant-card-bg, #12161F); border-color: var(--tenant-input-border, rgba(255,255,255,0.12));">
                    <div @click="openOfp = !openOfp" class="p-5 flex items-center justify-between cursor-pointer border-b hover:bg-white/5 transition" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                            <span>SIMBRIEF OFP</span>
                        </h2>
                        <span class="text-gray-400 transform transition-transform text-xs" :class="openOfp ? 'rotate-180' : ''">∨</span>
                    </div>

                    <div x-show="openOfp" class="p-6 space-y-4">
                        <div class="flex items-center justify-between bg-black/40 p-3.5 rounded-xl border border-white/10 text-xs flex-wrap gap-3">
                            <div class="flex items-center gap-2 text-gray-300">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>Format: <strong class="text-white">{{ $headerOfpLayout }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="const el = document.getElementById('ofp-full-text'); navigator.clipboard.writeText(el.innerText || el.textContent); alert('OFP copied to clipboard!');" class="px-3.5 py-1.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-lg text-xs transition">
                                    📋 Copy OFP Text
                                </button>
                                <button type="button" onclick="window.print()" class="px-3.5 py-1.5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-lg text-xs transition">
                                    🖨️ Print
                                </button>
                                <a href="{!! $simbriefPopupUrl !!}" target="_blank" class="px-3.5 py-1.5 bg-tenant-accent hover:opacity-90 text-white font-bold rounded-lg text-xs transition">
                                    ↗ SimBrief
                                </a>
                            </div>
                        </div>

                        <div id="ofp-full-text" class="bg-[#090C12] border border-white/15 rounded-2xl p-5 font-mono text-xs text-slate-200 overflow-x-auto max-h-[700px] leading-relaxed shadow-inner select-text">
                            @if(!empty($ofp['text']['plan_html']) && is_string($ofp['text']['plan_html']))
                                <div class="ofp-html-content font-mono whitespace-pre-wrap">{!! $ofp['text']['plan_html'] !!}</div>
                            @elseif(!empty($ofp['text']['plan_text']))
                                <pre class="whitespace-pre font-mono text-slate-200">{{ $safeStr($ofp['text']['plan_text']) }}</pre>
                            @else
                                <pre class="whitespace-pre font-mono text-slate-200">
================================================================================
                           OPERATIONAL FLIGHT PLAN
================================================================================
RELEASE: {{ strtoupper($callsign) }} / {{ date('dMY') }}   AIRFRAME: {{ $selectedAirframe ? $selectedAirframe->registration : 'HB-AYE' }} ({{ $selectedAirframe ? $selectedAirframe->aircraftType->code : 'A320' }})
ORIGIN:  {{ $booking->route->departure_icao }}               DESTINATION: {{ $booking->route->arrival_icao }}
ALTN:    {{ $ofpAlt1 }} (ALTN2: {{ $ofpAlt2 }})
CRUISE:  {{ $ofpFl ?? 'FL360' }}   COST INDEX: {{ $safeStr($ofp['general']['cost_index'] ?? 4) }}   EST ETE: {{ $safeStr($ofp['general']['est_time_enroute'] ?? '01:30') }}

--------------------------------------------------------------------------------
ATC ROUTE
--------------------------------------------------------------------------------
{{ $booking->route->departure_icao }}/{{ $ofpFl ?? 'FL360' }} {{ $safeStr($ofp['general']['route'] ?? ($routing ?: 'DIRECT')) }} {{ $booking->route->arrival_icao }}
================================================================================
                                END OF OFP
================================================================================
                                </pre>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT SIDEBAR COLUMN (4 Cols) -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- CARD 1: BOOKING ACTIONS -->
                <div class="rounded-2xl p-5 border shadow-xl space-y-3" style="background-color: var(--tenant-card-bg, #12161F); border-color: var(--tenant-input-border, rgba(255,255,255,0.12));">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-tenant-accent mb-2" style="color: var(--tenant-accent, #21A19D);">
                        Booking Actions
                    </h3>

                    <div class="space-y-2">
                        <!-- 1. Change Booking Details -->
                        <button type="button" wire:click="editDispatch" class="w-full py-2.5 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-gray-200 font-semibold text-xs text-center transition">
                            Change Booking Details
                        </button>

                        <!-- 2. Send to VATSIM -->
                        <a href="{{ $vatsimPrefileUrl }}" target="_blank" class="w-full block py-2.5 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-gray-200 font-semibold text-xs text-center transition">
                            Send to VATSIM
                        </a>

                        <!-- 3. Send to IVAO -->
                        <a href="{{ $ivaoPrefileUrl }}" target="_blank" class="w-full block py-2.5 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-gray-200 font-semibold text-xs text-center transition">
                            Send to IVAO
                        </a>

                        <!-- 4. Send to POSCON -->
                        <a href="{{ $posconPrefileUrl }}" target="_blank" class="w-full block py-2.5 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-gray-200 font-semibold text-xs text-center transition">
                            Send to POSCON
                        </a>

                        <!-- 5. Generate / Re-generate SimBrief OFP -->
                        <button type="button" wire:click="dispatchSimbriefPopup" class="w-full py-2.5 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-gray-200 font-semibold text-xs text-center transition">
                            Generate SimBrief OFP
                        </button>

                        <!-- 6. Flightradar24 -->
                        <a href="https://www.flightradar24.com/data/flights/{{ strtolower($headerCallsign) }}" target="_blank" class="w-full block py-2.5 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-gray-200 font-semibold text-xs text-center transition">
                            Flightradar24
                        </a>

                        <!-- 7. AirNav Radar -->
                        <a href="https://www.airnavradar.com/flight/{{ strtolower($headerCallsign) }}" target="_blank" class="w-full block py-2.5 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-gray-200 font-semibold text-xs text-center transition">
                            AirNav Radar
                        </a>

                        <!-- 8. Make Additional Booking -->
                        <a href="{{ route('pilot.flights') }}" class="w-full block py-2.5 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-gray-200 font-semibold text-xs text-center transition">
                            Make Additional Booking
                        </a>

                        <!-- 9. Manual PIREP / File a Claim -->
                        <a href="{{ route('pilot.pireps.create') }}?booking_id={{ $booking->id }}" class="w-full block py-2.5 px-4 rounded-xl border border-amber-500/30 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 font-semibold text-xs text-center transition">
                            Manual PIREP / File a Claim
                        </a>

                        <!-- 10. Cancel Booking -->
                        <button type="button" wire:click="cancelBooking" wire:confirm="Are you sure you want to cancel this booking?" class="w-full py-2.5 px-4 rounded-xl border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-red-300 font-semibold text-xs text-center transition">
                            Cancel Booking
                        </button>

                        <!-- 11. Cancel & Rebook -->
                        <button type="button" wire:click="cancelAndRebook" wire:confirm="Are you sure you want to cancel this booking and re-select route options?" class="w-full py-2.5 px-4 rounded-xl border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 text-red-300 font-semibold text-xs text-center transition">
                            Cancel & Rebook
                        </button>
                    </div>
                </div>

                <!-- CARD 2: SIMBRIEF ACTIONS -->
                <div class="rounded-2xl p-5 border shadow-xl space-y-4" style="background-color: var(--tenant-card-bg, #12161F); border-color: var(--tenant-input-border, rgba(255,255,255,0.12));">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-tenant-accent" style="color: var(--tenant-accent, #21A19D);">
                        SimBrief Actions
                    </h3>

                    <div class="space-y-2">
                        <!-- Edit SimBrief OFP -->
                        <a href="{!! $simbriefPopupUrl !!}" target="_blank" class="w-full block py-2.5 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-gray-200 font-semibold text-xs text-center transition">
                            Edit SimBrief OFP
                        </a>

                        <!-- Open Navigraph Charts -->
                        <a href="https://charts.navigraph.com/flights/create?origin={{ $headerDep }}&destination={{ $headerArr }}" target="_blank" class="w-full block py-2.5 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-gray-200 font-semibold text-xs text-center transition">
                            Open Navigraph Charts
                        </a>

                        <!-- SimBrief Downloads Form -->
                        <div class="pt-3 border-t border-white/10">
                            <label class="text-[11px] font-bold text-gray-400 block mb-1.5 uppercase tracking-wider">SimBrief Downloads</label>
                            <select wire:model.live="selectedFmsFormat" class="w-full rounded-xl bg-black/40 border border-white/15 text-xs text-white p-2.5 focus:border-tenant-accent focus:ring-1 focus:ring-tenant-accent">
                                @forelse($availableFmsDownloads as $key => $fmt)
                                    <option value="{{ $key }}">{{ $fmt['name'] }}</option>
                                @empty
                                    <option value="mfs">MSFS 2020/2024 (.pln)</option>
                                    <option value="fenix">Fenix A320 XML</option>
                                    <option value="pmr">PMDG (.rte)</option>
                                    <option value="xp9">X-Plane 11/12 (.fms)</option>
                                    <option value="pdf">SimBrief PDF Briefing</option>
                                @endforelse
                            </select>
                            <button type="button" wire:click="downloadFmsFile" class="w-full mt-2.5 py-2.5 px-4 rounded-xl border border-tenant-accent/40 bg-tenant-accent/15 hover:bg-tenant-accent/25 text-tenant-accent font-bold text-xs flex items-center justify-center gap-2 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: COMPARE STATS -->
                <div class="rounded-2xl p-5 border shadow-xl space-y-4" style="background-color: var(--tenant-card-bg, #12161F); border-color: var(--tenant-input-border, rgba(255,255,255,0.12));">
                    <div class="text-center">
                        <h3 class="text-amber-400 font-bold text-sm tracking-wide">Compare</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Against {{ $routeComparisonStats['count'] }} other {{ Str::plural('flight', $routeComparisonStats['count']) }} between {{ $headerDep }} and {{ $headerArr }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-y-4 gap-x-3 text-xs pt-2 border-t border-white/10">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Landing Rate</span>
                            <span class="text-sm font-black text-white font-mono block mt-0.5">
                                {{ $routeComparisonStats['landing_rate'] ? $routeComparisonStats['landing_rate'] . ' FPM' : 'N/A' }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Fuel Used</span>
                            <span class="text-sm font-black text-white font-mono block mt-0.5">
                                {{ $routeComparisonStats['fuel_used'] ? number_format($routeComparisonStats['fuel_used']) . ' kg' : 'N/A' }}
                            </span>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Flight Time</span>
                            <span class="text-sm font-black text-white font-mono block mt-0.5">
                                {{ $routeComparisonStats['flight_time'] }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Points</span>
                            <span class="text-sm font-black text-white font-mono block mt-0.5">
                                {{ $routeComparisonStats['points'] }}
                            </span>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Passengers</span>
                            <span class="text-sm font-black text-white font-mono block mt-0.5">
                                {{ $routeComparisonStats['passengers'] }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Freight</span>
                            <span class="text-sm font-black text-white font-mono block mt-0.5">
                                {{ $routeComparisonStats['freight'] ? number_format($routeComparisonStats['freight']) . ' kg' : '0 kg' }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    @else
        @if(!empty($booking->simbrief_data['weights']) || $booking->status === 'dispatched')
            <!-- Return to OFP prompt banner -->
            <div class="flex items-center justify-between p-4 rounded-xl border bg-black/40 border-white/10 text-xs">
                <span class="text-gray-300">You are editing parameters for an already dispatched flight plan.</span>
                <button type="button" wire:click="returnToOfp" class="px-4 py-2 bg-tenant-accent text-white font-bold rounded-xl hover:opacity-90 transition">
                    ⬅ Return to Dispatched Flight Plan
                </button>
            </div>
        @endif
            <!-- READY TO DISPATCH Main Banner -->
            <div class="bg-[#0F224A] rounded-xl p-6 shadow-2xl space-y-5 border border-[#142954]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-[#21A19D] animate-pulse"></div>
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
                        @if (session()->has('simbrief_error') || session()->has('error'))
                            <div class="p-3 bg-red-500/20 border border-red-500 text-red-200 rounded-lg text-xs flex items-center justify-between">
                                <span>{{ session('simbrief_error') ?? session('error') }}</span>
                                <a href="{{ route('profile.preferences') }}" target="_blank" class="px-2.5 py-1 bg-red-600 hover:bg-red-500 text-white font-bold rounded text-xs transition">Account Settings →</a>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <x-label for="airframe_id" value="{{ __('Aircraft') }}" class="text-white font-medium" />
                                    <div class="flex gap-3 text-xs">
                                        <a href="{{ route('fleet') }}" target="_blank" class="text-tenant-accent hover:underline">Fleet</a>
                                        <a href="https://www.flightradar24.com" target="_blank" class="text-gray-400 hover:text-white underline">FR24</a>
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
                                <div class="flex justify-between items-center mb-1">
                                    <x-label for="airplane_profile" value="{{ __('Airplane Profile') }}" class="text-white font-medium" />
                                    <span class="text-[11px] text-gray-400">SimBrief matched</span>
                                </div>
                                <select id="airplane_profile" wire:model.live="airplane_profile" class="w-full bg-[#1C212E] border border-gray-700 text-white rounded-lg p-2.5 text-sm focus:border-tenant-accent focus:ring-tenant-accent font-medium">
                                    @foreach($availableAirplaneProfiles as $pKey => $prof)
                                        <option value="{{ $pKey }}">{{ $prof['name'] ?? $pKey }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-label for="ofp_format" value="{{ __('SimBrief OFP Format / Layout') }}" class="text-white font-medium mb-1" />
                                <select id="ofp_format" wire:model.live="ofp_format" class="w-full bg-[#1C212E] border border-gray-700 text-white rounded-lg p-2.5 text-sm focus:border-tenant-accent focus:ring-tenant-accent font-semibold">
                                    @if(!empty($ofp_format) && !isset($availableOfpFormats[$ofp_format]))
                                        <option value="{{ $ofp_format }}">{{ $ofp_format }}</option>
                                    @endif
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
                                @if(empty($simbrief_username))
                                    <span class="block text-[11px] text-amber-400 mt-1">⚠️ SimBrief ID not configured in <a href="{{ route('profile.preferences') }}" target="_blank" class="underline hover:text-amber-300 font-semibold">Account Settings</a></span>
                                @endif
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
                            <p class="text-xs text-gray-400">Alternate 1: {{ $auto_find_alternates ? 'Auto (SimBrief)' : ($alternate_1 ?: 'None') }} · Alternate 2: {{ $auto_find_alternates ? 'Auto (SimBrief)' : ($alternate_2 ?: 'None') }}</p>
                        </div>
                    </div>
                    <span class="text-gray-400">{{ $showSectionAlternates ? '∧' : '∨' }}</span>
                </div>

                @if($showSectionAlternates)
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between p-3 bg-black/30 rounded-lg border border-white/5">
                            <div>
                                <span class="text-sm font-semibold text-white block">Auto-find Alternates</span>
                                <span class="text-xs text-gray-400">SimBrief automatically selects optimal alternate airports</span>
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
                                <x-input id="alternate_1" type="text" wire:model="alternate_1" :disabled="$auto_find_alternates" class="w-full bg-[#1C212E] border-gray-700 text-white uppercase text-sm disabled:opacity-50 disabled:cursor-not-allowed" placeholder="{{ $auto_find_alternates ? 'Auto (SimBrief chooses)' : 'e.g. EDDH' }}" />
                            </div>
                            @if($num_alternates >= 2)
                                <div>
                                    <x-label for="alternate_2" value="{{ __('Alternate 2 (ICAO)') }}" class="text-white font-medium mb-1" />
                                    <x-input id="alternate_2" type="text" wire:model="alternate_2" :disabled="$auto_find_alternates" class="w-full bg-[#1C212E] border-gray-700 text-white uppercase text-sm disabled:opacity-50 disabled:cursor-not-allowed" placeholder="{{ $auto_find_alternates ? 'Auto (SimBrief chooses)' : 'e.g. EDDW' }}" />
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
                            <p class="text-xs text-gray-400">{{ !empty($passengers) ? $passengers . ' pax' : 'Auto (SimBrief Load)' }} · {{ !empty($hold_bags) ? $hold_bags . ' bags' : 'Auto' }} · ZFW {{ !empty($passengers) ? number_format($estimated_zfw) . ' kg' : 'Calculated by SimBrief (Auto)' }}</p>
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
                                    <div class="flex items-center gap-2 text-xs">
                                        <button type="button" wire:click="generatePassengers" class="text-tenant-accent hover:underline flex items-center gap-1">↻ Generate</button>
                                        @if($passengers !== null && $passengers !== '')
                                            <button type="button" wire:click="clearPassengers" class="text-gray-400 hover:text-white underline">Clear (Auto)</button>
                                        @endif
                                    </div>
                                </div>
                                <x-input id="passengers" type="number" min="0" max="{{ $passengers_max }}" wire:model.live="passengers" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" placeholder="Auto (SimBrief chooses load)" />
                                @if(empty($passengers))
                                    <span class="text-[11px] text-gray-400 block mt-1">Left empty: SimBrief will automatically calculate realistic passengers and load.</span>
                                @endif
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <x-label for="hold_bags" value="Passengers with Hold Luggage (max {{ $hold_bags_max }})" class="text-white font-medium" />
                                    <div class="flex items-center gap-2 text-xs">
                                        <button type="button" wire:click="generateHoldBags" class="text-tenant-accent hover:underline flex items-center gap-1">↻ Generate</button>
                                        @if($hold_bags !== null && $hold_bags !== '')
                                            <button type="button" wire:click="clearHoldBags" class="text-gray-400 hover:text-white underline">Clear (Auto)</button>
                                        @endif
                                    </div>
                                </div>
                                <x-input id="hold_bags" type="number" min="0" max="{{ $hold_bags_max }}" wire:model.live="hold_bags" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" placeholder="Auto (SimBrief chooses luggage)" />
                                @if(empty($hold_bags))
                                    <span class="text-[11px] text-gray-400 block mt-1">Left empty: SimBrief will automatically calculate cargo/luggage weight.</span>
                                @endif
                            </div>
                        </div>

                        <div class="pt-3 border-t border-white/5 flex items-center justify-between text-sm">
                            <span class="text-gray-400">Estimated Zero Fuel Weight (ZFW):</span>
                            <span class="font-bold text-white font-mono text-base">{{ !empty($passengers) ? number_format($estimated_zfw) . ' kg' : 'Calculated by SimBrief (Auto Load)' }}</span>
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

    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    <script>
        (function() {
            function registerAlpineDispatchMap() {
                if (typeof Alpine === 'undefined') return;
                if (Alpine._dispatchMapRegistered) return;
                Alpine._dispatchMapRegistered = true;

                Alpine.data('dispatchRouteMap', (waypoints) => ({
                    map: null,
                    init() {
                        const container = this.$refs.mapContainer;
                        if (!container) return;

                        const setupLeaflet = () => {
                            if (typeof L === 'undefined') return;
                            if (container._leaflet_id) {
                                try {
                                    container._leaflet_id = null;
                                } catch (e) {}
                            }

                            this.map = L.map(container, {
                                zoomControl: true,
                                attributionControl: false
                            });

                            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                                maxZoom: 19,
                                subdomains: 'abcd',
                            }).addTo(this.map);

                            if (Array.isArray(waypoints) && waypoints.length > 0) {
                                const latlngs = waypoints.map(wp => [wp.lat, wp.lon]);
                                const polyline = L.polyline(latlngs, {
                                    color: '#21A19D',
                                    weight: 3.5,
                                    opacity: 0.9,
                                    smoothFactor: 1
                                }).addTo(this.map);

                                waypoints.forEach((wp, index) => {
                                    const isDep = index === 0;
                                    const isArr = index === waypoints.length - 1;
                                    if (isDep || isArr) {
                                        const icon = L.divIcon({
                                            className: 'custom-map-icon',
                                            html: '<div style="width:28px;height:28px;background:#12161F;border:2px solid #21A19D;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;box-shadow:0 4px 10px rgba(0,0,0,0.6);">' + (isDep ? '🛫' : '🛬') + '</div>',
                                            iconSize: [28, 28],
                                            iconAnchor: [14, 14]
                                        });
                                        L.marker([wp.lat, wp.lon], { icon }).addTo(this.map).bindPopup('<b>' + wp.ident + '</b><br>' + (wp.name || ''));
                                    } else {
                                        L.circleMarker([wp.lat, wp.lon], {
                                            radius: 4,
                                            fillColor: '#21A19D',
                                            color: '#ffffff',
                                            weight: 1.5,
                                            opacity: 1,
                                            fillOpacity: 0.9
                                        }).addTo(this.map).bindPopup('<b>' + wp.ident + '</b>' + (wp.alt ? '<br>FL' + Math.floor(wp.alt/100) : ''));
                                    }
                                });

                                this.map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
                            } else {
                                this.map.setView([50.0, 10.0], 4);
                            }

                            setTimeout(() => {
                                if (this.map) {
                                    this.map.invalidateSize();
                                }
                            }, 300);
                        };

                        if (typeof L === 'undefined') {
                            const link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                            document.head.appendChild(link);

                            const script = document.createElement('script');
                            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                            script.onload = () => setupLeaflet();
                            document.head.appendChild(script);
                        } else {
                            setupLeaflet();
                        }
                    }
                }));
            }

            if (window.Alpine) {
                registerAlpineDispatchMap();
            } else {
                document.addEventListener('alpine:init', registerAlpineDispatchMap);
            }

            window.addEventListener('open-simbrief-custom-popup', () => {
                window.open(@js($simbriefPopupUrl), '_blank');
            });
        })();
    </script>
</div>
