<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="glass-panel overflow-hidden p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-white">Global Airline Schedules Import</h2>
                <p class="text-gray-400 text-sm mt-1">Search real-world worldwide airline schedules live from the central microservice and import them into your Virtual Airline network.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Live Schedules API Connected
                </span>
            </div>
        </div>

        @if (session()->has('message'))
            <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative">
                <span class="block sm:inline">{{ session('message') }}</span>
            </div>
        @endif
        
        @if (session()->has('error'))
            <div class="mb-4 bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded relative">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if($errorMessage)
            <div class="mb-4 bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded relative">
                <span class="block sm:inline">{{ $errorMessage }}</span>
            </div>
        @endif

        @if($currentBatch)
            <div wire:poll.2s="updateBatchProgress" class="mb-6 p-4 bg-black/40 rounded-lg border border-white/10">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-white font-bold text-sm">Import Progress</h3>
                    <span class="text-tenant-accent text-sm font-bold">{{ $currentBatch->progress() }}%</span>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2.5">
                    <div class="bg-tenant-accent h-2.5 rounded-full transition-all duration-500" style="width: {{ $currentBatch->progress() }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-2">Processed {{ $currentBatch->processedJobs() }} of {{ $currentBatch->totalJobs }} jobs. {{ $currentBatch->failedJobs }} failed.</p>
            </div>
        @endif

        <!-- ATC Callsign Import Configuration -->
        <div class="p-4 rounded-xl border border-white/10 mb-6 flex flex-wrap items-center justify-between gap-4" style="background-color: var(--tenant-card-bg, #141923);">
            <div class="flex items-center gap-4 flex-wrap flex-1">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Target VA ICAO Prefix</label>
                    <select wire:model="targetIcao" class="rounded-xl py-2 px-3 text-sm font-mono font-bold bg-slate-900 border border-white/10 text-white">
                        @foreach($availableIcaos as $icaoOpt)
                            <option value="{{ $icaoOpt }}">{{ $icaoOpt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Prefix to Cut Off (Optional)</label>
                    <input type="text" wire:model="stripPrefix" placeholder="e.g. BA, KLM, U2 (auto if blank)" class="rounded-xl py-2 px-3 text-sm uppercase font-mono w-60 bg-slate-900 border border-white/10 text-white" />
                </div>
                <div class="text-xs text-slate-400 self-end pb-2">
                    Example: Schedule <span class="font-mono font-bold text-white">BAW1420</span> generates VA Route Callsign <span class="font-mono font-bold text-amber-300">{{ strtoupper($targetIcao ?: 'VOPS') }}1420</span>.
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <x-input type="text" wire:model.defer="searchDeparture" placeholder="Origin ICAO (e.g. EGLL, EHAM, KJFK)" class="w-full uppercase" />
            </div>
            <div class="flex-1">
                <x-input type="text" wire:model.defer="searchArrival" placeholder="Destination ICAO (e.g. LFPG, EDDF, KLAX)" class="w-full uppercase" />
            </div>
            <div class="flex-1">
                <x-input type="text" wire:model.defer="searchOperator" placeholder="Airline ICAO or Callsign (e.g. DLH, KLM, BAW)" class="w-full uppercase" />
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="search" class="bg-slate-800 hover:bg-slate-700 text-white font-bold py-2 px-6 rounded-xl transition h-full flex items-center border border-white/10">
                    🔍 Search Live
                </button>
                <button wire:click="importSelected" class="bg-tenant-accent hover:opacity-90 text-white font-bold py-2 px-5 rounded-xl transition h-full flex items-center shadow-lg" {{ $currentBatch || empty($selectedFlights) ? 'disabled' : '' }}>
                    <span class="mr-1.5">📥</span> Import Selected ({{ count($selectedFlights) }})
                </button>
                @if($flights && $flights->total() > 0)
                    <button wire:click="importAllMatching" wire:confirm="Are you sure you want to import ALL {{ number_format($flights->total()) }} matching schedules in the background?" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-5 rounded-xl transition h-full flex items-center shadow-lg" {{ $currentBatch ? 'disabled' : '' }}>
                        <span class="mr-1.5">⚡</span> Import All ({{ number_format($flights->total()) }})
                    </button>
                @endif
            </div>
        </div>

        @if($flights === null)
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="text-6xl mb-4">🌍</div>
                <h3 class="text-xl font-bold text-white mb-2">Live Worldwide Schedules Database</h3>
                <p class="text-gray-400 max-w-md">Enter an origin airport, destination, or airline ICAO (e.g., <span class="font-mono text-tenant-accent font-bold">KLM</span>, <span class="font-mono text-tenant-accent font-bold">BAW</span>, <span class="font-mono text-tenant-accent font-bold">EHAM</span>) and click <strong class="text-white">Search Live</strong>.</p>
            </div>
        @else
            <div class="flex items-center justify-between text-xs text-gray-400 mb-3 px-1">
                <span>Showing {{ number_format($flights->firstItem() ?? 0) }}–{{ number_format($flights->lastItem() ?? 0) }} of <strong class="text-white">{{ number_format($flights->total()) }}</strong> schedules found</span>
                <span>Sorted by latest observation</span>
            </div>

            <div class="overflow-x-auto bg-black/20 rounded-lg border border-white/5">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/5 text-gray-400 text-sm uppercase tracking-wider border-b border-white/10">
                            <th class="p-4 w-12 text-center">
                                <input type="checkbox" wire:model.live="selectAll" class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent">
                            </th>
                            <th class="p-4">Airline</th>
                            <th class="p-4">Callsign / Flight</th>
                            <th class="p-4">Origin</th>
                            <th class="p-4">Destination</th>
                            <th class="p-4">Schedule UTC</th>
                            <th class="p-4">Duration</th>
                            <th class="p-4 text-center">Observed</th>
                        </tr>
                    </thead>
                    <tbody class="text-white divide-y divide-white/5">
                        @forelse($flights as $flight)
                            @php
                                $itemKey = $flight['id'] ?? ($flight['callsign'] . '_' . $flight['origin_icao'] . '_' . $flight['destination_icao']);
                                $depAirport = $flight['origin_airport'] ?? null;
                                $arrAirport = $flight['destination_airport'] ?? null;
                                $durationMins = (int)($flight['duration_minutes'] ?? 0);
                                $durationStr = $durationMins > 0 ? sprintf('%02dh %02dm', floor($durationMins/60), $durationMins%60) : '—';
                            @endphp
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="p-4 text-center">
                                    <input type="checkbox" wire:model.live="selectedFlights" value="{{ $itemKey }}" class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent">
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 text-xs font-mono font-bold bg-slate-800 text-white rounded border border-white/10">
                                        {{ $flight['airline_icao'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="font-mono font-bold text-amber-300 text-sm">
                                        {{ $flight['callsign'] ?? '—' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="font-mono text-tenant-accent font-bold">{{ $flight['origin_icao'] }}</div>
                                    @if($depAirport && !empty($depAirport['name']))
                                        <div class="text-xs text-gray-400 truncate max-w-xs" title="{{ $depAirport['name'] }}">
                                            {{ $depAirport['name'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <div class="font-mono text-tenant-accent font-bold">{{ $flight['destination_icao'] }}</div>
                                    @if($arrAirport && !empty($arrAirport['name']))
                                        <div class="text-xs text-gray-400 truncate max-w-xs" title="{{ $arrAirport['name'] }}">
                                            {{ $arrAirport['name'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-4 font-mono text-sm text-gray-300">
                                    @if(!empty($flight['departure_time_utc']) && !empty($flight['arrival_time_utc']))
                                        {{ substr($flight['departure_time_utc'], 0, 5) }} → {{ substr($flight['arrival_time_utc'], 0, 5) }}z
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="p-4 text-sm text-gray-300 font-mono">
                                    {{ $durationStr }}
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                        {{ $flight['times_observed'] ?? 1 }}x
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-gray-500">
                                    No live schedules found matching your query.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $flights->links() }}
            </div>
        @endif
    </div>
</div>
