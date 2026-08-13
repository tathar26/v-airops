<div>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 space-y-8">
        
        @if (session()->has('message'))
            <div class="bg-green-500/20 border border-green-500 text-green-400 p-4 rounded-lg mb-6 text-sm">
                {{ session('message') }}
            </div>
        @endif

        <!-- Pilot Preferences -->
        <div class="glass-panel overflow-hidden border-t-4 border-t-tenant-accent">
            <div class="bg-black/30 border-b border-white/10 px-6 py-4">
                <h3 class="text-lg font-bold text-white tracking-wide uppercase">PILOT PREFERENCES</h3>
                <p class="text-xs text-gray-400 mt-1">These preferences apply to your {{ auth()->user()->tenant->name ?? 'vRYR' }} pilot account only.</p>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Use Imperial Units -->
                <div>
                    <h4 class="text-sm font-bold text-white mb-3">Use Imperial Units</h4>
                    <label class="relative inline-flex items-center cursor-pointer mb-2">
                        <input type="checkbox" wire:model="useImperial" wire:change="savePreferences" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                    <p class="text-xs text-gray-400">All weights will be shown in pounds.</p>
                </div>

                <!-- Prefer Honorary Rank -->
                <div>
                    <h4 class="text-sm font-bold text-white mb-3">Prefer Honorary Rank</h4>
                    <label class="relative inline-flex items-center cursor-pointer mb-2">
                        <input type="checkbox" wire:model="preferHonorary" wire:change="savePreferences" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                    <p class="text-xs text-gray-400">If enabled, your honorary rank, should you have one, will be used instead of your regular pilot rank in flight lists, leaderboards etc.</p>
                </div>

                <!-- Preferred Network -->
                <div>
                    <div class="flex justify-between items-end mb-2">
                        <h4 class="text-sm font-bold text-white">Preferred Network<span class="text-red-500">*</span></h4>
                    </div>
                    <select wire:model="preferredNetwork" wire:change="savePreferences" class="w-full bg-blue-900/30 border border-blue-500/50 text-white text-sm rounded focus:ring-tenant-accent focus:border-tenant-accent block p-2.5">
                        <option value="">Offline</option>
                        <option value="VATSIM">VATSIM</option>
                        <option value="IVAO">IVAO</option>
                        <option value="POSCON">POSCON</option>
                        <option value="PILOTEDGE">PilotEdge</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-2">Default Network to select when Dispatching a Flight</p>
                </div>

                <!-- Simbrief OFP Format -->
                <div>
                    <div class="flex justify-between items-end mb-2">
                        <h4 class="text-sm font-bold text-white">SimBrief OFP Format</h4>
                        <span class="text-xs text-gray-400">Set by VA: {{ strtoupper($tenantDefaultSimbriefFormat) }}</span>
                    </div>
                    <select wire:model="simbriefFormat" wire:change="savePreferences" class="w-full bg-black/40 border border-white/10 text-white text-sm rounded focus:ring-tenant-accent focus:border-tenant-accent block p-2.5">
                        <option value="">Use VA Default ({{ strtoupper($tenantDefaultSimbriefFormat) }})</option>
                        @foreach($simbriefFormats as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-2">Format used for SimBrief OFP</p>
                </div>

                <!-- SimBrief Username / Pilot ID -->
                <div class="md:col-span-2">
                    <div class="flex justify-between items-end mb-2">
                        <h4 class="text-sm font-bold text-white">SimBrief Username or Pilot ID</h4>
                        <a href="https://dispatch.simbrief.com/account" target="_blank" class="text-xs text-tenant-accent font-bold hover:underline">Find SimBrief ID</a>
                    </div>
                    <x-input type="text" wire:model="simbriefUsername" wire:change="savePreferences" class="w-full bg-black/40 border border-white/10 text-white text-sm rounded block p-2.5" placeholder="e.g. Navigraph Alias or 6-digit Pilot ID (e.g. 123456)" />
                    <p class="text-xs text-gray-400 mt-2">Your Navigraph Alias or SimBrief Pilot ID used to sync real live OFPs into V-Ops.</p>
                </div>

            </div>
            <div class="px-6 py-4 bg-green-600/90 border-t border-green-700 text-center cursor-pointer hover:bg-green-500 transition-colors" wire:click="savePreferences">
                <span class="text-white font-bold text-sm">Save Preferences</span>
            </div>
        </div>

        <!-- Reset Pilot Account -->
        <div class="glass-panel overflow-hidden border-t-4 border-t-tenant-accent">
            <div class="bg-black/30 border-b border-white/10 px-6 py-4">
                <h3 class="text-lg font-bold text-white tracking-wide uppercase">RESET PILOT ACCOUNT</h3>
                <p class="text-xs text-gray-400 mt-1">Reset your Pilot Account by removing all PIREPs and Bookings</p>
            </div>
            <div class="p-4">
                <button wire:click="confirmReset" class="w-full bg-red-500/80 hover:bg-red-500 text-white font-bold py-3 px-4 rounded transition-colors text-sm">
                    Reset {{ auth()->user()->pilotProfile->user_id ?? '' }} Account
                </button>
            </div>
        </div>

        <!-- Delete Pilot Account -->
        <div class="glass-panel overflow-hidden border-t-4 border-t-tenant-accent">
            <div class="bg-black/30 border-b border-white/10 px-6 py-4">
                <h3 class="text-lg font-bold text-white tracking-wide uppercase">DELETE PILOT ACCOUNT</h3>
                <p class="text-xs text-gray-400 mt-1">Delete your Pilot account and all data</p>
            </div>
            <div class="p-4">
                <button wire:click="confirmDelete" class="w-full bg-red-500/80 hover:bg-red-500 text-white font-bold py-3 px-4 rounded transition-colors text-sm">
                    Delete {{ auth()->user()->pilotProfile->user_id ?? '' }} Account
                </button>
            </div>
        </div>

    </div>

    <!-- Reset Account Modal -->
    <x-dialog-modal wire:model.live="showResetModal">
        <x-slot name="title">
            {{ __('Reset Pilot Account') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to reset your account? This will delete all your PIREPs, Bookings, and Statistics. Please enter your password to confirm.') }}

            <div class="mt-4" x-data="{}" x-on:confirming-reset-account.window="setTimeout(() => $refs.password.focus(), 250)">
                <x-input type="password" class="mt-1 block w-3/4"
                            autocomplete="current-password"
                            placeholder="{{ __('Password') }}"
                            x-ref="password"
                            wire:model="resetPassword"
                            wire:keydown.enter="resetAccount" />

                <x-input-error for="resetPassword" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('showResetModal')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3" wire:click="resetAccount" wire:loading.attr="disabled">
                {{ __('Reset Account') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Delete Account Modal -->
    <x-dialog-modal wire:model.live="showDeleteModal">
        <x-slot name="title">
            {{ __('Delete Pilot Account') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to delete your account? This action is permanent. All your PIREPs and data will be lost. Please enter your password to confirm.') }}

            <div class="mt-4" x-data="{}" x-on:confirming-delete-account.window="setTimeout(() => $refs.password.focus(), 250)">
                <x-input type="password" class="mt-1 block w-3/4"
                            autocomplete="current-password"
                            placeholder="{{ __('Password') }}"
                            x-ref="password"
                            wire:model="deletePassword"
                            wire:keydown.enter="deleteAccount" />

                <x-input-error for="deletePassword" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('showDeleteModal')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3" wire:click="deleteAccount" wire:loading.attr="disabled">
                {{ __('Delete Account') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>
</div>
