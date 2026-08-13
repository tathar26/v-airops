<x-app-layout>
    <div class="space-y-6 max-w-[1600px] mx-auto w-full">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">Flights List</h2>
            <div class="flex gap-4">
                <a href="{{ route('flight-centre.book') }}" class="bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white px-4 py-2 rounded-md text-sm font-semibold transition border border-white/5">
                    Map View
                </a>
            </div>
        </div>

        <div class="glass-panel overflow-hidden">
            <!-- Search & Filters -->
            <form action="{{ route('flight-centre.flights') }}" method="GET" class="p-4 border-b border-white/5 flex gap-4 bg-white/5 items-center">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search flights (e.g. LHR, EZY123)..." class="bg-[#212631] border-gray-600 text-white rounded-md w-64 focus:border-tenant-accent focus:ring-tenant-accent text-sm">
                
                <select name="dep" class="bg-[#212631] border-gray-600 text-white rounded-md focus:border-tenant-accent focus:ring-tenant-accent text-sm w-48" onchange="this.form.submit()">
                    <option value="">Departure Airport</option>
                    @foreach($departureIcaos as $icao)
                        <option value="{{ $icao }}" {{ request('dep') == $icao ? 'selected' : '' }}>{{ $icao }}</option>
                    @endforeach
                </select>
                
                <select name="arr" class="bg-[#212631] border-gray-600 text-white rounded-md focus:border-tenant-accent focus:ring-tenant-accent text-sm w-48" onchange="this.form.submit()">
                    <option value="">Arrival Airport</option>
                    @foreach($arrivalIcaos as $icao)
                        <option value="{{ $icao }}" {{ request('arr') == $icao ? 'selected' : '' }}>{{ $icao }}</option>
                    @endforeach
                </select>

                <select name="type" class="bg-[#212631] border-gray-600 text-white rounded-md focus:border-tenant-accent focus:ring-tenant-accent text-sm w-48" onchange="this.form.submit()">
                    <option value="">Route Type</option>
                    <option value="SCHEDULED" {{ request('type') == 'SCHEDULED' ? 'selected' : '' }}>Scheduled</option>
                    <option value="CHARTER" {{ request('type') == 'CHARTER' ? 'selected' : '' }}>Charter</option>
                    <option value="CARGO" {{ request('type') == 'CARGO' ? 'selected' : '' }}>Cargo</option>
                </select>

                <button type="submit" class="bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold transition">Search</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/5">
                    <thead class="bg-black/20">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Flight</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Dep</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Arr</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Block Time</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Distance</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($routes as $route)
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-tenant-accent">{{ $route->callsign ?? $route->flight_number }}</div>
                                <div class="text-xs text-gray-500">{{ $route->operator ?? auth()->user()->tenant->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-white font-bold">{{ $route->departure_icao }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-white font-bold">{{ $route->arrival_icao }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">
                                {{ sprintf('%02d:%02d', floor($route->flight_time / 60), $route->flight_time % 60) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">{{ $route->distance ? $route->distance . ' nm' : 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded bg-white/10 text-gray-300">{{ $route->route_type ?? 'Scheduled' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button onclick="bookFlightDirect({{ $route->id }}, this)" class="bg-tenant-accent hover:opacity-90 text-white px-3 py-1.5 rounded text-sm font-semibold shadow transition flex items-center justify-end gap-1 ml-auto">
                                    Book
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">
                                No flights found matching your criteria.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <script>
                async function bookFlightDirect(routeId, btn) {
                    btn.disabled = true;
                    btn.innerText = 'Booking...';
                    try {
                        let response = await fetch('/api/flight-centre/book', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ route_id: routeId })
                        });
                        let data = await response.json();
                        if (response.ok && data.booking_id) {
                            window.location.href = '/profile/dispatch/' + data.booking_id;
                        } else {
                            btn.disabled = false;
                            btn.innerText = 'Book';
                            alert('Error booking flight: ' + (data.message || data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        btn.disabled = false;
                        btn.innerText = 'Book';
                        alert('An error occurred while booking flight.');
                    }
                }
            </script>
            
            <div class="px-6 py-4 border-t border-white/5 bg-black/10">
                {{ $routes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
