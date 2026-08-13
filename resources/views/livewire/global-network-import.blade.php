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

        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <x-input type="text" wire:model.defer="searchDeparture" placeholder="Filter Origin (e.g. EGLL, LHR, Heathrow)" class="w-full bg-[#212631] border-gray-600 text-white uppercase" />
            </div>
            <div class="flex-1">
                <x-input type="text" wire:model.defer="searchArrival" placeholder="Filter Destination (e.g. LFPG, CDG, Paris)" class="w-full bg-[#212631] border-gray-600 text-white uppercase" />
            </div>
            <div class="flex-1">
                <x-input type="text" wire:model.defer="searchOperator" placeholder="Filter Operator (e.g. EZY, U2, easyJet)" class="w-full bg-[#212631] border-gray-600 text-white uppercase" />
            </div>
            <div class="flex space-x-2">
                <button wire:click="search" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded transition h-full flex items-center">
                    🔍 Search
                </button>
                <button wire:click="importSelected" class="bg-tenant-accent hover:opacity-80 text-white font-bold py-2 px-6 rounded transition h-full flex items-center" {{ $currentBatch ? 'disabled' : '' }}>
                    <span class="mr-2">📥</span> Import Selected
                </button>
            </div>
        </div>

        <div class="overflow-x-auto bg-black/20 rounded-lg border border-white/5">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/5 text-gray-400 text-sm uppercase tracking-wider border-b border-white/10">
                        <th class="p-4 w-12 text-center">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent">
                        </th>
                        <th class="p-4">Operator</th>
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
                                    {{ $flight->flight_number }}
                                </div>
                                @if($flight->airline_iata || $flight->airline_icao)
                                    <div class="text-xs text-gray-400">
                                        {{ implode(' / ', array_filter([$flight->airline_iata, $flight->airline_icao])) }}
                                    </div>
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
                            <td colspan="6" class="p-8 text-center text-gray-500">
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
    </div>
</div>
