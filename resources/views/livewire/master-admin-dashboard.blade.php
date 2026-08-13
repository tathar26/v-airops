<x-slot name="header">
    <h2 class="font-semibold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-vops-primary to-vops-secondary leading-tight">
        {{ __('Platform Administration') }}
    </h2>
</x-slot>

<div class="py-6 sm:py-8 lg:py-10 flex-grow flex flex-col relative z-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full">
    
    @if (session()->has('message'))
        <div class="mb-6 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="mb-6 bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded relative">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if($currentBatch)
        <div wire:poll.2s="updateBatchProgress" class="mb-6 p-4 bg-black/40 rounded-lg border border-white/10">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-white font-bold text-sm">Global Network Aggregation Progress</h3>
                <span class="text-vops-primary text-sm font-bold">{{ $currentBatch->progress() }}%</span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-2.5">
                <div class="bg-vops-primary h-2.5 rounded-full transition-all duration-500" style="width: {{ $currentBatch->progress() }}%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Processed {{ $currentBatch->processedJobs() }} of {{ $currentBatch->totalJobs }} jobs. {{ $currentBatch->failedJobs }} failed.</p>
        </div>
    @endif

    <!-- Top Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Stat Card 1 -->
        <div class="glass-panel p-6 transform transition-all duration-300 hover:translate-y-[-5px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-400">Total Virtual Airlines</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ $tenants->count() }}</p>
                </div>
                <div class="p-3 bg-vops-primary/20 rounded-lg">
                    <svg class="w-8 h-8 text-vops-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="glass-panel p-6 transform transition-all duration-300 hover:translate-y-[-5px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-400">Total Pilots (Platform)</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ $totalUsers }}</p>
                </div>
                <div class="p-3 bg-vops-secondary/20 rounded-lg">
                    <svg class="w-8 h-8 text-vops-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="glass-panel p-6 transform transition-all duration-300 hover:translate-y-[-5px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-400">Total Flights (Platform)</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ $totalFlights }}</p>
                </div>
                <div class="p-3 bg-vops-success/20 rounded-lg">
                    <svg class="w-8 h-8 text-vops-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Network Repository Management Card -->
    <div class="glass-panel p-6 mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <span>🌍</span> Global Network Database
                </h3>
                <p class="text-sm text-gray-400 mt-1">
                    Central route repository containing <strong class="text-white">{{ number_format($totalGlobalFlights) }}</strong> global routes across <strong class="text-white">{{ number_format($totalGlobalAirlines) }}</strong> airlines.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button wire:click="clearGlobalNetwork" 
                        wire:confirm="Are you sure you want to completely empty the global network database? All cached routes, airlines, and airports will be deleted." 
                        class="bg-red-600/80 hover:bg-red-500 text-white font-bold py-2 px-4 rounded transition flex items-center gap-2 text-sm">
                    <span>🗑️</span> Empty Database
                </button>
                <button wire:click="rebuildGlobalNetwork" 
                        {{ $currentBatch ? 'disabled' : '' }}
                        class="bg-vops-primary hover:opacity-80 text-white font-bold py-2 px-4 rounded transition flex items-center gap-2 text-sm disabled:opacity-50">
                    <span>🔄</span> Rebuild Network
                </button>
            </div>
        </div>
    </div>

    <!-- Active Tenants Table -->
    <div class="glass-panel overflow-hidden">
        <div class="px-6 py-5 border-b border-white/10 flex justify-between items-center">
            <h3 class="text-lg font-medium text-white">Registered Virtual Airlines</h3>
            <button class="glass-button text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New VA
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">VA Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Domain</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Members</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($tenants as $tenant)
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-vops-primary to-vops-secondary flex items-center justify-center text-white font-bold text-xs">
                                    {{ substr($tenant->name, 0, 2) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-white">{{ $tenant->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            {{ $tenant->domain }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-vops-primary/20 text-vops-primary">
                                {{ $tenant->users_count }} users
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            {{ $tenant->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="#" class="text-vops-primary hover:text-vops-secondary transition-colors">Manage</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                            No Virtual Airlines registered yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
