<div class="space-y-6">
    @if (session()->has('message'))
        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl mt-6">
        <div class="px-6 py-4 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h3 class="text-base font-bold text-white tracking-wide">FLEET MANAGEMENT &amp; AIRFRAMES</h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Manage virtual airline airframe registrations, aircraft types, and status &bull;
                    <span class="text-tenant-accent font-semibold">{{ number_format($totalAirframesCount) }} Total Airframes</span>
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <button wire:click="downloadTemplate" class="text-tenant-accent hover:underline transition-colors text-sm font-semibold">
                    Download CSV Template
                </button>
                <div class="relative flex items-center">
                    <input type="file" wire:model="csvFile" id="csvFile" class="hidden" accept=".csv,.txt" />
                    <label for="csvFile" class="cursor-pointer bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition border border-slate-700 mr-2 inline-flex items-center gap-1.5">
                        Select CSV
                    </label>
                    @if($csvFile)
                        <button wire:click="importCsv" class="bg-tenant-accent text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm hover:opacity-90 transition">
                            Import
                        </button>
                    @endif
                </div>
                <button wire:click="openGlobalImportModal" class="bg-slate-800 hover:bg-slate-700 border border-tenant-accent/40 text-tenant-accent px-4 py-2 rounded-xl text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Import Airframes
                </button>
                <button wire:click="openAddModal" class="bg-tenant-accent text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Airframe
                </button>
            </div>
        </div>

        <!-- Search & Filter Controls with Autocomplete -->
        <div class="px-6 py-3.5 bg-[#141923] border-b border-white/5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-1 min-w-[280px] max-w-md">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" 
                           list="airframe-autocomplete" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search by registration, type, aircraft name..." 
                           class="pl-9 pr-4 py-2 rounded-xl text-xs w-full bg-slate-900 border border-slate-700 text-white placeholder-slate-500 focus:border-tenant-accent focus:ring-1 focus:ring-tenant-accent transition" />
                    
                    <datalist id="airframe-autocomplete">
                        @foreach($autocompleteList as $item)
                            <option value="{{ $item }}"></option>
                        @endforeach
                    </datalist>
                </div>
                @if($search || $filterAircraftType)
                    <button wire:click="resetFilters" class="text-xs text-slate-400 hover:text-white px-2 py-1">Clear</button>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <x-search-select 
                    wireModel="filterAircraftType" 
                    :selected="$filterAircraftType" 
                    label="Type" 
                    allLabel="All Aircraft Types" 
                    placeholder="Search aircraft types..." 
                    :options="$aircraftTypes->map(fn($t) => ['id' => $t->id, 'code' => $t->code, 'name' => $t->name])->toArray()" 
                    minWidth="min-w-[170px]" />

                <select wire:model.live="perPage" class="bg-slate-900 border border-slate-700 text-white text-xs font-semibold rounded-xl px-3.5 py-2 min-w-[120px] focus:border-tenant-accent shadow-sm">
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider">Registration</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($airframes as $airframe)
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-bold text-tenant-accent">
                            {{ $airframe->registration }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold font-mono rounded-lg bg-tenant-accent/15 text-tenant-accent">
                                {{ $airframe->aircraftType->code ?? 'Unknown' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            {{ $airframe->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="editAirframe({{ $airframe->id }})" class="text-tenant-accent hover:opacity-80 transition-colors mr-3 font-semibold">Edit</button>
                            <button wire:click="deleteAirframe({{ $airframe->id }})" wire:confirm="Are you sure you want to delete this airframe?" class="text-red-400 hover:text-red-300 transition-colors font-semibold">Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                            @if($search || $filterAircraftType)
                                No airframes match your search filter. <button wire:click="resetFilters" class="text-tenant-accent underline ml-1">Reset filter</button>
                            @else
                                No aircraft in your fleet. Click <strong>Add Airframe</strong> or <strong>Import Airframes</strong> above to build your fleet!
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($airframes->hasPages())
            <div class="px-6 py-4 bg-[#141923] border-t border-white/5">
                {{ $airframes->links() }}
            </div>
        @endif
    </div>

    <!-- Add/Edit Airframe Modal -->
    <x-dialog-modal wire:model.live="showAddModal">
        <x-slot name="title">
            {{ $editMode ? __('Edit Airframe') : __('Add New Airframe') }}
        </x-slot>

        <x-slot name="content">
            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="registration" value="{{ __('Registration') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                <x-input id="registration" type="text" class="mt-1 block w-full uppercase" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" wire:model="registration" placeholder="e.g. G-EZYM" />
                <x-input-error for="registration" class="mt-2 text-red-400 text-xs" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="aircraft_type_id" value="{{ __('Aircraft Type') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                <select id="aircraft_type_id" wire:model="aircraft_type_id" class="mt-1 block w-full rounded-xl shadow-sm text-sm focus:border-tenant-accent" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important; padding: 0.625rem 0.875rem;">
                    <option value="" style="background-color: #1e293b; color: #ffffff;">Select a type...</option>
                    @foreach($aircraftTypes as $type)
                        <option value="{{ $type->id }}" style="background-color: #1e293b; color: #ffffff;">{{ $type->code }} - {{ $type->name }}</option>
                    @endforeach
                </select>
                <x-input-error for="aircraft_type_id" class="mt-2 text-red-400 text-xs" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-label for="name" value="{{ __('Name (Optional)') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                <x-input id="name" type="text" class="mt-1 block w-full" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" wire:model="name" placeholder="e.g. Spirit of easyJet" />
                <x-input-error for="name" class="mt-2 text-red-400 text-xs" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddModal', false)" wire:loading.attr="disabled" class="bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white border-slate-700">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveAirframe" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md hover:opacity-90 transition">
                {{ $editMode ? __('Save Changes') : __('Save Airframe') }}
            </button>
        </x-slot>
    </x-dialog-modal>

    <!-- Global Fleet / Airframes Import Modal -->
    <x-dialog-modal wire:model.live="showGlobalImportModal" maxWidth="4xl">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-tenant-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span>{{ __('Import Airframes') }}</span>
            </div>
        </x-slot>

        <x-slot name="content">
            <!-- Mode Toggle Tabs -->
            <div class="flex border-b border-white/10 mb-5 gap-2">
                <button wire:click="$set('importMode', 'api')" class="py-2.5 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 {{ $importMode === 'api' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent text-gray-400 hover:text-white' }}">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Live Airline Fleet API (OpenSky)
                </button>
                <button wire:click="$set('importMode', 'real_world')" class="py-2.5 px-4 text-sm font-semibold border-b-2 transition-colors {{ $importMode === 'real_world' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent text-gray-400 hover:text-white' }}">
                    Internal Repository (OpenFlights)
                </button>
                <button wire:click="$set('importMode', 'generate')" class="py-2.5 px-4 text-sm font-semibold border-b-2 transition-colors {{ $importMode === 'generate' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent text-gray-400 hover:text-white' }}">
                    Auto-Generate / Custom
                </button>
            </div>

            @if($importMode === 'api')
                <!-- Live Airline Fleet Database API Mode -->
                <div class="space-y-4">
                    <div class="bg-slate-900/80 border border-white/10 rounded-xl p-4">
                        <div class="flex flex-col sm:flex-row items-end gap-3">
                            <div class="flex-1 w-full">
                                <x-label for="apiOperatorIcao" value="{{ __('Operator / Airline 3-Letter ICAO') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                                <div class="relative">
                                    <x-input id="apiOperatorIcao" type="text" wire:model="apiOperatorIcao" wire:keydown.enter="fetchApiFleet" placeholder="e.g. DLH, KLM, BAW, EZY, RYR, AFR" class="block w-full uppercase font-mono text-sm tracking-wider" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" />
                                </div>
                            </div>
                            <div class="w-full sm:w-36">
                                <x-label for="apiLimit" value="{{ __('Fetch Limit') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                                <select id="apiLimit" wire:model="apiLimit" class="block w-full rounded-xl text-sm" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important; padding: 0.625rem 0.875rem;">
                                    <option value="100">100 aircraft</option>
                                    <option value="250">250 aircraft</option>
                                    <option value="500">500 aircraft</option>
                                    <option value="1000">1,000 aircraft</option>
                                    <option value="2000">2,000 aircraft</option>
                                </select>
                            </div>
                            <div>
                                <button type="button" wire:click="fetchApiFleet" wire:loading.attr="disabled" class="w-full sm:w-auto bg-tenant-accent hover:opacity-90 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition shadow flex items-center justify-center gap-2">
                                    <span wire:loading.remove wire:target="fetchApiFleet">
                                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        Fetch Fleet
                                    </span>
                                    <span wire:loading wire:target="fetchApiFleet" class="inline-flex items-center gap-2">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Fetching...
                                    </span>
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">
                            Connects to the Central Fleet Database to retrieve live airline registrations, ICAO airframe type codes, and manufacturer models.
                        </p>
                    </div>

                    @if($apiErrorMessage)
                        <div class="bg-red-500/20 border border-red-500/50 text-red-200 px-4 py-3 rounded-xl text-sm flex items-center justify-between">
                            <span>{{ $apiErrorMessage }}</span>
                            <button type="button" wire:click="$set('apiErrorMessage', null)" class="text-red-300 hover:text-white text-xs ml-2">&times;</button>
                        </div>
                    @endif

                    @if($apiSuccessMessage)
                        <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-200 px-4 py-2.5 rounded-xl text-xs flex items-center justify-between">
                            <span>{{ $apiSuccessMessage }}</span>
                            <span class="text-slate-400 font-mono">{{ count($selectedApiAirframes) }} selected</span>
                        </div>
                    @endif

                    @if(!empty($apiFleetResults))
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="relative w-full sm:w-72">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <input type="text" wire:model.live.debounce.250ms="searchApiFleet" placeholder="Filter by reg, type, model..." class="pl-8 pr-3 py-1.5 rounded-xl text-xs w-full bg-slate-900 border border-slate-700 text-white placeholder-slate-500 focus:border-tenant-accent focus:ring-1 focus:ring-tenant-accent transition" />
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-400 w-full sm:w-auto justify-between sm:justify-end">
                                <span>Showing {{ count($filteredApiFleet) }} of {{ count($apiFleetResults) }}</span>
                                <button type="button" wire:click="$set('selectedApiAirframes', {{ json_encode(array_values(array_filter(array_map(fn($item) => strtoupper($item['registration'] ?? ''), $filteredApiFleet)))) }})" class="text-tenant-accent hover:underline font-semibold ml-2">
                                    Select Visible ({{ count($filteredApiFleet) }})
                                </button>
                                <span>&bull;</span>
                                <button type="button" wire:click="$set('selectedApiAirframes', [])" class="text-slate-400 hover:text-white font-semibold">
                                    Clear
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-slate-700 max-h-80" style="background-color: #020617 !important;">
                            <table class="min-w-full divide-y divide-slate-800 text-sm">
                                <thead class="sticky top-0" style="background-color: #0f172a !important;">
                                    <tr>
                                        <th class="p-3 w-12 text-center">
                                            <input type="checkbox" wire:model.live="selectAllApiAirframes" class="rounded bg-slate-900 border-slate-700 text-tenant-accent focus:ring-tenant-accent">
                                        </th>
                                        <th class="p-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Registration</th>
                                        <th class="p-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Type</th>
                                        <th class="p-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Manufacturer &amp; Model</th>
                                        <th class="p-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">ICAO24 (Hex)</th>
                                        <th class="p-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Built</th>
                                        <th class="p-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800 text-white">
                                    @forelse($filteredApiFleet as $item)
                                        @php
                                            $regUpper = strtoupper(trim($item['registration'] ?? ''));
                                            $inFleet = isset($existingRegistrations[$regUpper]);
                                        @endphp
                                        <tr class="hover:bg-slate-800/50 transition-colors {{ in_array($regUpper, $selectedApiAirframes) ? 'bg-tenant-accent/10' : '' }}">
                                            <td class="p-3 text-center">
                                                <input type="checkbox" wire:model.live="selectedApiAirframes" value="{{ $regUpper }}" class="rounded bg-slate-900 border-slate-700 text-tenant-accent focus:ring-tenant-accent">
                                            </td>
                                            <td class="p-3 font-mono font-bold text-tenant-accent">
                                                {{ $regUpper }}
                                            </td>
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 inline-flex text-xs font-bold font-mono rounded bg-white/10 text-slate-200">
                                                    {{ $item['resolved_typecode'] ?? ($item['typecode'] ?: 'N/A') }}
                                                </span>
                                            </td>
                                            <td class="p-3 text-slate-300">
                                                <div class="font-medium text-white">{{ $item['model'] ?: 'Unknown Model' }}</div>
                                                <div class="text-[11px] text-slate-400">{{ $item['manufacturername'] ?: ($item['manufacturericao'] ?? '') }}</div>
                                            </td>
                                            <td class="p-3 font-mono text-xs text-slate-400">
                                                {{ $item['icao24'] ?? 'N/A' }}
                                            </td>
                                            <td class="p-3 text-xs text-slate-400">
                                                {{ $item['built'] ?? 'N/A' }}
                                            </td>
                                            <td class="p-3 text-right">
                                                @if($inFleet)
                                                    <span class="px-2 py-0.5 inline-flex text-[10px] font-bold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                        In Fleet
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 inline-flex text-[10px] font-bold rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                                        New
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="p-6 text-center text-slate-500">
                                                No aircraft match the filter criteria.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

            @elseif($importMode === 'real_world')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                    <div>
                        <x-label for="filterAircraftCode" value="{{ __('Filter by Aircraft Type') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                        <select id="filterAircraftCode" wire:model.live="filterAircraftCode" class="mt-1 block w-full rounded-xl shadow-sm text-sm focus:border-tenant-accent" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important; padding: 0.625rem 0.875rem;">
                            <option value="" style="background-color: #1e293b; color: #ffffff;">All Aircraft Types</option>
                            @foreach($globalAircraftTypes as $gType)
                                <option value="{{ $gType->code }}" style="background-color: #1e293b; color: #ffffff;">{{ $gType->code }} - {{ $gType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-label for="searchRealWorld" value="{{ __('Search Registration or Operator') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                        <x-input id="searchRealWorld" type="text" wire:model.live.debounce.300ms="searchRealWorld" placeholder="Search (e.g. G-EZY, KLM, N737, Ryanair...)" class="mt-1 block w-full text-sm" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" />
                    </div>
                </div>

                @if($realWorldAirframes)
                    <div class="overflow-x-auto rounded-xl border border-slate-700 max-h-80" style="background-color: #020617 !important;">
                        <table class="min-w-full divide-y divide-slate-800 text-sm">
                            <thead class="sticky top-0" style="background-color: #0f172a !important;">
                                <tr>
                                    <th class="p-3 w-12 text-center">
                                        <input type="checkbox" wire:model.live="selectAllRealWorld" class="rounded bg-slate-900 border-slate-700 text-tenant-accent focus:ring-tenant-accent">
                                    </th>
                                    <th class="p-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Registration</th>
                                    <th class="p-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">ICAO Code</th>
                                    <th class="p-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Operator / Model</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 text-white">
                                @forelse($realWorldAirframes as $airframeItem)
                                    <tr class="hover:bg-slate-800/50 transition-colors">
                                        <td class="p-3 text-center">
                                            <input type="checkbox" wire:model.live="selectedRealWorldAirframes" value="{{ $airframeItem->id }}" class="rounded bg-slate-900 border-slate-700 text-tenant-accent focus:ring-tenant-accent">
                                        </td>
                                        <td class="p-3 font-mono font-bold text-tenant-accent">
                                            {{ $airframeItem->registration }}
                                        </td>
                                        <td class="p-3 font-mono text-slate-300 font-bold">
                                            {{ $airframeItem->icao_code }}
                                        </td>
                                        <td class="p-3 text-slate-300">
                                            {{ $airframeItem->operator ?: $airframeItem->name ?: 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-6 text-center text-slate-500">
                                            No real-world airframes found in repository matching search.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $realWorldAirframes->links() }}
                    </div>
                @endif
            @else
                <div class="col-span-6 sm:col-span-4 mb-4">
                    <x-label for="globalAircraftCode" value="{{ __('Select Aircraft Type from Global Repository') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                    <select id="globalAircraftCode" wire:model="globalAircraftCode" class="mt-1 block w-full rounded-xl shadow-sm text-sm focus:border-tenant-accent" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important; padding: 0.625rem 0.875rem;">
                        <option value="" style="background-color: #1e293b; color: #ffffff;">Select a global aircraft type...</option>
                        @foreach($globalAircraftTypes as $gType)
                            <option value="{{ $gType->code }}" style="background-color: #1e293b; color: #ffffff;">{{ $gType->code }} - {{ $gType->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="globalAircraftCode" class="mt-2 text-red-400 text-xs" />
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <x-label for="registrationPrefix" value="{{ __('Registration Prefix') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                        <x-input id="registrationPrefix" type="text" class="mt-1 block w-full uppercase" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" wire:model="registrationPrefix" placeholder="e.g. G-, PH-, N-, D-" />
                    </div>
                    <div>
                        <x-label for="quantityToGenerate" value="{{ __('Quantity to Generate') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                        <x-input id="quantityToGenerate" type="number" min="1" max="50" class="mt-1 block w-full" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" wire:model="quantityToGenerate" />
                    </div>
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="customRegistrationsText" value="{{ __('Or Enter Custom Registrations (Optional - comma or line separated)') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                    <textarea id="customRegistrationsText" wire:model="customRegistrationsText" rows="3" class="mt-1 block w-full rounded-xl uppercase font-mono text-sm" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" placeholder="e.g. G-EZYA, G-EZYB, G-EZYC"></textarea>
                    <p class="text-xs text-slate-400 mt-1">If specified, custom registrations will be used instead of auto-generated ones.</p>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showGlobalImportModal', false)" wire:loading.attr="disabled" class="bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white border-slate-700">
                {{ __('Cancel') }}
            </x-secondary-button>

            @if($importMode === 'api')
                @if(!empty($apiFleetResults))
                    <button type="button" wire:click="importAllApiAirframes" wire:loading.attr="disabled" class="ml-3 bg-slate-800 hover:bg-slate-700 border border-tenant-accent/40 text-tenant-accent px-4 py-2 rounded-xl text-sm font-bold shadow transition">
                        {{ __('Import Entire Fleet') }} ({{ count($apiFleetResults) }})
                    </button>
                    <button type="button" wire:click="importSelectedApiAirframes" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md hover:opacity-90 transition">
                        {{ __('Import Selected') }} ({{ count($selectedApiAirframes) }})
                    </button>
                @endif
            @elseif($importMode === 'real_world')
                <button wire:click="importSelectedRealWorldAirframes" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md hover:opacity-90 transition">
                    {{ __('Import Selected Real-World Airframes') }} ({{ count($selectedRealWorldAirframes) }})
                </button>
            @else
                <button wire:click="importGlobalAirframes" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md hover:opacity-90 transition">
                    {{ __('Import Airframes') }}
                </button>
            @endif
        </x-slot>
    </x-dialog-modal>
</div>
