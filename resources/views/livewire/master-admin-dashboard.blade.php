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
                    <p class="text-3xl font-bold text-white mt-1">{{ $tenants->count() + $pendingTenants->count() }}</p>
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

    <!-- Pending VA Approvals Section (If Any) -->
    @if($pendingTenants->isNotEmpty())
    <div class="glass-panel border-amber-500/40 shadow-xl shadow-amber-500/10 overflow-hidden mb-8">
        <div class="px-6 py-5 bg-gradient-to-r from-amber-500/10 via-slate-900 to-slate-900 border-b border-amber-500/30 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30">
                    <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        Pending Virtual Airline Approvals
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            {{ $pendingTenants->count() }} Action Needed
                        </span>
                    </h3>
                    <p class="text-xs text-gray-400">These virtual airlines were registered by pilots and require system administrator approval before going live.</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Airline</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ICAO</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Creator</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Base Hub</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Submitted</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($pendingTenants as $pending)
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if($pending->logo_path)
                                    <img src="{{ Storage::url($pending->logo_path) }}" alt="{{ $pending->name }}" class="h-8 w-8 object-contain rounded bg-slate-900 p-0.5 border border-white/10">
                                @else
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white font-bold text-xs">
                                        {{ substr($pending->name, 0, 2) }}
                                    </div>
                                @endif
                                <div class="text-sm font-semibold text-white">{{ $pending->name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-bold text-sky-400">
                            {{ $pending->icao }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            {{ $pending->creator ? $pending->creator->full_name : 'Pilot' }}
                            <div class="text-xs text-gray-500">{{ $pending->creator ? $pending->creator->email : '' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-bold text-purple-400">
                            {{ $pending->hubs->first() ? $pending->hubs->first()->airport->icao : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            {{ $pending->created_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <button wire:click="approveVirtualAirline({{ $pending->id }})"
                                class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-all inline-flex items-center gap-1">
                                <span>✓ Approve</span>
                            </button>
                            <button wire:click="rejectVirtualAirline({{ $pending->id }})"
                                wire:confirm="Are you sure you want to reject this Virtual Airline?"
                                class="px-3 py-1.5 rounded-xl bg-red-600/70 hover:bg-red-600 text-white text-xs font-semibold transition-all inline-flex items-center gap-1">
                                <span>✕ Reject</span>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

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
            <button wire:click="openCreateVaModal" class="glass-button text-sm flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New VA
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">VA Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ICAO</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
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
                                @if($tenant->logo_path)
                                    <img src="{{ Storage::url($tenant->logo_path) }}" alt="{{ $tenant->name }}" class="h-8 w-8 object-contain rounded bg-slate-900 p-0.5 border border-white/10">
                                @else
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-vops-primary to-vops-secondary flex items-center justify-center text-white font-bold text-xs">
                                        {{ substr($tenant->name, 0, 2) }}
                                    </div>
                                @endif
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-white">{{ $tenant->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-bold text-sky-400">
                            {{ $tenant->icao ?: 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($tenant->status === 'active' || $tenant->is_approved)
                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    Active
                                </span>
                            @elseif($tenant->status === 'rejected')
                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-500/20 text-red-300 border border-red-500/30">
                                    Rejected
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-vops-primary/20 text-vops-primary">
                                {{ $tenant->users_count }} users
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            {{ $tenant->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <form action="{{ route('session.switch-airline') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                                <button type="submit" class="text-sky-400 hover:text-sky-300 transition-colors text-xs font-semibold">
                                    Enter VA &rarr;
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No Virtual Airlines registered yet. Click <strong>+ New VA</strong> above to create the first one!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create New VA Modal -->
    @if($showCreateVaModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background Overlay -->
            <div wire:click="closeCreateVaModal" class="fixed inset-0 transition-opacity" style="background-color: rgba(0, 0, 0, 0.85) !important; backdrop-filter: blur(8px);" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel (100% Solid Opaque Dark Background) -->
            <div class="inline-block align-bottom rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
                 style="background-color: #0f172a !important; color: #ffffff !important; border: 1px solid #334155 !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8) !important;">
                <form wire:submit.prevent="createVirtualAirline">
                    <div class="px-6 py-5 flex items-center justify-between" style="background-color: #020617 !important; border-bottom: 1px solid #1e293b !important;">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl" style="background-color: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3);">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold" style="color: #ffffff !important;">Create Virtual Airline</h3>
                                <p class="text-xs" style="color: #94a3b8 !important;">Specify all required airline settings, branding, and base hub.</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeCreateVaModal" class="transition-colors" style="color: #94a3b8;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#94a3b8'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto" style="background-color: #0f172a !important;">
                        @error('general')
                            <div class="p-3 rounded-lg text-xs" style="background-color: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5;">
                                {{ $message }}
                            </div>
                        @enderror

                        <!-- Airline Name & ICAO -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    Airline Name <span style="color: #f87171;">*</span>
                                </label>
                                <input type="text" wire:model="vaName" placeholder="e.g. British Airways Virtual"
                                    class="w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition-all"
                                    style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                                @error('vaName') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    Airline ICAO <span style="color: #f87171;">*</span>
                                </label>
                                <input type="text" wire:model="vaIcao" placeholder="e.g. BAW" maxlength="4"
                                    class="w-full rounded-xl px-3.5 py-2.5 font-mono uppercase text-sm outline-none transition-all"
                                    style="background-color: #1e293b !important; color: #38bdf8 !important; font-weight: 700; border: 1px solid #334155 !important;">
                                @error('vaIcao') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Base Hub ICAO & SimBrief Format -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    Base Hub Airport (ICAO) <span style="color: #f87171;">*</span>
                                </label>
                                <input type="text" wire:model="vaBaseHubIcao" placeholder="e.g. EGLL or KJFK" maxlength="4"
                                    class="w-full rounded-xl px-3.5 py-2.5 font-mono uppercase text-sm outline-none transition-all"
                                    style="background-color: #1e293b !important; color: #c084fc !important; font-weight: 700; border: 1px solid #334155 !important;">
                                <p class="text-[11px] mt-1" style="color: #94a3b8 !important;">Airport will be automatically imported as primary hub.</p>
                                @error('vaBaseHubIcao') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    SimBrief OFP Format <span style="color: #f87171;">*</span>
                                </label>
                                <select wire:model="vaSimbriefFormat"
                                    class="w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition-all"
                                    style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                                    @foreach($simbriefFormats as $key => $label)
                                        <option value="{{ $key }}" style="background-color: #1e293b; color: #ffffff;">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('vaSimbriefFormat') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Theme Colors -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl" style="background-color: #020617 !important; border: 1px solid #1e293b !important;">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    Accent Color <span style="color: #f87171;">*</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color" wire:model.live="vaAccentColor" class="h-10 w-14 rounded-lg bg-transparent cursor-pointer p-1" style="border: 1px solid #334155 !important;">
                                    <input type="text" wire:model="vaAccentColor" class="w-full rounded-xl px-3 py-2 font-mono text-xs uppercase"
                                        style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                                </div>
                                @error('vaAccentColor') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    Background Color <span style="color: #f87171;">*</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color" wire:model.live="vaBgColor" class="h-10 w-14 rounded-lg bg-transparent cursor-pointer p-1" style="border: 1px solid #334155 !important;">
                                    <input type="text" wire:model="vaBgColor" class="w-full rounded-xl px-3 py-2 font-mono text-xs uppercase"
                                        style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                                </div>
                                @error('vaBgColor') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                Airline Logo (Optional)
                            </label>
                            <input type="file" wire:model="vaLogo" accept="image/*"
                                class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:cursor-pointer"
                                style="color: #94a3b8; background-color: #1e293b; border: 1px solid #334155; border-radius: 0.75rem; padding: 0.5rem;">
                            @if ($vaLogo)
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-xs" style="color: #34d399;">Preview:</span>
                                    <img src="{{ $vaLogo->temporaryUrl() }}" class="h-8 max-w-[120px] object-contain rounded p-1" style="background-color: #020617; border: 1px solid #334155;">
                                </div>
                            @endif
                            @error('vaLogo') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="px-6 py-4 flex items-center justify-end gap-3" style="background-color: #020617 !important; border-top: 1px solid #1e293b !important;">
                        <button type="button" wire:click="closeCreateVaModal" class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                            style="background-color: #1e293b; color: #94a3b8; border: 1px solid #334155;">
                            Cancel
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="px-6 py-2 rounded-xl text-sm font-bold shadow-lg transition-all flex items-center gap-2"
                            style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important; color: #ffffff !important; border: none;">
                            <span wire:loading.remove wire:target="createVirtualAirline">Create Virtual Airline</span>
                            <span wire:loading wire:target="createVirtualAirline">Creating VA...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>
