<div>
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-white">Rank Management</h3>
        <button wire:click="openModal" class="bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Rank
        </button>
    </div>

    @if (session()->has('rank_message'))
        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('rank_message') }}</span>
        </div>
    @endif

    <div class="overflow-x-auto -mx-6">
        <table class="min-w-full divide-y divide-white/5">
            <thead class="bg-white/5">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Rank Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Min Hours</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Min Points</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse($ranks as $rank)
                <tr class="hover:bg-white/5 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                        {{ $rank->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        {{ $rank->min_hours }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        {{ $rank->min_points }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button wire:click="editRank({{ $rank->id }})" class="text-tenant-accent hover:opacity-80 transition-colors mr-3">Edit</button>
                        <button wire:click="deleteRank({{ $rank->id }})" wire:confirm="Are you sure you want to delete this rank?" class="text-red-400 hover:text-red-300 transition-colors">Delete</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">
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
                <x-input id="name" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="name" />
                <x-input-error for="name" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="min_hours" value="{{ __('Minimum Flight Hours Required') }}" />
                <x-input id="min_hours" type="number" min="0" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="min_hours" />
                <x-input-error for="min_hours" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="min_points" value="{{ __('Minimum Points Required') }}" />
                <x-input id="min_points" type="number" min="0" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="min_points" />
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
