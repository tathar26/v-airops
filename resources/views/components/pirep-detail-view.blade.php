@props(['pirep', 'isAdmin' => false])

@php
    $fLog = $pirep->flight_log ?? [];
    if (is_string($fLog)) {
        $fLog = json_decode($fLog, true) ?? [];
    }
    
    $flightId = $fLog['flight_id'] ?? null;
    
    // Fallback: Check raw_acars_log or match AcarsPirep if flight_id is missing from flight_log
    if (!$flightId && !empty($pirep->raw_acars_log)) {
        $raw = is_string($pirep->raw_acars_log) ? json_decode($pirep->raw_acars_log, true) : $pirep->raw_acars_log;
        if (is_array($raw) && !empty($raw['flight_id'])) {
            $flightId = $raw['flight_id'];
        }
    }
    if (!$flightId && $pirep->user_id && $pirep->created_at) {
        $matchedAcars = \App\Models\AcarsPirep::where('user_id', $pirep->user_id)
            ->whereBetween('created_at', [
                $pirep->created_at->copy()->subMinutes(60),
                $pirep->created_at->copy()->addMinutes(60)
            ])
            ->latest('id')
            ->first();
        if ($matchedAcars && $matchedAcars->flight_id) {
            $flightId = $matchedAcars->flight_id;
        }
    }
    
    // 1. Fetch Real Telemetry from AcarsPosition if available
    $acarsPositions = collect();
    if ($flightId) {
        $acarsPositions = \App\Models\AcarsPosition::where('flight_id', $flightId)
            ->orderBy('timestamp')
            ->get();
    }
    
    // Sample telemetry to avoid sending massive arrays if flight was hours long
    $telemetryData = [];
    if ($acarsPositions->isNotEmpty()) {
        $totalPos = $acarsPositions->count();
        $step = $totalPos > 150 ? (int) ceil($totalPos / 150) : 1;
        
        foreach ($acarsPositions as $idx => $pos) {
            if ($idx % $step === 0 || $idx === $totalPos - 1) {
                $telemetryData[] = [
                    'lat' => (float) $pos->latitude,
                    'lon' => (float) $pos->longitude,
                    'alt' => round((float) $pos->altitude_ft),
                    'spd' => round((float) $pos->ground_speed_kt),
                    'hdg' => round((float) $pos->heading_deg),
                    'vs' => round((float) $pos->vertical_speed_fpm),
                    'fuel' => round((float) $pos->fuel_qty_kg),
                    'phase' => $pos->flight_phase,
                    'time' => $pos->timestamp ? date('H:i', strtotime($pos->timestamp)) : '',
                ];
            }
        }
    } elseif (!empty($fLog['positions']) && is_array($fLog['positions'])) {
        $rawPts = $fLog['positions'];
        $totalPts = count($rawPts);
        $step = $totalPts > 150 ? (int) ceil($totalPts / 150) : 1;
        foreach ($rawPts as $idx => $pos) {
            if ($idx % $step === 0 || $idx === $totalPts - 1) {
                $telemetryData[] = [
                    'lat' => (float) ($pos['lat'] ?? ($pos['latitude'] ?? 0)),
                    'lon' => (float) ($pos['lon'] ?? ($pos['longitude'] ?? 0)),
                    'alt' => round((float) ($pos['alt'] ?? ($pos['altitude'] ?? ($pos['altitude_ft'] ?? 0)))),
                    'spd' => round((float) ($pos['spd'] ?? ($pos['speed'] ?? ($pos['ground_speed_kt'] ?? 0)))),
                    'hdg' => round((float) ($pos['hdg'] ?? ($pos['heading'] ?? 0))),
                    'vs' => round((float) ($pos['vs'] ?? ($pos['vertical_speed'] ?? 0))),
                    'fuel' => round((float) ($pos['fuel'] ?? ($pos['fuel_qty_kg'] ?? 0))),
                    'phase' => $pos['phase'] ?? ($pos['flight_phase'] ?? 'Enroute'),
                    'time' => !empty($pos['time']) ? $pos['time'] : (!empty($pos['timestamp']) ? date('H:i', strtotime($pos['timestamp'])) : ''),
                ];
            }
        }
    }
    
    // 2. Fetch Events (Penalties, Touchdown, etc.)
    $acarsEvents = collect();
    if ($flightId) {
        $acarsEvents = \App\Models\AcarsEvent::where('flight_id', $flightId)
            ->orderBy('timestamp')
            ->get();
    }
    
    // 3. Fetch OFP and Booking/Route metadata
    $ofp = $fLog['simbrief_data'] ?? ($fLog['ofp'] ?? []);
    
    // Origin / Destination
    $depIcao = $fLog['origin'] ?? ($ofp['origin']['icao_code'] ?? ($pirep->route?->departure_icao ?? 'EGLL'));
    $arrIcao = $fLog['destination'] ?? ($ofp['destination']['icao_code'] ?? ($pirep->route?->arrival_icao ?? 'LFPG'));
    
    // Callsign vs Flight Number distinction
    $callsignVal = $fLog['callsign'] 
        ?? ($ofp['params']['callsign'] 
        ?? ($ofp['atc']['callsign'] 
        ?? ($pirep->route?->callsign 
        ?? 'SVK101')));
        
    $flightNumVal = $fLog['flight_number'] 
        ?? ($ofp['general']['flight_number'] 
        ?? ($ofp['params']['fltnum'] 
        ?? ($pirep->route?->flight_number 
        ?? $callsignVal)));
        
    $pilotRoute = $fLog['route'] 
        ?? ($ofp['general']['route'] 
        ?? ($pirep->route?->route_string 
        ?? 'DIRECT'));
        
    // Aircraft info
    $aircraftTypeVal = $fLog['aircraft_type'] 
        ?? ($ofp['aircraft']['icao_code'] 
        ?? ($pirep->airframe?->aircraftType?->code 
        ?? ($pirep->route?->aircraftTypes?->first()?->code ?? 'A320')));
        
    $airframeRegVal = $pirep->airframe?->registration 
        ?? ($ofp['aircraft']['reg'] ?? 'HB-AYE');

    // Simulator, Aircraft Title & Livery
    $simVal = $pirep->simulator ?? ($fLog['simulator'] ?? 'MSFS');
    $acftTitleVal = $pirep->aircraft_title ?? ($fLog['aircraft_title'] ?? ($fLog['livery'] ?? $aircraftTypeVal));
    $liveryVal = $fLog['livery'] ?? ($pirep->aircraft_title ?? 'Default');
        
    // Timing calculations
    $blockMins = (int) ($fLog['block_time_minutes'] ?? ($pirep->flight_time ?? 0));
    $blockFormatted = sprintf('%02d:%02d:00', floor($blockMins / 60), $blockMins % 60);
    
    $schedMins = 0;
    if (!empty($ofp['times']['est_time_enroute'])) {
        $schedMins = round((int)$ofp['times']['est_time_enroute'] / 60);
    } elseif ($pirep->route?->flight_time) {
        $schedMins = (int) $pirep->route->flight_time;
    } else {
        $schedMins = $blockMins;
    }
    $schedFormatted = sprintf('%02d:%02d:00', floor($schedMins / 60), $schedMins % 60);
    
    // Landing & Score Analysis
    $touchdownFpm = (int) round($fLog['touchdown_fpm'] ?? ($pirep->touchdown_rate_fpm ?? 0));
    $touchdownG = (float) ($fLog['touchdown_gforce'] ?? 1.0);
    $landingGrade = $fLog['landing_grade'] ?? 'Good Landing';
    $scoreVal = (int) ($pirep->points_awarded ?? 100);
    $penalties = $fLog['penalties'] ?? [];
    
    // Fuel
    $actualFuel = (float) ($fLog['fuel_used_kg'] ?? ($pirep->fuel_used ?? 0));
    $plannedFuel = (float) ($fLog['planned_fuel_kg'] ?? ($ofp['fuel']['plan_ramp'] ?? 0));
