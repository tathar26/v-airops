<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="glass-panel overflow-hidden p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-white">Global Airline Schedules Import</h2>
                <p class="text-gray-400 text-sm mt-1">Search real-world worldwide airline schedules live from the central microservice and import them into your Virtual Airline network.</p>
            </div>
            <div class="flex items-center gap-2">
                @if($apiConnected)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-sm" title="{{ $apiStatusMessage }}">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Live Schedules API Connected
                    </span>
                @else
                    <button wire:click="checkApiStatus" type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20 transition cursor-pointer" title="Click to test connection">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        {{ $apiStatusMessage ?: 'API Disconnected (Click to retry)' }}
                    </button>
                @endif
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

        <!-- Active Callsign & Flight Number Conversion Rules Banner -->
        @php
            $tenantMappings = $tenant ? $tenant->getCallsignMappings() : [];
        @endphp
        <div class="p-4 rounded-xl border border-white/10 mb-6" style="background-color: var(--tenant-card-bg, #141923);">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Flight Number Synthesis & Callsign Rules</h4>
                        <span class="text-[10px] bg-blue-500/20 text-blue-300 px-2 py-0.5 rounded font-mono font-bold">Auto-Mapped</span>
                    </div>
                    <p class="text-xs text-gray-400">
                        The live API provides ATC callsigns. Commercial flight numbers are generated using your VA's prefix mapping rules (e.g. <span class="font-mono text-amber-300 font-bold">EZY8412</span> &rarr; <span class="font-mono text-tenant-accent font-bold">U28412</span>).
                    </p>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        @forelse($tenantMappings as $rule)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-black/40 border border-white/10 text-xs font-mono">
                                <strong class="text-amber-300">{{ $rule['callsign_prefix'] }}</strong>
                                <span class="text-gray-500">&rarr;</span>
                                <strong class="text-tenant-accent">{{ $rule['flight_number_prefix'] }}</strong>
                            </span>
                        @empty
                            <span class="text-xs text-gray-500 italic">Using standard default airline prefix mappings (e.g. EZY&rarr;U2, BAW&rarr;BA, KLM&rarr;KL, RYR&rarr;FR, DLH&rarr;LH).</span>
                        @endforelse
                    </div>
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
                <x-input type="text" wire:model.defer="searchOperator" placeholder="Airline ICAO or Callsign (e.g. EZY, KLM, BAW)" class="w-full uppercase" />
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="search" class="bg-slate-800 hover:bg-slate-700 text-white font-bold py-2 px-6 rounded-xl transition h-full flex items-center border border-white/10">
                    🔍 Search Live
                </button>
                <button wire:click="openImportModal('selected')" class="bg-tenant-accent hover:opacity-90 text-white font-bold py-2 px-5 rounded-xl transition h-full flex items-center shadow-lg" {{ $currentBatch || empty($selectedFlights) ? 'disabled' : '' }}>
                    <span class="mr-1.5">📥</span> Import Selected ({{ count($selectedFlights) }})
                </button>
                @if($flights && $flights->total() > 0)
                    <button wire:click="openImportModal('all')" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-5 rounded-xl transition h-full flex items-center shadow-lg" {{ $currentBatch ? 'disabled' : '' }}>
                        <span class="mr-1.5">⚡</span> Import All ({{ number_format($flights->total()) }})
                    </button>
                @endif
            </div>
        </div>

        @if($flights === null)
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="text-6xl mb-4">🌍</div>
                <h3 class="text-xl font-bold text-white mb-2">Live Worldwide Schedules Database</h3>
                <p class="text-gray-400 max-w-md">Enter an origin airport, destination, or airline ICAO (e.g., <span class="font-mono text-tenant-accent font-bold">EZY</span>, <span class="font-mono text-tenant-accent font-bold">KLM</span>, <span class="font-mono text-tenant-accent font-bold">BAW</span>) and click <strong class="text-white">Search Live</strong>.</p>
            </div>
        @else
            <div class="flex items-center justify-between text-xs text-gray-400 mb-3 px-1">
                <span>Showing {{ number_format($flights->firstItem() ?? 0) }}–{{ number_format($flights->lastItem() ?? 0) }} of <strong class="text-white">{{ number_format($flights->total()) }}</strong> live schedules found</span>
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
                            <th class="p-4">Live Callsign</th>
                            <th class="p-4">Target Flight #</th>
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
                                $rawCallsign = $flight['callsign'] ?? '';
                                $airlineIcao = $flight['airline_icao'] ?? '';
                                $generatedFlightNumber = $tenant ? $tenant->resolveFlightNumberForTarget($rawCallsign, $targetIcao, $stripPrefix) : $rawCallsign;
                                $durationMins = (int)($flight['duration_minutes'] ?? 0);
                                $durationStr = $durationMins > 0 ? sprintf('%02dh %02dm', floor($durationMins/60), $durationMins%60) : '—';
                            @endphp
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="p-4 text-center">
                                    <input type="checkbox" wire:model.live="selectedFlights" value="{{ $itemKey }}" class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent">
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 text-xs font-mono font-bold bg-slate-800 text-white rounded border border-white/10">
                                        {{ $airlineIcao ?: 'N/A' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="font-mono font-bold text-amber-300 text-sm">
                                        {{ $rawCallsign ?: '—' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 text-xs font-mono font-bold bg-tenant-accent/20 text-tenant-accent rounded border border-tenant-accent/30 shadow-sm">
                                        {{ $generatedFlightNumber }}
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
                                        {{ substr($flight['departure_time_utc'], 0, 5) }} &rarr; {{ substr($flight['arrival_time_utc'], 0, 5) }}z
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
                                <td colspan="9" class="p-8 text-center text-gray-500">
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

    <!-- Import Configuration Modal -->
    <x-dialog-modal wire:model.live="showImportModal">
        <x-slot name="title">
            <div class="flex items-center gap-2 text-white">
                <span class="text-xl">📥</span>
                <span>Configure Route Import</span>
            </div>
        </x-slot>

        <x-slot name="content">
            @if($importMode === 'all')
                <div class="mb-4 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs text-emerald-300 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-sm text-emerald-200">Bulk Network Import</div>
                        <div class="text-[11px] text-emerald-400/80 mt-0.5">Importing all schedules matching your active search filters in the background.</div>
                    </div>
                    <span class="font-mono font-bold px-3 py-1 bg-emerald-600 text-white rounded-lg text-sm shadow">
                        {{ number_format($flights?->total() ?? 0) }} routes
                    </span>
                </div>
            @else
                <div class="mb-4 p-3.5 rounded-xl bg-tenant-accent/10 border border-tenant-accent/30 text-xs text-tenant-accent flex items-center justify-between">
                    <div>
                        <div class="font-bold text-sm text-white">Selected Schedules Import</div>
                        <div class="text-[11px] text-gray-300 mt-0.5">Importing your specifically chosen route records into your VA network.</div>
                    </div>
                    <span class="font-mono font-bold px-3 py-1 bg-tenant-accent text-white rounded-lg text-sm shadow">
                        {{ count($selectedFlights) }} routes
                    </span>
                </div>
            @endif

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-200 uppercase tracking-wider mb-1.5">Target VA Airline ICAO</label>
                    <select wire:model.live="targetIcao" class="w-full rounded-xl py-2.5 px-3 text-sm font-mono font-bold bg-[#1e2532] border border-gray-700 text-white focus:ring-tenant-accent focus:border-tenant-accent">
                        @foreach($availableIcaos as $icaoOpt)
                            <option value="{{ $icaoOpt }}">{{ $icaoOpt }} ({{ $icaoOpt === ($tenant?->icao ?? 'VOPS') ? 'Primary' : 'Secondary' }})</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Routes will be created under this airline ICAO code with commercial flight numbers derived from its mapping rule.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-200 uppercase tracking-wider mb-1.5">Callsign Prefix to Strip (Optional)</label>
                    <x-input type="text" wire:model.live="stripPrefix" placeholder="e.g. BA, KLM, U2, TOM (auto if blank)" class="w-full bg-[#1e2532] border-gray-700 text-white uppercase text-sm font-mono" />
                    <p class="text-xs text-gray-400 mt-1">Leave blank to automatically strip standard airline prefixes and apply your VA's configured Callsign &rarr; Flight Number mapping rules.</p>
                </div>

                <!-- Preview Box -->
                <div class="p-3.5 bg-black/40 rounded-xl border border-white/10 text-xs font-mono">
                    <div class="text-gray-400 text-[10px] uppercase font-bold mb-1.5">Live Example Transformation:</div>
                    <div class="flex items-center gap-2 flex-wrap text-slate-200">
                        <span class="text-gray-400">Incoming:</span>
                        <span class="text-amber-300 font-bold bg-amber-400/10 px-1.5 py-0.5 rounded">KLM82A</span>
                        <span class="text-gray-500">&rarr;</span>
                        <span>VA ATC Callsign:</span>
                        <span class="text-amber-300 font-bold bg-amber-400/10 px-1.5 py-0.5 rounded">{{ strtoupper($targetIcao ?: 'VOPS') }}82A</span>
                        <span class="text-gray-500">|</span>
                        <span>Flight #:</span>
                        <span class="text-tenant-accent font-bold bg-tenant-accent/10 px-1.5 py-0.5 rounded">{{ $tenant ? $tenant->resolveFlightNumberForTarget('KLM82A', $targetIcao, $stripPrefix) : 'EC82A' }}</span>
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeImportModal" class="bg-gray-700 hover:bg-gray-600 text-white border-none py-2 px-4 rounded-xl text-xs font-bold">
                Cancel
            </x-secondary-button>

            <button wire:click="confirmImport" class="bg-tenant-accent hover:opacity-90 text-white px-5 py-2 rounded-xl text-xs font-bold shadow-lg transition flex items-center gap-2">
                <span>🚀 Confirm & Start Import</span>
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
