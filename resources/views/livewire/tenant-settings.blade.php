<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="glass-panel overflow-hidden">
        <!-- Tabs -->
        <div class="border-b border-white/10 flex">
            <button wire:click="$set('activeTab', 'general')" class="px-6 py-4 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'general' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent text-gray-400 hover:text-white' }}">
                General Settings
            </button>
            <button wire:click="$set('activeTab', 'users')" class="px-6 py-4 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'users' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent text-gray-400 hover:text-white' }}">
                User Management
            </button>
            <button wire:click="$set('activeTab', 'hubs')" class="px-6 py-4 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'hubs' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent text-gray-400 hover:text-white' }}">
                Hubs & Bases
            </button>
            <button wire:click="$set('activeTab', 'ranks')" class="px-6 py-4 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'ranks' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent text-gray-400 hover:text-white' }}">
                Rank Management
            </button>
        </div>

        <div class="p-6">
            @if($activeTab === 'general')
                <div>
                    <h3 class="text-lg font-medium text-white mb-4">VA General Settings</h3>
                    
                    @if (session()->has('settings_message'))
                        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('settings_message') }}</span>
                        </div>
                    @endif

                    <form wire:submit.prevent="saveSettings" class="space-y-6">
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 md:col-span-3">
                                <x-label for="name" value="{{ __('Virtual Airline Name') }}" class="text-white" />
                                <x-input id="name" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="name" />
                                <x-input-error for="name" class="mt-2 text-red-400 text-xs" />
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="icao" value="{{ __('Primary Airline ICAO (Default Callsign Prefix)') }}" class="text-white" />
                                <x-input id="icao" type="text" maxlength="4" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white uppercase font-mono font-bold" wire:model="icao" placeholder="e.g. EZY" />
                                <x-input-error for="icao" class="mt-2 text-red-400 text-xs" />
                                <p class="text-xs text-gray-400 mt-1">Default 3-letter ICAO prefix for the airline (e.g. EZY for easyJet UK).</p>
                            </div>
                        </div>

                        <!-- Secondary ICAOs Management -->
                        <div class="p-4 bg-[#141a24] rounded-xl border border-white/10 space-y-3">
                            <div>
                                <h4 class="text-white font-bold text-sm">Secondary Airline ICAOs (Subsidiaries / Call-signs)</h4>
                                <p class="text-xs text-gray-400 mt-0.5">Add secondary ICAO codes (e.g. <strong class="text-slate-200 font-mono">EZS</strong> for easyJet Switzerland, <strong class="text-slate-200 font-mono">EJU</strong> for easyJet Europe). These will be available in the Route Manager when creating routes.</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <!-- Primary ICAO Badge -->
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-tenant-accent/20 border border-tenant-accent/40 text-tenant-accent text-xs font-mono font-bold shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-tenant-accent"></span>
                                    <span>{{ strtoupper($icao ?: 'N/A') }}</span>
                                    <span class="text-[10px] text-slate-300 font-normal ml-0.5">(Primary)</span>
                                </div>

                                <!-- Secondary ICAO Badges -->
                                @foreach($secondary_icaos as $index => $code)
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-800 border border-slate-700 text-slate-200 text-xs font-mono font-bold shadow-sm hover:border-slate-600 transition">
                                        <span>{{ $code }}</span>
                                        <button type="button" wire:click="removeSecondaryIcao({{ $index }})" class="text-red-400 hover:text-red-300 transition ml-1 p-0.5" title="Remove Secondary ICAO">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Add Secondary ICAO Input -->
                            <div class="flex items-center gap-2 pt-2 max-w-md">
                                <x-input type="text" wire:model="newSecondaryIcao" maxlength="4" class="bg-[#1e2532] border-gray-700 text-white uppercase text-xs font-mono font-bold" placeholder="e.g. EZS or EJU" />
                                <button type="button" wire:click="addSecondaryIcao" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-tenant-accent border border-tenant-accent/30 rounded-xl text-xs font-bold transition whitespace-nowrap">
                                    + Add Secondary ICAO
                                </button>
                            </div>
                            <x-input-error for="newSecondaryIcao" class="text-red-400 text-xs" />
                        </div>

                        <!-- Base Theme Settings -->
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 md:col-span-3">
                                <x-label for="accent_color" value="{{ __('Accent Color (Hex)') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="accent_color_picker" type="color" wire:model="accent_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="accent_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white" wire:model="accent_color" placeholder="#f97316" />
                                </div>
                                <x-input-error for="accent_color" class="mt-2" />
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="bg_color" value="{{ __('Background Color (Hex)') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="bg_color_picker" type="color" wire:model="bg_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="bg_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white" wire:model="bg_color" placeholder="#1e1e1e" />
                                </div>
                                <x-input-error for="bg_color" class="mt-2" />
                                <p class="text-xs text-gray-400 mt-1">This will change the body background of the Virtual Airline.</p>
                            </div>

                            <!-- Dispatch Settings -->
                            <div class="col-span-6">
                                <hr class="border-white/10 my-2">
                                <h4 class="text-white font-bold text-sm mb-4">Dispatch Settings</h4>
                            </div>

                            <div class="col-span-6 sm:col-span-4">
                                <x-label for="default_simbrief_ofp_format" value="{{ __('Default SimBrief OFP Format') }}" class="text-white" />
                                <select id="default_simbrief_ofp_format" wire:model="default_simbrief_ofp_format" class="mt-1 block w-full bg-black/40 border border-white/10 text-white text-sm rounded focus:ring-tenant-accent focus:border-tenant-accent p-2.5">
                                    @foreach($simbriefFormats as $key => $name)
                                        <option value="{{ $key }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error for="default_simbrief_ofp_format" class="mt-2" />
                                <p class="text-xs text-gray-400 mt-1">This format will be used for all pilots unless they override it in their personal preferences.</p>
                            </div>
                        </div>

                        <div>
                            <x-label for="logo" value="{{ __('VA Logo') }}" />
                            
                            <div class="mt-2 flex items-center gap-4">
                                @if(auth()->user()->tenant->logo_path)
                                    <div class="w-16 h-16 rounded overflow-hidden bg-white/5 flex items-center justify-center">
                                        <img src="{{ Storage::url(auth()->user()->tenant->logo_path) }}" alt="Logo" class="max-w-full max-h-full object-contain">
                                    </div>
                                @endif
                                <div class="flex-grow">
                                    <input type="file" id="logo" wire:model="logo" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-tenant-accent file:text-white hover:file:opacity-90 transition cursor-pointer" accept="image/*">
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG up to 1MB</p>
                                    <x-input-error for="logo" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-white/10">
                            <button type="submit" wire:loading.attr="disabled" class="bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if($activeTab === 'hubs')
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
            @endif

            @if($activeTab === 'users')
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-white">User Management</h3>
                        <button wire:click="openUserModal" class="bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add User
                        </button>
                    </div>

                    @if (session()->has('user_message'))
                        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('user_message') }}</span>
                        </div>
                    @endif

                    <div class="overflow-x-auto -mx-6">
                        <table class="min-w-full divide-y divide-white/5">
                            <thead class="bg-white/5">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @forelse($users as $user)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-tenant-accent/20 text-tenant-accent">
                                            {{ $user->roles->first()->name ?? 'None' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button wire:click="editUser({{ $user->id }})" class="text-tenant-accent hover:opacity-80 transition-colors mr-3">Edit</button>
                                        @if(auth()->id() !== $user->id)
                                            <button wire:click="deleteUser({{ $user->id }})" wire:confirm="Are you sure you want to delete this user?" class="text-red-400 hover:text-red-300 transition-colors">Delete</button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                        No users found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($activeTab === 'ranks')
                <livewire:rank-manager />
            @endif
        </div>
    </div>

    <!-- User Modal -->
    <x-dialog-modal wire:model.live="showUserModal">
        <x-slot name="title">
            {{ $editingUserId ? __('Edit User') : __('Add New User') }}
        </x-slot>

        <x-slot name="content">
            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="userName" value="{{ __('Name') }}" />
                <x-input id="userName" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="userName" />
                <x-input-error for="userName" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="userEmail" value="{{ __('Email') }}" />
                <x-input id="userEmail" type="email" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="userEmail" />
                <x-input-error for="userEmail" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="userPassword" value="{{ $editingUserId ? __('Password (leave blank to keep current)') : __('Password') }}" />
                <x-input id="userPassword" type="password" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="userPassword" />
                <x-input-error for="userPassword" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="userRole" value="{{ __('Role') }}" />
                <select id="userRole" wire:model="userRole" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white rounded-md shadow-sm focus:border-tenant-accent focus:ring focus:ring-tenant-accent focus:ring-opacity-50">
                    <option value="Pilot">Pilot</option>
                    <option value="VA Owner">VA Owner</option>
                </select>
                <x-input-error for="userRole" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showUserModal', false)" wire:loading.attr="disabled" class="bg-gray-600 text-white hover:bg-gray-500 border-none">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveUser" wire:loading.attr="disabled" class="ml-3 bg-tenant-accent text-white px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition">
                {{ $editingUserId ? __('Save Changes') : __('Create User') }}
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
