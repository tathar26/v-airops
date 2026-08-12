<div class="space-y-6" x-data="statisticsDashboard({{ json_encode($stats) }})">
    @if(!$stats)
        <div class="bg-black/20 border border-white/10 rounded-lg p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            <h3 class="text-lg font-medium text-white mb-2">No Statistics Available</h3>
            <p class="text-gray-400">Complete your first flight to generate statistics.</p>
        </div>
    @else
        <!-- Doughnut Charts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Aircraft Types -->
            <div class="glass-panel p-5">
                <div class="border-b border-white/10 pb-2 mb-4 relative">
                    <h4 class="text-white font-bold text-sm tracking-wide">AIRCRAFT TYPES</h4>
                    <p class="text-xs text-gray-400">Aircraft Types flown in your PIREPs</p>
                    <div class="absolute top-0 right-0 w-8 h-0.5 bg-tenant-accent"></div>
                </div>
                <div class="relative h-48">
                    <canvas x-ref="chartAircraft"></canvas>
                </div>
            </div>

            <!-- Callsign -->
            <div class="glass-panel p-5">
                <div class="border-b border-white/10 pb-2 mb-4 relative">
                    <h4 class="text-white font-bold text-sm tracking-wide">CALLSIGN</h4>
                    <p class="text-xs text-gray-400">Prefixes used in your PIREPs</p>
                    <div class="absolute top-0 right-0 w-8 h-0.5 bg-tenant-accent"></div>
                </div>
                <div class="relative h-48">
                    <canvas x-ref="chartCallsign"></canvas>
                </div>
            </div>

            <!-- Networks -->
            <div class="glass-panel p-5">
                <div class="border-b border-white/10 pb-2 mb-4 relative">
                    <h4 class="text-white font-bold text-sm tracking-wide">NETWORKS</h4>
                    <p class="text-xs text-gray-400">Networks flown on</p>
                    <div class="absolute top-0 right-0 w-8 h-0.5 bg-tenant-accent"></div>
                </div>
                <div class="relative h-48">
                    <canvas x-ref="chartNetworks"></canvas>
                </div>
            </div>

            <!-- Takeoffs -->
            <div class="glass-panel p-5">
                <div class="border-b border-white/10 pb-2 mb-4 relative">
                    <h4 class="text-white font-bold text-sm tracking-wide">TAKEOFFS</h4>
                    <p class="text-xs text-gray-400">Day/Night takeoff breakdown</p>
                    <div class="absolute top-0 right-0 w-8 h-0.5 bg-tenant-accent"></div>
                </div>
                <div class="relative h-48">
                    <canvas x-ref="chartTakeoffs"></canvas>
                </div>
            </div>

            <!-- Route Types -->
            <div class="glass-panel p-5">
                <div class="border-b border-white/10 pb-2 mb-4 relative">
                    <h4 class="text-white font-bold text-sm tracking-wide">ROUTE TYPES</h4>
                    <p class="text-xs text-gray-400">Route Types of your PIREPs</p>
                    <div class="absolute top-0 right-0 w-8 h-0.5 bg-tenant-accent"></div>
                </div>
                <div class="relative h-48">
                    <canvas x-ref="chartRouteTypes"></canvas>
                </div>
            </div>

            <!-- Simulators -->
            <div class="glass-panel p-5">
                <div class="border-b border-white/10 pb-2 mb-4 relative">
                    <h4 class="text-white font-bold text-sm tracking-wide">SIMULATORS</h4>
                    <p class="text-xs text-gray-400">Sims used in your PIREPs</p>
                    <div class="absolute top-0 right-0 w-8 h-0.5 bg-tenant-accent"></div>
                </div>
                <div class="relative h-48">
                    <canvas x-ref="chartSimulators"></canvas>
                </div>
            </div>

            <!-- Event Flights -->
            <div class="glass-panel p-5">
                <div class="border-b border-white/10 pb-2 mb-4 relative">
                    <h4 class="text-white font-bold text-sm tracking-wide">EVENT FLIGHTS</h4>
                    <p class="text-xs text-gray-400">PIREPs with Bonus Points</p>
                    <div class="absolute top-0 right-0 w-8 h-0.5 bg-tenant-accent"></div>
                </div>
                <div class="relative h-48">
                    <canvas x-ref="chartEvents"></canvas>
                </div>
            </div>

            <!-- Landings -->
            <div class="glass-panel p-5">
                <div class="border-b border-white/10 pb-2 mb-4 relative">
                    <h4 class="text-white font-bold text-sm tracking-wide">LANDINGS</h4>
                    <p class="text-xs text-gray-400">Day/Night landing breakdown</p>
                    <div class="absolute top-0 right-0 w-8 h-0.5 bg-tenant-accent"></div>
                </div>
                <div class="relative h-48">
                    <canvas x-ref="chartLandings"></canvas>
                </div>
            </div>
        </div>

        <!-- Line Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            
            <!-- Flights Per Month -->
            <div class="glass-panel p-5">
                <div class="border-b border-white/10 pb-2 mb-4 relative">
                    <h4 class="text-white font-bold text-sm tracking-wide">FLIGHTS</h4>
                    <p class="text-xs text-gray-400">PIREPs Per Month</p>
                    <div class="absolute top-0 right-0 w-8 h-0.5 bg-tenant-accent"></div>
                </div>
                <div class="relative h-64">
                    <canvas x-ref="chartFlightsLine"></canvas>
                </div>
            </div>

            <!-- Landing Rate Average -->
            <div class="glass-panel p-5">
                <div class="border-b border-white/10 pb-2 mb-4 relative">
                    <h4 class="text-white font-bold text-sm tracking-wide">LANDING RATE</h4>
                    <p class="text-xs text-gray-400">Landing Rate Average</p>
                    <div class="absolute top-0 right-0 w-8 h-0.5 bg-tenant-accent"></div>
                </div>
                <div class="relative h-64">
                    <canvas x-ref="chartLandingRateLine"></canvas>
                </div>
            </div>
        </div>

        <!-- Logbook Table -->
        <div class="glass-panel overflow-hidden">
            <div class="bg-black/30 border-b border-white/10 px-5 py-4">
                <h4 class="text-white font-bold text-sm tracking-wide">LOGBOOK</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10">
                    <thead class="bg-black/20">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-tenant-accent uppercase tracking-wider">AIRCRAFT TYPE</th>
                            <th class="px-5 py-3 text-center text-[10px] font-bold text-tenant-accent uppercase tracking-wider">FLIGHTS</th>
                            <th class="px-5 py-3 text-center text-[10px] font-bold text-tenant-accent uppercase tracking-wider">PASSENGERS</th>
                            <th class="px-5 py-3 text-center text-[10px] font-bold text-tenant-accent uppercase tracking-wider">FREIGHT</th>
                            <th class="px-5 py-3 text-center text-[10px] font-bold text-tenant-accent uppercase tracking-wider">AIR TIME</th>
                            <th class="px-5 py-3 text-center text-[10px] font-bold text-tenant-accent uppercase tracking-wider">FUEL USED</th>
                            <th class="px-5 py-3 text-center text-[10px] font-bold text-tenant-accent uppercase tracking-wider">TAKEOFFS DAY</th>
                            <th class="px-5 py-3 text-center text-[10px] font-bold text-tenant-accent uppercase tracking-wider">TAKEOFFS NIGHT</th>
                            <th class="px-5 py-3 text-center text-[10px] font-bold text-tenant-accent uppercase tracking-wider">LANDINGS DAY</th>
                            <th class="px-5 py-3 text-center text-[10px] font-bold text-tenant-accent uppercase tracking-wider">LANDINGS NIGHT</th>
                            <th class="px-5 py-3 text-center text-[10px] font-bold text-tenant-accent uppercase tracking-wider">AVG FPM</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @if($stats->logbook_json)
                            @foreach($stats->logbook_json as $log)
                            <tr class="hover:bg-white/5 transition-colors text-sm text-gray-300">
                                <td class="px-5 py-4 whitespace-nowrap">{{ $log['type'] }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ $log['flights'] }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ number_format($log['passengers']) }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ number_format($log['freight']) }} kg</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ floor($log['air_time']/60) }}:{{ sprintf('%02d', $log['air_time']%60) }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ number_format($log['fuel_used']) }} kg</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ $log['takeoffs_day'] }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ $log['takeoffs_night'] }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ $log['landings_day'] }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ $log['landings_night'] }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ $log['avg_fpm'] }} FPM</td>
                            </tr>
                            @endforeach
                            <!-- Total Row -->
                            <tr class="bg-black/30 font-bold text-white text-sm">
                                <td class="px-5 py-4 whitespace-nowrap">TOTAL:</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ $stats->total_flights }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ number_format($stats->total_passengers) }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ number_format($stats->total_freight) }} kg</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ floor($stats->total_flight_time/60) }}:{{ sprintf('%02d', $stats->total_flight_time%60) }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ number_format($stats->total_block_fuel) }} kg</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ collect($stats->logbook_json)->sum('takeoffs_day') }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ collect($stats->logbook_json)->sum('takeoffs_night') }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ collect($stats->logbook_json)->sum('landings_day') }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ collect($stats->logbook_json)->sum('landings_night') }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">{{ $stats->avg_landing_rate }} FPM</td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="11" class="px-5 py-8 text-center text-gray-500">No logbook data found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    
    <!-- Alpine JS Component -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('statisticsDashboard', (stats) => ({
                stats: stats,
                chartColors: ['#3b82f6', '#f43f5e', '#f59e0b', '#10b981', '#8b5cf6', '#06b6d4', '#64748b'],
                
                init() {
                    if(!this.stats) return;
                    
                    // Load Chart.js dynamically
                    if (typeof Chart === 'undefined') {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                        script.onload = () => this.drawCharts();
                        document.head.appendChild(script);
                    } else {
                        this.drawCharts();
                    }
                },
                
                drawCharts() {
                    Chart.defaults.color = '#9ca3af';
                    Chart.defaults.font.family = 'Inter, sans-serif';
                    
                    const createDoughnut = (ref, dataObj) => {
                        if(!dataObj || Object.keys(dataObj).length === 0) return;
                        
                        // replace empty string keys with "Unknown"
                        const labels = Object.keys(dataObj).map(k => k === '' ? 'Unknown' : k);
                        const data = Object.values(dataObj);
                        
                        new Chart(this.$refs[ref], {
                            type: 'doughnut',
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: data,
                                    backgroundColor: this.chartColors,
                                    borderWidth: 0,
                                    cutout: '60%'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: { boxWidth: 12, padding: 15, color: '#9ca3af', font: { size: 10 } }
                                    }
                                }
                            }
                        });
                    };

                    createDoughnut('chartAircraft', this.stats.aircraft_types_json);
                    createDoughnut('chartCallsign', this.stats.callsigns_json);
                    createDoughnut('chartNetworks', this.stats.networks_json);
                    createDoughnut('chartTakeoffs', this.stats.takeoffs_json);
                    createDoughnut('chartRouteTypes', this.stats.route_types_json);
                    createDoughnut('chartSimulators', this.stats.simulators_json);
                    createDoughnut('chartEvents', this.stats.events_json);
                    createDoughnut('chartLandings', this.stats.landings_json);

                    // Line Chart - Flights Per Month
                    if(this.stats.flights_per_month_json && Object.keys(this.stats.flights_per_month_json).length > 0) {
                        new Chart(this.$refs.chartFlightsLine, {
                            type: 'line',
                            data: {
                                labels: Object.keys(this.stats.flights_per_month_json),
                                datasets: [{
                                    label: 'Flights',
                                    data: Object.values(this.stats.flights_per_month_json),
                                    borderColor: getComputedStyle(document.documentElement).getPropertyValue('--tenant-accent').trim() || '#3b82f6',
                                    backgroundColor: (getComputedStyle(document.documentElement).getPropertyValue('--tenant-accent').trim() || '#3b82f6') + '1A', // adding opacity
                                    tension: 0.4,
                                    fill: true,
                                    pointBackgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--tenant-accent').trim() || '#3b82f6',
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    }

                    // Line/Scatter Chart - Landing Rate
                    if(this.stats.landing_rate_history_json && this.stats.landing_rate_history_json.length > 0) {
                        new Chart(this.$refs.chartLandingRateLine, {
                            type: 'line',
                            data: {
                                labels: this.stats.landing_rate_history_json.map(l => l.date),
                                datasets: [{
                                    label: 'FPM',
                                    data: this.stats.landing_rate_history_json.map(l => l.fpm),
                                    borderColor: getComputedStyle(document.documentElement).getPropertyValue('--tenant-accent').trim() || '#0ea5e9',
                                    backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--tenant-accent').trim() || '#0ea5e9',
                                    borderWidth: 1,
                                    showLine: false, // Make it look like a scatter or broken line as in the example
                                    pointRadius: 4,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    }
                }
            }));
        });
    </script>
</div>