@endphp

<div class="max-w-[1600px] mx-auto w-full space-y-6"
    x-data="pirepDetailDashboard({
        telemetry: {{ json_encode($telemetryData) }},
        depIcao: '{{ $depIcao }}',
        arrIcao: '{{ $arrIcao }}'
    })"
>
    @if (session()->has('message'))
        <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded-xl relative text-xs font-bold" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if($isAdmin)
        <div class="bg-[#181D29] border border-white/10 rounded-xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-lg" x-data="{ showReplyInput: false }">
            <div class="text-xs font-mono font-bold text-gray-300">
                <span>STAFF PIREP AUDIT ACTIONS:</span>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <button wire:click="accept" wire:confirm="Approve and Accept this PIREP?" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-md transition">
                    ✓ Accept PIREP
                </button>
                <button wire:click="reject" wire:confirm="Reject this PIREP? (Flight hours awarded, 0 points awarded)" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-md transition">
                    ⚠ Reject (0 Pts)
                </button>
                <button wire:click="invalidate" wire:confirm="Invalidate this PIREP? (0 hours and 0 points awarded)" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-md transition">
                    ✕ Invalidate PIREP
                </button>
                <button @click="showReplyInput = !showReplyInput" class="bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-md transition">
                    ✉ Request Reply
                </button>
            </div>

            <!-- Inline Reply Needed Reason Box -->
            <div x-show="showReplyInput" x-cloak class="w-full mt-3 border-t border-white/10 pt-3 flex gap-2">
                <input type="text" wire:model="requestReplyReason" placeholder="Specify reason why pilot input is urgently required..." class="flex-grow bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-xs text-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-amber-500" />
                <button wire:click="requestReply" class="bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-lg text-xs font-bold transition">
                    Send 'Reply Needed'
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <!-- Left Column (Map & Flight Profile) -->
        <div class="xl:col-span-2 space-y-6">
            
            <!-- PIREP STATUS Card -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
                <div class="px-5 py-3 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between items-center">
                    <span>PIREP STATUS &amp; DISPATCH</span>
                    <span class="text-xs font-mono text-gray-400">ID: #{{ $pirep->id }}</span>
                </div>
                <div class="p-6 bg-[#12161F] flex justify-between items-center flex-wrap gap-4">
                    <div class="flex items-center gap-6">
                        <div class="text-sm">
                            <span class="text-gray-400 block mb-1 text-xs">Status</span>
                            @php $st = strtolower($pirep->status); @endphp
                            @if(in_array($st, ['accepted', 'complete', 'approved']))
                                <span class="px-3 py-1 rounded bg-green-500/20 text-green-400 border border-green-500/30 font-bold text-xs">ACCEPTED / COMPLETE</span>
                            @elseif($st === 'rejected')
                                <span class="px-3 py-1 rounded bg-orange-500/20 text-orange-400 border border-orange-500/30 font-bold text-xs">REJECTED (0 PTS)</span>
                            @elseif($st === 'invalidated')
                                <span class="px-3 py-1 rounded bg-red-500/20 text-red-400 border border-red-500/30 font-bold text-xs">INVALIDATED (0 HRS)</span>
                            @elseif($st === 'awaiting_review' || $st === 'awaiting review')
                                <span class="px-3 py-1 rounded bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 font-bold text-xs">AWAITING REVIEW</span>
                            @elseif($st === 'reply_needed' || $st === 'reply needed')
                                <span class="px-3 py-1 rounded bg-red-500/30 text-amber-300 border border-red-500/40 font-bold text-xs animate-pulse">REPLY NEEDED (ACTION REQUIRED)</span>
                            @elseif($st === 'processing')
                                <span class="px-3 py-1 rounded bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 font-bold text-xs">PROCESSING</span>
                            @elseif($st === 'scoring')
                                <span class="px-3 py-1 rounded bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 font-bold text-xs">SCORING</span>
                            @else
                                <span class="px-3 py-1 rounded bg-slate-500/20 text-slate-300 border border-slate-500/30 font-bold text-xs">{{ strtoupper($pirep->status) }}</span>
                            @endif
                        </div>
                        <div class="text-sm pl-6 border-l border-white/10">
                            <span class="text-gray-400 block mb-1 text-xs">Pilot</span>
                            <span class="text-white font-bold">{{ $pirep->user->name }}</span>
                        </div>
                        <div class="text-sm pl-6 border-l border-white/10">
                            <span class="text-gray-400 block mb-1 text-xs">Callsign</span>
                            <span class="text-tenant-accent font-mono font-bold">{{ strtoupper($callsignVal) }}</span>
                        </div>
                    </div>
                    <div class="text-sm text-right">
                        <span class="text-gray-400 block mb-1 text-xs">Rank</span>
                        <span class="text-white font-medium">{{ $pirep->user->pilotProfiles()->where('tenant_id', $pirep->tenant_id)->first()?->rank?->name ?? 'First Officer' }}</span>
                    </div>
                </div>
            </div>

            <!-- Map Card -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl relative" style="height: 480px;">
                <div id="flightMap" x-ref="mapContainer" wire:ignore class="w-full h-full bg-[#0d111a]"></div>

                <!-- Flight Status Color Gradient Legend Overlay -->
                <div class="absolute top-3 right-3 bg-[#0a0d14]/85 backdrop-blur border border-white/10 px-3 py-2 rounded-xl text-[10px] font-mono text-gray-300 z-[1000] shadow-2xl flex flex-wrap items-center gap-2.5">
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#eab308]"></span>
                        <span>Taxi</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#38bdf8]"></span>
                        <span>Climb</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#a855f7]"></span>
                        <span>Cruise</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#f97316]"></span>
                        <span>Descent</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#22c55e]"></span>
                        <span>Landing</span>
                    </div>
                </div>
            </div>

            <!-- Flight Profile Card -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
                <div class="px-5 py-3 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between items-center">
                    <span>FLIGHT PROFILE (TELEMETRY)</span>
                    <span class="text-[11px] text-gray-400">Altitude &amp; Groundspeed Profile</span>
                </div>
                <div class="p-4 bg-[#12161F]" style="height: 280px;">
                    <canvas id="flightProfileChart" x-ref="chartContainer" wire:ignore></canvas>
                </div>
            </div>

        </div>

        <!-- Right Column (Route & Dispatch, Comments, Flight Time, Points & Scoring) -->
        <div class="space-y-6">
            
            <!-- ROUTE & DISPATCH -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
                <div class="px-5 py-3 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between">
                    <span>ROUTE &amp; DISPATCH</span>
                </div>
                <div class="p-6 bg-[#12161F] space-y-4 text-xs font-mono">
                    <div class="flex justify-between">
                        <div>
                            <span class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">Departure</span>
                            <span class="text-base text-sky-400 font-bold">{{ $depIcao }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">Arrival</span>
                            <span class="text-base text-sky-400 font-bold">{{ $arrIcao }}</span>
                        </div>
                    </div>
                    <div class="flex justify-between border-t border-white/5 pt-3">
                        <div>
                            <span class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">ATC Callsign</span>
                            <span class="text-sm text-tenant-accent font-bold">{{ strtoupper($callsignVal) }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">Flight Number</span>
                            <span class="text-sm text-white font-bold">{{ strtoupper($flightNumVal) }}</span>
                        </div>
                    </div>
                    <div class="flex justify-between border-t border-white/5 pt-3">
                        <div>
                            <span class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">Airframe</span>
                            <span class="text-xs text-gray-200 font-bold">{{ $airframeRegVal }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">Aircraft Type</span>
                            <span class="text-xs text-gray-200 font-bold">{{ $aircraftTypeVal }}</span>
                        </div>
                    </div>
                    <div class="flex justify-between border-t border-white/5 pt-3">
                        <div>
                            <span class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">Simulator</span>
                            <span class="text-xs text-cyan-400 font-mono font-bold">{{ $simVal }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-[10px] text-gray-500 uppercase font-bold mb-0.5">Livery / Model</span>
                            <span class="text-xs text-gray-200 font-medium max-w-[200px] truncate block" title="{{ $acftTitleVal }}">{{ $acftTitleVal }}</span>
                        </div>
                    </div>
                    <div class="border-t border-white/5 pt-3">
                        <span class="block text-[10px] text-gray-500 uppercase font-bold mb-1">Flown ATC Routing</span>
                        <div class="p-2.5 bg-[#0a0d14] rounded text-green-400 break-words border border-white/5 leading-relaxed text-[11px]">
                            {{ $pilotRoute }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- PIREP COMMENTS -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
                <div class="px-5 py-3 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between">
                    <span>PIREP COMMENTS</span>
                </div>
                <div class="p-6 bg-[#12161F] space-y-4">
                    @forelse($pirep->comments ?? [] as $comment)
                        <div class="bg-black/30 p-3 rounded-lg border border-white/5">
                            <div class="flex justify-between items-center mb-1.5 text-xs">
                                <span class="font-bold text-white">{{ $comment->user->name ?? 'Pilot' }}</span>
                                <span class="text-gray-500 text-[10px]">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="text-xs text-gray-300 leading-relaxed">{{ $comment->comment }}</div>
                        </div>
                    @empty
                        <div class="text-xs text-gray-500 italic mb-2">No comments filed on this PIREP yet.</div>
                    @endforelse
                    
                    <form wire:submit.prevent="addComment" class="mt-4 border-t border-white/5 pt-4">
                        <textarea wire:model="newComment" rows="2" class="w-full bg-black/40 border border-white/10 rounded-lg p-3 text-xs text-white placeholder-gray-500 focus:ring-1 focus:ring-tenant-accent focus:border-tenant-accent focus:outline-none" placeholder="Write a flight debrief comment..."></textarea>
                        @error('newComment') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        
                        <button type="submit" class="mt-3 w-full bg-tenant-accent hover:opacity-90 text-white px-4 py-2 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 shadow">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            Post Comment
                        </button>
                    </form>
                </div>
            </div>

            <!-- TIME -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
                <div class="px-5 py-3 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between">
                    <span>FLIGHT TIME</span>
                </div>
                <div class="p-6 bg-[#12161F]">
                    <div class="flex justify-between items-center mb-6">
                        <div class="text-center">
                            <span class="block text-xs text-gray-400 mb-1">Airborne</span>
                            <span class="text-sm text-white font-mono font-semibold">{{ $blockFormatted }}</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-xs text-tenant-accent font-bold mb-1 uppercase tracking-wider">Awarded</span>
                            <span class="text-2xl text-white font-mono font-bold">{{ $blockFormatted }}</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-xs text-gray-400 mb-1">Block</span>
                            <span class="text-sm text-white font-mono font-semibold">{{ $blockFormatted }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 border-t border-white/5 pt-4 text-center">
                        <div>
                            <span class="block text-[11px] text-gray-400 mb-1">Scheduled</span>
                            <span class="text-xs text-white font-mono">{{ $schedFormatted }}</span>
                        </div>
                        <div>
                            <span class="block text-[11px] text-gray-400 mb-1">Fuel Used</span>
                            <span class="text-xs text-tenant-accent font-mono font-bold">{{ number_format($actualFuel) }} kg</span>
                        </div>
                        <div>
                            <span class="block text-[11px] text-gray-400 mb-1">Planned Fuel</span>
                            <span class="text-xs text-gray-300 font-mono">{{ number_format($plannedFuel) }} kg</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- POINTS & SCORING -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
                <div class="px-5 py-3 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between items-center">
                    <span>POINTS &amp; SCORING</span>
                    <span class="text-xs font-mono font-bold {{ $scoreVal >= 90 ? 'text-green-400' : ($scoreVal >= 70 ? 'text-yellow-400' : 'text-red-400') }}">{{ $scoreVal }} / 100 PTS</span>
                </div>
                <div class="p-6 bg-[#12161F] space-y-4 text-xs font-mono">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Touchdown Rate:</span>
                        <span class="font-bold {{ abs($touchdownFpm) <= 200 ? 'text-green-400' : (abs($touchdownFpm) <= 400 ? 'text-yellow-400' : 'text-red-400') }}">
                            {{ $touchdownFpm }} FPM ({{ number_format($touchdownG, 2) }} G)
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Landing Grade:</span>
                        <span class="text-white font-bold">{{ $landingGrade }}</span>
                    </div>

                    @if(!empty($fLog['failure_reasons'] ?? []))
                        <div class="border-t border-white/10 pt-3 space-y-2">
                            <span class="text-[10px] text-amber-400 uppercase font-bold tracking-wider block">Triggered Failure Rules:</span>
                            @foreach($fLog['failure_reasons'] as $reason)
                                <div class="flex items-center gap-2 text-amber-300 bg-amber-500/10 p-2.5 rounded-lg border border-amber-500/20 font-sans text-xs">
                                    <span class="text-amber-400 font-bold">⚠</span>
                                    <span>{{ $reason }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($penalties))
                        <div class="border-t border-white/5 pt-3 space-y-2">
                            <span class="text-[10px] text-red-400 uppercase font-bold tracking-wider block">Deductions:</span>
                            @foreach($penalties as $pen)
                                <div class="flex justify-between text-red-300 bg-red-500/10 p-2 rounded border border-red-500/20">
                                    <span>{{ $pen['description'] ?? ($pen['category'] ?? 'Rule Violation') }}</span>
                                    <span class="font-bold">-{{ $pen['points_deducted'] ?? 10 }} pts</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($fLog['bonuses'] ?? []))
                        <div class="border-t border-white/5 pt-3 space-y-2">
                            <span class="text-[10px] text-green-400 uppercase font-bold tracking-wider block">Score Bonuses:</span>
                            @foreach($fLog['bonuses'] as $bon)
                                <div class="flex justify-between text-green-300 bg-green-500/10 p-2 rounded border border-green-500/20">
                                    <span>{{ $bon['description'] }}</span>
                                    <span class="font-bold">+{{ $bon['points'] }} pts</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="border-t border-white/10 pt-3 flex justify-between items-center mt-4 text-sm font-sans">
                        <span class="text-gray-300 font-bold">Total Score Awarded:</span>
                        <span class="text-tenant-accent font-mono font-extrabold text-xl">+{{ $scoreVal }} pts</span>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    (() => {
        const registerComponent = () => {
            if (typeof Alpine !== 'undefined') {
                Alpine.data('pirepDetailDashboard', (config) => ({
                    telemetry: config.telemetry || [],
                    depIcao: config.depIcao || '',
                    arrIcao: config.arrIcao || '',
                    mapInstance: null,
                    chartInstance: null,

                    loadScript(src, globalName) {
                        return new Promise((resolve, reject) => {
                            if (globalName && typeof window[globalName] !== 'undefined') {
                                return resolve(window[globalName]);
                            }
                            let existing = document.querySelector(`script[src='${src}']`);
                            if (existing) {
                                if (globalName && typeof window[globalName] !== 'undefined') {
                                    return resolve(window[globalName]);
                                }
                                existing.addEventListener('load', () => resolve(globalName ? window[globalName] : true));
                                existing.addEventListener('error', (e) => reject(e));
                                return;
                            }
                            const s = document.createElement('script');
                            s.src = src;
                            s.onload = () => resolve(globalName ? window[globalName] : true);
                            s.onerror = (e) => reject(e);
                            document.head.appendChild(s);
                        });
                    },

                    loadStylesheet(href) {
                        return new Promise((resolve) => {
                            if (document.querySelector(`link[href*='${href}']`)) return resolve();
                            const l = document.createElement('link');
                            l.rel = 'stylesheet';
                            l.href = href;
                            l.onload = () => resolve();
                            l.onerror = () => resolve();
                            document.head.appendChild(l);
                        });
                    },

                    async ensureDependencies() {
                        const promises = [];
                        if (typeof L === 'undefined') {
                            this.loadStylesheet('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
                            promises.push(this.loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', 'L'));
                        }
                        if (typeof Chart === 'undefined') {
                            promises.push(this.loadScript('https://cdn.jsdelivr.net/npm/chart.js', 'Chart'));
                        }
                        if (promises.length > 0) {
                            await Promise.all(promises);
                        }
                    },

                    async init() {
                        await this.ensureDependencies();
                        this.$nextTick(() => {
                            this.renderDashboard();
                        });
                    },

                    renderDashboard() {
                        this.renderMap();
                        this.renderChart();
                    },

                    async renderMap() {
                        try {
                            const container = this.$refs.mapContainer;
                            if (!container || typeof L === 'undefined') return;

                            if (this.mapInstance) {
                                this.mapInstance.remove();
                                this.mapInstance = null;
                            } else if (container._leaflet_id) {
                                container._leaflet_id = null;
                            }

                            const map = L.map(container, {
                                zoomControl: true,
                                attributionControl: true
                            }).setView([50.0, 10.0], 4);
                            this.mapInstance = map;

                            const cartoKey = window.CARTO_API_KEY || document.querySelector('meta[name="carto-api-key"]')?.getAttribute('content') || '';
                            const tileUrl = cartoKey
                                ? `https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png?key=${encodeURIComponent(cartoKey)}`
                                : 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';

                            L.tileLayer(tileUrl, {
                                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                                subdomains: 'abcd',
                                maxZoom: 20
                            }).addTo(map);

                            const getPhaseColor = (phase, alt) => {
                                const p = (phase || '').toUpperCase();
                                if (p.includes('CLIMB') || p.includes('TAKEOFF')) return '#38bdf8';
                                if (p.includes('CRUISE') || p.includes('ENROUTE') || p.includes('LEVEL')) return '#a855f7';
                                if (p.includes('DESCENT')) return '#f97316';
                                if (p.includes('APPROACH') || p.includes('LANDING') || p.includes('TOUCHDOWN') || p.includes('FINAL')) return '#22c55e';
                                if (p.includes('TAXI') || p.includes('BOARD') || p.includes('PREFLIGHT') || p.includes('PARKED')) return '#eab308';
                                
                                if (alt >= 28000) return '#a855f7';
                                if (alt >= 18000) return '#818cf8';
                                if (alt >= 8000) return '#38bdf8';
                                if (alt >= 2000) return '#f97316';
                                return '#22c55e';
                            };

                            const validTelemetry = Array.isArray(this.telemetry) 
                                ? this.telemetry.filter(p => p && typeof p.lat === 'number' && typeof p.lon === 'number' && !isNaN(p.lat) && !isNaN(p.lon) && (p.lat !== 0 || p.lon !== 0))
                                : [];

                            if (validTelemetry.length > 1) {
                                const bounds = [];
                                for (let i = 0; i < validTelemetry.length - 1; i++) {
                                    const p1 = validTelemetry[i];
                                    const p2 = validTelemetry[i + 1];
                                    const seg = [[p1.lat, p1.lon], [p2.lat, p2.lon]];
                                    bounds.push([p1.lat, p1.lon]);
                                    const color = getPhaseColor(p1.phase, p1.alt);
                                    
                                    L.polyline(seg, {
                                        color: color,
                                        weight: 4,
                                        opacity: 0.95,
                                        smoothFactor: 1
                                    }).bindPopup(`<strong>Phase:</strong> ${p1.phase || 'Enroute'}<br><strong>Altitude:</strong> ${p1.alt} ft<br><strong>Speed:</strong> ${p1.spd} kts<br><strong>Time:</strong> ${p1.time}`).addTo(map);
                                }
                                const lastPt = validTelemetry[validTelemetry.length - 1];
                                bounds.push([lastPt.lat, lastPt.lon]);

                                if (bounds.length > 0) {
                                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 12 });

                                    L.circleMarker(bounds[0], { 
                                        radius: 7, 
                                        color: '#38bdf8', 
                                        fillColor: '#0284c7', 
                                        fillOpacity: 1, 
                                        weight: 2 
                                    }).bindPopup(`<strong>Departure:</strong> ${this.depIcao}`).addTo(map);

                                    L.circleMarker(bounds[bounds.length - 1], { 
                                        radius: 7, 
                                        color: '#22c55e', 
                                        fillColor: '#16a34a', 
                                        fillOpacity: 1, 
                                        weight: 2 
                                    }).bindPopup(`<strong>Arrival:</strong> ${this.arrIcao}`).addTo(map);
                                }
                            } else {
                                const getCoords = async (icao) => {
                                    if (!icao) return null;
                                    try {
                                        const res = await fetch(`/api/airport/${encodeURIComponent(icao)}`);
                                        if (res.ok) {
                                            const data = await res.json();
                                            if (data && !isNaN(data.lat) && !isNaN(data.lon)) {
                                                return [parseFloat(data.lat), parseFloat(data.lon)];
                                            }
                                        }
                                    } catch(e) {
                                        console.warn('Airport lookup failed for ' + icao, e);
                                    }
                                    return null;
                                };

                                const [depCoords, arrCoords] = await Promise.all([
                                    getCoords(this.depIcao),
                                    getCoords(this.arrIcao)
                                ]);

                                let pathCoordinates = [];
                                if (depCoords && arrCoords) {
                                    pathCoordinates = [depCoords, arrCoords];
                                } else if (depCoords) {
                                    pathCoordinates = [depCoords, depCoords];
                                } else {
                                    pathCoordinates = [
                                        [45.725, 5.081],
                                        [51.148, -0.190]
                                    ];
                                }

                                const flightPath = L.polyline(pathCoordinates, {
                                    color: '#a855f7', 
                                    weight: 3.5,
                                    opacity: 0.9,
                                    dashArray: '6, 6'
                                }).addTo(map);

                                map.fitBounds(flightPath.getBounds(), { padding: [50, 50], maxZoom: 12 });

                                L.circleMarker(pathCoordinates[0], { radius: 7, color: '#38bdf8', fillColor: '#0284c7', fillOpacity: 1, weight: 2 }).bindPopup(`<strong>Departure:</strong> ${this.depIcao}`).addTo(map);
                                L.circleMarker(pathCoordinates[pathCoordinates.length - 1], { radius: 7, color: '#22c55e', fillColor: '#16a34a', fillOpacity: 1, weight: 2 }).bindPopup(`<strong>Arrival:</strong> ${this.arrIcao}`).addTo(map);
                            }

                            setTimeout(() => {
                                if (this.mapInstance) {
                                    this.mapInstance.invalidateSize();
                                }
                            }, 200);
                        } catch (err) {
                            console.error('Error rendering Leaflet map:', err);
                        }
                    },

                    renderChart() {
                        try {
                            const canvas = this.$refs.chartContainer;
                            if (!canvas || typeof Chart === 'undefined') return;

                            if (this.chartInstance) {
                                this.chartInstance.destroy();
                                this.chartInstance = null;
                            }
                            if (typeof Chart.getChart === 'function') {
                                const existing = Chart.getChart(canvas);
                                if (existing) existing.destroy();
                            }
                            if (window.flightProfileChartInstance) {
                                window.flightProfileChartInstance.destroy();
                                window.flightProfileChartInstance = null;
                            }

                            let chartLabels = [];
                            let altitudeData = [];
                            let speedData = [];

                            const validTelemetry = Array.isArray(this.telemetry) ? this.telemetry.filter(p => p && p.alt !== undefined) : [];

                            if (validTelemetry.length > 0) {
                                validTelemetry.forEach((pt, idx) => {
                                    chartLabels.push(pt.time || (idx + ''));
                                    altitudeData.push(typeof pt.alt === 'number' ? pt.alt : 0);
                                    speedData.push(typeof pt.spd === 'number' ? pt.spd : 0);
                                });
                            } else {
                                const totalPoints = 40;
                                for (let i = 0; i <= totalPoints; i++) {
                                    chartLabels.push(i + 'm');
                                    if (i < 8) altitudeData.push(Math.round(i * 4200)); 
                                    else if (i > 32) altitudeData.push(Math.round((totalPoints - i) * 4200));
                                    else altitudeData.push(35000);
                                    
                                    if (i < 8) speedData.push(Math.round(150 + (i * 35))); 
                                    else if (i > 32) speedData.push(Math.round(150 + ((totalPoints - i) * 35)));
                                    else speedData.push(440);
                                }
                            }

                            const ctx = canvas.getContext('2d');
                            this.chartInstance = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: chartLabels,
                                    datasets: [
                                        {
                                            label: 'Altitude (ft)',
                                            data: altitudeData,
                                            borderColor: '#38bdf8',
                                            backgroundColor: 'rgba(56, 189, 248, 0.1)',
                                            yAxisID: 'y',
                                            tension: 0.3,
                                            fill: true,
                                            pointRadius: altitudeData.length > 50 ? 0 : 2
                                        },
                                        {
                                            label: 'Groundspeed (kts)',
                                            data: speedData,
                                            borderColor: '#f87171',
                                            backgroundColor: 'transparent',
                                            yAxisID: 'y1',
                                            tension: 0.3,
                                            fill: false,
                                            pointRadius: speedData.length > 50 ? 0 : 2
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    interaction: { mode: 'index', intersect: false },
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: { color: '#9ca3af' }
                                        }
                                    },
                                    scales: {
                                        x: { display: false },
                                        y: {
                                            type: 'linear',
                                            display: true,
                                            position: 'left',
                                            grid: { color: '#273142' },
                                            ticks: { color: '#9ca3af' },
                                            title: { display: true, text: 'Altitude (ft)', color: '#9ca3af' }
                                        },
                                        y1: {
                                            type: 'linear',
                                            display: true,
                                            position: 'right',
                                            grid: { drawOnChartArea: false },
                                            ticks: { color: '#9ca3af' },
                                            title: { display: true, text: 'Speed (kts)', color: '#9ca3af' }
                                        }
                                    }
                                }
                            });
                            window.flightProfileChartInstance = this.chartInstance;
                        } catch (err) {
                            console.error('Error rendering Chart.js profile:', err);
                        }
                    }
                }));
            }
        };

        if (window.Alpine) {
            registerComponent();
        } else {
            document.addEventListener('alpine:init', registerComponent);
        }
    })();
</script>
