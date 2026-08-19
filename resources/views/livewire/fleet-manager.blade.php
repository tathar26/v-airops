<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    @if (session()->has('message'))
        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl mt-6">
        <div class="px-6 py-4 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h3 class="text-base font-bold text-white tracking-wide">FLEET MANAGER &amp; AIRFRAMES</h3>
                <p class="text-xs text-gray-400 mt-0.5">Manage virtual airline airframe registrations, locations, and status</p>
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
                            No aircraft in your fleet. Click <strong>Add Airframe</strong> or <strong>Import Airframes</strong> above to build your fleet!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
    <x-dialog-modal wire:model.live="showGlobalImportModal" maxWidth="3xl">
        <x-slot name="title">
            {{ __('Import Airframes from Global Repository') }}
        </x-slot>

        <x-slot name="content">
            <!-- Mode Toggle Tabs -->
            <div class="flex border-b border-white/10 mb-4">
                <button wire:click="$set('importMode', 'real_world')" class="py-2 px-4 text-sm font-semibold border-b-2 transition-colors {{ $importMode === 'real_world' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent text-gray-400 hover:text-white' }}">
                    Real-World Registrations (OpenFlights Repository)
                </button>
                <button wire:click="$set('importMode', 'generate')" class="py-2 px-4 text-sm font-semibold border-b-2 transition-colors {{ $importMode === 'generate' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent text-gray-400 hover:text-white' }}">
                    Auto-Generate / Custom
                </button>
            </div>

            @if($importMode === 'real_world')
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

            @if($importMode === 'real_world')
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
