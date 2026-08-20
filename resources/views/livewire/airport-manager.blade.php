<div class="space-y-6">
    @if (session()->has('message'))
        <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded-xl relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="flex justify-between items-center flex-wrap gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Airport Management</h2>
            <p class="text-gray-400 text-sm mt-1">
                Manage airports and fetch coordinates from global aviation databases &bull;
                <span class="text-tenant-accent font-semibold">{{ number_format($totalAirportsCount) }} Total Airports</span>
            </p>
        </div>
        <button wire:click="openModal" class="bg-tenant-accent hover:opacity-90 text-white font-bold py-2.5 px-5 rounded-xl transition flex items-center shadow-lg">
            <span class="mr-2">+</span> Add Airport
        </button>
    </div>

    <!-- Airports List -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
        <div class="px-6 py-4 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 flex justify-between items-center">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">AIRPORT DIRECTORY &amp; COORDINATES</span>
            <span class="text-xs font-mono text-tenant-accent font-semibold">{{ number_format($totalAirportsCount) }} Airports in DB</span>
        </div>

        <!-- Search & Filter Controls with Autocomplete -->
        <div class="px-6 py-3.5 bg-[#141923] border-b border-white/5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1 min-w-[260px] max-w-sm">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" 
                           list="airport-autocomplete"
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search by ICAO, airport name, elevation..." 
                           class="pl-9 pr-4 py-2 rounded-xl text-xs w-full bg-slate-900 border border-slate-700 text-white placeholder-slate-500 focus:border-tenant-accent focus:ring-1 focus:ring-tenant-accent transition" />
                    
                    <datalist id="airport-autocomplete">
                        @foreach($autocompleteList as $item)
                            <option value="{{ $item }}"></option>
                        @endforeach
                    </datalist>
                </div>
                @if($search || $filterPrefix)
                    <button wire:click="resetFilters" class="text-xs text-slate-400 hover:text-white px-2 py-1 flex-shrink-0">Clear</button>
                @endif
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <!-- Region / Prefix Filter -->
                <x-search-select 
                    wireModel="filterPrefix" 
                    :selected="$filterPrefix" 
                    label="Region" 
                    allLabel="Region: All" 
                    placeholder="Search region prefix (e.g. EG, LF)..." 
                    :options="$allPrefixes->toArray()" 
                    minWidth="min-w-[135px]" />

                <!-- Per Page -->
                <select wire:model.live="perPage" class="bg-slate-900 border border-slate-700 text-white text-xs font-semibold rounded-xl px-3.5 py-2 min-w-[120px] focus:border-tenant-accent shadow-sm">
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-300">
                <thead class="text-xs text-gray-400 uppercase bg-[#181D29] border-b border-white/10">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">ICAO</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Name</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Coordinates</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Elevation</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($airports as $airport)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 font-bold font-mono text-tenant-accent">{{ $airport->icao }}</td>
                            <td class="px-6 py-4 font-medium text-white">{{ $airport->name }}</td>
                            <td class="px-6 py-4 font-mono">
                                <div class="text-xs text-gray-400">Lat: {{ $airport->lat }}</div>
                                <div class="text-xs text-gray-400">Lon: {{ $airport->lon }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono">{{ $airport->elevation ? $airport->elevation . ' ft' : '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="editAirport({{ $airport->id }})" class="text-tenant-accent hover:opacity-80 transition px-2 font-semibold">Edit</button>
                                <button wire:click="deleteAirport({{ $airport->id }})" wire:confirm="Are you sure you want to delete this airport?" class="text-red-400 hover:text-red-300 transition px-2 font-semibold">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                @if($search || $filterPrefix)
                                    No airports match your filter. <button wire:click="resetFilters" class="text-tenant-accent underline ml-1">Reset filter</button>
                                @else
                                    No airports found. Click "Add Airport" to create one.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-black/20 border-t border-white/10">
            {{ $airports->links() }}
        </div>
    </div>

    <!-- Add/Edit Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md" style="display: none;" x-data="{ show: @entangle('showModal') }" x-show="show" x-transition>
        <div class="rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]" style="background-color: var(--tenant-card-bg, #0f172a) !important; color: var(--tenant-card-text, #ffffff) !important; border: 1px solid var(--tenant-input-border, #334155) !important;">
            <!-- Header -->
            <div class="px-6 py-4 flex justify-between items-center border-b border-white/10" style="background-color: var(--tenant-panel-bg, #020617) !important;">
                <h3 class="text-lg font-bold" style="color: var(--tenant-panel-text, #ffffff);">{{ $editMode ? 'Edit Airport' : 'Add Airport' }}</h3>
                <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-white transition text-lg font-bold">✕</button>
            </div>
            
            <!-- Body -->
            <div class="p-6 overflow-y-auto custom-scrollbar space-y-4">
                
                @if (session()->has('success'))
                    <div class="p-3 bg-green-500/20 border border-green-500/50 text-green-200 rounded-xl text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="p-3 bg-red-500/20 border border-red-500/50 text-red-200 rounded-xl text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <form wire:submit.prevent="saveAirport" class="space-y-4">
                    
                    <div class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">ICAO Code</label>
                            <input type="text" wire:model.defer="icao" class="w-full rounded-xl py-2.5 px-3 uppercase text-sm font-mono" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" maxlength="4" placeholder="e.g., EGLL">
                        </div>
                        <button type="button" wire:click="fetchAirportData" class="bg-tenant-accent hover:opacity-90 text-white font-bold py-2.5 px-5 rounded-xl transition h-[44px] flex items-center shadow">
                            Auto-Fetch
                        </button>
                    </div>
                    @error('icao') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Airport Name</label>
                        <input type="text" wire:model.defer="name" class="w-full rounded-xl py-2.5 px-3 text-sm" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;" placeholder="e.g., London Heathrow">
                        @error('name') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Latitude</label>
                            <input type="text" wire:model.defer="lat" class="w-full rounded-xl py-2.5 px-3 text-sm font-mono" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                            @error('lat') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Longitude</label>
                            <input type="text" wire:model.defer="lon" class="w-full rounded-xl py-2.5 px-3 text-sm font-mono" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                            @error('lon') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--tenant-card-text, #cbd5e1);">Elevation (ft)</label>
                        <input type="number" wire:model.defer="elevation" class="w-full rounded-xl py-2.5 px-3 text-sm font-mono">
                        @error('elevation') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Metadata Fields -->
                    <div class="mt-6 pt-4 border-t border-white/10">
                        <div class="flex justify-between items-center mb-3">
                            <label class="block text-xs font-bold text-tenant-accent uppercase tracking-wider">Extra Variables (Metadata)</label>
                            <button type="button" wire:click="addMetadataField" class="btn-secondary text-xs py-1 px-3 rounded-lg transition">
                                + Add Variable
                            </button>
                        </div>
                        
                        <div class="space-y-3">
                            @foreach($metadata_keys as $index => $key)
                                <div class="flex gap-2">
                                    <input type="text" wire:model.defer="metadata_keys.{{ $index }}" placeholder="Key (e.g., stands_used)" class="w-1/3 rounded-xl py-2 px-3 text-sm">
                                    <input type="text" wire:model.defer="metadata_values.{{ $index }}" placeholder="Value" class="flex-1 rounded-xl py-2 px-3 text-sm">
                                    <button type="button" wire:click="removeMetadataField({{ $index }})" class="text-red-400 hover:text-red-300 px-2 transition font-bold">✕</button>
                                </div>
                            @endforeach
                            
                            @if(count($metadata_keys) === 0)
                                <p class="text-xs text-slate-500 italic">No extra variables added.</p>
                            @endif
                        </div>
                    </div>

                </form>
            </div>
            
            <!-- Footer -->
            <div class="px-6 py-4 border-t border-white/10 flex justify-end gap-3" style="background-color: var(--tenant-card-bg, #020617) !important;">
                <button wire:click="$set('showModal', false)" class="btn-secondary px-4 py-2 text-sm font-semibold rounded-xl transition">Cancel</button>
                <button wire:click="saveAirport" class="btn-primary font-bold py-2 px-6 rounded-xl transition shadow-md">
                    {{ $editMode ? 'Update Airport' : 'Save Airport' }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
