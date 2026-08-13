<x-app-layout>
    <div class="space-y-6 max-w-[1600px] mx-auto w-full" x-data="flightMap('book')">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">Book a Flight</h2>
        </div>
        <div class="glass-panel overflow-hidden rounded-xl border border-white/10 relative w-full h-[75vh]">
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
                <div class="flex-1 bg-black/40 rounded p-2 text-center transition-all" :class="selectedAirport ? 'border-l-4 border-tenant-accent opacity-100' : 'opacity-50'">
                    <div class="text-[10px] text-tenant-accent font-bold uppercase mb-1">Arrival</div>
                    <div class="text-white font-bold" x-text="selectedAirport ? selectedAirport.icao : 'Select Airport'"></div>
                </div>
            </div>

            <button x-show="selectedAirport" @click="openBookingModal()" style="display: none;" class="w-full bg-tenant-accent hover:opacity-80 text-white font-bold py-3 rounded-md transition mb-2 text-sm flex items-center justify-center shadow-lg">
                <span class="mr-2">🛫</span> Book Flight to <span x-text="selectedAirport ? selectedAirport.icao : ''" class="ml-1"></span>
            </button>

            <button x-show="selectedAirport" @click="jumpseat()" style="display: none;" class="w-full bg-white/10 hover:bg-white/20 text-white py-2 rounded-md transition mb-4 text-sm flex items-center justify-center border border-white/20">
                <span class="mr-2">💺</span> Jumpseat to <span x-text="selectedAirport ? selectedAirport.icao : ''" class="ml-1"></span>
            </button>

            <button x-show="!selectedAirport" class="w-full bg-white/5 hover:bg-white/10 text-gray-300 py-2 rounded-md transition mb-6 text-sm">
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
    <div x-show="bookingModalOpen" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div @click.outside="bookingModalOpen = false" class="glass-panel rounded-xl shadow-2xl w-full max-w-md border border-white/10 overflow-hidden">
            <div class="p-4 border-b border-white/10 flex justify-between items-center bg-black/20">
                <h3 class="text-lg font-bold text-white">Select Route</h3>
                <button @click="bookingModalOpen = false" class="text-gray-400 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-300 mb-4">Multiple routes available. Please select the specific flight you wish to book.</p>
                <div class="space-y-3 max-h-60 overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-white/20 scrollbar-track-transparent">
                    <template x-for="route in availableRoutes" :key="route.id">
                        <label class="flex items-center p-3 rounded-lg border cursor-pointer transition-colors"
                            :class="selectedRouteId === route.id ? 'border-tenant-accent bg-tenant-accent/20' : 'border-white/10 bg-black/20 hover:bg-white/5'">
                            <input type="radio" :value="route.id" x-model="selectedRouteId" class="hidden">
                            <div class="flex-1">
                                <div class="font-bold text-white flex justify-between items-center">
                                    <span x-text="route.callsign"></span>
                                    <span class="text-xs font-bold text-tenant-accent bg-tenant-accent/20 px-2 py-0.5 rounded" x-text="route.flight_number"></span>
                                </div>
                            </div>
                            <div class="w-4 h-4 rounded-full border border-tenant-accent flex items-center justify-center ml-3" :class="selectedRouteId === route.id ? 'bg-tenant-accent' : ''"></div>
                        </label>
                    </template>
                </div>
            </div>
            <div class="p-4 border-t border-white/10 bg-black/20 flex justify-end gap-3">
                <button @click="bookingModalOpen = false" class="px-4 py-2 text-sm text-gray-300 hover:text-white transition">Cancel</button>
                <button @click="confirmBooking()" :disabled="!selectedRouteId" class="px-4 py-2 text-sm bg-tenant-accent text-white font-bold rounded hover:bg-opacity-80 transition disabled:opacity-50 disabled:cursor-not-allowed">Confirm Booking</button>
            </div>
        </div>
    </div>
    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        @vite('resources/js/flight-map.js')
    @endpush
</x-app-layout>
