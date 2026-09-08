<div class="space-y-8">
    <!-- Top Information Callout Banner (Recognition, Not Restriction) -->
    <div class="p-4 rounded-xl border flex items-start gap-3.5 shadow-sm"
        style="background-color: rgba(33, 161, 157, 0.08); border-color: rgba(33, 161, 157, 0.3);">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
            style="background-color: rgba(33, 161, 157, 0.2); color: #21A19D;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-bold text-white flex items-center gap-2">
                <span>Recognition, Not Restriction</span>
                <span class="text-[10px] px-2 py-0.5 rounded font-mono font-bold bg-[#21A19D]/20 text-[#21A19D] border border-[#21A19D]/30 uppercase">V-Air Ops Standard</span>
            </h4>
            <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                In V-Air Ops, ranks are purely for recognition and pilot progression. They cannot restrict access to routes, aircraft, or airports. Pilots have full access to your entire route network from the moment they join.
            </p>
        </div>
    </div>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b"
        style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
        <div>
            <h3 class="text-lg font-bold flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                <span>🎖️</span> Pilot Ranks &amp; Progression System
            </h3>
            <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
                Configure automatic regular progression ranks, honorary staff titles, and official epaulette insignia.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" wire:click="openModal(true)"
                class="px-3.5 py-2 rounded-lg text-xs font-semibold transition border flex items-center gap-1.5"
                style="background-color: var(--tenant-button-secondary-bg, #1E293B); color: var(--tenant-button-secondary-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Honorary Rank
            </button>
            <button type="button" wire:click="openModal(false)"
                class="px-4 py-2 rounded-lg text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-1.5"
                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Regular Rank
            </button>
        </div>
    </div>

    @if (session()->has('rank_message'))
        <div class="bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 px-4 py-3 rounded-xl flex items-center justify-between text-xs font-semibold" role="alert">
            <span>{{ session('rank_message') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-300">✕</button>
        </div>
    @endif

    @if (session()->has('rank_error'))
        <div class="bg-rose-500/20 border border-rose-500/40 text-rose-200 px-4 py-3 rounded-xl flex items-center justify-between text-xs font-semibold" role="alert">
            <span>{{ session('rank_error') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-300">✕</button>
        </div>
    @endif

    <!-- 1. REGULAR RANKS SECTION -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-bold tracking-wide uppercase flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                <span>📈</span> Regular Progression Ranks
                <span class="text-xs px-2 py-0.5 rounded-full font-mono font-semibold" style="background-color: var(--tenant-input-bg, #1E293B); color: var(--tenant-card-muted, #94a3b8);">
                    {{ $regularRanks->count() }}
                </span>
            </h4>
            <span class="text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                Pilots qualify automatically when meeting all milestone criteria
            </span>
        </div>

        <div class="rounded-xl border overflow-hidden shadow-sm"
            style="background-color: var(--tenant-card-bg, #141A24); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/5 text-left text-xs">
                    <thead style="background-color: var(--tenant-input-bg, #18202F); color: var(--tenant-card-muted, #94a3b8);">
                        <tr>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider w-16 text-center">Order</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider w-28">Epaulette</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider">Rank Name</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider w-20">Abbr</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider text-right">Hours</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider text-right">Points</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider text-right">Bonus Pts</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider text-right">PIREPs</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider text-right w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5" style="color: var(--tenant-card-text, #ffffff);">
                        @forelse($regularRanks as $index => $rank)
                            <tr class="hover:bg-white/5 transition">
                                <!-- Order & Reorder Buttons -->
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1 font-mono font-bold text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                                        <button type="button" wire:click="moveRankUp({{ $rank->id }})" class="p-1 hover:text-white transition disabled:opacity-30" title="Move Up" {{ $loop->first ? 'disabled' : '' }}>▲</button>
                                        <span>{{ $rank->position }}</span>
                                        <button type="button" wire:click="moveRankDown({{ $rank->id }})" class="p-1 hover:text-white transition disabled:opacity-30" title="Move Down" {{ $loop->last ? 'disabled' : '' }}>▼</button>
                                    </div>
                                </td>
                                <!-- Epaulette Image (85x36 standard) -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <img src="{{ $rank->image_url }}" alt="{{ $rank->name }}" class="h-8 w-auto rounded object-contain border border-black/40 shadow-sm" style="max-width: 85px;">
                                </td>
                                <!-- Name -->
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-white text-sm">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $rank->name }}</span>
                                        @if($rank->is_default)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded font-mono font-normal bg-sky-500/20 text-sky-300 border border-sky-500/30">Default</span>
                                        @endif
                                    </div>
                                </td>
                                <!-- Abbr -->
                                <td class="px-4 py-3 whitespace-nowrap font-mono font-bold text-tenant-accent">
                                    {{ $rank->abbreviation ?: '—' }}
                                </td>
                                <!-- Hours -->
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-right text-slate-200">
                                    {{ number_format($rank->min_hours) }}h
                                </td>
                                <!-- Points -->
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-right text-amber-300 font-semibold">
                                    {{ number_format($rank->min_points) }}
                                </td>
                                <!-- Bonus Points -->
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-right text-purple-300">
                                    {{ number_format($rank->min_bonus_points) }}
                                </td>
                                <!-- PIREPs -->
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-right text-emerald-400">
                                    {{ number_format($rank->min_pireps) }}
                                </td>
                                <!-- Actions -->
                                <td class="px-4 py-3 whitespace-nowrap text-right space-x-2">
                                    <button type="button" wire:click="editRank({{ $rank->id }})"
                                        class="text-xs font-bold text-tenant-accent hover:opacity-80 transition">
                                        Edit
                                    </button>
                                    @if(!$rank->is_default)
                                        <button type="button" wire:click="deleteRank({{ $rank->id }})" wire:confirm="Are you sure you want to delete rank '{{ $rank->name }}'?"
                                            class="text-xs font-bold text-rose-400 hover:text-rose-300 transition">
                                            Delete
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-600 cursor-not-allowed" title="Default ranks cannot be deleted">Locked</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center italic" style="color: var(--tenant-card-muted, #64748b);">
                                    No regular ranks configured. Click "New Regular Rank" above to set up pilot progression.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. HONORARY RANKS SECTION -->
    <div class="space-y-3 pt-4 border-t" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-bold tracking-wide uppercase flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                <span>🎖️</span> Honorary Ranks (Special Recognition)
                <span class="text-xs px-2 py-0.5 rounded-full font-mono font-semibold" style="background-color: var(--tenant-input-bg, #1E293B); color: var(--tenant-card-muted, #94a3b8);">
                    {{ $honoraryRanks->count() }}
                </span>
            </h4>
            <span class="text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                Assigned manually by staff to individual pilots (Staff, Instructors, Contest Winners)
            </span>
        </div>

        <div class="rounded-xl border overflow-hidden shadow-sm"
            style="background-color: var(--tenant-card-bg, #141A24); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/5 text-left text-xs">
                    <thead style="background-color: var(--tenant-input-bg, #18202F); color: var(--tenant-card-muted, #94a3b8);">
                        <tr>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider w-28">Epaulette</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider">Honorary Title</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider w-20">Abbr</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider">Purpose / Type</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider text-right">Pilots Assigned</th>
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider text-right w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5" style="color: var(--tenant-card-text, #ffffff);">
                        @forelse($honoraryRanks as $rank)
                            <tr class="hover:bg-white/5 transition">
                                <!-- Epaulette Image -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <img src="{{ $rank->image_url }}" alt="{{ $rank->name }}" class="h-8 w-auto rounded object-contain border border-black/40 shadow-sm" style="max-width: 85px;">
                                </td>
                                <!-- Name -->
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-white text-sm">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $rank->name }}</span>
                                        @if($rank->is_default)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded font-mono font-normal bg-purple-500/20 text-purple-300 border border-purple-500/30">Default Staff</span>
                                        @endif
                                    </div>
                                </td>
                                <!-- Abbr -->
                                <td class="px-4 py-3 whitespace-nowrap font-mono font-bold text-purple-400">
                                    {{ $rank->abbreviation ?: '—' }}
                                </td>
                                <!-- Purpose / Type -->
                                <td class="px-4 py-3 whitespace-nowrap text-slate-300">
                                    Manual Recognition (No requirements)
                                </td>
                                <!-- Pilots Count -->
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-right font-bold text-slate-200">
                                    {{ $rank->honoraryPilotProfiles()->count() }}
                                </td>
                                <!-- Actions -->
                                <td class="px-4 py-3 whitespace-nowrap text-right space-x-2">
                                    <button type="button" wire:click="editRank({{ $rank->id }})"
                                        class="text-xs font-bold text-tenant-accent hover:opacity-80 transition">
                                        Edit
                                    </button>
                                    @if(!$rank->is_default)
                                        <button type="button" wire:click="deleteRank({{ $rank->id }})" wire:confirm="Are you sure you want to delete honorary rank '{{ $rank->name }}'?"
                                            class="text-xs font-bold text-rose-400 hover:text-rose-300 transition">
                                            Delete
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-600 cursor-not-allowed" title="Default ranks cannot be deleted">Locked</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center italic" style="color: var(--tenant-card-muted, #64748b);">
                                    No honorary ranks created. Click "New Honorary Rank" to add special staff or competition titles.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Rank Create / Edit Modal -->
    <x-dialog-modal wire:model.live="showModal" maxWidth="2xl">
        <x-slot name="title">
            <div class="flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                <span>🎖️</span>
                <span>{{ $editingRankId ? __('Edit Rank') : ($is_honorary ? __('Create Honorary Rank') : __('Create Regular Rank')) }}</span>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <!-- Rank Type Toggle (Only editable on creation or non-default) -->
                <div class="p-3.5 rounded-xl border flex items-center justify-between"
                    style="background-color: var(--tenant-input-bg, #1E293B); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    <div>
                        <span class="text-xs font-bold text-white block">Honorary Rank</span>
                        <span class="text-[11px] block" style="color: var(--tenant-card-muted, #94a3b8);">
                            Honorary ranks are assigned manually to individual pilots with no milestone requirements.
                        </span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="is_honorary" class="sr-only peer" {{ $is_default ? 'disabled' : '' }}>
                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <!-- Basic Information: Name, Abbr, Position -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold block mb-1 text-white">Rank Full Name <span class="text-rose-400">*</span></label>
                        <x-input type="text" class="w-full text-xs" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                            wire:model="name" placeholder="e.g. First Officer, Senior Captain, Flight Instructor" />
                        <x-input-error for="name" class="mt-1" />
                    </div>

                    <div>
                        <label class="text-xs font-semibold block mb-1 text-white">Abbreviation</label>
                        <x-input type="text" maxlength="10" class="w-full text-xs uppercase font-mono font-bold" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                            wire:model="abbreviation" placeholder="e.g. FO, CPT" />
                        <x-input-error for="abbreviation" class="mt-1" />
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold block mb-1 text-white">Position / Order</label>
                    <x-input type="number" min="1" max="9999" class="w-full text-xs font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                        wire:model="position" />
                    <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Controls order from easiest to hardest. Lower numbers appear first.</p>
                    <x-input-error for="position" class="mt-1" />
                </div>

                <!-- Milestone Requirements (Only for Regular Ranks) -->
                @if(!$is_honorary)
                    <div class="p-4 rounded-xl border space-y-3"
                        style="background-color: var(--tenant-card-bg, #141A24); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        <div>
                            <h5 class="text-xs font-bold text-white uppercase tracking-wider">Milestone Requirements</h5>
                            <p class="text-[11px]" style="color: var(--tenant-card-muted, #94a3b8);">Pilot must satisfy all 4 criteria to earn this rank automatically.</p>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="text-[11px] block mb-1 text-slate-300">Min Hours</label>
                                <x-input type="number" min="0" class="w-full text-xs font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                                    wire:model="min_hours" />
                                <x-input-error for="min_hours" class="mt-1" />
                            </div>

                            <div>
                                <label class="text-[11px] block mb-1 text-slate-300">Min Points</label>
                                <x-input type="number" min="0" class="w-full text-xs font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                                    wire:model="min_points" />
                                <x-input-error for="min_points" class="mt-1" />
                            </div>

                            <div>
                                <label class="text-[11px] block mb-1 text-slate-300">Min Bonus Pts</label>
                                <x-input type="number" min="0" class="w-full text-xs font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                                    wire:model="min_bonus_points" />
                                <x-input-error for="min_bonus_points" class="mt-1" />
                            </div>

                            <div>
                                <label class="text-[11px] block mb-1 text-slate-300">Min PIREPs</label>
                                <x-input type="number" min="0" class="w-full text-xs font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                                    wire:model="min_pireps" />
                                <x-input-error for="min_pireps" class="mt-1" />
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Epaulette Insignia Selection (Built-in or Upload) -->
                <div class="p-4 rounded-xl border space-y-3"
                    style="background-color: var(--tenant-card-bg, #141A24); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="text-xs font-bold text-white uppercase tracking-wider">Epaulette Insignia Image</h5>
                            <p class="text-[11px]" style="color: var(--tenant-card-muted, #94a3b8);">Standard size: 85×36 pixels.</p>
                        </div>
                    </div>

                    <!-- Built-in Gallery Options -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        @foreach($builtinEpaulettes as $ep)
                            <label class="p-2 rounded-lg border cursor-pointer flex flex-col items-center gap-1.5 transition text-center {{ $selected_builtin_epaulette === $ep['id'] ? 'border-tenant-accent bg-tenant-accent/10 ring-1 ring-tenant-accent' : 'border-white/10 bg-black/20 hover:border-white/20' }}">
                                <input type="radio" value="{{ $ep['id'] }}" wire:model.live="selected_builtin_epaulette" class="sr-only">
                                <img src="{{ $ep['preview'] }}" alt="{{ $ep['name'] }}" class="h-6 w-auto object-contain">
                                <span class="text-[10px] text-slate-300 leading-tight block">{{ $ep['name'] }}</span>
                            </label>
                        @endforeach
                    </div>

                    <!-- Custom Epaulette File Upload -->
                    <div class="pt-2 border-t border-white/10">
                        <label class="text-xs font-semibold block mb-1 text-slate-300">Or Upload Custom Epaulette Image (85×36 px PNG/GIF/SVG)</label>
                        <input type="file" wire:model="epaulette_upload" accept="image/*" class="block w-full text-xs text-slate-300 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-tenant-accent file:text-white hover:file:opacity-90 cursor-pointer">
                        <x-input-error for="epaulette_upload" class="mt-1" />
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled" class="border-none"
                style="background-color: var(--tenant-button-secondary-bg, #374151); color: var(--tenant-button-secondary-text, #ffffff);">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button type="button" wire:click="saveRank" wire:loading.attr="disabled"
                class="ml-3 px-5 py-2 rounded-lg text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-1.5"
                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ $editingRankId ? __('Save Changes') : __('Create Rank') }}</span>
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
