<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Sidebar Navigation -->
        <div class="w-full md:w-64 flex-shrink-0">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 px-3">Your Account</h2>
            <nav class="space-y-2">
                <button wire:click="$set('activeTab', 'account')" class="w-full flex items-center px-4 py-3 text-sm font-semibold rounded-lg transition-all duration-200 {{ $activeTab === 'account' ? 'bg-tenant-accent text-white shadow-lg shadow-tenant-accent/20 translate-x-1' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3 {{ $activeTab === 'account' ? 'text-white' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Account Settings
                </button>
                <button wire:click="$set('activeTab', 'social')" class="w-full flex items-center px-4 py-3 text-sm font-semibold rounded-lg transition-all duration-200 {{ $activeTab === 'social' ? 'bg-tenant-accent text-white shadow-lg shadow-tenant-accent/20 translate-x-1' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3 {{ $activeTab === 'social' ? 'text-white' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                    Social, Online & 3rd Party
                </button>
                <button wire:click="$set('activeTab', 'security')" class="w-full flex items-center px-4 py-3 text-sm font-semibold rounded-lg transition-all duration-200 {{ $activeTab === 'security' ? 'bg-tenant-accent text-white shadow-lg shadow-tenant-accent/20 translate-x-1' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-5 h-5 mr-3 {{ $activeTab === 'security' ? 'text-white' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Password & 2FA
                </button>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-grow">
            @if($activeTab === 'account')
                <div class="glass-panel p-6 mb-6">
                    <h3 class="text-lg font-medium text-white mb-4">Account Details</h3>
                    
                    @if (session()->has('account_message'))
                        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative">
                            {{ session('account_message') }}
                        </div>
                    @endif

                    <form wire:submit.prevent="saveAccount" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-label for="first_name" value="{{ __('First Name') }}" class="text-white" />
                                <x-input id="first_name" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="first_name" placeholder="John" />
                                <p class="text-xs text-gray-500 mt-1">Your full First Name.</p>
                                <x-input-error for="first_name" class="mt-2" />
                            </div>
                            <div>
                                <x-label for="last_name" value="{{ __('Last Name') }}" class="text-white" />
                                <x-input id="last_name" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="last_name" placeholder="Doe" />
                                <p class="text-xs text-gray-500 mt-1">Your full Last Name.</p>
                                <x-input-error for="last_name" class="mt-2" />
                            </div>
                            <div>
                                <x-label for="email" value="{{ __('Email Address') }}" class="text-white" />
                                <x-input id="email" type="email" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="email" />
                                <p class="text-xs text-gray-500 mt-1">Used for login and notifications.</p>
                                <x-input-error for="email" class="mt-2" />
                            </div>
                            <div>
                                <x-label for="name" value="{{ __('Name Display') }}" class="text-white" />
                                <x-input id="name" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="name" required />
                                <p class="text-xs text-gray-500 mt-1">How your name is displayed across the system.</p>
                                <x-input-error for="name" class="mt-2" />
                            </div>
                        </div>

                        <div class="pt-4 border-t border-white/10">
                            <button type="submit" wire:loading.attr="disabled" class="w-full sm:w-auto bg-tenant-accent text-white px-6 py-2 rounded-md text-sm font-semibold hover:opacity-90 transition shadow-md shadow-tenant-accent/20">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if($activeTab === 'social')
                <div class="space-y-6">
                    @if (session()->has('social_message'))
                        <div class="mb-4 bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded relative">
                            {{ session('social_message') }}
                        </div>
                    @endif

                    <form wire:submit.prevent="saveSocial" class="space-y-6">
                        <!-- Social Networks -->
                        <div class="glass-panel p-6">
                            <h3 class="text-lg font-medium text-white mb-2">Social Networks</h3>
                            <p class="text-sm text-gray-400 mb-6">Social Network Integration will enable Youtube & Twitch buttons in your Pilot Profile linking to your accounts.</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <x-label for="twitch_username" value="{{ __('Twitch Username') }}" class="text-white" />
                                    <x-input id="twitch_username" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="twitch_username" />
                                    <x-input-error for="twitch_username" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="youtube_username" value="{{ __('Youtube Username / Channel Name') }}" class="text-white" />
                                    <x-input id="youtube_username" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="youtube_username" />
                                    <x-input-error for="youtube_username" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Online Networks -->
                        <div class="glass-panel p-6">
                            <h3 class="text-lg font-medium text-white mb-2">Online Networks</h3>
                            <p class="text-sm text-gray-400 mb-6">You can record your Online Network IDs here. Having them here will allow you to book flights on these networks, we may also track them and some VAs will award you extra points for flying online.</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <x-label for="vatsim_id" value="{{ __('VATSIM ID') }}" class="text-white" />
                                    <x-input id="vatsim_id" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="vatsim_id" />
                                    <x-input-error for="vatsim_id" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="ivao_id" value="{{ __('IVAO ID') }}" class="text-white" />
                                    <x-input id="ivao_id" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="ivao_id" />
                                    <x-input-error for="ivao_id" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="poscon_id" value="{{ __('POSCON ID') }}" class="text-white" />
                                    <x-input id="poscon_id" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="poscon_id" />
                                    <x-input-error for="poscon_id" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="apoc_cid" value="{{ __('APOC CID') }}" class="text-white" />
                                    <x-input id="apoc_cid" type="text" class="mt-1 block w-full bg-[#212631] border-gray-600 text-white" wire:model="apoc_cid" />
                                    <x-input-error for="apoc_cid" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" wire:loading.attr="disabled" class="w-full bg-tenant-accent text-white px-6 py-2 rounded-md text-sm font-semibold hover:opacity-90 transition shadow-md shadow-tenant-accent/20">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if($activeTab === 'security')
                <div class="space-y-6">
                    <div class="glass-panel p-6">
                        @livewire('profile.update-password-form')
                    </div>

                    <div class="glass-panel p-6">
                        @livewire('profile.two-factor-authentication-form')
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
