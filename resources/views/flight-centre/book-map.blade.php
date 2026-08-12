<x-app-layout>
    <div class="space-y-6 max-w-[1600px] mx-auto w-full">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">Book a Flight</h2>
        </div>
        <div class="glass-panel overflow-hidden rounded-xl border border-white/10 relative w-full h-[75vh]" x-data="flightMap('book')">
            <!-- Map Container -->
            <div id="map" class="absolute inset-0 z-0 bg-black/20 rounded-xl"></div>

        <!-- Sidebar Overlay -->
        <div class="absolute top-4 left-4 z-10 w-80 glass-panel rounded-lg shadow-2xl p-4 max-h-[calc(100vh-96px)] overflow-y-auto">
            <h2 class="text-xl font-bold text-white mb-1">Book a Flight</h2>
            <p class="text-gray-400 text-sm mb-4">Select destination airport</p>

            <div class="flex gap-2 mb-4">
                <div class="flex-1 bg-black/40 border-l-4 border-tenant-accent rounded p-2 text-center">
                    <div class="text-[10px] text-tenant-accent font-bold uppercase mb-1">Current Location</div>
                    <div class="text-white font-bold" x-text="currentAirport ? currentAirport.icao : '...'"></div>
                </div>
                <div class="flex-1 bg-black/40 rounded p-2 text-center transition-all" :class="selectedAirport ? 'border-l-4 border-green-400 opacity-100' : 'opacity-50'">
                    <div class="text-[10px] text-green-400 font-bold uppercase mb-1">Arrival</div>
                    <div class="text-white font-bold" x-text="selectedAirport ? selectedAirport.icao : 'Select Airport'"></div>
                </div>
            </div>

            <button class="w-full bg-white/5 hover:bg-white/10 text-gray-300 py-2 rounded-md transition mb-6 text-sm">
                <span class="mr-2">↑↓</span> Pick Random Destination
            </button>

            <!-- Overlays -->
            <div class="space-y-1 mb-6">
                <template x-for="toggle in toggles" :key="toggle.id">
                    <div class="flex justify-between items-center py-2 border-b border-white/5 last:border-0">
                        <div class="flex items-center text-gray-300 text-sm">
                            <span x-html="toggle.icon" class="mr-2 w-4 h-4"></span>
                            <span x-text="toggle.label"></span>
                        </div>
                        <button @click="toggleLayer(toggle.id)" :class="toggle.active ? 'bg-tenant-accent text-white' : 'bg-white/10 text-gray-400'" class="px-2 py-0.5 rounded text-xs transition">
                            <span x-text="toggle.active ? 'ON' : 'OFF'"></span>
                        </button>
                    </div>
                </template>
            </div>

            <div class="space-y-2">
                <button class="w-full bg-white/5 hover:bg-white/10 text-tenant-accent py-2 rounded-md transition text-sm flex items-center justify-center font-semibold">
                    <span class="mr-2">ⓘ</span> <span x-text="(currentAirport ? currentAirport.icao : '') + ' Info'"></span>
                </button>
                <button @click="resetMap()" class="w-full bg-white/5 hover:bg-white/10 text-gray-400 py-2 rounded-md transition text-sm flex items-center justify-center font-semibold">
                    <span class="mr-2">↺</span> Reset Map
                </button>
            </div>
        </div>

        <!-- Tooltip Template -->
        <div id="map-tooltip" class="hidden absolute z-20 glass-panel p-3 rounded-lg shadow-lg pointer-events-none transform -translate-x-1/2 -translate-y-full mt-[-10px]">
            <div class="flex justify-between items-start mb-1">
                <div class="font-bold text-white text-lg"><span id="tt-icao"></span> <span class="text-gray-400 text-sm font-normal" id="tt-name"></span></div>
                <div id="tt-badge" class="hidden bg-red-500/20 text-red-400 text-[10px] px-1.5 py-0.5 rounded ml-2 font-bold uppercase">Base</div>
            </div>
            <div class="text-gray-400 text-xs flex gap-3">
                <div title="Elevation"><span id="tt-elev"></span> ft</div>
                <div title="Connections"><span id="tt-conn"></span> connections</div>
                <div title="Distance"><span id="tt-dist"></span> nm from <span id="tt-from"></span></div>
            </div>
            <div class="mt-2 text-tenant-accent text-xs font-semibold uppercase text-center" id="tt-action">CLICK TO SELECT ARRIVAL</div>
        </div>
        </div>
    </div>
    
    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        @vite('resources/js/flight-map.js')
    @endpush
</x-app-layout>
