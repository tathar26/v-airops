<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
    <!-- Header with Breadcrumb & Create Action -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                <a href="{{ route('notams') }}" class="hover:text-tenant-accent transition">NOTAMs</a>
                <span>/</span>
                <span class="font-semibold" style="color: var(--tenant-card-text, #ffffff);">Staff Management</span>
            </div>
            <h1 class="text-2xl font-black uppercase tracking-wider flex items-center gap-2 mt-1"
                style="color: var(--tenant-card-text, #ffffff);">
                <span>🛡️</span> NOTAM Operations Center
            </h1>
            <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
                Author, publish, and manage operational bulletins. Unread NOTAMs will be enforced on flight bookings automatically.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('notams') }}" class="px-4 py-2 rounded-xl text-xs font-semibold border transition hover:opacity-80 flex items-center gap-2"
                style="background-color: var(--tenant-button-secondary-bg, #1f2937); color: var(--tenant-button-secondary-text, #f3f4f6); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                <span>👁️</span> Pilot View
            </a>

            <button wire:click="openCreateModal"
                class="px-5 py-2 rounded-xl text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-2"
                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create NOTAM
            </button>
        </div>
    </div>

    @if (session()->has('notam_manager_message'))
        <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-200 flex items-center justify-between shadow-lg" role="alert">
            <span class="text-sm font-medium">{{ session('notam_manager_message') }}</span>
            <span class="text-emerald-400 text-lg">✓</span>
        </div>
    @endif

    <!-- Cards Grid & Filters -->
    <div class="va-card rounded-2xl p-6 border shadow-2xl space-y-6"
        style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-text, #ffffff);">
        
        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b"
            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
            
            <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                <div class="relative flex-1 min-w-[220px]">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--tenant-card-muted, #94a3b8);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search NOTAMs..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border focus:outline-none transition"
                        style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-input-text, #ffffff);">
                </div>

                <select wire:model.live="selectedPriority"
                    class="py-2 px-3 text-xs rounded-xl border focus:outline-none transition cursor-pointer"
                    style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-input-text, #ffffff);">
                    <option value="all">All Priorities</option>
                    <option value="High">🔴 High Priority</option>
                    <option value="Medium">🟠 Medium Priority</option>
                    <option value="Low">🔵 Low Priority</option>
                </select>

                <select wire:model.live="selectedCategory"
                    class="py-2 px-3 text-xs rounded-xl border focus:outline-none transition cursor-pointer"
                    style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-input-text, #ffffff);">
                    <option value="all">All Categories</option>
                    <option value="Operations">Operations</option>
                    <option value="Fleet">Fleet</option>
                    <option value="Training">Training</option>
                    <option value="Briefings">Briefings</option>
                    <option value="General">General</option>
                </select>
            </div>
        </div>

        <!-- NOTAM Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($notams as $notam)
                @php
                    $readCount = $notam->reads->count();
                    $pct = $totalPilots > 0 ? round(($readCount / $totalPilots) * 100) : 0;
                    $priorityClass = match(strtolower($notam->priority)) {
                        'high'   => 'bg-red-500/20 text-red-300 border-red-500/40',
                        'medium' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                        'low'    => 'bg-sky-500/20 text-sky-300 border-sky-500/40',
                        default  => 'bg-gray-700/50 text-gray-300 border-white/10',
                    };
                @endphp
                <div class="va-card p-5 rounded-2xl border transition-all shadow-lg flex flex-col justify-between"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-text, #ffffff);">
                    
                    <div>
                        <!-- Header Badges -->
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold border {{ $priorityClass }}">
                                    {{ $notam->priority }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-sky-500/20 text-sky-300 border border-sky-500/30">
                                    {{ $notam->category }}
                                </span>
                            </div>

                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $notam->is_active ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/30' }}">
                                {{ $notam->is_active ? 'Active' : 'Draft/Disabled' }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h4 class="text-base font-bold mb-2 line-clamp-2" style="color: var(--tenant-card-text, #ffffff);">
                            {{ $notam->title }}
                        </h4>

                        <!-- Body Snippet -->
                        <p class="text-xs mb-4 line-clamp-3" style="color: var(--tenant-card-muted, #94a3b8);">
                            {{ $notam->body }}
                        </p>

                        <!-- Expiry / Author Info -->
                        <div class="p-3 rounded-xl border mb-4 text-xs space-y-1"
                            style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.3)); border-color: var(--tenant-input-border, rgba(255,255,255,0.06));">
                            <div class="flex justify-between">
                                <span style="color: var(--tenant-card-muted, #94a3b8);">Expires:</span>
                                <span class="font-mono font-medium {{ $notam->isExpired() ? 'text-red-400' : '' }}" style="{{ !$notam->isExpired() ? 'color: var(--tenant-card-text, #ffffff);' : '' }}">
                                    {{ $notam->isPermanent() ? 'Never (Permanent)' : $notam->expires_at->format('Y-m-d H:i') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span style="color: var(--tenant-card-muted, #94a3b8);">Author:</span>
                                <span style="color: var(--tenant-card-text, #ffffff);">{{ $notam->author?->name ?? 'System' }}</span>
                            </div>
                        </div>

                        <!-- Pilot Acknowledgment Progress -->
                        <div class="mb-4 space-y-1.5">
                            <div class="flex justify-between text-xs font-medium">
                                <span style="color: var(--tenant-card-muted, #94a3b8);">Pilot Acknowledgments</span>
                                <span class="text-tenant-accent font-bold">{{ $readCount }} / {{ $totalPilots }} ({{ $pct }}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full overflow-hidden border"
                                style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.4)); border-color: var(--tenant-input-border, rgba(255,255,255,0.05));">
                                <div class="h-full transition-all duration-500"
                                    style="width: {{ $pct }}%; background-color: var(--tenant-accent, #21A19D);"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t flex items-center justify-between gap-2"
                        style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        
                        <button wire:click="viewReads({{ $notam->id }})" class="text-xs font-semibold hover:text-tenant-accent transition"
                            style="color: var(--tenant-card-muted, #94a3b8);">
                            👥 View Reads ({{ $readCount }})
                        </button>

                        <div class="flex items-center gap-2">
                            <button wire:click="editNotam({{ $notam->id }})" class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition hover:opacity-80"
                                style="background-color: var(--tenant-button-secondary-bg, #1f2937); color: var(--tenant-button-secondary-text, #f3f4f6); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                Edit
                            </button>
                            <button wire:click="deleteNotam({{ $notam->id }})"
                                wire:confirm="Are you sure you want to delete NOTAM '{{ $notam->title }}'?"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-red-600/20 hover:bg-red-600/40 text-red-300 transition">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center rounded-2xl border"
                    style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                    <span class="text-4xl">📢</span>
                    <h4 class="font-bold text-base mt-2" style="color: var(--tenant-card-text, #ffffff);">No NOTAMs Found</h4>
                    <p class="text-xs mt-1 max-w-md mx-auto" style="color: var(--tenant-card-muted, #94a3b8);">
                        Publish your first operational NOTAM to inform pilots of route updates, airport procedures, or fleet rules.
                    </p>
                    <button wire:click="openCreateModal"
                        class="mt-4 px-4 py-2 rounded-xl text-xs font-bold shadow-md hover:opacity-90 transition"
                        style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                        + Create First NOTAM
                    </button>
                </div>
            @endforelse
        </div>

        <div class="pt-4">
            {{ $notams->links() }}
        </div>
    </div>

    <!-- Create / Edit NOTAM Modal -->
    <x-dialog-modal wire:model.live="showNotamModal" maxWidth="3xl">
        <x-slot name="title">
            <div class="flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                <span>📢</span>
                <span>{{ $editingNotamId ? __('Edit Operational NOTAM') : __('Create Operational NOTAM') }}</span>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <!-- Title -->
                <div>
                    <x-label for="title" value="{{ __('NOTAM Title / Subject') }}" style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="title" type="text" class="mt-1 block w-full"
                        style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                        wire:model="title" placeholder="e.g. Reminder Regarding Wet Lease Operators and Hot Weather Operations" />
                    <x-input-error for="title" class="mt-1 text-red-400 text-xs" />
                </div>

                <!-- Grid: Priority & Category -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-label for="priority" value="{{ __('Priority Level') }}" style="color: var(--tenant-card-text, #ffffff);" />
                        <select id="priority" wire:model="priority" class="mt-1 block w-full rounded-md shadow-sm text-sm"
                            style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);">
                            <option value="Low">Low Priority (Informational)</option>
                            <option value="Medium">Medium Priority (Standard Notice)</option>
                            <option value="High">High Priority (Urgent SOP/Safety Warning)</option>
                        </select>
                        <x-input-error for="priority" class="mt-1 text-red-400 text-xs" />
                    </div>

                    <div>
                        <x-label for="category" value="{{ __('Category') }}" style="color: var(--tenant-card-text, #ffffff);" />
                        <select id="category" wire:model.live="category" class="mt-1 block w-full rounded-md shadow-sm text-sm"
                            style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);">
                            <option value="Operations">Operations</option>
                            <option value="Fleet">Fleet</option>
                            <option value="Training">Training</option>
                            <option value="Briefings">Briefings</option>
                            <option value="General">General</option>
                            <option value="Other">Custom Category...</option>
                        </select>
                        <x-input-error for="category" class="mt-1 text-red-400 text-xs" />
                    </div>
                </div>

                @if($category === 'Other')
                    <div>
                        <x-label for="customCategory" value="{{ __('Custom Category Name') }}" style="color: var(--tenant-card-text, #ffffff);" />
                        <x-input id="customCategory" type="text" class="mt-1 block w-full"
                            style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                            wire:model="customCategory" placeholder="e.g. Events, Dispatch, Air Traffic Control" />
                    </div>
                @endif

                <!-- Expiry Settings -->
                <div class="p-4 rounded-xl border space-y-3"
                    style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.3)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model.live="isPermanent" class="rounded border-gray-600 text-tenant-accent focus:ring-tenant-accent" style="background-color: var(--tenant-input-bg, #111827);">
                        <span class="text-sm font-semibold" style="color: var(--tenant-card-text, #ffffff);">Permanent NOTAM (Never Expires)</span>
                    </label>

                    @if(!$isPermanent)
                        <div class="pt-2">
                            <x-label for="expiresAt" value="{{ __('Expiration Date & Time (UTC)') }}" style="color: var(--tenant-card-text, #ffffff);" />
                            <x-input id="expiresAt" type="datetime-local" class="mt-1 block w-full"
                                style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                                wire:model="expiresAt" />
                            <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Pilots will no longer be prompted to acknowledge this NOTAM after expiration.</p>
                            <x-input-error for="expiresAt" class="mt-1 text-red-400 text-xs" />
                        </div>
                    @endif
                </div>

                <!-- NOTAM Body Text -->
                <div>
                    <x-label for="body" value="{{ __('NOTAM Content / Directives (Markdown & Plain text supported)') }}" style="color: var(--tenant-card-text, #ffffff);" />
                    <textarea id="body" rows="8" wire:model="body"
                        class="mt-1 block w-full rounded-xl border text-sm focus:outline-none transition p-3"
                        style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                        placeholder="Write the full NOTAM instructions, SOP changes, procedures, or restrictions here..."></textarea>
                    <x-input-error for="body" class="mt-1 text-red-400 text-xs" />
                </div>

                <!-- Active Toggle -->
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="isActive" class="rounded border-gray-600 text-tenant-accent focus:ring-tenant-accent" style="background-color: var(--tenant-input-bg, #111827);">
                        <span class="text-sm font-semibold" style="color: var(--tenant-card-text, #ffffff);">Publish and Activate Immediately</span>
                    </label>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showNotamModal', false)" wire:loading.attr="disabled" class="border-none"
                style="background-color: var(--tenant-button-secondary-bg, #1f2937); color: var(--tenant-button-secondary-text, #f3f4f6);">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveNotam" wire:loading.attr="disabled" class="ml-3 px-5 py-2 rounded-lg text-sm font-bold shadow-md hover:opacity-90 transition"
                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                {{ $editingNotamId ? __('Save Changes') : __('Publish NOTAM') }}
            </button>
        </x-slot>
    </x-dialog-modal>

    <!-- Pilot Reads Audit Modal -->
    @if($showReadsModal && $viewingNotam)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto bg-black/80 backdrop-blur-sm animate-fade-in"
             wire:keydown.escape="$set('showReadsModal', false)">
            
            <div class="va-card rounded-2xl w-full max-w-2xl shadow-2xl border overflow-hidden relative"
                 style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.15)); color: var(--tenant-card-text, #ffffff);">
                
                <div class="p-6 border-b flex justify-between items-center"
                     style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); background-color: var(--tenant-card-bg, #181D29);">
                    <div>
                        <h3 class="text-base font-bold" style="color: var(--tenant-card-text, #ffffff);">
                            Pilot Acknowledgments Audit
                        </h3>
                        <p class="text-xs mt-0.5 truncate max-w-md" style="color: var(--tenant-card-muted, #94a3b8);">
                            {{ $viewingNotam->title }}
                        </p>
                    </div>
                    <button wire:click="$set('showReadsModal', false)" class="p-1 rounded-lg hover:bg-white/10 text-gray-400 hover:text-white">✕</button>
                </div>

                <div class="p-6 max-h-96 overflow-y-auto">
                    <table class="min-w-full divide-y" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.06));">
                        <thead>
                            <tr>
                                <th class="text-left text-xs font-bold uppercase pb-2" style="color: var(--tenant-card-muted, #94a3b8);">Pilot</th>
                                <th class="text-right text-xs font-bold uppercase pb-2" style="color: var(--tenant-card-muted, #94a3b8);">Read Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.04));">
                            @forelse($viewingNotam->reads as $read)
                                <tr class="text-xs">
                                    <td class="py-2.5 font-semibold" style="color: var(--tenant-card-text, #ffffff);">
                                        {{ $read->user->name }}
                                        <span class="font-mono text-[10px] ml-1" style="color: var(--tenant-card-muted, #94a3b8);">({{ $read->user->email }})</span>
                                    </td>
                                    <td class="py-2.5 text-right font-mono" style="color: var(--tenant-card-muted, #94a3b8);">
                                        {{ $read->read_at->format('Y-m-d H:i:s') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-6 text-center text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                                        No pilots have acknowledged this NOTAM yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t flex justify-end"
                     style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); background-color: var(--tenant-card-bg, #181D29);">
                    <button wire:click="$set('showReadsModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold border transition hover:opacity-80"
                        style="background-color: var(--tenant-button-secondary-bg, #1f2937); color: var(--tenant-button-secondary-text, #f3f4f6); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
