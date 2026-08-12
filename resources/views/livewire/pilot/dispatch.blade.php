<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Flight Dispatch</h1>
        <div class="flex space-x-3">
            <a href="{{ route('flight-centre.book') }}" class="px-4 py-2 bg-gray-200 dark:bg-[#353535] text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-[#404040] transition">
                Cancel Booking
            </a>
        </div>
    </div>

    @if (session()->has('error'))
        <div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form Column -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-[#2B2B2B] shadow rounded-lg overflow-hidden border dark:border-[#353535]">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-[#353535] bg-gray-50 dark:bg-[#202020]">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <span class="mr-2">📝</span> Dispatch Parameters
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Route Overview -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-[#353535] rounded-lg">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Flight</p>
                            <p class="font-bold text-lg text-gray-900 dark:text-white">{{ $booking->route->callsign }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Departure</p>
                            <p class="font-bold text-lg text-gray-900 dark:text-white">{{ $booking->route->departure_icao }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Arrival</p>
                            <p class="font-bold text-lg text-gray-900 dark:text-white">{{ $booking->route->arrival_icao }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aircraft</p>
                            <p class="font-bold text-lg text-gray-900 dark:text-white">{{ $booking->route->aircraftType->code ?? 'Any' }}</p>
                        </div>
                    </div>

                    <!-- Dispatch Settings Form -->
                    <form wire:submit.prevent="generateSimbrief" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Airframe <span class="text-red-500">*</span></label>
                                <select wire:model="airframe_id" class="w-full bg-white dark:bg-[#353535] border border-gray-300 dark:border-[#404040] rounded-md py-2 px-3 focus:outline-none focus:ring-1 focus:ring-[#F16100] focus:border-[#F16100] text-gray-900 dark:text-white">
                                    <option value="">Select Airframe...</option>
                                    @foreach($fleet as $airframe)
                                        <option value="{{ $airframe->id }}">{{ $airframe->registration }} ({{ $airframe->aircraftType->code }})</option>
                                    @endforeach
                                </select>
                                @error('airframe_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cost Index</label>
                                <input type="text" wire:model="cost_index" class="w-full bg-white dark:bg-[#353535] border border-gray-300 dark:border-[#404040] rounded-md py-2 px-3 focus:outline-none focus:ring-1 focus:ring-[#F16100] focus:border-[#F16100] text-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Passengers</label>
                                <input type="text" wire:model="pax" class="w-full bg-white dark:bg-[#353535] border border-gray-300 dark:border-[#404040] rounded-md py-2 px-3 focus:outline-none focus:ring-1 focus:ring-[#F16100] focus:border-[#F16100] text-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Freight / Cargo</label>
                                <input type="text" wire:model="freight" class="w-full bg-white dark:bg-[#353535] border border-gray-300 dark:border-[#404040] rounded-md py-2 px-3 focus:outline-none focus:ring-1 focus:ring-[#F16100] focus:border-[#F16100] text-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cruise Altitude</label>
                                <input type="text" wire:model="altitude" class="w-full bg-white dark:bg-[#353535] border border-gray-300 dark:border-[#404040] rounded-md py-2 px-3 focus:outline-none focus:ring-1 focus:ring-[#F16100] focus:border-[#F16100] text-gray-900 dark:text-white" placeholder="AUTO or FL350">
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-[#353535]">
                            <button type="submit" 
                                    class="px-6 py-2 bg-[#F16100] hover:bg-[#d95600] text-white font-bold rounded-md transition shadow-lg flex items-center justify-center disabled:opacity-50"
                                    wire:loading.attr="disabled"
                                    wire:target="generateSimbrief">
                                <span wire:loading.remove wire:target="generateSimbrief">
                                    <i class="fa-solid fa-file-invoice mr-2"></i> Generate SimBrief OFP
                                </span>
                                <span wire:loading wire:target="generateSimbrief">
                                    <i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Generating via API...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar / Status Column -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-[#2B2B2B] shadow rounded-lg overflow-hidden border dark:border-[#353535]">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-[#353535] bg-gray-50 dark:bg-[#202020]">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Status</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-gray-600 dark:text-gray-400">Flight Status</span>
                        @if($booking->status === 'pending')
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-500 rounded-full text-xs font-bold uppercase tracking-wider">Pending Dispatch</span>
                        @elseif($booking->status === 'dispatched')
                            <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-500 rounded-full text-xs font-bold uppercase tracking-wider">Dispatched</span>
                        @endif
                    </div>
                    
                    @if($booking->simbrief_data)
                        <div class="mt-6">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-2 border-b border-gray-200 dark:border-[#353535] pb-2">OFP Summary</h3>
                            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex justify-between">
                                    <span>Block Fuel:</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ $booking->simbrief_data['fuel']['plan_ramp'] ?? 'N/A' }} lbs</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>ZFW:</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ $booking->simbrief_data['weights']['est_zfw'] ?? 'N/A' }} lbs</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Route:</span>
                                    <span class="text-gray-900 dark:text-white font-medium truncate ml-4">{{ $booking->simbrief_data['general']['route'] ?? 'N/A' }}</span>
                                </div>
                            </div>
                            
                            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-[#353535]">
                                <a href="#" class="block w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-center font-bold rounded transition">
                                    Start Flight (Pegasus)
                                </a>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic text-center mt-6">
                            Generate an OFP to begin your flight.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
