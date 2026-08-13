<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    @if (session()->has('message'))
        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="glass-panel overflow-hidden mt-6">
        <div class="px-6 py-5 border-b border-white/10 flex justify-between items-center flex-wrap gap-4">
            <h3 class="text-lg font-medium text-white">Fleet Manager</h3>
            <div class="flex items-center gap-3">
                <button wire:click="downloadTemplate" class="text-gray-400 hover:text-white transition-colors text-sm underline">
                    Download CSV Template
                </button>
                <div class="relative flex items-center">
                    <input type="file" wire:model="csvFile" id="csvFile" class="hidden" accept=".csv,.txt" />
                    <label for="csvFile" class="cursor-pointer bg-[#212631] border border-gray-600 text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition mr-2">
                        Select CSV
                    </label>
                    @if($csvFile)
                        <button wire:click="importCsv" class="bg-vops-secondary text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
                            Import
                        </button>
                    @endif
                </div>
                <button wire:click="openGlobalImportModal" class="bg-[#212631] border border-tenant-accent text-tenant-accent px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Import Airframes
                </button>
                <button wire:click="openAddModal" class="bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Airframe
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Registration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($airframes as $airframe)
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-white">
                            {{ $airframe->registration }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-tenant-accent/20 text-tenant-accent">
                                {{ $airframe->aircraftType->code ?? 'Unknown' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            {{ $airframe->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="editAirframe({{ $airframe->id }})" class="text-tenant-accent hover:opacity-80 transition-colors mr-3">Edit</button>
                            <button wire:click="deleteAirframe({{ $airframe->id }})" wire:confirm="Are you sure you want to delete this airframe?" class="text-red-400 hover:text-red-300 transition-colors">Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                            No aircraft in your fleet.
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
                <x-label for="registration" value="{{ __('Registration') }}" />
                <x-input id="registration" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white uppercase" wire:model="registration" placeholder="e.g. G-EZYM" />
                <x-input-error for="registration" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="aircraft_type_id" value="{{ __('Aircraft Type') }}" />
                <select id="aircraft_type_id" wire:model="aircraft_type_id" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white rounded-md shadow-sm focus:border-tenant-accent focus:ring focus:ring-tenant-accent focus:ring-opacity-50">
                    <option value="">Select a type...</option>
                    @foreach($aircraftTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->code }} - {{ $type->name }}</option>
                    @endforeach
                </select>
                <x-input-error for="aircraft_type_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-label for="name" value="{{ __('Name (Optional)') }}" />
                <x-input id="name" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="name" placeholder="e.g. Spirit of easyJet" />
                <x-input-error for="name" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddModal', false)" wire:loading.attr="disabled" class="bg-gray-600 text-white hover:bg-gray-500 border-none">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveAirframe" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
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
                        <x-label for="filterAircraftCode" value="{{ __('Filter by Aircraft Type') }}" />
                        <select id="filterAircraftCode" wire:model.live="filterAircraftCode" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white rounded-md shadow-sm text-sm">
                            <option value="">All Aircraft Types</option>
                            @foreach($globalAircraftTypes as $gType)
                                <option value="{{ $gType->code }}">{{ $gType->code }} - {{ $gType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-label for="searchRealWorld" value="{{ __('Search Registration or Operator') }}" />
                        <x-input id="searchRealWorld" type="text" wire:model.live.debounce.300ms="searchRealWorld" placeholder="Search (e.g. G-EZY, KLM, N737, Ryanair...)" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white text-sm" />
                    </div>
                </div>

                @if($realWorldAirframes)
                    <div class="overflow-x-auto bg-black/20 rounded-lg border border-white/5 max-h-80">
                        <table class="min-w-full divide-y divide-white/5 text-sm">
                            <thead class="bg-white/5 sticky top-0 bg-[#151C2C]">
                                <tr>
                                    <th class="p-3 w-12 text-center">
                                        <input type="checkbox" wire:model.live="selectAllRealWorld" class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent">
                                    </th>
                                    <th class="p-3 text-left text-xs font-medium text-gray-400 uppercase">Registration</th>
                                    <th class="p-3 text-left text-xs font-medium text-gray-400 uppercase">ICAO Code</th>
                                    <th class="p-3 text-left text-xs font-medium text-gray-400 uppercase">Operator / Model</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-white">
                                @forelse($realWorldAirframes as $airframeItem)
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="p-3 text-center">
                                            <input type="checkbox" wire:model.live="selectedRealWorldAirframes" value="{{ $airframeItem->id }}" class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent">
                                        </td>
                                        <td class="p-3 font-mono font-bold text-tenant-accent">
                                            {{ $airframeItem->registration }}
                                        </td>
                                        <td class="p-3 font-mono text-gray-300">
                                            {{ $airframeItem->icao_code }}
                                        </td>
                                        <td class="p-3 text-gray-300">
                                            {{ $airframeItem->operator ?: $airframeItem->name ?: 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-6 text-center text-gray-500">
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
                    <x-label for="globalAircraftCode" value="{{ __('Select Aircraft Type from Global Repository') }}" />
                    <select id="globalAircraftCode" wire:model="globalAircraftCode" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white rounded-md shadow-sm focus:border-tenant-accent focus:ring focus:ring-tenant-accent focus:ring-opacity-50">
                        <option value="">Select a global aircraft type...</option>
                        @foreach($globalAircraftTypes as $gType)
                            <option value="{{ $gType->code }}">{{ $gType->code }} - {{ $gType->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="globalAircraftCode" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <x-label for="registrationPrefix" value="{{ __('Registration Prefix') }}" />
                        <x-input id="registrationPrefix" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white uppercase" wire:model="registrationPrefix" placeholder="e.g. G-, PH-, N-, D-" />
                    </div>
                    <div>
                        <x-label for="quantityToGenerate" value="{{ __('Quantity to Generate') }}" />
                        <x-input id="quantityToGenerate" type="number" min="1" max="50" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="quantityToGenerate" />
                    </div>
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="customRegistrationsText" value="{{ __('Or Enter Custom Registrations (Optional - comma or line separated)') }}" />
                    <textarea id="customRegistrationsText" wire:model="customRegistrationsText" rows="3" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white rounded-md uppercase" placeholder="e.g. G-EZYA, G-EZYB, G-EZYC"></textarea>
                    <p class="text-xs text-gray-400 mt-1">If specified, custom registrations will be used instead of auto-generated ones.</p>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showGlobalImportModal', false)" wire:loading.attr="disabled" class="bg-gray-600 text-white hover:bg-gray-500 border-none">
                {{ __('Cancel') }}
            </x-secondary-button>

            @if($importMode === 'real_world')
                <button wire:click="importSelectedRealWorldAirframes" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
                    {{ __('Import Selected Real-World Airframes') }} ({{ count($selectedRealWorldAirframes) }})
                </button>
            @else
                <button wire:click="importGlobalAirframes" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
                    {{ __('Import Airframes') }}
                </button>
            @endif
        </x-slot>
    </x-dialog-modal>
</div>
