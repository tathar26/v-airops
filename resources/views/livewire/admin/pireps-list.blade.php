<div class="space-y-6">
    <div class="flex justify-between items-center mb-2">
        <h2 class="text-2xl font-bold text-white tracking-tight">PIREP Audit &amp; Staff Management</h2>
    </div>

    <!-- Filters Card -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
        <div class="px-6 py-3.5 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between items-center">
            <span>FILTER PIREPS FOR AUDIT</span>
            <button class="text-tenant-accent text-xs font-semibold hover:opacity-80 transition">Reset Filters</button>
        </div>
        <div class="p-6 bg-[#12161F]">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs text-gray-400 mb-1 font-medium">Departure</label>
                    <select class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-lg text-xs p-2.5 focus:outline-none focus:border-tenant-accent"><option>Select an option</option></select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1 font-medium">Arrival</label>
                    <select class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-lg text-xs p-2.5 focus:outline-none focus:border-tenant-accent"><option>Select an option</option></select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1 font-medium">Fleet</label>
                    <select class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-lg text-xs p-2.5 focus:outline-none focus:border-tenant-accent"><option>Select an option</option></select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1 font-medium">PIREP Type</label>
                    <select class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-lg text-xs p-2.5 focus:outline-none focus:border-tenant-accent"><option>Select an option</option></select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1 font-medium">PIREP Status</label>
                    <select class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-lg text-xs p-2.5 focus:outline-none focus:border-tenant-accent"><option>All</option></select>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
        <div class="px-6 py-3.5 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between items-center">
            <span>SUBMITTED PIREPS LIST</span>
            <span class="text-xs text-gray-400 font-mono font-semibold">Total Records: {{ count($pireps) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/5">
                <thead class="bg-[#181D29]">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 tracking-wider">Pilot</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 tracking-wider">Flight / Callsign</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 tracking-wider">Departure</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 tracking-wider">Arrival</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 tracking-wider">Aircraft</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-400 tracking-wider">FPM</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-400 tracking-wider">Time</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-400 tracking-wider">Points</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-400 tracking-wider">Review</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 bg-[#12161F]">
                    @forelse($pireps as $pirep)
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-white">
                            {{ $pirep->user->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            {{ $pirep->route->flight_number ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-sky-400 font-mono font-bold">
                            {{ $pirep->route->departure_icao ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-sky-400 font-mono font-bold">
                            {{ $pirep->route->arrival_icao ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            <span class="font-bold">{{ $pirep->airframe->registration ?? 'N/A' }}</span><br>
                            <span class="text-xs text-gray-500">{{ $pirep->airframe->aircraftType->name ?? '' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-mono font-bold {{ abs($pirep->touchdown_rate_fpm ?? 0) <= 200 ? 'text-green-400' : 'text-yellow-400' }}">
                            {{ $pirep->touchdown_rate_fpm ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 text-center font-mono">
                            {{ sprintf('%02d:%02d', floor($pirep->flight_time / 60), $pirep->flight_time % 60) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-tenant-accent font-bold text-center font-mono">
                            {{ $pirep->points_awarded }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @php $st = strtolower($pirep->status); @endphp
                            @if(in_array($st, ['accepted', 'complete', 'approved']))
                                <span class="px-2.5 py-0.5 rounded bg-green-500/20 text-green-400 border border-green-500/30 font-bold text-[11px]">ACCEPTED</span>
                            @elseif($st === 'rejected')
                                <span class="px-2.5 py-0.5 rounded bg-orange-500/20 text-orange-400 border border-orange-500/30 font-bold text-[11px]">REJECTED</span>
                            @elseif($st === 'invalidated')
                                <span class="px-2.5 py-0.5 rounded bg-red-500/20 text-red-400 border border-red-500/30 font-bold text-[11px]">INVALIDATED</span>
                            @elseif($st === 'reply_needed' || $st === 'reply needed')
                                <span class="px-2.5 py-0.5 rounded bg-red-500/30 text-amber-300 border border-red-500/40 font-bold text-[11px] animate-pulse">REPLY NEEDED</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 font-bold text-[11px]">{{ strtoupper($pirep->status) }}</span>
                            @endif
                            <span class="text-[10px] text-gray-500 mt-1 block font-mono">{{ $pirep->created_at->format('jS M y H:i') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.pireps.show', $pirep->id) }}" class="text-tenant-accent hover:text-white transition-colors bg-white/5 hover:bg-tenant-accent px-3 py-1.5 rounded-lg text-xs font-bold border border-white/10 inline-flex items-center gap-1">
                                Audit
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-8 text-center text-gray-500 italic">
                            No PIREPs found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
