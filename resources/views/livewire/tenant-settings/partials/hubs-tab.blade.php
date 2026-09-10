                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-white">Hubs & Bases</h3>
                    </div>

                    @if (session()->has('hub_message'))
                        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('hub_message') }}</span>
                        </div>
                    @endif

                    <div class="bg-black/20 p-4 rounded-lg border border-white/10 mb-6">
                        <h4 class="text-sm font-bold text-white mb-2">Add New Base</h4>
                        <form wire:submit.prevent="addHub" class="flex gap-4 items-start">
                            <div class="flex-1">
                                <x-input type="text" wire:model="newHubIcao" placeholder="ICAO (e.g. KJFK)" class="block w-full bg-[#212631] border-gray-600 text-white uppercase" maxlength="4" />
                                <x-input-error for="newHubIcao" class="mt-2" />
                            </div>
                            <button type="submit" class="bg-tenant-accent hover:opacity-80 text-white font-bold py-2 px-6 rounded transition">
                                Add Base
                            </button>
                        </form>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($hubs as $hub)
                            <div class="glass-panel p-4 rounded-lg border border-tenant-accent/50 flex justify-between items-center relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-16 h-16 bg-tenant-accent/10 rounded-bl-full pointer-events-none"></div>
                                <div>
                                    <h4 class="text-xl font-bold text-white">{{ $hub->airport->icao }}</h4>
                                    <p class="text-sm text-gray-400">{{ $hub->airport->name ?? 'Unknown Airport' }}</p>
                                </div>
                                <button wire:click="removeHub({{ $hub->id }})" wire:confirm="Are you sure you want to remove this base?" class="text-red-400 hover:text-red-300 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        @endforeach

                        @if($hubs->isEmpty())
                            <div class="col-span-full text-center py-8 text-gray-500">
                                No bases defined yet.
                            </div>
                        @endif
                    </div>
                </div>
