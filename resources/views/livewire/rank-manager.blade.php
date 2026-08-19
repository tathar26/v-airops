<div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-xl">
    <div class="px-6 py-4 bg-[#181D29] border-b border-tenant-accent/40 border-t-2 flex justify-between items-center flex-wrap gap-4">
        <div>
            <h3 class="text-base font-bold text-white tracking-wide">RANK PROGRESSION SYSTEM</h3>
            <p class="text-xs text-gray-400 mt-0.5">Manage pilot ranks, hour milestones, and minimum point requirements</p>
        </div>
        <button wire:click="openModal" class="bg-tenant-accent text-white px-4 py-2 rounded-lg text-xs font-bold shadow transition hover:opacity-90 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Rank
        </button>
    </div>

    @if (session()->has('rank_message'))
        <div class="m-6 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded-lg text-xs font-bold" role="alert">
            <span class="block sm:inline">{{ session('rank_message') }}</span>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-white/5">
            <thead class="bg-[#181D29]">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Rank Name</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Min Hours Required</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Min Points Required</th>
                    <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 bg-[#12161F]">
                @forelse($ranks as $rank)
                <tr class="hover:bg-white/5 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-white">
                        {{ $rank->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 font-mono">
                        {{ $rank->min_hours }} hrs
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-tenant-accent font-bold font-mono">
                        {{ $rank->min_points }} pts
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button wire:click="editRank({{ $rank->id }})" class="text-tenant-accent hover:opacity-80 transition-colors mr-3 text-xs font-bold">Edit</button>
                        <button wire:click="deleteRank({{ $rank->id }})" wire:confirm="Are you sure you want to delete this rank?" class="text-red-400 hover:text-red-300 transition-colors text-xs font-bold">Delete</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">
                        No ranks found. Create one to get started!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Rank Modal -->
    <x-dialog-modal wire:model.live="showModal">
        <x-slot name="title">
            {{ $editingRankId ? __('Edit Rank') : __('Add New Rank') }}
        </x-slot>

        <x-slot name="content">
            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="name" value="{{ __('Rank Name') }}" />
                <x-input id="name" type="text" class="mt-1 block w-full bg-[#0a0d14] border-white/10 text-white" wire:model="name" />
                <x-input-error for="name" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="min_hours" value="{{ __('Minimum Flight Hours Required') }}" />
                <x-input id="min_hours" type="number" min="0" class="mt-1 block w-full bg-[#0a0d14] border-white/10 text-white" wire:model="min_hours" />
                <x-input-error for="min_hours" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="min_points" value="{{ __('Minimum Points Required') }}" />
                <x-input id="min_points" type="number" min="0" class="mt-1 block w-full bg-[#0a0d14] border-white/10 text-white" wire:model="min_points" />
                <x-input-error for="min_points" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled" class="bg-gray-600 text-white hover:bg-gray-500 border-none">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveRank" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
                {{ $editingRankId ? __('Save Changes') : __('Create Rank') }}
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
