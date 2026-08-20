@props([
    'wireModel',
    'selected' => '',
    'label' => '',
    'allLabel' => 'All',
    'placeholder' => 'Search choices...',
    'options' => [],
    'minWidth' => 'min-w-[140px]'
])

@php
$selectedStr = (string)$selected;
$displayLabel = $allLabel;

// Find matching label if options is array of objects/arrays with id and name
if (!empty($selectedStr)) {
    foreach ($options as $key => $opt) {
        if (is_array($opt) || is_object($opt)) {
            $optId = is_array($opt) ? ($opt['id'] ?? $opt['value'] ?? '') : ($opt->id ?? $opt->value ?? '');
            $optName = is_array($opt) ? ($opt['name'] ?? $opt['label'] ?? $optId) : ($opt->name ?? $opt->label ?? $optId);
            if ((string)$optId === $selectedStr) {
                $displayLabel = ($label ? $label . ': ' : '') . $optName;
                break;
            }
        } else {
            if ((string)$opt === $selectedStr || (string)$key === $selectedStr) {
                $displayLabel = ($label ? $label . ': ' : '') . (is_string($key) && !is_numeric($key) ? $key : $opt);
                break;
            }
        }
    }
}
@endphp

<div x-data="{
    open: false,
    query: '',
    selected: @entangle($wireModel).live,
    rawOptions: @js($options),
    labelPrefix: '{{ $label ? $label . ': ' : '' }}',
    allText: '{{ $allLabel }}',
    get filteredOptions() {
        if (!this.query.trim()) {
            return this.rawOptions;
        }
        let q = this.query.toLowerCase();
        if (Array.isArray(this.rawOptions)) {
            return this.rawOptions.filter(item => {
                if (typeof item === 'object' && item !== null) {
                    let name = (item.name || item.label || item.code || item.id || '').toString().toLowerCase();
                    return name.includes(q);
                }
                return item.toString().toLowerCase().includes(q);
            });
        }
        return this.rawOptions;
    },
    select(val) {
        this.selected = val;
        this.open = false;
        this.query = '';
    }
}" class="relative inline-block text-left" @click.outside="open = false">

    <!-- Trigger Button -->
    <button type="button" 
            @click="open = !open" 
            class="{{ $minWidth }} inline-flex items-center justify-between gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold shadow-sm transition border focus:outline-none"
            :class="selected ? 'border-tenant-accent bg-tenant-accent/15 text-tenant-accent font-bold' : 'border-white/10 text-white'"
            style="background-color: var(--tenant-input-bg, #141923); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
        
        <span class="truncate" x-text="selected ? (
            (() => {
                let found = Array.isArray(rawOptions) ? rawOptions.find(o => (typeof o === 'object' && o !== null ? (o.id == selected || o.value == selected) : o == selected)) : null;
                if (found) {
                    return labelPrefix + (typeof found === 'object' ? (found.code || found.name || found.label) : found);
                }
                return labelPrefix + selected;
            })()
        ) : allText">
            {{ $displayLabel }}
        </span>

        <svg class="w-3.5 h-3.5 opacity-60 flex-shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
         class="absolute right-0 mt-1.5 w-64 rounded-xl shadow-2xl z-[120] overflow-hidden p-2 border"
         style="background-color: var(--tenant-card-bg, #141923); border-color: var(--tenant-input-border, rgba(255,255,255,0.15)); display: none;">

        <!-- Search input inside dropdown -->
        <div class="relative mb-2">
            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" 
                   x-model="query" 
                   @click.stop
                   placeholder="{{ $placeholder }}" 
                   class="w-full pl-8 pr-3 py-1.5 rounded-lg text-xs font-medium focus:ring-1 focus:ring-tenant-accent transition"
                   style="background-color: var(--tenant-input-bg, #0a0d14); color: var(--tenant-input-text, #ffffff); border: 1px solid var(--tenant-input-border, rgba(255,255,255,0.2));" />
        </div>

        <!-- Options List -->
        <div class="max-h-56 overflow-y-auto space-y-0.5 custom-scrollbar pr-0.5">
            <!-- Reset / All Option -->
            <button type="button" 
                    @click="select('')"
                    class="w-full text-left px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center justify-between"
                    :class="!selected ? 'bg-tenant-accent text-white font-bold' : 'hover:bg-white/10 text-slate-300 hover:text-white'">
                <span x-text="allText">{{ $allLabel }}</span>
                <span x-show="!selected" class="text-xs">✓</span>
            </button>

            <!-- Dynamic Options -->
            <template x-for="(item, idx) in filteredOptions" :key="typeof item === 'object' && item !== null ? (item.id || item.value || idx) : item">
                <button type="button" 
                        @click="select(typeof item === 'object' && item !== null ? (item.id !== undefined ? item.id : item.value) : item)"
                        class="w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center justify-between"
                        :class="(selected == (typeof item === 'object' && item !== null ? (item.id !== undefined ? item.id : item.value) : item)) 
                                ? 'bg-tenant-accent text-white font-bold' 
                                : 'hover:bg-white/10 text-slate-300 hover:text-white'">
                    
                    <span class="truncate" x-text="typeof item === 'object' && item !== null ? (item.code ? (item.code + (item.name ? ' - ' + item.name : '')) : (item.name || item.label)) : item"></span>
                    <span x-show="selected == (typeof item === 'object' && item !== null ? (item.id !== undefined ? item.id : item.value) : item)" class="text-xs ml-1.5 flex-shrink-0">✓</span>
                </button>
            </template>

            <div x-show="filteredOptions.length === 0" class="px-3 py-4 text-xs text-center text-slate-400">
                No matching choices found
            </div>
        </div>
    </div>
</div>
