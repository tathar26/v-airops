<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="glass-panel overflow-hidden p-6">
        <h2 class="text-2xl font-bold text-white mb-6">Global Network Import</h2>
        <p class="text-gray-400 mb-6">Import routes and aircraft from the central global repository into your Virtual Airline's network.</p>

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
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Target Airline ICAO</label>
                    <select wire:model="targetIcao" class="rounded-xl py-2 px-3 text-sm font-mono font-bold">
                        @foreach($availableIcaos as $icaoOpt)
                            <option value="{{ $icaoOpt }}">{{ $icaoOpt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Flight # Prefix to Cut Off (Optional)</label>
                    <input type="text" wire:model="stripPrefix" placeholder="e.g. U2, FR, BA (auto if blank)" class="rounded-xl py-2 px-3 text-sm uppercase font-mono w-60" />
                </div>
                <div class="text-xs text-slate-400 self-end pb-2">
                    Example: Flight <span class="font-mono font-bold text-white">U29999</span> with prefix <span class="font-mono font-bold text-tenant-accent">U2</span> generates ATC Callsign <span class="font-mono font-bold text-amber-300">{{ strtoupper($targetIcao ?: 'EZY') }}9999</span>.
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <x-input type="text" wire:model.defer="searchDeparture" placeholder="Filter Origin (e.g. EGLL, LHR, Heathrow)" class="w-full uppercase" />
            </div>
            <div class="flex-1">
                <x-input type="text" wire:model.defer="searchArrival" placeholder="Filter Destination (e.g. LFPG, CDG, Paris)" class="w-full uppercase" />
            </div>
            <div class="flex-1">
                <x-input type="text" wire:model.defer="searchOperator" placeholder="Filter Operator (e.g. EZY, U2, easyJet)" class="w-full uppercase" />
            </div>
            <div class="flex space-x-2">
                <button wire:click="search" class="bg-slate-800 hover:opacity-90 text-white font-bold py-2 px-6 rounded-xl transition h-full flex items-center">
                    🔍 Search
                </button>
                <button wire:click="importSelected" class="bg-tenant-accent hover:opacity-90 text-white font-bold py-2 px-6 rounded-xl transition h-full flex items-center shadow-lg" {{ $currentBatch ? 'disabled' : '' }}>
                    <span class="mr-2">📥</span> Import Selected
                </button>
            </div>
        </div>

        @if($flights === null)
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="text-6xl mb-4">🌍</div>
                <h3 class="text-xl font-bold text-white mb-2">Search the Global Route Network</h3>
                <p class="text-gray-400 max-w-md">Enter an origin, destination, or operator above and click <strong class="text-white">Search</strong> to browse from over 200,000 global routes.</p>
            </div>
        @else
            <div class="overflow-x-auto bg-black/20 rounded-lg border border-white/5">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white/5 text-gray-400 text-sm uppercase tracking-wider border-b border-white/10">
                            <th class="p-4 w-12 text-center">
                                <input type="checkbox" wire:model.live="selectAll" class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent">
                            </th>
                            <th class="p-4">Operator</th>
                            <th class="p-4">Flight #</th>
                            <th class="p-4">Origin</th>
                            <th class="p-4">Destination</th>
                            <th class="p-4">Aircraft</th>
                            <th class="p-4">Distance</th>
                        </tr>
                    </thead>
                    <tbody class="text-white divide-y divide-white/5">
                        @forelse($flights as $flight)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="p-4 text-center">
                                    <input type="checkbox" wire:model.live="selectedFlights" value="{{ $flight->id }}" class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent">
                                </td>
                                <td class="p-4">
                                    <div class="font-bold">
                                        {{ $flight->airline_name ?? $flight->operator ?? 'Unknown Operator' }} 
                                    </div>
                                    @if($flight->airline_iata || $flight->airline_icao)
                                        <div class="text-xs text-gray-400">
                                            {{ implode(' / ', array_filter([$flight->airline_iata, $flight->airline_icao])) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-4">
                                    @if($flight->flight_number)
                                        <span class="px-2 py-1 text-xs font-mono font-bold bg-tenant-accent/20 text-tenant-accent rounded border border-tenant-accent/30">
                                            {{ $flight->flight_number }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-500 font-mono">—</span>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <div class="font-mono text-tenant-accent font-bold">{{ $flight->departure_icao }}</div>
                                    @if($flight->dep_name || $flight->dep_iata)
                                        <div class="text-xs text-gray-400">
                                            {{ implode(' / ', array_filter([$flight->dep_iata, $flight->dep_name])) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <div class="font-mono text-tenant-accent font-bold">{{ $flight->arrival_icao }}</div>
                                    @if($flight->arr_name || $flight->arr_iata)
                                        <div class="text-xs text-gray-400">
                                            {{ implode(' / ', array_filter([$flight->arr_iata, $flight->arr_name])) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-4 text-sm text-gray-400">{{ $flight->aircraft_types ?? 'Any' }}</td>
                                <td class="p-4 text-sm">{{ $flight->distance ? $flight->distance . ' NM' : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-500">
                                    No global routes found matching your filters.
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
