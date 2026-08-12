<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    @if (session()->has('message'))
        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="glass-panel overflow-hidden mt-6">
        <div class="px-6 py-5 border-b border-white/10 flex justify-between items-center flex-wrap gap-4">
            <h3 class="text-lg font-medium text-white">Route Network</h3>
            <div class="flex items-center gap-3">
                <button wire:click="downloadTemplate" class="text-gray-400 hover:text-white transition-colors text-sm underline">
                    Download CSV Template
                </button>
                <div class="relative flex items-center">
                    <input type="file" wire:model="csvFile" id="csvRouteFile" class="hidden" accept=".csv,.txt" />
                    <label for="csvRouteFile" class="cursor-pointer bg-[#212631] border border-gray-600 text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition mr-2">
                        Select CSV
                    </label>
                    @if($csvFile)
                        <button wire:click="importCsv" class="bg-vops-secondary text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
                            Import
                        </button>
                    @endif
                </div>
                <button wire:click="openAddModal" class="bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Route
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Flight Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Departure</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Arrival</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Block Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Route Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Aircraft Types</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($routes as $route)
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-white">
                            {{ $route->flight_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-tenant-accent/20 text-tenant-accent">
                                {{ $route->departure_icao }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-tenant-accent/20 text-tenant-accent">
                                {{ $route->arrival_icao }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            {{ $route->block_time }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $route->route_type === 'Charter' ? 'bg-purple-900/50 text-purple-300' : ($route->route_type === 'Cargo' ? 'bg-yellow-900/50 text-yellow-300' : 'bg-blue-900/50 text-blue-300') }}">
                                {{ $route->route_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-400">
                            @forelse($route->aircraftTypes as $type)
                                <span class="px-2 py-1 mr-1 mb-1 inline-block text-xs font-semibold rounded bg-gray-700 text-gray-300">{{ $type->code }}</span>
                            @empty
                                <span class="text-xs text-gray-500 italic">Any</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="editRoute({{ $route->id }})" class="text-tenant-accent hover:opacity-80 transition-colors mr-3">Edit</button>
                            <button wire:click="deleteRoute({{ $route->id }})" wire:confirm="Are you sure you want to delete this route?" class="text-red-400 hover:text-red-300 transition-colors">Delete</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                            No routes in your network.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Route Modal -->
    <x-dialog-modal wire:model.live="showAddModal">
        <x-slot name="title">
            {{ $editMode ? __('Edit Route') : __('Add New Route') }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label for="flight_number" value="{{ __('Flight Number') }}" />
                    <x-input id="flight_number" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="flight_number" placeholder="e.g. EZY123" />
                    <x-input-error for="flight_number" class="mt-2" />
                </div>
                <div class="col-span-1">
                    <x-label for="route_type" value="{{ __('Route Type') }}" />
                    <select id="route_type" wire:model="route_type" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white rounded-md shadow-sm focus:border-tenant-accent focus:ring focus:ring-tenant-accent focus:ring-opacity-50">
                        <option value="Scheduled">Scheduled</option>
                        <option value="Charter">Charter</option>
                        <option value="Cargo">Cargo</option>
                    </select>
                    <x-input-error for="route_type" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-1">
                    <x-label for="departure_icao" value="{{ __('Departure ICAO') }}" />
                    <x-input id="departure_icao" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white uppercase" wire:model="departure_icao" placeholder="e.g. EGLL" maxlength="4" />
                    <x-input-error for="departure_icao" class="mt-2" />
                </div>
                <div class="col-span-1">
                    <x-label for="arrival_icao" value="{{ __('Arrival ICAO') }}" />
                    <x-input id="arrival_icao" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white uppercase" wire:model="arrival_icao" placeholder="e.g. LFPG" maxlength="4" />
                    <x-input-error for="arrival_icao" class="mt-2" />
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="block_time" value="{{ __('Block Time (HH:MM)') }}" />
                <x-input id="block_time" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="block_time" placeholder="e.g. 02:30" />
                <x-input-error for="block_time" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="route_string" value="{{ __('Route String (Optional)') }}" />
                <x-input id="route_string" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="route_string" placeholder="e.g. DCT BOVIS L608..." />
                <p class="text-xs text-gray-500 mt-1">Leave empty to have SimBrief calculate the route automatically.</p>
                <x-input-error for="route_string" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-label value="{{ __('Allowed Aircraft Types') }}" />
                <div class="mt-2 grid grid-cols-2 gap-2 max-h-32 overflow-y-auto bg-[#212631] border border-gray-600 rounded-md p-2">
                    @foreach($aircraftTypes as $type)
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model="selectedAircraftTypes" value="{{ $type->id }}" class="rounded bg-gray-900 border-gray-600 text-tenant-accent shadow-sm focus:ring-tenant-accent">
                            <span class="ml-2 text-sm text-gray-300">{{ $type->code }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error for="selectedAircraftTypes" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddModal', false)" wire:loading.attr="disabled" class="bg-gray-600 text-white hover:bg-gray-500 border-none">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveRoute" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
                {{ $editMode ? __('Save Changes') : __('Save Route') }}
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
