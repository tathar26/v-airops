<div class="space-y-6 max-w-[1600px] mx-auto w-full" x-data="liveFlightMap({{ json_encode($liveFlights) }})">

    <!-- ── 1. AIRLINE & PILOT STATS ROW 1 ─────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- PIREPs -->
        <div class="va-card">
            <div class="va-card-header flex justify-between items-center">
                <span>PIREPs</span>
                <span class="text-[9px] text-slate-600 font-mono uppercase tracking-wider">Total / Accept / Pend / Reject</span>
            </div>
            <div class="va-card-body flex justify-between items-center">
                <div>
                    <div class="text-2xl font-bold text-tenant-accent">{{ $userTotalPireps }}</div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider mt-0.5">Filed</div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-center">
                        <div class="text-base font-bold text-emerald-400">{{ $userAcceptedPireps }}</div>
                        <div class="text-[9px] text-slate-500 uppercase">OK</div>
                    </div>
                    <div class="text-center">
                        <div class="text-base font-bold text-amber-400">{{ $userPendingPireps }}</div>
                        <div class="text-[9px] text-slate-500 uppercase">Pend</div>
                    </div>
                    <div class="text-center">
                        <div class="text-base font-bold text-red-400">{{ $userRejectedPireps }}</div>
                        <div class="text-[9px] text-slate-500 uppercase">Rej</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Points -->
        <div class="va-card">
            <div class="va-card-header flex justify-between items-center">
                <span>Points</span>
                <span class="text-[9px] text-slate-600 font-mono uppercase tracking-wider">Career Score</span>
            </div>
            <div class="va-card-body">
                <div class="text-2xl font-bold text-tenant-accent">{{ number_format($profile->points) }}</div>
                <div class="text-[10px] text-slate-500 uppercase tracking-wider mt-0.5">Points earned</div>
            </div>
        </div>

        <!-- Time Flown -->
        <div class="va-card">
            <div class="va-card-header flex justify-between items-center">
                <span>Time Flown</span>
                <span class="text-[9px] text-slate-600 font-mono uppercase tracking-wider">Active Airline</span>
            </div>
            <div class="va-card-body">
                <div class="text-2xl font-bold text-tenant-accent font-mono">
                    {{ $flightTimeHours }}<span class="text-base text-slate-400 font-sans">h</span>
                    {{ str_pad($flightTimeMins, 2, '0', STR_PAD_LEFT) }}<span class="text-base text-slate-400 font-sans">m</span>
                </div>
                <div class="text-[10px] text-slate-500 uppercase tracking-wider mt-0.5">Block hours</div>
            </div>
        </div>
    </div>

    <!-- ── 2. AIRLINE NETWORK & PILOT INFO ROW 2 ──────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Airline Network -->
        <div class="va-card">
            <div class="va-card-header flex justify-between items-center">
                <span>Airline Network</span>
                <span class="text-[9px] text-slate-600 font-mono uppercase tracking-wider">Fleet &amp; Routes</span>
            </div>
            <div class="va-card-body flex justify-between items-center">
                <div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider">Active Fleet</div>
                    <div class="text-xl font-bold text-tenant-accent">{{ $fleetCount }}<span class="text-sm text-slate-400 ml-1 font-normal">airframes</span></div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider">Total Routes</div>
                    <div class="text-xl font-bold text-tenant-accent">{{ $routeCount }}<span class="text-sm text-slate-400 ml-1 font-normal">routes</span></div>
                </div>
            </div>
        </div>

        <!-- Current Rank -->
        <div class="va-card">
            <div class="va-card-header flex justify-between items-center">
                <span>Current Rank</span>
                <span class="text-[9px] text-slate-600 font-mono uppercase tracking-wider">Pilot Classification</span>
            </div>
            <div class="va-card-body flex justify-between items-center">
                <div class="text-xl font-bold text-tenant-accent">{{ $rankName }}</div>
                <div class="text-sm font-mono text-sky-400 bg-sky-500/10 px-2 py-1 rounded border border-sky-500/20">{{ $callsign }}</div>
            </div>
        </div>

        <!-- Pilot Card -->
        <div class="va-card">
            <div class="va-card-header flex justify-between items-center">
                <span>Pilot</span>
                <span class="text-[9px] text-slate-600 font-mono uppercase tracking-wider">Identity &amp; Location</span>
            </div>
            <div class="va-card-body flex justify-between items-center gap-4">
                <div class="min-w-0">
                    <div class="text-base font-bold text-slate-100 truncate">{{ $user->full_name }}</div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="text-xs font-mono text-tenant-accent bg-tenant-accent/10 px-1.5 py-0.5 rounded border border-tenant-accent/20">{{ $callsign }}</span>
                        <span class="text-slate-600 text-xs">&bull;</span>
                        <span class="text-xs text-slate-400">{{ $rankName }}</span>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider">Location</div>
                    <div class="text-2xl font-bold text-sky-400 font-mono tracking-wider">{{ $currentLocation }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 3. LIVE FLIGHT MAP (Placed at Bottom) ──────────────────── -->
    <div class="va-card overflow-hidden shadow-2xl relative">
        <div class="va-card-header flex justify-between items-center border-b border-white/10 px-4 py-3">
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="font-bold text-sm text-slate-100 tracking-wide">LIVE OPERATIONS RADAR</span>
                <span class="text-xs text-slate-400 font-mono" x-text="'(' + flights.length + ' active)'"></span>
            </div>
            <div class="flex items-center gap-3">
                <button @click="resetView()" type="button" class="text-xs text-slate-400 hover:text-white transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    Reset View
                </button>
            </div>
        </div>

        <!-- Map Canvas Container -->
        <div class="relative w-full h-[460px] bg-[#080c14]" id="live-flight-map" wire:ignore></div>
    </div>

    <!-- ── 4. LIVE FLIGHTS TABLE (Placed at Bottom) ───────────────── -->
    <div class="va-card overflow-hidden shadow-2xl">
        <!-- Table Header Bar -->
        <div class="va-card-header flex justify-between items-center border-b border-white/10 px-6 py-3.5">
            <div class="flex items-center gap-3">
                <h3 class="text-base font-extrabold text-white tracking-wide" x-text="flights.length + ' LIVE FLIGHTS'"></h3>
            </div>
            <div class="flex items-center gap-4 text-xs font-mono">
                <span class="text-slate-400">Updated: <strong class="text-slate-200" x-text="lastUpdated"></strong></span>
                <button @click="fetchLiveFlights()" type="button" :disabled="isLoading"
                        class="px-4 py-1.5 rounded-lg border border-amber-400/60 bg-amber-400/10 hover:bg-amber-400/20 text-amber-300 font-bold transition flex items-center gap-1.5 focus:outline-none disabled:opacity-50">
                    <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Update</span>
                </button>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="va-table w-full">
                <thead>
                    <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-white/10">
                        <th class="px-5 py-3 text-left">PILOT</th>
                        <th class="px-4 py-3 text-left">CALLSIGN</th>
                        <th class="px-4 py-3 text-left">DEPARTURE</th>
                        <th class="px-4 py-3 text-left">ARRIVAL</th>
                        <th class="px-4 py-3 text-left">AIRCRAFT</th>
                        <th class="px-4 py-3 text-center">ETE/ETD</th>
                        <th class="px-4 py-3 text-center">DISTANCE</th>
                        <th class="px-4 py-3 text-left">STATUS</th>
                        <th class="px-4 py-3 text-left">NETWORK</th>
                        <th class="px-4 py-3 text-right">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-xs">
                    <template x-for="flight in flights" :key="flight.id">
                        <tr :id="'flight-row-' + flight.id"
                            @click="selectFlight(flight)"
                            class="hover:bg-white/5 transition-colors cursor-pointer"
                            :class="selectedFlightId === flight.id ? 'bg-amber-400/10 border-l-4 border-amber-400' : ''">
                            
                            <!-- PILOT -->
                            <td class="px-5 py-3 whitespace-nowrap">
                                <div class="font-bold text-slate-100" x-text="flight.pilot_name"></div>
                                <div class="text-[10px] text-slate-400" x-text="flight.pilot_rank"></div>
                            </td>

                            <!-- CALLSIGN -->
                            <td class="px-4 py-3 whitespace-nowrap font-mono font-bold text-amber-300" x-text="flight.callsign"></td>

                            <!-- DEPARTURE -->
                            <td class="px-4 py-3 whitespace-nowrap font-mono font-bold text-sky-400">
                                <span class="bg-sky-500/10 border border-sky-500/20 px-2 py-0.5 rounded" x-text="flight.departure_icao"></span>
                            </td>

                            <!-- ARRIVAL -->
                            <td class="px-4 py-3 whitespace-nowrap font-mono font-bold text-sky-400">
                                <span class="bg-sky-500/10 border border-sky-500/20 px-2 py-0.5 rounded" x-text="flight.arrival_icao"></span>
                            </td>

                            <!-- AIRCRAFT -->
                            <td class="px-4 py-3 whitespace-nowrap text-slate-300" x-text="flight.aircraft_display || flight.aircraft_type"></td>

                            <!-- ETE / ETD -->
                            <td class="px-4 py-3 whitespace-nowrap text-center font-mono text-slate-300" x-text="flight.ete || '--:--'"></td>

                            <!-- DISTANCE -->
                            <td class="px-4 py-3 whitespace-nowrap text-center font-mono text-slate-300" x-text="flight.distance_nm ? flight.distance_nm + ' nm' : 'N/A'"></td>

                            <!-- STATUS -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold font-mono tracking-wider inline-block"
                                      :class="{
                                          'bg-sky-500/15 text-sky-300 border border-sky-500/30': ['Cruising', 'Enroute'].includes(flight.status),
                                          'bg-purple-500/15 text-purple-300 border border-purple-500/30': ['Climbing', 'Takeoff'].includes(flight.status),
                                          'bg-orange-500/15 text-orange-300 border border-orange-500/30': ['Descending', 'Approach', 'Final Approach'].includes(flight.status),
                                          'bg-amber-500/15 text-amber-300 border border-amber-500/30': ['Preflight', 'Boarding', 'Pushback', 'Taxiing'].includes(flight.status),
                                          'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30': ['Landed', 'Parked'].includes(flight.status)
                                      }"
                                      x-text="flight.status">
                                </span>
                            </td>

                            <!-- NETWORK -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono"
                                      :class="{
                                          'bg-sky-500/20 text-sky-300': flight.network === 'VATSIM',
                                          'bg-blue-600/20 text-blue-300': flight.network === 'IVAO',
                                          'bg-slate-700/40 text-slate-400': flight.network === 'Offline',
                                          'bg-pink-500/20 text-pink-300': flight.network === 'POSCON',
                                          'bg-emerald-500/20 text-emerald-300': flight.network === 'PilotEdge'
                                      }"
                                      x-text="flight.network || 'Offline'">
                                </span>
                            </td>

                            <!-- ACTION -->
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <button @click.stop="selectFlight(flight)" type="button"
                                        class="p-1.5 rounded bg-white/5 hover:bg-amber-400 hover:text-black text-slate-400 transition"
                                        title="Track on Map">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <template x-if="flights.length === 0">
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500 italic">
                                No active flights currently tracked for this airline.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @vite('resources/js/live-flight-map.js')
@endpush
