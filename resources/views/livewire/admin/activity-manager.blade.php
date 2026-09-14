<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
    <!-- Header with Breadcrumb & Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                <a href="{{ route('activities.index') }}" class="hover:text-tenant-accent transition">Activities</a>
                <span>/</span>
                <span class="font-semibold" style="color: var(--tenant-card-text, #ffffff);">Operations Management</span>
            </div>
            <h1 class="text-2xl font-black uppercase tracking-wider flex items-center gap-2 mt-1"
                style="color: var(--tenant-card-text, #ffffff);">
                <span>🎯</span> Activities & Flight Events Center
            </h1>
            <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
                Build events, slotted flyouts, tours, scheduled rosters, and collaborative community challenges.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('activities.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold border transition hover:opacity-80 flex items-center gap-2"
                style="background-color: var(--tenant-button-secondary-bg, #1f2937); color: var(--tenant-button-secondary-text, #f3f4f6); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                <span>👁️</span> Pilot Hub
            </a>

            <button wire:click="openCreateModal"
                class="px-5 py-2 rounded-xl text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-2"
                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create Activity
            </button>
        </div>
    </div>

    @if (session()->has('activity_manager_message'))
        <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-200 flex items-center justify-between shadow-lg" role="alert">
            <span class="text-sm font-medium">{{ session('activity_manager_message') }}</span>
            <span class="text-emerald-400 text-lg">✓</span>
        </div>
    @endif

    <!-- Filter Bar & Type Tabs -->
    <div class="va-card rounded-2xl p-6 border shadow-2xl space-y-6"
        style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-text, #ffffff);">
        
        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-1.5 pb-4 border-b overflow-x-auto"
            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
            @php
                $tabs = [
                    'all'                 => 'All Activities',
                    'current'             => '🔥 Active Now',
                    'event'               => 'Events',
                    'slotted_event'       => 'Slotted Flyouts',
                    'focus_airport'       => 'Focus Airports',
                    'tour'                => 'Tours',
                    'roster'              => 'Rosters',
                    'curated_roster'      => 'Curated Rosters',
                    'community_goal'      => 'Community Goals',
                    'community_challenge' => 'Challenges',
                ];
            @endphp
            @foreach($tabs as $tabKey => $tabLabel)
                <button wire:click="setTypeTab('{{ $tabKey }}')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $selectedTypeTab === $tabKey ? 'shadow' : 'opacity-70 hover:opacity-100' }}"
                    style="{{ $selectedTypeTab === $tabKey ? 'background-color: var(--tenant-accent, #21A19D); color: #ffffff;' : 'background-color: rgba(255,255,255,0.05);' }}">
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>

        <!-- Search Bar -->
        <div class="flex items-center gap-3">
            <div class="relative flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search activities by name or keywords..."
                    class="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-black/20 border transition focus:outline-none"
                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15)); color: var(--tenant-card-text, #ffffff);">
                <svg class="w-4 h-4 absolute left-3 top-2.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>

        <!-- Activities Table / List -->
        @if($activities->isEmpty())
            <div class="py-16 text-center space-y-3">
                <div class="text-4xl">✈️</div>
                <h3 class="text-base font-bold">No Activities Found</h3>
                <p class="text-xs max-w-md mx-auto opacity-70">
                    No activities match your current filter. Click "Create Activity" above to schedule an exciting new flight challenge for your pilots!
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b uppercase tracking-wider text-[10px] font-bold opacity-70"
                            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                            <th class="pb-3 px-3">Activity</th>
                            <th class="pb-3 px-3">Type</th>
                            <th class="pb-3 px-3">Timeline (UTC)</th>
                            <th class="pb-3 px-3 text-center">Registrations</th>
                            <th class="pb-3 px-3 text-center">Completions</th>
                            <th class="pb-3 px-3 text-center">Points</th>
                            <th class="pb-3 px-3 text-center">Status</th>
                            <th class="pb-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.05));">
                        @foreach($activities as $activity)
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="py-3.5 px-3">
                                    <div class="font-bold text-sm flex items-center gap-2">
                                        <a href="{{ route('activities.show', $activity->id) }}" class="hover:underline hover:text-tenant-accent">
                                            {{ $activity->name }}
                                        </a>
                                        @if($activity->isMultiLeg())
                                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-sky-500/20 text-sky-300 font-mono">
                                                {{ $activity->legs_count }} Legs
                                            </span>
                                        @endif
                                    </div>
                                    @if($activity->tags && count($activity->tags))
                                        <div class="flex items-center gap-1 mt-1">
                                            @foreach($activity->tags as $tag)
                                                <span class="text-[9px] px-1.5 py-0.2 rounded bg-white/5 opacity-80">#{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded-full font-bold text-[10px]"
                                        style="background-color: rgba(255,255,255,0.08); color: var(--tenant-accent, #21A19D);">
                                        {{ $activity->type_label }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 font-mono text-[11px]">
                                    <div>{{ $activity->start_at->format('M d, H:i') }}z</div>
                                    <div class="opacity-60">{{ $activity->end_at->format('M d, H:i') }}z</div>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold font-mono">
                                    {{ $activity->registrations_count }}
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold font-mono text-emerald-400">
                                    {{ $activity->completions_count }}
                                </td>
                                <td class="py-3.5 px-3 text-center font-mono font-bold text-amber-300">
                                    +{{ $activity->points_value }} {{ $activity->points_mode === 'percentage' ? '%' : 'pts' }}
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    @php $st = $activity->status; @endphp
                                    @if($st === 'Active')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            Active
                                        </span>
                                    @elseif($st === 'Upcoming')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            Upcoming
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-500/20 text-gray-400 border border-gray-500/30">
                                            {{ $st }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button wire:click="reprocessActivity({{ $activity->id }})" title="Reprocess Progress & Scores"
                                            wire:confirm="Are you sure? This will re-evaluate all matching PIREPs and recalculate pilot progress."
                                            class="p-1.5 rounded-lg bg-black/20 hover:bg-black/40 text-amber-300 transition">
                                            🔄
                                        </button>
                                        <button wire:click="copyActivity({{ $activity->id }})" title="Duplicate Activity"
                                            class="p-1.5 rounded-lg bg-black/20 hover:bg-black/40 text-sky-300 transition">
                                            📋
                                        </button>
                                        <button wire:click="editActivity({{ $activity->id }})" title="Edit Activity"
                                            class="p-1.5 rounded-lg bg-black/20 hover:bg-black/40 text-tenant-accent transition">
                                            ✏️
                                        </button>
                                        <button wire:click="deleteActivity({{ $activity->id }})" title="Delete Activity"
                                            wire:confirm="Permanently delete this activity and its records?"
                                            class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/30 text-red-400 transition">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 border-t" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                {{ $activities->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Activity Modal -->
    @if($showActivityModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="va-card rounded-3xl border shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150"
                style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.15)); color: var(--tenant-card-text, #ffffff);">
                
                <!-- Modal Header -->
                <div class="p-5 border-b flex items-center justify-between"
                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                    <div>
                        <h2 class="text-lg font-black uppercase tracking-wider flex items-center gap-2">
                            <span>🎯</span> {{ $editingActivityId ? 'Edit Activity' : 'Create New Activity' }}
                        </h2>
                        <p class="text-xs opacity-70">
                            Configure event details, multi-leg flight sequences, departure waves, or community goals.
                        </p>
                    </div>
                    <button wire:click="$set('showActivityModal', false)" class="text-xl font-bold opacity-60 hover:opacity-100">&times;</button>
                </div>

                <!-- Modal Sub-Tabs -->
                <div class="flex border-b bg-black/10 px-5 gap-4 text-xs font-bold"
                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                    <button wire:click="$set('activeModalTab', 'general')"
                        class="py-3 border-b-2 transition {{ $activeModalTab === 'general' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent opacity-60 hover:opacity-100' }}">
                        1. General Details
                    </button>
                    @if(in_array($type, ['tour', 'roster', 'curated_roster']))
                        <button wire:click="$set('activeModalTab', 'legs')"
                            class="py-3 border-b-2 transition {{ $activeModalTab === 'legs' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent opacity-60 hover:opacity-100' }}">
                            2. Legs Builder ({{ count($legs) }})
                        </button>
                    @elseif($type === 'slotted_event')
                        <button wire:click="$set('activeModalTab', 'waves')"
                            class="py-3 border-b-2 transition {{ $activeModalTab === 'waves' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent opacity-60 hover:opacity-100' }}">
                            2. Waves & Slots ({{ count($waves) }})
                        </button>
                    @elseif($type === 'community_challenge')
                        <button wire:click="$set('activeModalTab', 'teams')"
                            class="py-3 border-b-2 transition {{ $activeModalTab === 'teams' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent opacity-60 hover:opacity-100' }}">
                            2. Competition Teams
                        </button>
                    @else
                        <button wire:click="$set('activeModalTab', 'criteria')"
                            class="py-3 border-b-2 transition {{ $activeModalTab === 'criteria' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent opacity-60 hover:opacity-100' }}">
                            2. Flight Criteria
                        </button>
                    @endif
                    <button wire:click="$set('activeModalTab', 'restrictions')"
                        class="py-3 border-b-2 transition {{ $activeModalTab === 'restrictions' ? 'border-tenant-accent text-tenant-accent' : 'border-transparent opacity-60 hover:opacity-100' }}">
                        3. Restrictions & Rewards
                    </button>
                </div>

                <!-- Modal Body Scrollable -->
                <div class="p-6 overflow-y-auto space-y-6 flex-1 text-xs">
                    
                    <!-- TAB 1: GENERAL DETAILS -->
                    @if($activeModalTab === 'general')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block font-bold mb-1">Activity Name *</label>
                                <input type="text" wire:model="name" placeholder="e.g. Transatlantic Crossings 2026 or Munich Rush Hour"
                                    class="w-full px-3 py-2 rounded-xl bg-black/20 border transition"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                @error('name') <span class="text-red-400 text-[11px]">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-bold mb-1">Activity Type *</label>
                                <select wire:model.live="type"
                                    class="w-full px-3 py-2 rounded-xl bg-black/20 border transition"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                    <option value="event">Event (Airport/Route Based)</option>
                                    <option value="slotted_event">Slotted Event (Flyout with Waves)</option>
                                    <option value="focus_airport">Focus Airport</option>
                                    <option value="tour">Tour (Sequential Multi-Leg)</option>
                                    <option value="roster">Roster (Airframe Assigned Legs)</option>
                                    <option value="curated_roster">Curated Roster (Flight Centre Rotation)</option>
                                    <option value="community_goal">Community Goal (Shared Target)</option>
                                    <option value="community_challenge">Community Challenge (2-Team Duel)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold mb-1">Subtype</label>
                                <select wire:model="subtype"
                                    class="w-full px-3 py-2 rounded-xl bg-black/20 border transition"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                    <option value="airport_based">Airport-Based (Matches ICAO Pairs)</option>
                                    <option value="route_based">Route-Based (Requires Exact Route ID)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold mb-1">Start Time (UTC) *</label>
                                <input type="datetime-local" wire:model="startAt"
                                    class="w-full px-3 py-2 rounded-xl bg-black/20 border transition"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                @error('startAt') <span class="text-red-400 text-[11px]">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-bold mb-1">End Time (UTC) *</label>
                                <input type="datetime-local" wire:model="endAt"
                                    class="w-full px-3 py-2 rounded-xl bg-black/20 border transition"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                @error('endAt') <span class="text-red-400 text-[11px]">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-bold mb-1">Visible From (UTC - Optional)</label>
                                <input type="datetime-local" wire:model="showFrom"
                                    class="w-full px-3 py-2 rounded-xl bg-black/20 border transition"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                            </div>

                            <div>
                                <label class="block font-bold mb-1">Time Leeway (Hidden Grace Minutes)</label>
                                <input type="number" wire:model="timeLeewayMinutes" min="0" max="120"
                                    class="w-full px-3 py-2 rounded-xl bg-black/20 border transition"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block font-bold mb-1">Tags (Comma-separated for filtering)</label>
                                <input type="text" wire:model="tagsInput" placeholder="vatsim, fly-in, weekend, long-haul"
                                    class="w-full px-3 py-2 rounded-xl bg-black/20 border transition"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block font-bold mb-1">Banner Image URL</label>
                                <input type="url" wire:model="imageUrl" placeholder="https://..."
                                    class="w-full px-3 py-2 rounded-xl bg-black/20 border transition"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block font-bold mb-1">Description</label>
                                <textarea wire:model="description" rows="3" placeholder="Provide full details and instructions for pilots participating in this activity..."
                                    class="w-full px-3 py-2 rounded-xl bg-black/20 border transition"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));"></textarea>
                            </div>
                        </div>

                    <!-- TAB 2: MULTI-LEG BUILDER -->
                    @elseif($activeModalTab === 'legs')
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <p class="text-xs opacity-70">
                                    Configure ordered flight legs. Pilots must complete legs sequentially.
                                </p>
                                <button type="button" wire:click="addLeg"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-tenant-accent text-white hover:opacity-90">
                                    + Add Leg
                                </button>
                            </div>

                            @if(empty($legs))
                                <div class="py-8 text-center border rounded-xl border-dashed opacity-70"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                    No legs defined yet. Click "+ Add Leg" above to add the first segment.
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach($legs as $index => $leg)
                                        <div class="p-4 rounded-xl border bg-black/20 flex flex-wrap items-center gap-3"
                                            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                                            <div class="w-7 h-7 rounded-full bg-tenant-accent/20 text-tenant-accent font-bold font-mono flex items-center justify-center shrink-0">
                                                {{ $index + 1 }}
                                            </div>

                                            <div class="flex-1 min-w-[120px]">
                                                <label class="block text-[10px] font-bold mb-0.5">Dep ICAO *</label>
                                                <input type="text" wire:model="legs.{{ $index }}.dep_icao" placeholder="EGLL" maxlength="4"
                                                    class="w-full px-2.5 py-1.5 rounded-lg bg-black/40 border uppercase font-mono text-xs"
                                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                            </div>

                                            <div class="flex-1 min-w-[120px]">
                                                <label class="block text-[10px] font-bold mb-0.5">Arr ICAO *</label>
                                                <input type="text" wire:model="legs.{{ $index }}.arr_icao" placeholder="KJFK" maxlength="4"
                                                    class="w-full px-2.5 py-1.5 rounded-lg bg-black/40 border uppercase font-mono text-xs"
                                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                            </div>

                                            @if($type === 'roster')
                                                <div class="flex-1 min-w-[150px]">
                                                    <label class="block text-[10px] font-bold mb-0.5">Mandatory Airframe</label>
                                                    <select wire:model="legs.{{ $index }}.airframe_id"
                                                        class="w-full px-2 py-1.5 rounded-lg bg-black/40 border text-xs"
                                                        style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                                        <option value="">Any Airframe</option>
                                                        @foreach($airframes as $af)
                                                            <option value="{{ $af->id }}">{{ $af->registration }} ({{ $af->aircraftType->icao ?? 'Type' }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            @if($type === 'curated_roster')
                                                <div class="flex-1 min-w-[150px]">
                                                    <label class="block text-[10px] font-bold mb-0.5">Aircraft Type</label>
                                                    <select wire:model="legs.{{ $index }}.aircraft_type_id"
                                                        class="w-full px-2 py-1.5 rounded-lg bg-black/40 border text-xs"
                                                        style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                                        <option value="">Any Type</option>
                                                        @foreach($fleetTypes as $ft)
                                                            <option value="{{ $ft->id }}">{{ $ft->icao }} - {{ $ft->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            <div class="flex-1 min-w-[150px]">
                                                <label class="block text-[10px] font-bold mb-0.5">Show From (UTC)</label>
                                                <input type="datetime-local" wire:model="legs.{{ $index }}.show_from"
                                                    class="w-full px-2 py-1 rounded-lg bg-black/40 border text-xs"
                                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                            </div>

                                            <button type="button" wire:click="removeLeg({{ $index }})"
                                                class="text-red-400 hover:text-red-300 font-bold px-2 py-1 rounded bg-red-500/10">
                                                ✕
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    <!-- TAB 2: WAVES & SLOTS BUILDER -->
                    @elseif($activeModalTab === 'waves')
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-4 border-b"
                                style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                <div>
                                    <label class="block font-bold mb-1">Departure Airport ICAO *</label>
                                    <input type="text" wire:model="slottedDepartureIcao" placeholder="EDDF" maxlength="4"
                                        class="w-full px-3 py-2 rounded-xl bg-black/20 border uppercase font-mono"
                                        style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                </div>
                                <div>
                                    <label class="block font-bold mb-1">Callsign System</label>
                                    <select wire:model="slottedCallsignSystem"
                                        class="w-full px-3 py-2 rounded-xl bg-black/20 border"
                                        style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                        <option value="username_a">Username A (Numeric Pilot ID suffix)</option>
                                        <option value="username_b">Username B (2-digit ID + initials)</option>
                                        <option value="generator">Generator Pattern</option>
                                        <option value="manual">Manual Pilot Input</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-sm">Departure Waves</h4>
                                <button type="button" wire:click="addWave"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-tenant-accent text-white hover:opacity-90">
                                    + Add Wave
                                </button>
                            </div>

                            @foreach($waves as $index => $wave)
                                <div class="p-4 rounded-xl border bg-black/20 grid grid-cols-1 sm:grid-cols-4 gap-3 items-center"
                                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                                    <div>
                                        <label class="block text-[10px] font-bold mb-0.5">Wave Name</label>
                                        <input type="text" wire:model="waves.{{ $index }}.name"
                                            class="w-full px-2.5 py-1.5 rounded-lg bg-black/40 border text-xs"
                                            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold mb-0.5">Window (HH:MM to HH:MM)</label>
                                        <div class="flex items-center gap-1">
                                            <input type="text" wire:model="waves.{{ $index }}.start_time" placeholder="12:00" class="w-16 px-2 py-1 rounded bg-black/40 border text-center font-mono text-xs">
                                            <span>-</span>
                                            <input type="text" wire:model="waves.{{ $index }}.end_time" placeholder="16:00" class="w-16 px-2 py-1 rounded bg-black/40 border text-center font-mono text-xs">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold mb-0.5">Interval (Minutes)</label>
                                        <input type="number" wire:model="waves.{{ $index }}.interval_minutes" min="5" max="60"
                                            class="w-full px-2 py-1.5 rounded-lg bg-black/40 border text-xs"
                                            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold mb-0.5">Unlock Rule</label>
                                            <select wire:model="waves.{{ $index }}.unlock_rule" class="px-2 py-1 rounded bg-black/40 border text-[11px]">
                                                <option value="always">Always Open</option>
                                                <option value="previous_wave_full">When Prev Wave Full</option>
                                            </select>
                                        </div>
                                        <button type="button" wire:click="removeWave({{ $index }})" class="text-red-400 font-bold p-1">✕</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    <!-- TAB 2: COMMUNITY TEAMS -->
                    @elseif($activeModalTab === 'teams')
                        <div class="space-y-4">
                            <p class="text-xs opacity-70">
                                Community Challenges pit exactly two teams against each other over the event period.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Team 1 -->
                                <div class="p-5 rounded-2xl border bg-blue-500/10 border-blue-500/30 space-y-3">
                                    <h4 class="font-black text-sm text-blue-300">Team 1 Configuration</h4>
                                    <div>
                                        <label class="block font-bold mb-1">Team Name</label>
                                        <input type="text" wire:model="team1Name" class="w-full px-3 py-2 rounded-xl bg-black/40 border">
                                    </div>
                                    <div>
                                        <label class="block font-bold mb-1">Count Metric</label>
                                        <select wire:model="team1CountType" class="w-full px-3 py-2 rounded-xl bg-black/40 border">
                                            <option value="flights">Flights Count</option>
                                            <option value="passengers">Passengers Transported</option>
                                            <option value="distance">Distance (Nautical Miles)</option>
                                            <option value="flight_time">Flight Time (Hours)</option>
                                            <option value="freight">Cargo Weight (kg)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-bold mb-1">Target Value</label>
                                        <input type="number" wire:model="team1Target" class="w-full px-3 py-2 rounded-xl bg-black/40 border font-mono">
                                    </div>
                                </div>

                                <!-- Team 2 -->
                                <div class="p-5 rounded-2xl border bg-red-500/10 border-red-500/30 space-y-3">
                                    <h4 class="font-black text-sm text-red-300">Team 2 Configuration</h4>
                                    <div>
                                        <label class="block font-bold mb-1">Team Name</label>
                                        <input type="text" wire:model="team2Name" class="w-full px-3 py-2 rounded-xl bg-black/40 border">
                                    </div>
                                    <div>
                                        <label class="block font-bold mb-1">Count Metric</label>
                                        <select wire:model="team2CountType" class="w-full px-3 py-2 rounded-xl bg-black/40 border">
                                            <option value="flights">Flights Count</option>
                                            <option value="passengers">Passengers Transported</option>
                                            <option value="distance">Distance (Nautical Miles)</option>
                                            <option value="flight_time">Flight Time (Hours)</option>
                                            <option value="freight">Cargo Weight (kg)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-bold mb-1">Target Value</label>
                                        <input type="number" wire:model="team2Target" class="w-full px-3 py-2 rounded-xl bg-black/40 border font-mono">
                                    </div>
                                </div>
                            </div>
                        </div>

                    <!-- TAB 2: CRITERIA FOR EVENTS -->
                    @elseif($activeModalTab === 'criteria')
                        <div class="space-y-4">
                            @if($type === 'focus_airport')
                                <div>
                                    <label class="block font-bold mb-1">Focus Airport ICAO *</label>
                                    <input type="text" wire:model="focusAirport" placeholder="e.g. LOWI" maxlength="4"
                                        class="w-full px-3 py-2 rounded-xl bg-black/20 border uppercase font-mono text-sm"
                                        style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                    <p class="text-[11px] opacity-70 mt-1">All departures and arrivals at this airport qualify automatically.</p>
                                </div>
                            @elseif($type === 'community_goal')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-bold mb-1">Target Metric *</label>
                                        <select wire:model="communityMetric" class="w-full px-3 py-2 rounded-xl bg-black/20 border">
                                            <option value="flights">Total Flights</option>
                                            <option value="passengers">Passengers Transported</option>
                                            <option value="distance">Total Distance (nm)</option>
                                            <option value="flight_time">Total Flight Time (hours)</option>
                                            <option value="freight">Cargo Freight (kg)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-bold mb-1">Target Goal Amount *</label>
                                        <input type="number" wire:model="communityTarget" class="w-full px-3 py-2 rounded-xl bg-black/20 border font-mono">
                                    </div>
                                    <div>
                                        <label class="block font-bold mb-1">Goal Completion Points</label>
                                        <input type="number" wire:model="communityCompletionPoints" class="w-full px-3 py-2 rounded-xl bg-black/20 border font-mono">
                                    </div>
                                    <div class="flex items-center gap-2 pt-6">
                                        <input type="checkbox" wire:model="communityTieredMultipliers" id="tm" class="rounded">
                                        <label for="tm" class="font-bold cursor-pointer">Award Tiered Multipliers (Top 10% 3x, Top 25% 2x...)</label>
                                    </div>
                                </div>
                            @else
                                <div class="space-y-4">
                                    <div>
                                        <label class="block font-bold mb-1">Departure Airports (Comma-separated ICAO codes)</label>
                                        <input type="text" wire:model="eventDepartureAirports" placeholder="EGLL, LFPG, EHAM"
                                            class="w-full px-3 py-2 rounded-xl bg-black/20 border uppercase font-mono"
                                            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                    </div>
                                    <div>
                                        <label class="block font-bold mb-1">Arrival Airports (Comma-separated ICAO codes)</label>
                                        <input type="text" wire:model="eventArrivalAirports" placeholder="KJFK, KBOS, KIAD"
                                            class="w-full px-3 py-2 rounded-xl bg-black/20 border uppercase font-mono"
                                            style="border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                    </div>
                                    <div>
                                        <label class="block font-bold mb-1">Operator</label>
                                        <select wire:model="eventOperator" class="w-full px-3 py-2 rounded-xl bg-black/20 border">
                                            <option value="AND">AND (Must match departure AND arrival)</option>
                                            <option value="OR">OR (Matching either departure OR arrival is sufficient)</option>
                                        </select>
                                    </div>
                                </div>
                            @endif
                        </div>

                    <!-- TAB 3: RESTRICTIONS & REWARDS -->
                    @elseif($activeModalTab === 'restrictions')
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-bold mb-1">Points Mode</label>
                                    <select wire:model="pointsMode" class="w-full px-3 py-2 rounded-xl bg-black/20 border">
                                        <option value="fixed">Fixed Points</option>
                                        <option value="percentage">Percentage of Base Score</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-bold mb-1">Points Value *</label>
                                    <input type="number" wire:model="pointsValue" min="0" class="w-full px-3 py-2 rounded-xl bg-black/20 border font-mono">
                                </div>

                                <div>
                                    <label class="block font-bold mb-1">Points Award Mode (Multi-Leg)</label>
                                    <select wire:model="pointAwardMode" class="w-full px-3 py-2 rounded-xl bg-black/20 border">
                                        <option value="per_leg">Per Leg Immediately</option>
                                        <option value="all_on_completion">All Legs on Tour Completion</option>
                                        <option value="final_leg_only">Final Leg Only</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-bold mb-1">Time Award Scale</label>
                                    <select wire:model="timeAwardScale" class="w-full px-3 py-2 rounded-xl bg-black/20 border">
                                        <option value="any_part">Any Part (Takeoff or Landing within window)</option>
                                        <option value="entire_flight">Entire Flight (Takeoff AND Landing)</option>
                                        <option value="takeoff_only">Takeoff Only</option>
                                        <option value="landing_only">Landing Only</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-bold mb-1">Maximum Landing Rate (fpm - Optional)</label>
                                    <input type="number" wire:model="maxLandingRate" placeholder="e.g. -400" class="w-full px-3 py-2 rounded-xl bg-black/20 border font-mono">
                                </div>

                                <div>
                                    <label class="block font-bold mb-1">Callsign Prefix Restrictions</label>
                                    <input type="text" wire:model="restrictedCallsignPrefixes" placeholder="BAW, SHT" class="w-full px-3 py-2 rounded-xl bg-black/20 border uppercase font-mono">
                                </div>

                                <div class="md:col-span-2 flex flex-wrap items-center gap-6 pt-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="registrationRequired" class="rounded">
                                        <span class="font-bold">Registration Required</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="allowRepeat" class="rounded">
                                        <span class="font-bold">Allow Repeat Completions</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="isActive" class="rounded">
                                        <span class="font-bold">Active / Published</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="p-5 border-t flex items-center justify-between bg-black/10"
                    style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                    <button wire:click="$set('showActivityModal', false)"
                        class="px-4 py-2 rounded-xl text-xs font-semibold border transition opacity-80 hover:opacity-100">
                        Cancel
                    </button>

                    <button wire:click="saveActivity"
                        class="px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg transition flex items-center gap-2"
                        style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                        <span>💾</span> {{ $editingActivityId ? 'Save Changes' : 'Publish Activity' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
