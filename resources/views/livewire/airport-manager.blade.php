<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-white">Airport Manager</h2>
            <p class="text-gray-400 text-sm mt-1">Manage airports and fetch their coordinates.</p>
        </div>
        <button wire:click="openModal" class="bg-tenant-accent hover:opacity-80 text-white font-bold py-2 px-4 rounded transition flex items-center">
            <span class="mr-2">+</span> Add Airport
        </button>
    </div>

    <!-- Airports List -->
    <div class="glass-panel rounded-xl overflow-hidden shadow-lg border border-white/10">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-300">
                <thead class="text-xs text-tenant-accent uppercase bg-black/40 border-b border-white/10">
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
                            <td class="px-6 py-4 font-bold text-white">{{ $airport->icao }}</td>
                            <td class="px-6 py-4">{{ $airport->name }}</td>
                            <td class="px-6 py-4">
                                <div class="text-xs text-gray-400">Lat: {{ $airport->lat }}</div>
                                <div class="text-xs text-gray-400">Lon: {{ $airport->lon }}</div>
                            </td>
                            <td class="px-6 py-4">{{ $airport->elevation }} ft</td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="editAirport({{ $airport->id }})" class="text-tenant-accent hover:text-white transition px-2">Edit</button>
                                <button wire:click="deleteAirport({{ $airport->id }})" wire:confirm="Are you sure you want to delete this airport?" class="text-red-400 hover:text-red-300 transition px-2">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                No airports found. Click "Add Airport" to create one.
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
        <div class="rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]" style="background-color: #0f172a !important; border: 1px solid #334155 !important;">
            <!-- Header -->
            <div class="px-6 py-4 flex justify-between items-center border-b border-slate-800" style="background-color: #020617 !important;">
                <h3 class="text-lg font-bold text-white">{{ $editMode ? 'Edit Airport' : 'Add Airport' }}</h3>
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
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">Elevation (ft)</label>
                        <input type="number" wire:model.defer="elevation" class="w-full rounded-xl py-2.5 px-3 text-sm font-mono" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                        @error('elevation') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Metadata Fields -->
                    <div class="mt-6 pt-4 border-t border-slate-800">
                        <div class="flex justify-between items-center mb-3">
                            <label class="block text-xs font-bold text-tenant-accent uppercase tracking-wider">Extra Variables (Metadata)</label>
                            <button type="button" wire:click="addMetadataField" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 py-1 px-3 rounded-lg border border-slate-700 transition">
                                + Add Variable
                            </button>
                        </div>
                        
                        <div class="space-y-3">
                            @foreach($metadata_keys as $index => $key)
                                <div class="flex gap-2">
                                    <input type="text" wire:model.defer="metadata_keys.{{ $index }}" placeholder="Key (e.g., stands_used)" class="w-1/3 rounded-xl py-2 px-3 text-sm" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                                    <input type="text" wire:model.defer="metadata_values.{{ $index }}" placeholder="Value" class="flex-1 rounded-xl py-2 px-3 text-sm" style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
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
            <div class="px-6 py-4 border-t border-slate-800 flex justify-end gap-3" style="background-color: #020617 !important;">
                <button wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-semibold text-slate-300 hover:text-white transition">Cancel</button>
                <button wire:click="saveAirport" class="bg-tenant-accent hover:opacity-90 text-white font-bold py-2 px-6 rounded-xl transition shadow-md">
                    {{ $editMode ? 'Update Airport' : 'Save Airport' }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
