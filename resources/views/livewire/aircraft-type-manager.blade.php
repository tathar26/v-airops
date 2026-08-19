<div class="space-y-6">
    @if (session()->has('message'))
        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl mt-6">
        <div class="px-6 py-4 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 flex justify-between items-center flex-wrap gap-4">
            <div>
                <h3 class="text-base font-bold text-white tracking-wide">AIRCRAFT TYPES &amp; FLEET DEFINITIONS</h3>
                <p class="text-xs text-gray-400 mt-0.5">Manage ICAO aircraft types, SimBrief profiles, and payload specs</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <button wire:click="openGlobalImportModal" class="bg-slate-800 hover:bg-slate-700 border border-tenant-accent/40 text-tenant-accent px-4 py-2 rounded-xl text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Import from Global Repository
                </button>
                <button wire:click="openAddModal" class="bg-tenant-accent text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Type
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($aircraftTypes as $type)
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-bold text-tenant-accent">
                            {{ $type->code }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            {{ $type->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="editAircraftType({{ $type->id }})" class="text-tenant-accent hover:opacity-80 transition-colors mr-3 font-semibold">Edit</button>
                            <button wire:click="deleteAircraftType({{ $type->id }})" wire:confirm="Are you sure you want to delete this aircraft type? This may affect associated fleet and routes." class="text-red-400 hover:text-red-300 transition-colors font-semibold">Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-gray-400">
                            No aircraft types defined. Click <strong>Add Type</strong> or <strong>Import</strong> above!
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
                <x-label for="code" value="{{ __('Code (e.g. A320)') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                <x-input id="code" type="text" class="mt-1 block w-full uppercase" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" wire:model="code" placeholder="e.g. A320" maxlength="10" />
                <x-input-error for="code" class="mt-2 text-red-400 text-xs" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="name" value="{{ __('Name (e.g. Airbus A320-200)') }}" class="text-slate-300 font-semibold text-xs uppercase tracking-wider mb-1.5" />
                <x-input id="name" type="text" class="mt-1 block w-full" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" wire:model="name" placeholder="e.g. Airbus A320-200" />
                <x-input-error for="name" class="mt-2 text-red-400 text-xs" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddModal', false)" wire:loading.attr="disabled" class="bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white border-slate-700">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveAircraftType" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md hover:opacity-90 transition">
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
                <x-input type="text" wire:model.live.debounce.300ms="searchGlobal" placeholder="Search Code or Name (e.g. A320, B737, Airbus, Embraer...)" class="w-full" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" />
            </div>

            @if($globalAircraft)
                <div class="overflow-x-auto rounded-xl border border-slate-700 max-h-96" style="background-color: #020617 !important;">
                    <table class="min-w-full divide-y divide-slate-800">
                        <thead class="sticky top-0" style="background-color: #0f172a !important;">
                            <tr>
                                <th class="p-3 w-12 text-center">
                                    <input type="checkbox" wire:model.live="selectAllGlobal" class="rounded bg-slate-900 border-slate-700 text-tenant-accent focus:ring-tenant-accent">
                                </th>
                                <th class="p-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">ICAO Code</th>
                                <th class="p-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Aircraft Name</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-white">
                            @forelse($globalAircraft as $aircraft)
                                <tr class="hover:bg-slate-800/50 transition-colors">
                                    <td class="p-3 text-center">
                                        <input type="checkbox" wire:model.live="selectedGlobalAircraft" value="{{ $aircraft->id }}" class="rounded bg-slate-900 border-slate-700 text-tenant-accent focus:ring-tenant-accent">
                                    </td>
                                    <td class="p-3 font-mono font-bold text-tenant-accent">
                                        {{ $aircraft->code }}
                                    </td>
                                    <td class="p-3 text-sm text-slate-300 font-medium">
                                        {{ $aircraft->name }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-6 text-center text-slate-500">
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
            <x-secondary-button wire:click="$set('showGlobalImportModal', false)" wire:loading.attr="disabled" class="bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white border-slate-700">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="importGlobalTypes" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md hover:opacity-90 transition">
                {{ __('Import Selected Types') }} ({{ count($selectedGlobalAircraft) }})
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
