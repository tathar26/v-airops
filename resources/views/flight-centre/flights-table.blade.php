<x-app-layout>
    <div class="space-y-6 max-w-[1600px] mx-auto w-full">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white tracking-tight">Flight Centre</h2>
            <div class="flex gap-4">
                <a href="{{ route('flight-centre.book') }}" class="bg-[#12161F] hover:bg-white/10 text-gray-300 hover:text-white px-4 py-2 rounded-xl text-xs font-bold transition border border-white/10 shadow flex items-center gap-2">
                    <svg class="w-4 h-4 text-tenant-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    Interactive Map View
                </a>
            </div>
        </div>

        <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
            <div class="px-6 py-3.5 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between items-center">
                <span>AVAILABLE ROUTE DISPATCH NETWORK</span>
                <span class="text-xs text-gray-400 font-mono font-semibold">Total Routes: {{ $routes->total() }}</span>
            </div>

            <!-- Search & Filters -->
            <form action="{{ route('flight-centre.flights') }}" method="GET" class="p-4 border-b border-white/5 flex flex-wrap gap-4 bg-[#12161F] items-center">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search flights (e.g. LHR, EZY123)..." class="bg-[#0a0d14] border border-white/10 text-white rounded-lg w-64 focus:border-tenant-accent focus:outline-none text-xs p-2.5">
                
                <select name="dep" class="bg-[#0a0d14] border border-white/10 text-white rounded-lg focus:border-tenant-accent focus:outline-none text-xs w-48 p-2.5" onchange="this.form.submit()">
                    <option value="">Departure Airport</option>
                    @foreach($departureIcaos as $icao)
                        <option value="{{ $icao }}" {{ request('dep') == $icao ? 'selected' : '' }}>{{ $icao }}</option>
                    @endforeach
                </select>
                
                <select name="arr" class="bg-[#0a0d14] border border-white/10 text-white rounded-lg focus:border-tenant-accent focus:outline-none text-xs w-48 p-2.5" onchange="this.form.submit()">
                    <option value="">Arrival Airport</option>
                    @foreach($arrivalIcaos as $icao)
                        <option value="{{ $icao }}" {{ request('arr') == $icao ? 'selected' : '' }}>{{ $icao }}</option>
                    @endforeach
                </select>

                <select name="type" class="bg-[#0a0d14] border border-white/10 text-white rounded-lg focus:border-tenant-accent focus:outline-none text-xs w-48 p-2.5" onchange="this.form.submit()">
                    <option value="">Route Type</option>
                    <option value="SCHEDULED" {{ request('type') == 'SCHEDULED' ? 'selected' : '' }}>Scheduled</option>
                    <option value="CHARTER" {{ request('type') == 'CHARTER' ? 'selected' : '' }}>Charter</option>
                    <option value="CARGO" {{ request('type') == 'CARGO' ? 'selected' : '' }}>Cargo</option>
                </select>

                <button type="submit" class="bg-tenant-accent hover:opacity-90 text-white px-4 py-2.5 rounded-lg text-xs font-bold transition shadow">Search</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/5">
                    <thead class="bg-[#181D29]">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Flight</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Dep</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Arr</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Block Time</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Distance</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 bg-[#12161F]">
                        @forelse($routes as $route)
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-tenant-accent">{{ $route->callsign ?? $route->flight_number }}</div>
                                <div class="text-xs text-gray-500">{{ $route->operator ?? auth()->user()->tenant->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sky-400 font-mono font-bold">{{ $route->departure_icao }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sky-400 font-mono font-bold">{{ $route->arrival_icao }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300 font-mono">
                                {{ sprintf('%02d:%02d', floor($route->flight_time / 60), $route->flight_time % 60) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300 font-mono">{{ $route->distance ? $route->distance . ' nm' : 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded bg-white/10 text-gray-300 border border-white/10">{{ $route->route_type ?? 'Scheduled' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button onclick="bookFlightDirect({{ $route->id }}, this)" class="bg-tenant-accent hover:opacity-90 text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow transition flex items-center justify-end gap-1 ml-auto">
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
                        } else if (data.redirect) {
                            alert(data.error || 'You must read and acknowledge all active NOTAMs before booking a flight.');
                            window.location.href = data.redirect;
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
            
            <div class="px-6 py-4 border-t border-white/5 bg-[#181D29]">
                {{ $routes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
