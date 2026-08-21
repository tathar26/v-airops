<div class="space-y-6">
    @if (session()->has('message'))
        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded-xl relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded-xl relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="va-card rounded-2xl overflow-hidden shadow-2xl mt-6 border"
        style="color: var(--tenant-card-text, #ffffff);">
        <div class="px-6 py-5 border-b flex justify-between items-center flex-wrap gap-4"
            style="background-color: var(--tenant-panel-bg, #141923); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
            <div>
                <h3 class="text-base font-bold tracking-wide" style="color: var(--tenant-panel-text, #ffffff);">ROUTE
                    MANAGEMENT &amp; SCHEDULES</h3>
                <p class="text-xs mt-0.5" style="color: var(--tenant-card-muted, #94a3b8);">
                    Manage schedules, flight numbers, aircraft assignments, and airline ATC callsigns &bull;
                    <span class="text-tenant-accent font-semibold">{{ number_format($totalRoutesCount) }} Total
                        Routes</span>
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <button wire:click="downloadTemplate"
                    class="text-tenant-accent hover:underline transition-colors text-sm font-semibold">
                    Download CSV Template
                </button>
                <div class="relative flex items-center">
                    <input type="file" wire:model="csvFile" id="csvRouteFile" class="hidden" accept=".csv,.txt" />
                    <label for="csvRouteFile"
                        class="cursor-pointer btn-secondary px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition border mr-2 inline-flex items-center gap-1.5"
                        style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                        Select CSV
                    </label>
                    @if($csvFile)
                        <button wire:click="importCsv"
                            class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition">
                            Import
                        </button>
                    @endif
                </div>
                <a href="{{ route('global-network') }}"
                    class="btn-secondary px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition flex items-center gap-2 border border-tenant-accent/40 text-tenant-accent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                    Global Network Import
                </a>
                <button wire:click="openAddModal"
                    class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Route
                </button>
            </div>
        </div>

        <!-- Mass Selection Floating / Active Bar -->
        @if(count($selectedRoutes) > 0)
            <div
                class="px-6 py-3.5 bg-tenant-accent/20 border-b border-tenant-accent/40 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-lg bg-tenant-accent text-white font-bold text-xs shadow">
                        {{ count($selectedRoutes) }} Selected
                    </span>
                    <span class="text-xs" style="color: var(--tenant-card-text, #ffffff);">
                        Perform bulk updates across all selected routes:
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="openMassUpdateModal"
                        class="btn-primary px-4 py-2 rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5">
                        ⚡ Bulk Update Callsigns / Aircraft
                    </button>
                    <button wire:click="massDelete"
                        wire:confirm="Are you sure you want to permanently delete these {{ count($selectedRoutes) }} route(s)?"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md transition">
                        🗑️ Delete Selected
                    </button>
                    <button wire:click="$set('selectedRoutes', []); $set('selectAll', false)"
                        class="text-xs hover:underline px-2 py-1" style="color: var(--tenant-card-muted, #cbd5e1);">
                        Clear Selection
                    </button>
                </div>
            </div>
        @endif

        <!-- Search & Filter Controls with Autocomplete -->
        <div class="px-6 py-4 border-b flex flex-wrap items-center justify-between gap-3"
            style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
            <div class="flex items-center gap-3 flex-1 min-w-[260px] max-w-md">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" list="route-autocomplete" wire:model.live.debounce.300ms="search"
                        placeholder="Search flight #, callsign, ICAO, DEP, ARR, type..."
                        class="pl-9 pr-4 py-2 rounded-xl text-xs w-full transition border"
                        style="background-color: var(--tenant-input-bg, #141923); color: var(--tenant-input-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));" />

                    <datalist id="route-autocomplete">
                        @foreach($autocompleteList as $item)
                            <option value="{{ $item }}"></option>
                        @endforeach
                    </datalist>
                </div>
                @if($search || $selectedRouteType || $filterDepIcao || $filterArrIcao || $filterAircraftType)
                    <button wire:click="resetFilters"
                        class="text-xs text-tenant-accent hover:underline px-2 py-1 flex-shrink-0 font-semibold">Clear</button>
                @endif
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Departure Airport Search Selection -->
                <x-search-select wireModel="filterDepIcao" :selected="$filterDepIcao" label="Dep" allLabel="Dep: All"
                    placeholder="Search origin airport (e.g. EGLL, LFPG)..." :options="$allDepIcaos->toArray()"
                    minWidth="min-w-[125px]" />

                <!-- Arrival Airport Search Selection -->
                <x-search-select wireModel="filterArrIcao" :selected="$filterArrIcao" label="Arr" allLabel="Arr: All"
                    placeholder="Search arrival airport (e.g. EHAM, EDDF)..." :options="$allArrIcaos->toArray()"
                    minWidth="min-w-[125px]" />

                <!-- Route Type Search Selection -->
                <x-search-select wireModel="selectedRouteType" :selected="$selectedRouteType" label="Type"
                    allLabel="Type: All" placeholder="Filter route type..." :options="['Scheduled', 'Charter', 'Cargo']"
                    minWidth="min-w-[120px]" />

                <!-- Aircraft Type Search Selection -->
                <x-search-select wireModel="filterAircraftType" :selected="$filterAircraftType" label="Aircraft"
                    allLabel="Aircraft: All" placeholder="Search aircraft code (e.g. A320)..."
                    :options="$aircraftTypes->map(fn($t) => ['id' => $t->id, 'code' => $t->code, 'name' => $t->name])->toArray()" minWidth="min-w-[135px]" />

                <!-- Per Page Selector -->
                <select wire:model.live="perPage"
                    class="text-xs font-semibold rounded-xl px-3.5 py-2 min-w-[115px] cursor-pointer shadow-sm border"
                    style="background-color: var(--tenant-input-bg, #141923); color: var(--tenant-input-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                    <option value="250">250 / page</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y"
                style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                <thead
                    style="background-color: rgba(0,0,0,0.12); border-bottom: 1px solid var(--tenant-input-border, rgba(255,255,255,0.08));">
                    <tr>
                        <th class="p-4 w-12 text-center">
                            <input type="checkbox" wire:model.live="selectAll"
                                class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent cursor-pointer">
                        </th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider"
                            style="color: var(--tenant-card-muted, #94a3b8);">Flight Number</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider"
                            style="color: var(--tenant-card-muted, #94a3b8);">ATC Callsign</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider"
                            style="color: var(--tenant-card-muted, #94a3b8);">Departure</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider"
                            style="color: var(--tenant-card-muted, #94a3b8);">Arrival</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider"
                            style="color: var(--tenant-card-muted, #94a3b8);">Block Time</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider"
                            style="color: var(--tenant-card-muted, #94a3b8);">Route Type</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider"
                            style="color: var(--tenant-card-muted, #94a3b8);">Aircraft Types</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider"
                            style="color: var(--tenant-card-muted, #94a3b8);">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.05));">
                    @forelse($routes as $route)
                        <tr class="hover:bg-white/5 transition-colors">
                            <!-- Selection Checkbox -->
                            <td class="p-4 text-center">
                                <input type="checkbox" wire:model.live="selectedRoutes" value="{{ $route->id }}"
                                    class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent cursor-pointer">
                            </td>

                            <!-- Flight Number (Commercial / IATA) -->
                            <td class="px-5 py-4 whitespace-nowrap text-sm font-bold font-mono"
                                style="color: var(--tenant-card-text, #ffffff);">
                                {{ $route->flight_number }}
                            </td>

                            <!-- ATC Callsign -->
                            <td class="px-5 py-4 whitespace-nowrap text-sm font-mono">
                                <span
                                    class="px-2.5 py-1 rounded-lg font-mono font-bold text-xs tracking-wider shadow-sm inline-block border"
                                    style="background-color: var(--tenant-input-bg, #141923); color: var(--tenant-accent); border-color: var(--tenant-accent);">
                                    {{ $route->callsign ?: $route->flight_number }}
                                </span>
                            </td>

                            <!-- Departure -->
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                <span
                                    class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold font-mono rounded-lg border"
                                    style="background-color: rgba(var(--tenant-accent-rgb, 249, 115, 22), 0.12); color: var(--tenant-accent); border-color: rgba(var(--tenant-accent-rgb, 249, 115, 22), 0.3);">
                                    {{ $route->departure_icao }}
                                </span>
                            </td>

                            <!-- Arrival -->
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                <span
                                    class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold font-mono rounded-lg border"
                                    style="background-color: rgba(var(--tenant-accent-rgb, 249, 115, 22), 0.12); color: var(--tenant-accent); border-color: rgba(var(--tenant-accent-rgb, 249, 115, 22), 0.3);">
                                    {{ $route->arrival_icao }}
                                </span>
                            </td>

                            <!-- Block Time -->
                            <td class="px-5 py-4 whitespace-nowrap text-sm font-mono font-medium"
                                style="color: var(--tenant-card-text, #cbd5e1);">
                                {{ $route->block_time }}
                            </td>

                            <!-- Route Type -->
                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-lg border"
                                    style="{{ $route->route_type === 'Charter' ? 'background-color: rgba(168, 85, 247, 0.15); color: #c084fc; border-color: rgba(168, 85, 247, 0.3);' : ($route->route_type === 'Cargo' ? 'background-color: rgba(234, 179, 8, 0.15); color: #facc15; border-color: rgba(234, 179, 8, 0.3);' : 'background-color: rgba(59, 130, 246, 0.15); color: #60a5fa; border-color: rgba(59, 130, 246, 0.3);') }}">
                                    {{ $route->route_type }}
                                </span>
                            </td>

                            <!-- Aircraft Types -->
                            <td class="px-5 py-4 text-sm">
                                @forelse($route->aircraftTypes as $type)
                                    <span class="px-2 py-1 mr-1 mb-1 inline-block text-xs font-mono font-bold rounded-md border"
                                        style="background-color: var(--tenant-input-bg, #141923); color: var(--tenant-card-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">{{ $type->code }}</span>
                                @empty
                                    <span class="text-xs italic" style="color: var(--tenant-card-muted, #64748b);">Any</span>
                                @endforelse
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="editRoute({{ $route->id }})"
                                    class="text-tenant-accent hover:underline transition-colors mr-3 font-bold">Edit</button>
                                <button wire:click="deleteRoute({{ $route->id }})"
                                    wire:confirm="Are you sure you want to delete this route?"
                                    class="text-red-400 hover:text-red-300 hover:underline transition-colors font-bold">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center" style="color: var(--tenant-card-muted, #94a3b8);">
                                @if($search || $selectedRouteType || $filterDepIcao || $filterArrIcao || $filterAircraftType)
                                    No routes matched your filter. <button wire:click="resetFilters"
                                        class="text-tenant-accent underline ml-1 font-semibold">Reset filters</button>
                                @else
                                    No routes in your network. Click <strong>Add Route</strong> or <strong>Global Network
                                        Import</strong> above!
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($routes->hasPages())
            <div class="px-6 py-4 border-t"
                style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                {{ $routes->links() }}
            </div>
        @endif
    </div>

    <!-- Add/Edit Route Modal -->
    <x-dialog-modal wire:model.live="showAddModal">
        <x-slot name="title">
            {{ $editMode ? __('Edit Route') : __('Add New Route') }}
        </x-slot>

        <x-slot name="content">
            <!-- Flight Number & Route Type -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label for="flight_number" value="{{ __('Flight Number (e.g. U28161 / FR2605 / 1181)') }}"
                        class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                        style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="flight_number" type="text" class="mt-1 block w-full uppercase font-mono font-bold"
                        wire:model="flight_number" placeholder="e.g. U28161 or 1181" />
                    <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Commercial flight
                        identifier (IATA/number).</p>
                    <x-input-error for="flight_number" class="mt-2 text-red-400 text-xs" />
                </div>
                <div class="col-span-1">
                    <x-label for="route_type" value="{{ __('Route Type') }}"
                        class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                        style="color: var(--tenant-card-text, #ffffff);" />
                    <select id="route_type" wire:model="route_type"
                        class="mt-1 block w-full rounded-xl shadow-sm text-sm"
                        style="background-color: var(--tenant-input-bg, #141923); color: var(--tenant-input-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                        <option value="Scheduled">Scheduled</option>
                        <option value="Charter">Charter</option>
                        <option value="Cargo">Cargo</option>
                    </select>
                    <x-input-error for="route_type" class="mt-2 text-red-400 text-xs" />
                </div>
            </div>

            <!-- ATC Callsign Selection (ICAO Prefix Dropdown + Callsign Suffix) -->
            <div class="p-4 rounded-xl border mb-4 space-y-2"
                style="background-color: var(--tenant-input-bg, #141923); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                <div class="flex items-center justify-between">
                    <x-label value="{{ __('ATC Callsign Configuration (ICAO Prefix + Suffix)') }}"
                        class="text-tenant-accent font-bold text-xs uppercase tracking-wider" />
                    @if($callsign_icao && $callsign_suffix)
                        <span class="px-2.5 py-1 rounded text-tenant-accent font-mono text-xs font-bold border shadow-sm"
                            style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-accent);">
                            Preview: {{ strtoupper($callsign_icao . $callsign_suffix) }}
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-3 pt-1">
                    <!-- Callsign ICAO Prefix Dropdown -->
                    <div class="col-span-1">
                        <x-label for="callsign_icao" value="{{ __('Airline ICAO') }}" class="text-xs mb-1"
                            style="color: var(--tenant-card-muted, #94a3b8);" />
                        <select id="callsign_icao" wire:model.live="callsign_icao"
                            class="block w-full rounded-xl shadow-sm text-sm font-mono font-bold"
                            style="background-color: var(--tenant-input-bg, #141923); color: var(--tenant-input-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                            @foreach($availableIcaos as $icaoOption)
                                <option value="{{ $icaoOption }}">{{ $icaoOption }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="callsign_icao" class="mt-1 text-red-400 text-xs" />
                    </div>

                    <!-- Callsign Suffix (Numbers/Letters) -->
                    <div class="col-span-2">
                        <x-label for="callsign_suffix" value="{{ __('Callsign Number / Suffix') }}" class="text-xs mb-1"
                            style="color: var(--tenant-card-muted, #94a3b8);" />
                        <x-input id="callsign_suffix" type="text" wire:model.live="callsign_suffix" maxlength="8"
                            class="block w-full uppercase font-mono font-bold text-sm"
                            placeholder="e.g. 508HZ or 8161" />
                        <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">e.g. <span
                                class="font-mono">508HZ</span> creates <span
                                class="font-mono text-tenant-accent font-bold">{{ strtoupper($callsign_icao ?: 'EZY') }}508HZ</span>
                        </p>
                        <x-input-error for="callsign_suffix" class="mt-1 text-red-400 text-xs" />
                    </div>
                </div>
            </div>

            <!-- Departure & Arrival ICAOs -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label for="departure_icao" value="{{ __('Departure ICAO') }}"
                        class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                        style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="departure_icao" type="text" class="mt-1 block w-full uppercase font-mono font-bold"
                        wire:model="departure_icao" placeholder="e.g. EGLL" maxlength="4" />
                    <x-input-error for="departure_icao" class="mt-2 text-red-400 text-xs" />
                </div>
                <div class="col-span-1">
                    <x-label for="arrival_icao" value="{{ __('Arrival ICAO') }}"
                        class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                        style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="arrival_icao" type="text" class="mt-1 block w-full uppercase font-mono font-bold"
                        wire:model="arrival_icao" placeholder="e.g. LFPG" maxlength="4" />
                    <x-input-error for="arrival_icao" class="mt-2 text-red-400 text-xs" />
                </div>
            </div>

            <!-- Block Time & Distance -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label for="block_time" value="{{ __('Block Time (HH:MM)') }}"
                        class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                        style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="block_time" type="text" class="mt-1 block w-full font-mono" wire:model="block_time"
                        placeholder="e.g. 02:30 (Auto if blank)" />
                    <x-input-error for="block_time" class="mt-2 text-red-400 text-xs" />
                </div>
                <div class="col-span-1">
                    <x-label for="distance" value="{{ __('Distance (NM)') }}"
                        class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                        style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="distance" type="number" class="mt-1 block w-full font-mono" wire:model="distance"
                        placeholder="Auto if blank" />
                    <x-input-error for="distance" class="mt-2 text-red-400 text-xs" />
                </div>
            </div>

            <!-- Route String -->
            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="route_string" value="{{ __('Route String (Optional)') }}"
                    class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                    style="color: var(--tenant-card-text, #ffffff);" />
                <x-input id="route_string" type="text" class="mt-1 block w-full font-mono uppercase text-sm"
                    wire:model="route_string" placeholder="e.g. DCT BOVIS L608..." />
                <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Leave empty to have SimBrief
                    calculate the route automatically.</p>
                <x-input-error for="route_string" class="mt-2 text-red-400 text-xs" />
            </div>

            <!-- Allowed Aircraft Types -->
            <div class="col-span-6 sm:col-span-4">
                <x-label value="{{ __('Allowed Aircraft Types') }}"
                    class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                    style="color: var(--tenant-card-text, #ffffff);" />
                <div class="mt-2 grid grid-cols-2 gap-2 max-h-36 overflow-y-auto rounded-xl p-3 border"
                    style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                    @foreach($aircraftTypes as $type)
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="selectedAircraftTypes" value="{{ $type->id }}"
                                class="rounded bg-black/50 border-gray-600 text-tenant-accent shadow-sm focus:ring-tenant-accent cursor-pointer">
                            <span class="ml-2 text-sm font-mono font-medium"
                                style="color: var(--tenant-card-text, #ffffff);">{{ $type->code }} -
                                {{ $type->name }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error for="selectedAircraftTypes" class="mt-2 text-red-400 text-xs" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddModal', false)" wire:loading.attr="disabled"
                class="btn-secondary px-5 py-2.5 rounded-xl font-bold text-sm">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveRoute" wire:loading.attr="disabled"
                class="ml-3 btn-primary px-6 py-2.5 rounded-xl text-sm font-bold shadow-md transition">
                {{ $editMode ? __('Save Changes') : __('Save Route') }}
            </button>
        </x-slot>
    </x-dialog-modal>

    <!-- Mass Update Modal -->
    <x-dialog-modal wire:model.live="showMassUpdateModal">
        <x-slot name="title">
            {{ __('Mass Update Selected Routes (:count routes)', ['count' => count($selectedRoutes)]) }}
        </x-slot>

        <x-slot name="content">
            <p class="text-xs mb-4" style="color: var(--tenant-card-muted, #94a3b8);">
                Update the ATC Airline ICAO Prefix and re-generate full callsigns for all
                <strong>{{ count($selectedRoutes) }}</strong> selected routes.
            </p>

            <!-- Airline ICAO selection -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label for="massTargetIcao" value="{{ __('New Airline ICAO Prefix') }}"
                        class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                        style="color: var(--tenant-card-text, #ffffff);" />
                    <select id="massTargetIcao" wire:model="massTargetIcao"
                        class="block w-full rounded-xl shadow-sm text-sm font-mono font-bold"
                        style="background-color: var(--tenant-input-bg, #141923); color: var(--tenant-input-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                        @foreach($availableIcaos as $icaoOpt)
                            <option value="{{ $icaoOpt }}">{{ $icaoOpt }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-1">
                    <x-label for="massStripPrefix" value="{{ __('Flight # Prefix to Cut Off (Optional)') }}"
                        class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                        style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="massStripPrefix" type="text" wire:model="massStripPrefix"
                        placeholder="e.g. U2 or FR (auto if blank)" class="block w-full uppercase font-mono" />
                </div>
            </div>

            <div class="p-3 rounded-xl border mb-4 text-xs"
                style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.15)); color: var(--tenant-card-muted, #94a3b8);">
                Example: If a selected route has Flight Number <strong
                    style="color: var(--tenant-card-text, #ffffff);">U29999</strong> and prefix is set to <strong
                    class="text-tenant-accent">U2</strong>, its ATC Callsign will become <strong
                    class="text-tenant-accent font-mono">{{ strtoupper($massTargetIcao ?: 'EZY') }}9999</strong>.
            </div>

            <!-- Mass Assign Aircraft Types -->
            <div class="mb-4">
                <x-label value="{{ __('Assign Aircraft Types to Selected (Optional)') }}"
                    class="font-semibold text-xs uppercase tracking-wider mb-1.5"
                    style="color: var(--tenant-card-text, #ffffff);" />
                <div class="grid grid-cols-2 gap-2 max-h-36 overflow-y-auto rounded-xl p-3 border"
                    style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                    @foreach($aircraftTypes as $type)
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="massSelectedAircraftTypes" value="{{ $type->id }}"
                                class="rounded bg-black/50 border-gray-600 text-tenant-accent shadow-sm focus:ring-tenant-accent cursor-pointer">
                            <span class="ml-2 text-sm font-mono font-medium"
                                style="color: var(--tenant-card-text, #ffffff);">{{ $type->code }} -
                                {{ $type->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showMassUpdateModal', false)" wire:loading.attr="disabled"
                class="btn-secondary px-5 py-2.5 rounded-xl font-bold text-sm">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="applyMassUpdate" wire:loading.attr="disabled"
                class="ml-3 btn-primary px-6 py-2.5 rounded-xl text-sm font-bold shadow-md transition">
                {{ __('Apply to :count Route(s)', ['count' => count($selectedRoutes)]) }}
            </button>
        </x-slot>
    </x-dialog-modal>
</div>