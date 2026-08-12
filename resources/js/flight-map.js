document.addEventListener('alpine:init', () => {
    Alpine.data('flightMap', (mode = 'book') => ({
        mode: mode,
        map: null,
        currentLayer: null,
        destinationsLayer: null,
        routesLayer: null,
        hubsLayer: null,
        currentAirport: null,
        destinations: [],
        routes: [],
        hubs: [],
        airports: [], // for network mode
        
        toggles: [
            { id: 'routes', label: 'Flight Routes', icon: '〰️', active: true },
            { id: 'destinations', label: 'Airports', icon: '✈️', active: true }
        ],

        init() {
            // Wait a tick for the DOM to render the map container
            setTimeout(() => {
                this.initMap();
                this.fetchData();
            }, 100);
        },

        initMap() {
            // Use CartoDB Dark Matter tiles for a sleek dark theme
            this.map = L.map('map', {
                zoomControl: false, // We'll add custom one or none
                attributionControl: false
            }).setView([40, 0], 3);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(this.map);
            
            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            this.currentLayer = L.layerGroup().addTo(this.map);
            this.destinationsLayer = L.layerGroup().addTo(this.map);
            this.routesLayer = L.layerGroup().addTo(this.map);
            this.hubsLayer = L.layerGroup().addTo(this.map);
        },

        async fetchData() {
            try {
                let url = this.mode === 'book' ? '/api/flight-centre/destinations' : '/api/flight-centre/network';
                let response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + document.querySelector('meta[name="csrf-token"]')?.content // For Sanctum cookie based auth, usually fetch handles it if same origin
                    }
                });
                
                let data = await response.json();

                if (this.mode === 'book') {
                    this.currentAirport = data.current;
                    this.destinations = data.destinations;
                    this.routes = data.routes;
                    this.renderBookMap();
                } else {
                    this.airports = data.airports;
                    this.routes = data.routes;
                    this.hubs = data.hubs;
                    this.renderNetworkMap();
                }

            } catch (error) {
                console.error("Error fetching map data:", error);
            }
        },

        getAirport(icao) {
            if (this.mode === 'book') {
                if (this.currentAirport?.icao === icao) return this.currentAirport;
                return this.destinations.find(a => a.icao === icao);
            } else {
                return this.airports.find(a => a.icao === icao);
            }
        },

        renderBookMap() {
            if (!this.currentAirport) return;

            // Draw current airport
            this.drawMarker(this.currentAirport, 'current');

            // Draw destinations
            this.destinations.forEach(airport => {
                this.drawMarker(airport, 'destination');
            });

            // Draw routes (curved lines)
            this.routes.forEach(route => {
                let dest = this.getAirport(route.arrival_icao);
                if (dest) {
                    this.drawRoute(this.currentAirport, dest);
                }
            });

            // Fit bounds
            if (this.destinations.length > 0) {
                let bounds = L.latLngBounds([this.currentAirport.lat, this.currentAirport.lon]);
                this.destinations.forEach(d => bounds.extend([d.lat, d.lon]));
                this.map.fitBounds(bounds, { padding: [50, 50] });
            } else {
                this.map.setView([this.currentAirport.lat, this.currentAirport.lon], 5);
            }
        },

        renderNetworkMap() {
            this.airports.forEach(airport => {
                let isHub = this.hubs.includes(airport.id);
                this.drawMarker(airport, isHub ? 'hub' : 'destination');
            });

            this.routes.forEach(route => {
                let dep = this.getAirport(route.departure_icao);
                let arr = this.getAirport(route.arrival_icao);
                if (dep && arr) {
                    this.drawRoute(dep, arr);
                }
            });

            if (this.airports.length > 0) {
                let bounds = L.latLngBounds(this.airports.map(a => [a.lat, a.lon]));
                this.map.fitBounds(bounds, { padding: [50, 50] });
            }
        },

        drawMarker(airport, type) {
            let color = type === 'current' ? '#f97316' : (type === 'hub' ? '#ef4444' : '#60a5fa');
            let radius = type === 'destination' ? 4 : 6;
            let weight = type === 'current' || type === 'hub' ? 2 : 1;

            let marker = L.circleMarker([airport.lat, airport.lon], {
                radius: radius,
                fillColor: color,
                color: '#ffffff',
                weight: weight,
                opacity: 1,
                fillOpacity: 0.8
            });

            // Hover tooltip
            marker.on('mouseover', (e) => this.showTooltip(e, airport, type));
            marker.on('mouseout', () => this.hideTooltip());

            if (type === 'current') {
                marker.addTo(this.currentLayer);
            } else if (type === 'hub') {
                marker.addTo(this.hubsLayer);
            } else {
                marker.addTo(this.destinationsLayer);
            }
        },

        drawRoute(from, to) {
            // Using Leaflet.Geodesic for curved lines
            let route = new L.Geodesic([
                [from.lat, from.lon],
                [to.lat, to.lon]
            ], {
                weight: 2,
                opacity: 0.3,
                color: '#f97316',
                steps: 50
            });
            route.addTo(this.routesLayer);
        },

        showTooltip(e, airport, type) {
            const tooltip = document.getElementById('map-tooltip');
            if (!tooltip) return;

            document.getElementById('tt-icao').innerText = airport.icao;
            document.getElementById('tt-name').innerText = airport.name || '';
            document.getElementById('tt-elev').innerText = airport.elevation || '0';
            
            // Calculate connections
            let connections = this.routes.filter(r => r.departure_icao === airport.icao || r.arrival_icao === airport.icao).length;
            document.getElementById('tt-conn').innerText = connections;

            if (this.mode === 'book' && this.currentAirport && airport.icao !== this.currentAirport.icao) {
                document.getElementById('tt-from').innerText = this.currentAirport.icao;
                document.getElementById('tt-dist').innerText = Math.round(this.map.distance([this.currentAirport.lat, this.currentAirport.lon], [airport.lat, airport.lon]) / 1852); // Convert meters to nm
                document.getElementById('tt-action').style.display = 'block';
            } else {
                document.getElementById('tt-from').innerText = '...';
                document.getElementById('tt-dist').innerText = '0';
                document.getElementById('tt-action').style.display = 'none';
            }

            if (type === 'hub') {
                document.getElementById('tt-badge').style.display = 'inline-block';
            } else {
                document.getElementById('tt-badge').style.display = 'none';
            }

            // Position tooltip
            let point = this.map.latLngToContainerPoint(e.latlng);
            tooltip.style.left = point.x + 'px';
            tooltip.style.top = point.y + 'px';
            tooltip.classList.remove('hidden');
        },

        hideTooltip() {
            const tooltip = document.getElementById('map-tooltip');
            if (tooltip) tooltip.classList.add('hidden');
        },

        toggleLayer(id) {
            let toggle = this.toggles.find(t => t.id === id);
            if (!toggle) return;
            
            toggle.active = !toggle.active;
            
            let layer = id === 'routes' ? this.routesLayer : this.destinationsLayer;
            
            if (toggle.active) {
                this.map.addLayer(layer);
            } else {
                this.map.removeLayer(layer);
            }
        },

        resetMap() {
            if (this.mode === 'book' && this.currentAirport && this.destinations.length > 0) {
                let bounds = L.latLngBounds([this.currentAirport.lat, this.currentAirport.lon]);
                this.destinations.forEach(d => bounds.extend([d.lat, d.lon]));
                this.map.flyToBounds(bounds, { padding: [50, 50], duration: 1.5 });
            } else if (this.mode === 'network' && this.airports.length > 0) {
                let bounds = L.latLngBounds(this.airports.map(a => [a.lat, a.lon]));
                this.map.flyToBounds(bounds, { padding: [50, 50], duration: 1.5 });
            }
        }
    }));
});
