<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-white">PIREP Management</h2>
    </div>

    <!-- Filters -->
    <div class="va-card mb-6">
        <div class="va-card-header flex justify-between items-center">
            <span>Filters</span>
            <button class="text-tenant-accent text-xs font-semibold hover:opacity-80">Reset</button>
        </div>
        <div class="va-card-body">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Departure</label>
                    <select class="w-full bg-[#111827] border-gray-700 text-white rounded-md text-xs"><option>Select an option</option></select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Arrival</label>
                    <select class="w-full bg-[#111827] border-gray-700 text-white rounded-md text-xs"><option>Select an option</option></select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Fleet</label>
                    <select class="w-full bg-[#111827] border-gray-700 text-white rounded-md text-xs"><option>Select an option</option></select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">PIREP Type</label>
                    <select class="w-full bg-[#111827] border-gray-700 text-white rounded-md text-xs"><option>Select an option</option></select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">PIREP Status</label>
                    <select class="w-full bg-[#111827] border-gray-700 text-white rounded-md text-xs"><option>All</option></select>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="va-card overflow-x-auto">
        <table class="va-table">
            <thead>
                <tr>
                    <th>Pilot</th>
                    <th>Callsign</th>
                    <th>Departure</th>
                    <th>Arrival</th>
                    <th>Aircraft</th>
                    <th class="text-center">FPM</th>
                    <th class="text-center">Time</th>
                    <th class="text-center">R-Pts</th>
                    <th>Status</th>
                    <th class="text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($pireps as $pirep)
                <tr>
                    <td class="font-medium text-white">
                        {{ $pirep->user->name }}
                    </td>
                    <td>
                        {{ $pirep->route->flight_number ?? 'N/A' }}
                    </td>
                    <td class="font-mono font-bold text-tenant-accent">
                        {{ $pirep->route->departure_icao ?? 'N/A' }}
                    </td>
                    <td class="font-mono font-bold text-tenant-accent">
                        {{ $pirep->route->arrival_icao ?? 'N/A' }}
                    </td>
                    <td class="text-slate-400">
                        {{ $pirep->airframe->registration ?? 'N/A' }}<br>
                        <span class="text-xs opacity-75">{{ $pirep->airframe->aircraftType->name ?? '' }}</span>
                    </td>
                    <td class="text-center font-mono">
                        {{ $pirep->touchdown_rate_fpm ?? '-' }}
                    </td>
                    <td class="text-center font-mono">
                        {{ sprintf('%02d:%02d', floor($pirep->flight_time / 60), $pirep->flight_time % 60) }}
                    </td>
                    <td class="text-center font-bold text-tenant-accent">
                        {{ $pirep->points_awarded }}
                    </td>
                    <td>
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
                        <span class="text-[10px] text-slate-500 mt-0.5 block">{{ $pirep->created_at->format('jS M y H:i') }}</span>
                    </td>
                    <td class="text-right">
                        <a href="{{ route('pireps.show', $pirep->id) }}" class="text-slate-400 hover:text-white transition-colors">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="py-8 text-center text-slate-500">
                        No PIREPs found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
