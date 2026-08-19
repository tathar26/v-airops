document.addEventListener('alpine:init', () => {
    Alpine.data('liveFlightMap', (initialFlights = []) => ({
        flights: initialFlights,
        flightsCount: initialFlights.length,
        selectedFlightId: null,
        selectedFlight: null,
        lastUpdated: new Date().toISOString().substring(11, 16) + 'z',
        isLoading: false,
        autoRefreshInterval: null,

        map: null,
        airplaneMarkersLayer: null,
        routesLayer: null,
        airportsLayer: null,
        markersMap: {},

        init() {
            setTimeout(() => {
                this.initMap();
                if (this.flights.length === 0) {
                    this.fetchLiveFlights();
                } else {
                    this.renderMap();
                }
            }, 100);

            // Auto-refresh every 30 seconds
            this.autoRefreshInterval = setInterval(() => {
                this.fetchLiveFlights(true);
            }, 30000);
        },

        destroy() {
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
            }
        },

        initMap() {
            const mapContainer = document.getElementById('live-flight-map');
            if (!mapContainer) return;

            // Initialize Leaflet Map with dark theme
            this.map = L.map('live-flight-map', {
                zoomControl: false,
                attributionControl: false,
                worldCopyJump: true,
                minZoom: 2,
                maxZoom: 18
            }).setView([48.5, 4.0], 4);

            // CartoDB Dark Matter base layer
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(this.map);

            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            this.routesLayer = L.layerGroup().addTo(this.map);
            this.airportsLayer = L.layerGroup().addTo(this.map);
            this.airplaneMarkersLayer = L.layerGroup().addTo(this.map);

            this.map.on('click', () => {
                this.selectedFlightId = null;
                this.selectedFlight = null;
                this.renderRoutes();
            });
        },

        async fetchLiveFlights(isBackground = false) {
            if (!isBackground) this.isLoading = true;
            try {
                const response = await fetch('/api/flight-centre/live-flights', {
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.flights = data.flights || [];
                    this.flightsCount = data.count ?? this.flights.length;
                    this.lastUpdated = data.timestamp || (new Date().toISOString().substring(11, 16) + 'z');
                    this.renderMap();
                }
            } catch (err) {
                console.error("Failed to fetch live flights:", err);
            } finally {
                this.isLoading = false;
            }
        },

        renderMap() {
            if (!this.map) return;

            this.airplaneMarkersLayer.clearLayers();
            this.airportsLayer.clearLayers();
            this.markersMap = {};

            const bounds = L.latLngBounds();

            this.flights.forEach(flight => {
                if (!flight.latitude || !flight.longitude) return;

                const latLng = [flight.latitude, flight.longitude];
                bounds.extend(latLng);

                // Create airplane icon rotated by flight heading
                const heading = flight.heading_deg || 0;
                const iconHtml = `
                    <div class="aircraft-marker-wrap" style="transform: rotate(${heading}deg);">
                        <svg class="aircraft-svg-icon" viewBox="0 0 24 24">
                            <path fill="#facc15" stroke="#854d0e" stroke-width="0.8" d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                        </svg>
                    </div>
                `;

                const airplaneIcon = L.divIcon({
                    html: iconHtml,
                    className: 'custom-aircraft-marker',
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                    popupAnchor: [0, -14]
                });

                const marker = L.marker(latLng, { icon: airplaneIcon });

                // Construct rich hover tooltip card matching user's reference design
                const tooltipHtml = `
                    <div class="va-tooltip-card">
                        <div class="va-tt-header">
                            <div class="va-tt-callsign">${flight.callsign}</div>
                            <div class="va-tt-flightnum">${flight.flight_number || ''}</div>
                        </div>
                        <div class="va-tt-pilot">${flight.pilot_name} (${flight.pilot_id})</div>
                        <div class="va-tt-route">
                            <span class="va-tt-icao">${flight.departure_icao}</span>
                            <span class="va-tt-arrow">┄✈┄</span>
                            <span class="va-tt-icao">${flight.arrival_icao}</span>
                        </div>
                        <div class="va-tt-grid">
                            <div class="va-tt-col">
                                <span class="va-tt-lbl">Aircraft</span>
                                <span class="va-tt-val">${flight.aircraft_type}</span>
                            </div>
                            <div class="va-tt-col">
                                <span class="va-tt-lbl">Altitude</span>
                                <span class="va-tt-val">${flight.altitude_ft ? Number(flight.altitude_ft).toLocaleString() + ' ft' : flight.flight_level}</span>
                            </div>
                            <div class="va-tt-col">
                                <span class="va-tt-lbl">Status</span>
                                <span class="va-tt-val va-status-${(flight.status || '').toLowerCase()}">${flight.status}</span>
                            </div>
                            <div class="va-tt-col">
                                <span class="va-tt-lbl">Speed</span>
                                <span class="va-tt-val">${flight.ground_speed_kt} kts</span>
                            </div>
                            <div class="va-tt-col">
                                <span class="va-tt-lbl">Heading</span>
                                <span class="va-tt-val">${flight.heading_deg}°</span>
                            </div>
                            <div class="va-tt-col">
                                <span class="va-tt-lbl">Network</span>
                                <span class="va-tt-val va-network-${(flight.network || '').toLowerCase()}">${flight.network}</span>
                            </div>
                        </div>
                    </div>
                `;

                marker.bindTooltip(tooltipHtml, {
                    direction: 'top',
                    offset: [0, -12],
                    className: 'va-aircraft-tooltip',
                    opacity: 1
                });

                marker.on('click', (e) => {
                    L.DomEvent.stopPropagation(e);
                    this.selectFlight(flight);
                });

                marker.addTo(this.airplaneMarkersLayer);
                this.markersMap[flight.id] = marker;
            });

            this.renderRoutes();

            // Fit map to flights bounds if available
            if (bounds.isValid() && this.flights.length > 0) {
                this.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 6 });
            }
        },

        renderRoutes() {
            this.routesLayer.clearLayers();
            this.airportsLayer.clearLayers();

            this.flights.forEach(flight => {
                const isSelected = this.selectedFlightId === flight.id;
                
                if (flight.dep_lat && flight.arr_lat) {
                    const isFocus = this.selectedFlightId ? isSelected : true;
                    if (!isFocus && this.selectedFlightId) return;

                    const routeColor = isSelected ? '#facc15' : 'rgba(250, 204, 21, 0.25)';
                    const routeWeight = isSelected ? 2.5 : 1.5;
                    const dashArray = isSelected ? null : '4, 6';

                    // Draw route line
                    const line = L.polyline([
                        [flight.dep_lat, flight.dep_lon],
                        [flight.latitude, flight.longitude],
                        [flight.arr_lat, flight.arr_lon]
                    ], {
                        color: routeColor,
                        weight: routeWeight,
                        opacity: isSelected ? 0.9 : 0.4,
                        dashArray: dashArray
                    });
                    line.addTo(this.routesLayer);

                    // Draw origin & destination airport dots if selected or visible
                    if (isSelected) {
                        const depDot = L.circleMarker([flight.dep_lat, flight.dep_lon], {
                            radius: 4,
                            fillColor: '#38bdf8',
                            color: '#ffffff',
                            weight: 1.5,
                            fillOpacity: 0.9
                        }).bindTooltip(`${flight.departure_icao} (${flight.departure_name || 'Origin'})`, { direction: 'top', className: 'va-airport-tooltip' });

                        const arrDot = L.circleMarker([flight.arr_lat, flight.arr_lon], {
                            radius: 4,
                            fillColor: '#4ade80',
                            color: '#ffffff',
                            weight: 1.5,
                            fillOpacity: 0.9
                        }).bindTooltip(`${flight.arrival_icao} (${flight.arrival_name || 'Destination'})`, { direction: 'top', className: 'va-airport-tooltip' });

                        depDot.addTo(this.airportsLayer);
                        arrDot.addTo(this.airportsLayer);
                    }
                }
            });
        },

        selectFlight(flight) {
            this.selectedFlightId = flight.id;
            this.selectedFlight = flight;
            this.renderRoutes();

            if (this.map && flight.latitude && flight.longitude) {
                this.map.flyTo([flight.latitude, flight.longitude], 6, {
                    duration: 1.2
                });

                const marker = this.markersMap[flight.id];
                if (marker) {
                    marker.openTooltip();
                }
            }

            // Scroll flight table into view if needed
            const rowElem = document.getElementById('flight-row-' + flight.id);
            if (rowElem) {
                rowElem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }));
});
