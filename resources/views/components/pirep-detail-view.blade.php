@props(['pirep', 'isAdmin' => false])

@php
    $fLog = $pirep->flight_log ?? [];
    if (is_string($fLog)) {
        $fLog = json_decode($fLog, true) ?? [];
    }
    
    $flightId = $fLog['flight_id'] ?? null;
    
    // 1. Fetch Real Telemetry from AcarsPosition if available
    $acarsPositions = collect();
    if ($flightId) {
        $acarsPositions = \App\Models\AcarsPosition::where('flight_id', $flightId)
            ->orderBy('timestamp')
            ->get();
    }
    
    // Sample telemetry to avoid sending massive arrays if flight was hours long
    $telemetryData = [];
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
    x-data="{
        telemetry: {{ json_encode($telemetryData) }},
        depIcao: '{{ $depIcao }}',
        arrIcao: '{{ $arrIcao }}',
        
        loadScript(src) {
            return new Promise((resolve, reject) => {
                if (document.querySelector(`script[src='${src}']`)) return resolve();
                const s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        },
        
        loadStylesheet(href) {
            return new Promise((resolve, reject) => {
                if (document.querySelector(`link[href='${href}']`)) return resolve();
                const l = document.createElement('link');
                l.rel = 'stylesheet';
                l.href = href;
                l.onload = resolve;
                l.onerror = reject;
                document.head.appendChild(l);
            });
        },

        async init() {
            if (typeof L === 'undefined') {
                await this.loadStylesheet('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
                await this.loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');
            }
            if (typeof Chart === 'undefined') {
                await this.loadScript('https://cdn.jsdelivr.net/npm/chart.js');
            }
            this.drawDashboard();
        },

        async drawDashboard() {
            // 1. Initialize Leaflet Map
            if (this.$refs.mapContainer._leaflet_id) {
                this.$refs.mapContainer._leaflet_id = null;
            }
            
            const map = L.map(this.$refs.mapContainer).setView([50.0, 10.0], 4);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(map);

            let pathCoordinates = [];

            if (this.telemetry && this.telemetry.length > 0) {
                this.telemetry.forEach(point => {
                    if (point.lat && point.lon) {
                        pathCoordinates.push([point.lat, point.lon]);
                    }
                });
            } else {
                let getCoords = async (icao) => {
                    if(!icao) return null;
                    try {
                        const res = await fetch(`/api/airport/${icao}`);
                        if(res.ok) {
                            const data = await res.json();
                            if(data && data.lat && data.lon) {
                                return [parseFloat(data.lat), parseFloat(data.lon)];
                            }
                        }
                    } catch(e) { console.error('Map lookup failed', e); }
                    return null;
                };

                let [depCoords, arrCoords] = await Promise.all([
                    getCoords(this.depIcao),
                    getCoords(this.arrIcao)
                ]);
                
                if(depCoords && arrCoords) {
                    pathCoordinates = [depCoords, arrCoords];
                } else if (depCoords) {
                    pathCoordinates = [depCoords, depCoords];
                } else {
                    pathCoordinates = [
                        [45.725, 5.081],
                        [51.148, -0.190]
                    ];
                }
            }

            if (pathCoordinates.length > 0) {
                const flightPath = L.polyline(pathCoordinates, {
                    color: '#f97316', 
                    weight: 3.5,
                    opacity: 0.9,
                    smoothFactor: 1
                }).addTo(map);

                map.fitBounds(flightPath.getBounds(), { padding: [50, 50], maxZoom: 12 });

                // Departure & Arrival Airport Markers
                const startPoint = pathCoordinates[0];
                const endPoint = pathCoordinates[pathCoordinates.length - 1];

                L.circleMarker(startPoint, { 
                    radius: 7, 
                    color: '#38bdf8', 
                    fillColor: '#0284c7', 
                    fillOpacity: 1,
                    weight: 2 
                }).bindPopup(`<strong>Departure:</strong> ${this.depIcao}`).addTo(map);

                L.circleMarker(endPoint, { 
                    radius: 7, 
                    color: '#4ade80', 
                    fillColor: '#16a34a', 
                    fillOpacity: 1,
                    weight: 2 
                }).bindPopup(`<strong>Arrival:</strong> ${this.arrIcao}`).addTo(map);
            }

            // 2. Initialize Chart.js with Real or Interpolated Telemetry
            let chartLabels = [];
            let altitudeData = [];
            let speedData = [];

            if (this.telemetry && this.telemetry.length > 0) {
                this.telemetry.forEach((pt, idx) => {
                    chartLabels.push(pt.time || (idx + ''));
                    altitudeData.push(pt.alt);
                    speedData.push(pt.spd);
                });
            } else {
                const totalPoints = 40;
                for(let i=0; i<=totalPoints; i++) {
                    chartLabels.push(i + 'm');
                    if (i < 8) altitudeData.push(Math.round(i * 4200)); 
                    else if (i > 32) altitudeData.push(Math.round((totalPoints - i) * 4200));
                    else altitudeData.push(35000);
                    
                    if (i < 8) speedData.push(Math.round(150 + (i * 35))); 
                    else if (i > 32) speedData.push(Math.round(150 + ((totalPoints - i) * 35)));
                    else speedData.push(440);
                }
            }

            const ctx = this.$refs.chartContainer.getContext('2d');
            
            if (window.flightProfileChartInstance) {
                window.flightProfileChartInstance.destroy();
            }

            window.flightProfileChartInstance = new Chart(ctx, {
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
                            pointRadius: speedData.length > 50 ? 0 : 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { color: '#9ca3af' } } },
                    scales: {
                        x: { display: false },
                        y: {
                            type: 'linear', display: true, position: 'left',
                            grid: { color: '#273142' }, ticks: { color: '#9ca3af' },
                            title: { display: true, text: 'Altitude (ft)', color: '#9ca3af' }
                        },
                        y1: {
                            type: 'linear', display: true, position: 'right',
                            grid: { drawOnChartArea: false }, ticks: { color: '#9ca3af' },
                            title: { display: true, text: 'Speed (kts)', color: '#9ca3af' }
                        }
                    }
                }
            });
        }
    }"
