<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="va-card rounded-2xl overflow-hidden shadow-2xl border"
        style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-text, #ffffff);">
        <!-- Tabs -->
        <div class="border-b flex flex-wrap" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); background-color: var(--tenant-card-bg, #181D29);">
            <button wire:click="$set('activeTab', 'general')" class="px-6 py-4 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'general' ? 'border-tenant-accent text-tenant-accent font-bold' : 'border-transparent text-gray-400 hover:text-white' }}">
                General Settings
            </button>
            <button wire:click="$set('activeTab', 'roles')" class="px-6 py-4 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'roles' ? 'border-tenant-accent text-tenant-accent font-bold' : 'border-transparent text-gray-400 hover:text-white' }}">
                Roles &amp; Permissions
            </button>
            <button wire:click="$set('activeTab', 'users')" class="px-6 py-4 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'users' ? 'border-tenant-accent text-tenant-accent font-bold' : 'border-transparent text-gray-400 hover:text-white' }}">
                User &amp; Staff Management
            </button>
            <button wire:click="$set('activeTab', 'hubs')" class="px-6 py-4 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'hubs' ? 'border-tenant-accent text-tenant-accent font-bold' : 'border-transparent text-gray-400 hover:text-white' }}">
                Hubs &amp; Bases
            </button>
            <button wire:click="$set('activeTab', 'ranks')" class="px-6 py-4 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'ranks' ? 'border-tenant-accent text-tenant-accent font-bold' : 'border-transparent text-gray-400 hover:text-white' }}">
                Rank Management
            </button>
            <button wire:click="$set('activeTab', 'scoring')" class="px-6 py-4 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'scoring' ? 'border-tenant-accent text-tenant-accent font-bold' : 'border-transparent text-gray-400 hover:text-white' }}">
                PIREP Scoring
            </button>
        </div>

        <div class="p-6">
            @if($activeTab === 'general')
                @include('livewire.tenant-settings.partials.general-tab')
            @elseif($activeTab === 'hubs')
                @include('livewire.tenant-settings.partials.hubs-tab')
            @elseif($activeTab === 'roles')
                @include('livewire.tenant-settings.partials.roles-tab')
            @elseif($activeTab === 'users')
                @include('livewire.tenant-settings.partials.users-tab')
            @elseif($activeTab === 'ranks')
                <livewire:rank-manager />
            @elseif($activeTab === 'scoring')
                @include('livewire.tenant-settings.partials.scoring-tab')
            @endif
        </div>
    </div>

    @include('livewire.tenant-settings.partials.modals')
</div>
