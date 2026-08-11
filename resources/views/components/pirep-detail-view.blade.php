@props(['pirep', 'isAdmin' => false])

<div class="max-w-[1600px] mx-auto w-full space-y-6"
    x-data="{
        flightLog: {{ json_encode($pirep->flight_log ?? []) }},
        depIcao: '{{ $pirep->route->departure_icao ?? '' }}',
        arrIcao: '{{ $pirep->route->arrival_icao ?? '' }}',
        
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
            // Prevent re-initialization if Livewire diffs the DOM but Leaflet is already bound
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

            if (this.flightLog && this.flightLog.length > 0) {
                this.flightLog.forEach(point => {
                    if(point.lat && point.lon) {
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
                        [50.819, 4.453],
                        [50.077, 19.784]
                    ];
                }
            }

            if (pathCoordinates.length > 0) {
                const flightPath = L.polyline(pathCoordinates, {
                    color: '#a855f7', 
                    weight: 3,
                    opacity: 0.8,
                    smoothFactor: 1
                }).addTo(map);

                map.fitBounds(flightPath.getBounds(), { padding: [50, 50], maxZoom: 12 });

                L.circleMarker(pathCoordinates[0], { radius: 6, color: '#3b82f6', fillColor: '#3b82f6', fillOpacity: 1 }).addTo(map);
                L.circleMarker(pathCoordinates[pathCoordinates.length - 1], { radius: 6, color: '#22c55e', fillColor: '#22c55e', fillOpacity: 1 }).addTo(map);
            }

            // 2. Initialize Chart.js
            let chartLabels = [];
            let altitudeData = [];
            let speedData = [];

            if (this.flightLog && this.flightLog.length > 0) {
                // process real telemetry
            } else {
                const totalPoints = 50;
                for(let i=0; i<=totalPoints; i++) {
                    chartLabels.push(i + 'm');
                    if (i < 10) altitudeData.push(i * 3500); 
                    else if (i > 40) altitudeData.push((50 - i) * 3500);
                    else altitudeData.push(35000 + (Math.random() * 500));
                    
                    if (i < 10) speedData.push(150 + (i * 30)); 
                    else if (i > 40) speedData.push(150 + ((50 - i) * 30));
                    else speedData.push(450 + (Math.random() * 10));
                }
            }

            const ctx = this.$refs.chartContainer.getContext('2d');
            
            // Destroy existing chart if Livewire re-renders
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
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            yAxisID: 'y',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Groundspeed (kts)',
                            data: speedData,
                            borderColor: '#ef4444',
                            backgroundColor: 'transparent',
                            yAxisID: 'y1',
                            tension: 0.4
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
                            grid: { color: '#3f475a' }, ticks: { color: '#9ca3af' },
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
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md p-4 flex justify-end gap-3 shadow-sm">
            <button wire:click="accept" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm transition">
                Accept PIREP
            </button>
            <button wire:click="invalidate" wire:confirm="Are you sure you want to invalidate this PIREP?" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm transition">
                Invalidate PIREP
            </button>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <!-- Left Column (Map & Flight Profile) -->
        <div class="xl:col-span-2 space-y-6">
            
            <!-- PIREP STATUS Card -->
            <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
                <div class="px-4 py-2 bg-[#2c323f] border-b border-tenant-accent border-t-2 text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                    <span>PIREP STATUS</span>
                </div>
                <div class="p-6 bg-[#212631] flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div class="text-sm">
                            <span class="text-gray-400 block mb-1">Status</span>
                            @if(strtolower($pirep->status) === 'accepted')
                                <span class="px-3 py-1 rounded bg-green-500/10 text-green-400 border border-green-500/20 font-semibold text-xs">Accepted</span>
                            @elseif(strtolower($pirep->status) === 'rejected' || strtolower($pirep->status) === 'invalidated')
                                <span class="px-3 py-1 rounded bg-red-500/10 text-red-400 border border-red-500/20 font-semibold text-xs">Invalidated</span>
                            @else
                                <span class="px-3 py-1 rounded bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 font-semibold text-xs">{{ strtoupper($pirep->status) }}</span>
                            @endif
                        </div>
                        <div class="text-sm pl-4 border-l border-white/10">
                            <span class="text-gray-400 block mb-1">Pilot</span>
                            <span class="text-white font-medium">{{ $pirep->user->name }}</span>
                        </div>
                    </div>
                    <div class="text-sm text-right">
                        <span class="text-gray-400 block mb-1">Rank</span>
                        <span class="text-white font-medium">{{ $pirep->user->pilotProfile->rank->name ?? 'No Rank' }}</span>
                    </div>
                </div>
            </div>

            <!-- Map Card -->
        <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm relative" style="height: 500px;" x-init="init()">
            <div id="flightMap" x-ref="mapContainer" wire:ignore class="w-full h-full bg-[#1a1a1a]"></div>
                
                <!-- Map Overlays -->
                <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex gap-3 z-[1000]">
                    <button class="bg-black/60 hover:bg-black/80 text-white px-4 py-2 rounded-md text-sm backdrop-blur transition border border-white/10 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Show Departure
                    </button>
                    <button class="bg-black/60 hover:bg-black/80 text-white px-4 py-2 rounded-md text-sm backdrop-blur transition border border-white/10 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Show Arrival
                    </button>
                </div>
            </div>

            <!-- Flight Profile Card -->
            <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
                <div class="px-4 py-2 bg-[#2c323f] border-b border-tenant-accent border-t-2 text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                    <span>FLIGHT PROFILE</span>
                </div>
                <div class="p-4 bg-[#212631]" style="height: 300px;">
                    <canvas id="flightProfileChart" x-ref="chartContainer" wire:ignore></canvas>
                </div>
            </div>

        </div>

        <!-- Right Column (Comments, Time, Points, Route) -->
        <div class="space-y-6">
            
            <!-- PIREP COMMENTS -->
            <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
                <div class="px-4 py-2 bg-[#2c323f] border-b border-tenant-accent border-t-2 text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                    <span>PIREP COMMENTS</span>
                </div>
                <div class="p-6 bg-[#212631] space-y-4">
                    @forelse($pirep->comments as $comment)
                        <div class="bg-[#2c323f] p-3 rounded border border-white/5">
                            <div class="flex justify-between items-center mb-2 text-xs">
                                <span class="font-semibold text-white">{{ $comment->user->name }}</span>
                                <span class="text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="text-sm text-gray-300">{{ $comment->comment }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-400 italic mb-4">No comments yet.</div>
                    @endforelse
                    
                    <form wire:submit.prevent="addComment" class="mt-4 border-t border-white/5 pt-4">
                        <textarea wire:model="newComment" rows="2" class="w-full bg-black/20 border border-white/10 rounded-md p-3 text-sm text-white placeholder-gray-500 focus:ring-1 focus:ring-tenant-accent focus:border-tenant-accent focus:outline-none" placeholder="Write a comment..."></textarea>
                        @error('newComment') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        
                        <button type="submit" class="mt-3 w-full bg-tenant-accent hover:bg-tenant-accent/90 text-white px-4 py-2 rounded-md text-sm font-medium transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            Post Comment
                        </button>
                    </form>
                </div>
            </div>

            <!-- TIME -->
            <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
                <div class="px-4 py-2 bg-[#2c323f] border-b border-tenant-accent border-t-2 text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                    <span>TIME</span>
                </div>
                <div class="p-6 bg-[#212631]">
                    <div class="flex justify-between items-center mb-6">
                        <div class="text-center">
                            <span class="block text-xs text-gray-400 mb-1">Airborne</span>
                            <span class="text-sm text-white font-semibold">{{ sprintf('%02d:%02d:00', floor($pirep->flight_time / 60), $pirep->flight_time % 60) }}</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-xs text-gray-400 mb-1 uppercase">Awarded</span>
                            <span class="text-xl text-white font-bold">{{ sprintf('%02d:%02d:00', floor($pirep->flight_time / 60), $pirep->flight_time % 60) }}</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-xs text-gray-400 mb-1">Block</span>
                            <span class="text-sm text-white font-semibold">{{ sprintf('%02d:%02d:00', floor($pirep->flight_time / 60), $pirep->flight_time % 60) }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 border-t border-[#3f475a] pt-4">
                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Scheduled</span>
                            <span class="text-sm text-white font-semibold">{{ $pirep->route->block_time ?? '00:00:00' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Paused Air</span>
                            <span class="text-sm text-white font-semibold">00:00:00</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Paused All</span>
                            <span class="text-sm text-white font-semibold">00:00:00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- POINTS -->
            <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
                <div class="px-4 py-2 bg-[#2c323f] border-b border-tenant-accent border-t-2 text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                    <span>POINTS</span>
                </div>
                <div class="p-6 bg-[#212631] space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-green-400 w-12">+150</span>
                        <span class="text-gray-300 flex-grow">Starting Points</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-green-400 w-12">+{{ $pirep->points_awarded > 150 ? $pirep->points_awarded - 150 : 0 }}</span>
                        <span class="text-gray-300 flex-grow">Flight Bonus</span>
                    </div>
                    <div class="border-t border-[#3f475a] pt-3 flex justify-between items-center mt-4">
                        <span class="text-gray-400 text-sm">Flight Score:</span>
                        <span class="text-white font-bold text-lg">{{ $pirep->points_awarded }}</span>
                    </div>
                </div>
            </div>

            <!-- ROUTE -->
            <div class="bg-[#2c323f] border border-[#3f475a] rounded-md overflow-hidden shadow-sm">
                <div class="px-4 py-2 bg-[#2c323f] border-b border-tenant-accent border-t-2 text-xs text-gray-400 font-semibold tracking-wider flex justify-between">
                    <span>ROUTE</span>
                </div>
                <div class="p-6 bg-[#212631] space-y-4">
                    <div class="flex justify-between">
                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Departure</span>
                            <span class="text-sm text-white font-semibold">{{ $pirep->route->departure_icao ?? 'N/A' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs text-gray-400 mb-1">Arrival</span>
                            <span class="text-sm text-white font-semibold">{{ $pirep->route->arrival_icao ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="flex justify-between">
                        <div>
                            <span class="block text-xs text-gray-400 mb-1">Callsign</span>
                            <span class="text-sm text-white font-semibold">{{ $pirep->route->flight_number ?? 'N/A' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs text-gray-400 mb-1">Flight Number</span>
                            <span class="text-sm text-white font-semibold">{{ $pirep->route->flight_number ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-400 mb-1">Pilot Route</span>
                        <span class="text-sm text-gray-300">{{ $pirep->route->route_string ?? 'DIRECT' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-400 mb-1">Route Remarks</span>
                        <span class="text-sm text-gray-300 uppercase">ROUTE GENERATED AUTOMATICALLY</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @script
    <script>
        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        function loadStylesheet(href) {
            return new Promise((resolve, reject) => {
                const l = document.createElement('link');
                l.rel = 'stylesheet';
                l.href = href;
                l.onload = resolve;
                l.onerror = reject;
                document.head.appendChild(l);
            });
        }

        async function initFlightDashboard() {
            if (typeof L === 'undefined') {
                await loadStylesheet('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
                await loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');
            }
            if (typeof Chart === 'undefined') {
                await loadScript('https://cdn.jsdelivr.net/npm/chart.js');
            }

            // 1. Initialize Leaflet Map
            const map = L.map('flightMap').setView([50.0, 10.0], 4);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(map);

            let flightLog = @json($pirep->flight_log ?? []);
            let pathCoordinates = [];

            async function drawMap() {
                if (flightLog && flightLog.length > 0) {
                    flightLog.forEach(point => {
                        if(point.lat && point.lon) {
                            pathCoordinates.push([point.lat, point.lon]);
                        }
                    });
                } else {
                    let depIcao = "{{ $pirep->route->departure_icao ?? '' }}";
                    let arrIcao = "{{ $pirep->route->arrival_icao ?? '' }}";

                    async function getCoords(icao) {
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
                    }

                    let depCoords = await getCoords(depIcao);
                    let arrCoords = await getCoords(arrIcao);
                    
                    if(depCoords && arrCoords) {
                        pathCoordinates = [depCoords, arrCoords];
                    } else if (depCoords) {
                        pathCoordinates = [depCoords, depCoords];
                    } else {
                        pathCoordinates = [
                            [50.819, 4.453], // Brussels approx fallback
                            [50.077, 19.784] // Krakow approx fallback
                        ];
                    }
                }

                if (pathCoordinates.length > 0) {
                    const flightPath = L.polyline(pathCoordinates, {
                        color: '#a855f7', 
                        weight: 3,
                        opacity: 0.8,
                        smoothFactor: 1
                    }).addTo(map);

                    map.fitBounds(flightPath.getBounds(), { padding: [50, 50], maxZoom: 12 });

                    L.circleMarker(pathCoordinates[0], { radius: 6, color: '#3b82f6', fillColor: '#3b82f6', fillOpacity: 1 }).addTo(map);
                    L.circleMarker(pathCoordinates[pathCoordinates.length - 1], { radius: 6, color: '#22c55e', fillColor: '#22c55e', fillOpacity: 1 }).addTo(map);
                }
            }
            
            drawMap();

            // 2. Initialize Chart.js for Flight Profile
            const ctx = document.getElementById('flightProfileChart').getContext('2d');
            
            let chartLabels = [];
            let altitudeData = [];
            let speedData = [];

            if (flightLog && flightLog.length > 0) {
                // process real telemetry
            } else {
                const totalPoints = 50;
                for(let i=0; i<=totalPoints; i++) {
                    chartLabels.push(i + 'm');
                    if (i < 10) altitudeData.push(i * 3500); 
                    else if (i > 40) altitudeData.push((50 - i) * 3500);
                    else altitudeData.push(35000 + (Math.random() * 500));
                    
                    if (i < 10) speedData.push(150 + (i * 30)); 
                    else if (i > 40) speedData.push(150 + ((50 - i) * 30));
                    else speedData.push(450 + (Math.random() * 10));
                }
            }

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'Altitude (ft)',
                            data: altitudeData,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            yAxisID: 'y',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Groundspeed (kts)',
                            data: speedData,
                            borderColor: '#ef4444',
                            backgroundColor: 'transparent',
                            yAxisID: 'y1',
                            tension: 0.4
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
                            grid: { color: '#3f475a' }, ticks: { color: '#9ca3af' },
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

        initFlightDashboard();
    </script>
    @endscript
</div>