>
    @if (session()->has('message'))
        <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if($isAdmin && strtolower($pirep->status) === 'submitted')
        <div class="bg-[#181D29] border border-white/10 rounded-xl p-4 flex justify-end gap-3 shadow-lg">
            <button wire:click="accept" class="bg-green-600 hover:bg-green-500 text-white px-5 py-2.5 rounded-lg text-xs font-bold shadow-md transition">
                ✓ Accept PIREP
            </button>
            <button wire:click="invalidate" wire:confirm="Are you sure you want to invalidate this PIREP?" class="bg-red-600 hover:bg-red-500 text-white px-5 py-2.5 rounded-lg text-xs font-bold shadow-md transition">
                ✕ Invalidate PIREP
            </button>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <!-- Left Column (Map & Flight Profile) -->
        <div class="xl:col-span-2 space-y-6">
            
            <!-- PIREP STATUS Card -->
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
                <div class="px-5 py-3 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between items-center">
                    <span>PIREP STATUS</span>
                    <span class="text-xs font-mono text-gray-400">ID: #{{ $pirep->id }}</span>
                </div>
                <div class="p-6 bg-[#12161F] flex justify-between items-center flex-wrap gap-4">
                    <div class="flex items-center gap-6">
                        <div class="text-sm">
                            <span class="text-gray-400 block mb-1 text-xs">Status</span>
                            @if(in_array(strtolower($pirep->status), ['accepted', 'complete', 'approved']))
                                <span class="px-3 py-1 rounded bg-green-500/20 text-green-400 border border-green-500/30 font-bold text-xs">Accepted</span>
                            @elseif(in_array(strtolower($pirep->status), ['rejected', 'invalidated']))
                                <span class="px-3 py-1 rounded bg-red-500/20 text-red-400 border border-red-500/30 font-bold text-xs">Invalidated</span>
                            @else
                                <span class="px-3 py-1 rounded bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 font-bold text-xs">{{ strtoupper($pirep->status) }}</span>
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
            <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl relative" style="height: 480px;" x-init="init()">
                <div id="flightMap" x-ref="mapContainer" wire:ignore class="w-full h-full bg-[#0d111a]"></div>
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

        <!-- Right Column (Comments, Time, Points, Route) -->
        <div class="space-y-6">
            
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

                    @if(!empty($penalties))
                        <div class="border-t border-white/5 pt-3 space-y-2">
                            <span class="text-[10px] text-red-400 uppercase font-bold tracking-wider block">Safety Deductions:</span>
                            @foreach($penalties as $pen)
                                <div class="flex justify-between text-red-300 bg-red-500/10 p-2 rounded border border-red-500/20">
                                    <span>{{ $pen['description'] ?? ($pen['category'] ?? 'Rule Violation') }}</span>
                                    <span class="font-bold">-{{ $pen['points_deducted'] ?? 10 }} pts</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="border-t border-white/5 pt-2 text-green-400 flex items-center gap-1.5">
                            <span>✓</span> Perfect Flight (No safety violations logged)
                        </div>
                    @endif

                    <div class="border-t border-white/10 pt-3 flex justify-between items-center mt-4 text-sm font-sans">
                        <span class="text-gray-300 font-bold">Total Score Awarded:</span>
                        <span class="text-tenant-accent font-mono font-extrabold text-xl">+{{ $scoreVal }} pts</span>
                    </div>
                </div>
            </div>

            <!-- ROUTE & FLIGHT SPECS -->
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

        </div>
    </div>
</div>
