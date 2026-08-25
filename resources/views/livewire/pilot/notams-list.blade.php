<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
    <!-- Top Alert / Flash Block Message -->
    @if(session('warning') || session('notam_block'))
        <div class="p-4 rounded-2xl bg-amber-500/15 border border-amber-500/30 text-amber-200 flex items-center justify-between shadow-lg animate-pulse" role="alert">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <h4 class="text-sm font-bold text-amber-300">Flight Booking Notice</h4>
                    <p class="text-xs text-amber-200/90 mt-0.5">{{ session('warning') ?? 'You must read and acknowledge all active NOTAMs before booking a flight.' }}</p>
                </div>
            </div>
            <span class="text-xs font-mono font-bold bg-amber-500/20 px-2.5 py-1 rounded-lg border border-amber-500/40 text-amber-300">
                Action Required
            </span>
        </div>
    @endif

    @if(session('notam_acknowledged'))
        <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-200 flex items-center justify-between shadow-lg" role="alert">
            <div class="flex items-center gap-3">
                <span class="text-xl text-emerald-400">✓</span>
                <p class="text-xs text-emerald-200">{{ session('notam_acknowledged') }}</p>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white text-xs">✕</button>
        </div>
    @endif

    <!-- Main NOTAM Card Panel -->
    <div class="va-card rounded-2xl overflow-hidden shadow-2xl border transition-all"
        style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-text, #ffffff);">
        
        <!-- Header Bar -->
        <div class="px-6 py-5 border-b flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); background-color: var(--tenant-card-bg, #181D29);">
            <div>
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📢</span>
                    <h2 class="text-xl font-black uppercase tracking-wider" style="color: var(--tenant-card-text, #ffffff);">
                        NOTAMs
                    </h2>
                    @if($unreadCount > 0)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 animate-pulse">
                            {{ $unreadCount }} Unread
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            All Caught Up
                        </span>
                    @endif
                </div>
                <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
                    Official operational bulletins, route restrictions, fleet notices, and standard operating procedure updates.
                </p>
            </div>

            <!-- Quick Action / Management Link for Staff -->
            <div class="flex items-center gap-3">
                @if(auth()->user()->hasAirlinePermission('manage_notams'))
                    <a href="{{ route('admin.notams') }}" class="px-4 py-2 rounded-xl text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-2 border"
                        style="background-color: var(--tenant-button-secondary-bg, rgba(255,255,255,0.08)); color: var(--tenant-button-secondary-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                        <span>⚙️</span> Manage NOTAMs
                    </a>
                @endif
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="p-4 border-b flex flex-wrap items-center justify-between gap-4"
            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08)); background-color: var(--tenant-card-bg, #181D29);">
            
            <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[200px]">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--tenant-card-muted, #94a3b8);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search NOTAM title or content..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border focus:outline-none transition"
                        style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-input-text, #ffffff);">
                </div>

                <!-- Category Filter -->
                <select wire:model.live="selectedCategory"
                    class="py-2 px-3 text-xs rounded-xl border focus:outline-none transition cursor-pointer"
                    style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-input-text, #ffffff);">
                    <option value="all">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>

                <!-- Priority Filter -->
                <select wire:model.live="selectedPriority"
                    class="py-2 px-3 text-xs rounded-xl border focus:outline-none transition cursor-pointer"
                    style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-input-text, #ffffff);">
                    <option value="all">All Priorities</option>
                    <option value="High">🔴 High Priority</option>
                    <option value="Medium">🟠 Medium Priority</option>
                    <option value="Low">🔵 Low Priority</option>
                </select>

                <!-- Status Filter -->
                <select wire:model.live="statusFilter"
                    class="py-2 px-3 text-xs rounded-xl border focus:outline-none transition cursor-pointer"
                    style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-input-text, #ffffff);">
                    <option value="all">All Status</option>
                    <option value="unread">⚡ Unread Only</option>
                    <option value="read">✓ Read Only</option>
                </select>
            </div>

            <!-- Per Page Dropdown -->
            <div class="flex items-center gap-2 text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                <span>Per page</span>
                <select wire:model.live="perPage"
                    class="py-1.5 px-2.5 text-xs rounded-xl border focus:outline-none transition cursor-pointer"
                    style="background-color: var(--tenant-input-bg, #0a0d14); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-input-text, #ffffff);">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <!-- NOTAMs Table View -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.06));">
                <thead style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                    <tr>
                        <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider w-28" style="color: var(--tenant-card-muted, #94a3b8);">Priority</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider w-24" style="color: var(--tenant-card-muted, #94a3b8);">Status</th>
                        <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wider w-36" style="color: var(--tenant-card-muted, #94a3b8);">Expires</th>
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">NOTAM Title</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider w-32" style="color: var(--tenant-card-muted, #94a3b8);">Category</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider w-40" style="color: var(--tenant-card-muted, #94a3b8);">Posted</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.04));">
                    @forelse($notams as $notam)
                        @php
                            $isRead = $notam->reads->isNotEmpty();
                            $priorityClass = match(strtolower($notam->priority)) {
                                'high'   => 'bg-red-500/20 text-red-300 border-red-500/40',
                                'medium' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                                'low'    => 'bg-sky-500/20 text-sky-300 border-sky-500/40',
                                default  => 'bg-gray-700/50 text-gray-300 border-white/10',
                            };
                            $categoryClass = match(strtolower($notam->category)) {
                                'operations' => 'bg-sky-500/20 text-sky-300 border-sky-500/30',
                                'fleet'      => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
                                'training'   => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
                                'briefings'  => 'bg-teal-500/20 text-teal-300 border-teal-500/30',
                                default      => 'bg-white/10 text-gray-300 border-white/10',
                            };
                        @endphp
                        <tr wire:click="openNotam({{ $notam->id }})"
                            class="cursor-pointer transition-all hover:bg-white/[0.04] group {{ !$isRead ? 'bg-amber-500/[0.03]' : '' }}">
                            
                            <!-- Priority Badge -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center px-3 py-1 rounded-lg text-xs font-bold border {{ $priorityClass }}">
                                    {{ $notam->priority }}
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($isRead)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        Read
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-500/25 text-amber-300 border border-amber-500/50 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        Unread
                                    </span>
                                @endif
                            </td>

                            <!-- Expires -->
                            <td class="px-5 py-4 whitespace-nowrap text-xs font-mono font-medium"
                                style="color: var(--tenant-card-text, #ffffff);">
                                @if($notam->expires_at)
                                    <span class="{{ $notam->isExpired() ? 'text-red-400 line-through' : '' }}" style="{{ !$notam->isExpired() ? 'color: var(--tenant-card-text, #ffffff);' : '' }}">
                                        {{ $notam->expires_at->format('jS M y H:i') }}
                                    </span>
                                @else
                                    <span style="color: var(--tenant-card-muted, #94a3b8);">Never</span>
                                @endif
                            </td>

                            <!-- Title -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold transition group-hover:text-tenant-accent"
                                        style="color: var(--tenant-card-text, #ffffff);">
                                        {{ $notam->title }}
                                    </span>
                                    @if(!$isRead)
                                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    @endif
                                </div>
                            </td>

                            <!-- Category Pill -->
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold border {{ $categoryClass }}">
                                    {{ $notam->category }}
                                </span>
                            </td>

                            <!-- Posted Date -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-mono"
                                style="color: var(--tenant-card-muted, #94a3b8);">
                                {{ $notam->posted_at ? $notam->posted_at->format('jS M y H:i') : $notam->created_at->format('jS M y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center" style="color: var(--tenant-card-muted, #94a3b8);">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <span class="text-3xl">📭</span>
                                    <p class="text-sm font-semibold" style="color: var(--tenant-card-text, #ffffff);">No NOTAMs Found</p>
                                    <p class="text-xs max-w-sm" style="color: var(--tenant-card-muted, #94a3b8);">No active notices match your selected filters. Check back later for operational updates.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer / Pagination -->
        <div class="px-6 py-4 border-t flex flex-col sm:flex-row items-center justify-between gap-4"
            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08)); background-color: var(--tenant-card-bg, #181D29);">
            <div class="text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                Showing <strong>{{ $notams->firstItem() ?? 0 }}</strong> to <strong>{{ $notams->lastItem() ?? 0 }}</strong> of <strong>{{ $notams->total() }}</strong> results
            </div>
            <div>
                {{ $notams->links() }}
            </div>
        </div>
    </div>

    <!-- NOTAM Reader & Acknowledgment Modal (Matching Screenshot 2) -->
    @if($showNotamModal && $selectedNotam)
        @php
            $isModalRead = $selectedNotam->reads->isNotEmpty();
            $modalPriorityClass = match(strtolower($selectedNotam->priority)) {
                'high'   => 'bg-red-500/20 text-red-300 border-red-500/40',
                'medium' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                'low'    => 'bg-sky-500/20 text-sky-300 border-sky-500/40',
                default  => 'bg-gray-700/50 text-gray-300 border-white/10',
            };
        @endphp

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto bg-black/80 backdrop-blur-sm animate-fade-in"
             wire:keydown.escape="closeModal">
            
            <div class="va-card rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl border overflow-hidden relative"
                 style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.15)); color: var(--tenant-card-text, #ffffff);">
                
                <!-- Modal Header (Matching Screenshot 2) -->
                <div class="p-6 border-b space-y-4"
                     style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); background-color: var(--tenant-card-bg, #181D29);">
                    
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <!-- Left Badges & Title -->
                        <div class="flex flex-wrap items-center gap-2.5 flex-1">
                            <!-- Priority -->
                            <span class="px-3 py-1 rounded-lg text-xs font-bold border {{ $modalPriorityClass }}">
                                {{ $selectedNotam->priority }}
                            </span>

                            <!-- Status -->
                            <span class="px-3 py-1 rounded-lg text-xs font-bold {{ $isModalRead ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-amber-500/25 text-amber-300 border border-amber-500/50' }}">
                                {{ $isModalRead ? 'Read' : 'Unread' }}
                            </span>

                            <!-- Expiry Indicator -->
                            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-semibold border"
                                  style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.3)); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-muted, #94a3b8);">
                                {{ $selectedNotam->isPermanent() ? 'Permanent NOTAM' : 'Expires: ' . $selectedNotam->expires_at->format('Y-m-d') }}
                            </span>
                        </div>

                        <!-- Right Badges -->
                        <div class="flex items-center gap-3">
                            <span class="px-3.5 py-1 rounded-lg text-xs font-bold shadow-sm"
                                  style="background-color: var(--tenant-accent, #21A19D); color: var(--tenant-button-text, #ffffff);">
                                {{ $selectedNotam->category }}
                            </span>
                            <span class="text-xs font-mono" style="color: var(--tenant-card-muted, #94a3b8);">
                                Posted: {{ $selectedNotam->posted_at ? $selectedNotam->posted_at->format('Y-m-d') : $selectedNotam->created_at->format('Y-m-d') }}
                            </span>
                            <button wire:click="closeModal" class="p-1 rounded-lg hover:bg-white/10 text-gray-400 hover:text-white transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Highlighted Title with Tenant Accent -->
                    <h3 class="text-lg md:text-xl font-bold tracking-tight text-tenant-accent">
                        {{ $selectedNotam->title }}
                    </h3>
                </div>

                <!-- Modal Body (Content with rich markdown rendering) -->
                <div class="p-6 md:p-8 overflow-y-auto max-h-[60vh] space-y-4 text-sm leading-relaxed"
                     style="color: var(--tenant-card-text, #f1f5f9);">
                    
                    <div class="prose prose-invert max-w-none space-y-3" style="color: var(--tenant-card-text, #f1f5f9);">
                        {!! nl2br(e($selectedNotam->body)) !!}
                    </div>

                    @if($selectedNotam->author)
                        <div class="pt-4 mt-6 border-t flex items-center justify-between text-xs"
                             style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08)); color: var(--tenant-card-muted, #94a3b8);">
                            <span>Issued by: <strong style="color: var(--tenant-card-text, #ffffff);">{{ $selectedNotam->author->name }}</strong></span>
                            @if($selectedNotam->expires_at)
                                <span>Valid until: <strong class="font-mono" style="color: var(--tenant-card-text, #ffffff);">{{ $selectedNotam->expires_at->toDayDateTimeString() }}</strong></span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="p-4 md:p-6 border-t flex items-center justify-between gap-3"
                     style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); background-color: var(--tenant-card-bg, #181D29);">
                    
                    <div class="text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                        @if($isModalRead)
                            <span class="text-emerald-400 font-semibold flex items-center gap-1.5">
                                <span>✓</span> NOTAM acknowledged. You may proceed with flight operations.
                            </span>
                        @else
                            <span>Click acknowledge to confirm you have read this notice.</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        <button wire:click="closeModal" class="px-4 py-2 rounded-xl text-xs font-semibold border transition hover:opacity-80"
                            style="background-color: var(--tenant-button-secondary-bg, #1f2937); color: var(--tenant-button-secondary-text, #f3f4f6); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                            Close
                        </button>

                        <button wire:click="acknowledgeNotam({{ $selectedNotam->id }})"
                            class="px-5 py-2 rounded-xl text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-2"
                            style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                            <span>✓</span> Acknowledge NOTAM
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
