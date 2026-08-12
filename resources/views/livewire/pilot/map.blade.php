    <div class="h-[calc(100vh-160px)] min-h-[600px] w-full relative rounded-lg overflow-hidden border border-white/5" x-init="init()"
    x-data="{
        routes: {{ json_encode($routesData) }},
        airports: {{ json_encode($airports) }},
        showPaths: true,
        showFilters: false,
        
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

        async getCoords(icao) {
            if (this.airports[icao]) {
                return [parseFloat(this.airports[icao].lat), parseFloat(this.airports[icao].lon)];
            }
            try {
                const res = await fetch(`/api/airport/${icao}`);
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.lat && data.lon) {
                        this.airports[icao] = data; // Cache it
                        return [parseFloat(data.lat), parseFloat(data.lon)];
                    }
                }
            } catch(e) { console.error('Map lookup failed for ' + icao, e); }
            return null;
        },

        async init() {
            if (typeof Globe === 'undefined') {
                await this.loadScript('https://unpkg.com/globe.gl');
            }
            this.drawGlobe();
        },

        async drawGlobe() {
            this.arcsDataCache = [];
            const labelsData = [];
            const uniqueAirports = new Set();

            for (const route of this.routes) {
                const [depCoords, arrCoords] = await Promise.all([
                    this.getCoords(route.dep),
                    this.getCoords(route.arr)
                ]);

                if (depCoords && arrCoords) {
                    this.arcsDataCache.push({
                        startLat: depCoords[0],
                        startLng: depCoords[1],
                        endLat: arrCoords[0],
                        endLng: arrCoords[1]
                    });

                    if (!uniqueAirports.has(route.dep)) {
                        labelsData.push({ lat: depCoords[0], lng: depCoords[1], name: route.dep });
                        uniqueAirports.add(route.dep);
                    }
                    if (!uniqueAirports.has(route.arr)) {
                        labelsData.push({ lat: arrCoords[0], lng: arrCoords[1], name: route.arr });
                        uniqueAirports.add(route.arr);
                    }
                }
            }

            const container = this.$refs.globeContainer;
            
            // If already initialized, just update data
            if (this.globeInstance) {
                this.globeInstance.arcsData(this.showPaths ? this.arcsDataCache : []);
                this.globeInstance.labelsData(labelsData);
                return;
            }

            container.innerHTML = '';

            this.globeInstance = Globe()(container)
                .globeImageUrl('https://unpkg.com/three-globe/example/img/earth-dark.jpg')
                .bumpImageUrl('https://unpkg.com/three-globe/example/img/earth-topology.png')
                .backgroundColor('rgba(0,0,0,0)')
                .arcsData(this.showPaths ? this.arcsDataCache : [])
                .arcStartLat(d => d.startLat)
                .arcStartLng(d => d.startLng)
                .arcEndLat(d => d.endLat)
                .arcEndLng(d => d.endLng)
                .arcColor(() => '#3b82f6')
                .arcStroke(0.5)
                .labelsData(labelsData)
                .labelLat(d => d.lat)
                .labelLng(d => d.lng)
                .labelText(d => d.name)
                .labelSize(1.5)
                .labelDotRadius(0.5)
                .labelColor(() => '#60a5fa')
                .labelResolution(2);
            
            // Focus on Europe/Default location
            this.globeInstance.pointOfView({ lat: 45, lng: 10, altitude: 2 });

            window.addEventListener('resize', () => {
                this.globeInstance.width(container.clientWidth);
                this.globeInstance.height(container.clientHeight);
            });
        },

        togglePaths() {
            this.showPaths = !this.showPaths;
            if (this.globeInstance) {
                this.globeInstance.arcsData(this.showPaths ? this.arcsDataCache : []);
            }
        },

        toggleFilters() {
            this.showFilters = !this.showFilters;
        },

        updateGlobeData(event) {
            if (event.detail.routes && event.detail.airports) {
                this.routes = event.detail.routes;
                Object.assign(this.airports, event.detail.airports); // Merge new airports
                this.drawGlobe(); // Re-draw
            }
        }
    }"
    @update-globe-data.window="updateGlobeData($event)"
>
    <!-- Globe Canvas -->
    <div x-ref="globeContainer" wire:ignore class="w-full h-full bg-[#0a0a0a]"></div>

    <!-- UI Overlays -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-[1000] flex gap-4 pointer-events-none">
        <div class="bg-[#2c323f]/90 backdrop-blur border border-[#3f475a] rounded-md p-2 flex gap-2 shadow-xl pointer-events-auto">
            <button @click="toggleFilters()" class="bg-[#212631] hover:bg-tenant-accent text-white border border-[#3f475a] hover:border-tenant-accent px-4 py-2 rounded text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                Filters
            </button>
            <button @click="togglePaths()" class="bg-[#212631] hover:bg-tenant-accent text-white border border-[#3f475a] hover:border-tenant-accent px-4 py-2 rounded text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                <span x-text="showPaths ? 'Hide Paths' : 'Show Paths'"></span>
            </button>
        </div>
    </div>

    <!-- Filter Modal/Panel -->
    <div x-show="showFilters" style="display: none;" class="absolute top-4 right-4 bg-[#2c323f] border border-[#3f475a] rounded-lg shadow-2xl p-6 w-80 z-[1000]">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-white font-bold text-lg">Map Filters</h3>
            <button @click="toggleFilters()" class="text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Aircraft Type</label>
                <select wire:model.live="aircraftFilter" class="w-full bg-black/20 border border-white/10 rounded-md text-white text-sm focus:border-tenant-accent focus:ring-1 focus:ring-tenant-accent focus:outline-none">
                    <option value="">All Aircraft</option>
                    @foreach($availableAircraft as $airframe)
                        <option value="{{ $airframe->id }}">
                            {{ $airframe->aircraftType->name ?? 'Unknown' }} ({{ $airframe->registration }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Date Range</label>
                <select wire:model.live="dateRangeFilter" class="w-full bg-black/20 border border-white/10 rounded-md text-white text-sm focus:border-tenant-accent focus:ring-1 focus:ring-tenant-accent focus:outline-none">
                    <option value="all">All Time</option>
                    <option value="30">Last 30 Days</option>
                    <option value="7">Last 7 Days</option>
                </select>
            </div>
            
            <div wire:loading class="text-xs text-gray-400 text-center w-full mt-2">
                Updating map...
            </div>
        </div>
    </div>
</div>
