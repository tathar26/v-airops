<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    @if (session()->has('message'))
        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="glass-panel overflow-hidden mt-6">
        <div class="px-6 py-5 border-b border-white/10 flex justify-between items-center flex-wrap gap-4">
            <h3 class="text-lg font-medium text-white">Aircraft Types</h3>
            <div class="flex items-center gap-3">
                <button wire:click="openGlobalImportModal" class="bg-[#212631] border border-tenant-accent text-tenant-accent px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Import from Global Repository
                </button>
                <button wire:click="openAddModal" class="bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Type
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($aircraftTypes as $type)
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-white">
                            {{ $type->code }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            {{ $type->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="editAircraftType({{ $type->id }})" class="text-tenant-accent hover:opacity-80 transition-colors mr-3">Edit</button>
                            <button wire:click="deleteAircraftType({{ $type->id }})" wire:confirm="Are you sure you want to delete this aircraft type? This may affect associated fleet and routes." class="text-red-400 hover:text-red-300 transition-colors">Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-gray-400">
                            No aircraft types defined.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Aircraft Type Modal -->
    <x-dialog-modal wire:model.live="showAddModal">
        <x-slot name="title">
            {{ $editMode ? __('Edit Aircraft Type') : __('Add New Aircraft Type') }}
        </x-slot>

        <x-slot name="content">
            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="code" value="{{ __('Code (e.g. A320)') }}" />
                <x-input id="code" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white uppercase" wire:model="code" placeholder="e.g. A320" maxlength="10" />
                <x-input-error for="code" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="name" value="{{ __('Name (e.g. Airbus A320-200)') }}" />
                <x-input id="name" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="name" placeholder="e.g. Airbus A320-200" />
                <x-input-error for="name" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddModal', false)" wire:loading.attr="disabled" class="bg-gray-600 text-white hover:bg-gray-500 border-none">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveAircraftType" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
                {{ $editMode ? __('Save Changes') : __('Save Type') }}
            </button>
        </x-slot>
    </x-dialog-modal>

    <!-- Global Aircraft Repository Import Modal -->
    <x-dialog-modal wire:model.live="showGlobalImportModal" maxWidth="3xl">
        <x-slot name="title">
            {{ __('Import Aircraft Types from Global Repository') }}
        </x-slot>

        <x-slot name="content">
            <div class="mb-4">
                <x-input type="text" wire:model.live.debounce.300ms="searchGlobal" placeholder="Search Code or Name (e.g. A320, B737, Airbus, Embraer...)" class="w-full bg-[#212631] border-gray-600 text-white" />
            </div>

            @if($globalAircraft)
                <div class="overflow-x-auto bg-black/20 rounded-lg border border-white/5 max-h-96">
                    <table class="min-w-full divide-y divide-white/5">
                        <thead class="bg-white/5 sticky top-0 bg-[#151C2C]">
                            <tr>
                                <th class="p-3 w-12 text-center">
                                    <input type="checkbox" wire:model.live="selectAllGlobal" class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent">
                                </th>
                                <th class="p-3 text-left text-xs font-medium text-gray-400 uppercase">ICAO Code</th>
                                <th class="p-3 text-left text-xs font-medium text-gray-400 uppercase">Aircraft Name</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-white">
                            @forelse($globalAircraft as $aircraft)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="p-3 text-center">
                                        <input type="checkbox" wire:model.live="selectedGlobalAircraft" value="{{ $aircraft->id }}" class="rounded bg-black/50 border-gray-600 text-tenant-accent focus:ring-tenant-accent">
                                    </td>
                                    <td class="p-3 font-mono font-bold text-tenant-accent">
                                        {{ $aircraft->code }}
                                    </td>
                                    <td class="p-3 text-sm text-gray-300">
                                        {{ $aircraft->name }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-6 text-center text-gray-500">
                                        No global aircraft types found matching "{{ $searchGlobal }}".
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $globalAircraft->links() }}
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showGlobalImportModal', false)" wire:loading.attr="disabled" class="bg-gray-600 text-white hover:bg-gray-500 border-none">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="importGlobalTypes" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
                {{ __('Import Selected Types') }} ({{ count($selectedGlobalAircraft) }})
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
