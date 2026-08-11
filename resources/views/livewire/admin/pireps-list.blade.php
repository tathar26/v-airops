<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-white">PIREP Management</h2>
    </div>

    <!-- Filters placeholder (matching screenshot design) -->
    <div class="bg-[#2c323f] border border-[#3f475a] rounded-t-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-gray-300 font-semibold text-sm">Filters</h3>
            <button class="text-tenant-accent text-sm font-semibold hover:opacity-80">Reset</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Departure</label>
                <select class="w-full bg-[#1e232f] border-gray-600 text-white rounded-md text-sm"><option>Select an option</option></select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Arrival</label>
                <select class="w-full bg-[#1e232f] border-gray-600 text-white rounded-md text-sm"><option>Select an option</option></select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Fleet</label>
                <select class="w-full bg-[#1e232f] border-gray-600 text-white rounded-md text-sm"><option>Select an option</option></select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">PIREP Type</label>
                <select class="w-full bg-[#1e232f] border-gray-600 text-white rounded-md text-sm"><option>Select an option</option></select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">PIREP Status</label>
                <select class="w-full bg-[#1e232f] border-gray-600 text-white rounded-md text-sm"><option>All</option></select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-[#212631] border-x border-b border-[#3f475a] rounded-b-md overflow-x-auto">
        <table class="min-w-full divide-y divide-white/5">
            <thead class="bg-[#2c323f]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 tracking-wider">Pilot</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 tracking-wider">Callsign</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 tracking-wider">Departure</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 tracking-wider">Arrival</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 tracking-wider">Aircraft</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-400 tracking-wider">FPM</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-400 tracking-wider">Time</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-400 tracking-wider">R-Pts</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-400 tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-400 tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#3f475a]">
                @forelse($pireps as $pirep)
                <tr class="hover:bg-[#2c323f] transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-white font-medium">
                        {{ $pirep->user->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        {{ $pirep->route->flight_number ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        {{ $pirep->route->departure_icao ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        {{ $pirep->route->arrival_icao ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                        {{ $pirep->airframe->registration ?? 'N/A' }}<br>
                        <span class="text-xs">{{ $pirep->airframe->aircraftType->name ?? '' }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 text-center">
                        {{ $pirep->touchdown_rate_fpm ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 text-center">
                        {{ sprintf('%02d:%02d', floor($pirep->flight_time / 60), $pirep->flight_time % 60) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 text-center">
                        {{ $pirep->points_awarded }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if(strtolower($pirep->status) === 'accepted')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-500/10 text-green-400 border border-green-500/20">
                                ACCEPTED
                            </span>
                        @elseif(strtolower($pirep->status) === 'rejected' || strtolower($pirep->status) === 'invalidated')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                INVALIDATED
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">
                                {{ strtoupper($pirep->status) }}
                            </span>
                        @endif
                        <br>
                        <span class="text-xs text-gray-500 mt-1 block">{{ $pirep->created_at->format('jS M y H:i') }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('pireps.show', $pirep->id) }}" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-6 py-8 text-center text-gray-400">
                        No PIREPs found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
