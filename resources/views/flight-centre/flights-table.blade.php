<x-app-layout>
    <div class="space-y-6 max-w-[1600px] mx-auto w-full">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">Flights List</h2>
            <div class="flex gap-4">
                <a href="{{ route('flight-centre.book') }}" class="bg-[#1a1f2b] hover:bg-[#252b3b] text-gray-300 px-4 py-2 rounded-md text-sm font-semibold transition border border-white/5">
                    Map View
                </a>
            </div>
        </div>

        <div class="glass-panel overflow-hidden">
            <!-- Search & Filters -->
            <div class="p-4 border-b border-white/5 flex gap-4 bg-white/5">
                <input type="text" placeholder="Search flights (e.g. LHR, EZY123)..." class="bg-[#212631] border-gray-600 text-white rounded-md w-64 focus:border-tenant-accent focus:ring-tenant-accent text-sm">
                
                <select class="bg-[#212631] border-gray-600 text-white rounded-md focus:border-tenant-accent focus:ring-tenant-accent text-sm w-48">
                    <option value="">Departure Airport</option>
                </select>
                
                <select class="bg-[#212631] border-gray-600 text-white rounded-md focus:border-tenant-accent focus:ring-tenant-accent text-sm w-48">
                    <option value="">Arrival Airport</option>
                </select>

                <select class="bg-[#212631] border-gray-600 text-white rounded-md focus:border-tenant-accent focus:ring-tenant-accent text-sm w-48">
                    <option value="">Route Type</option>
                    <option value="SCHEDULED">Scheduled</option>
                    <option value="CHARTER">Charter</option>
                    <option value="CARGO">Cargo</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/5">
                    <thead class="bg-black/20">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Flight</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Dep</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Arr</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Block Time</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Distance</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <!-- Placeholder row -->
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-tenant-accent">VOPS123</div>
                                <div class="text-xs text-gray-500">Virtual Operations</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-white font-bold">EGLL</td>
                            <td class="px-6 py-4 whitespace-nowrap text-white font-bold">EHAM</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">01:15</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">200 nm</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded bg-white/10 text-gray-300">Scheduled</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button class="bg-tenant-accent hover:opacity-90 text-white px-3 py-1.5 rounded text-sm font-semibold shadow transition">
                                    Book
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">
                                This is a placeholder table. Real data integration requires Livewire DispatchTable component.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-white/5 bg-black/10 flex items-center justify-between">
                <div class="text-sm text-gray-400">Showing 1 to 1 of 1 entries</div>
                <div class="flex gap-1">
                    <button class="px-3 py-1 rounded bg-[#212631] text-gray-400 border border-white/5 cursor-not-allowed">Prev</button>
                    <button class="px-3 py-1 rounded bg-[#212631] text-gray-400 border border-white/5 cursor-not-allowed">Next</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
