<div class="space-y-6">
    <div class="flex justify-between items-center mb-2 flex-wrap gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">PIREP Management &amp; Audit</h2>
            <p class="text-xs text-gray-400 mt-1">Review, approve, reject, and audit pilot flight reports &bull; <span class="text-tenant-accent font-semibold">{{ number_format($totalPirepsCount) }} Total PIREPs</span></p>
        </div>
    </div>

    <!-- Filters Card with Autocomplete -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
        <div class="px-6 py-3.5 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between items-center flex-wrap gap-2">
            <span>FILTER PIREPS FOR AUDIT</span>
            @if($search || $filterStatus || $filterDepIcao || $filterArrIcao || $filterFleet)
                <button wire:click="resetFilters" class="text-tenant-accent text-xs font-semibold hover:underline transition">Reset All Filters</button>
            @endif
        </div>
        <div class="p-6 bg-[#12161F]">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Omni Search with Autocomplete -->
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-400 mb-1 font-medium">Search Pilot, Flight #, Callsign, ICAO...</label>
                    <div class="relative">
                        <input type="text" 
                               list="pirep-autocomplete"
                               wire:model.live.debounce.300ms="search" 
                               placeholder="Type to search all fields..." 
                               class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-xl text-xs p-2.5 pl-8 focus:outline-none focus:border-tenant-accent" />
                        <svg class="w-4 h-4 text-gray-500 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        
                        <datalist id="pirep-autocomplete">
                            @foreach($autocompleteList as $item)
                                <option value="{{ $item }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <!-- Departure Filter -->
                <div>
                    <label class="block text-xs text-gray-400 mb-1 font-medium">Departure</label>
                    <select wire:model.live="filterDepIcao" class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-xl text-xs p-2.5 focus:outline-none focus:border-tenant-accent">
                        <option value="">All Departures</option>
                        @foreach($allDepIcaos as $dep)
                            <option value="{{ $dep }}">{{ $dep }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Arrival Filter -->
                <div>
                    <label class="block text-xs text-gray-400 mb-1 font-medium">Arrival</label>
                    <select wire:model.live="filterArrIcao" class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-xl text-xs p-2.5 focus:outline-none focus:border-tenant-accent">
                        <option value="">All Arrivals</option>
                        @foreach($allArrIcaos as $arr)
                            <option value="{{ $arr }}">{{ $arr }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-xs text-gray-400 mb-1 font-medium">PIREP Status</label>
                    <select wire:model.live="filterStatus" class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-xl text-xs p-2.5 focus:outline-none focus:border-tenant-accent">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted / Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="invalidated">Invalidated</option>
                        <option value="reply_needed">Reply Needed</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-white/5">
                <!-- Fleet / Airframe Filter -->
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-400 mb-1 font-medium">Airframe / Aircraft</label>
                    <select wire:model.live="filterFleet" class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-xl text-xs p-2.5 focus:outline-none focus:border-tenant-accent">
                        <option value="">All Airframes</option>
                        @foreach($airframes as $af)
                            <option value="{{ $af->id }}">{{ $af->registration }} ({{ $af->aircraftType->code ?? '' }} - {{ $af->name }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Per Page Selector -->
                <div>
                    <label class="block text-xs text-gray-400 mb-1 font-medium">Page Size</label>
                    <select wire:model.live="perPage" class="w-full bg-[#0a0d14] border border-white/10 text-white rounded-xl text-xs p-2.5 focus:outline-none focus:border-tenant-accent">
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
        <div class="px-6 py-3.5 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 text-xs text-gray-400 font-bold tracking-wider flex justify-between items-center">
            <span>SUBMITTED PIREPS LIST</span>
            <span class="text-xs text-gray-400 font-mono font-semibold">Showing {{ $pireps->count() }} of {{ number_format($totalPirepsCount) }}</span>
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
                            {{ $pirep->user->name ?? 'Unknown Pilot' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 font-mono font-semibold">
                            {{ $pirep->route->flight_number ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-sky-400 font-mono font-bold">
                            {{ $pirep->route->departure_icao ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-sky-400 font-mono font-bold">
                            {{ $pirep->route->arrival_icao ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            <span class="font-bold font-mono text-tenant-accent">{{ $pirep->airframe->registration ?? 'N/A' }}</span><br>
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
                            <a href="{{ route('pireps.show', $pirep->id) }}" class="text-tenant-accent hover:text-white transition-colors bg-white/5 hover:bg-tenant-accent px-3 py-1.5 rounded-lg text-xs font-bold border border-white/10 inline-flex items-center gap-1">
                                Audit
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-8 text-center text-gray-500 italic">
                            @if($search || $filterStatus || $filterDepIcao || $filterArrIcao || $filterFleet)
                                No PIREPs matched your filter criteria. <button wire:click="resetFilters" class="text-tenant-accent underline ml-1 font-semibold">Reset filters</button>
                            @else
                                No PIREPs found.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pireps->hasPages())
            <div class="px-6 py-4 bg-[#141923] border-t border-white/5">
                {{ $pireps->links() }}
            </div>
        @endif
    </div>
</div>
