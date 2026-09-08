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

                        <!-- Callsign to Flight Number Mappings -->
                        <div class="p-4 bg-[#141a24] rounded-xl border border-white/10 space-y-3">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-white font-bold text-sm flex items-center gap-2">
                                        <span>ATC Callsign &rarr; Commercial Flight Number Mappings</span>
                                        <span class="text-[10px] uppercase tracking-wider bg-blue-500/20 text-blue-300 px-2 py-0.5 rounded border border-blue-500/30 font-mono font-bold">Global Import Rule</span>
                                    </h4>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Configure automatic conversion rules for global imports. When a live schedule callsign matches a prefix (e.g. <strong class="text-amber-300 font-mono">EZY</strong>), it is converted to the commercial flight number prefix (e.g. <strong class="text-tenant-accent font-mono">U2</strong>): <span class="text-slate-300 font-mono font-bold">EZY8412 &rarr; U28412</span>.
                                    </p>
                                </div>
                            </div>

                            @if(!empty($callsign_mappings))
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 pt-1">
                                    @foreach($callsign_mappings as $index => $mapping)
                                        <div class="flex items-center justify-between p-2.5 bg-black/40 rounded-xl border border-white/10 text-xs font-mono">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-amber-300 bg-amber-400/10 px-2 py-1 rounded border border-amber-400/20">{{ $mapping['callsign_prefix'] }}</span>
                                                <span class="text-gray-500 font-bold">&rarr;</span>
                                                <span class="font-bold text-tenant-accent bg-tenant-accent/10 px-2 py-1 rounded border border-tenant-accent/20">{{ $mapping['flight_number_prefix'] }}</span>
                                            </div>
                                            <button type="button" wire:click="removeCallsignMapping({{ $index }})" class="text-red-400 hover:text-red-300 transition p-1" title="Remove Mapping">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-500 italic py-1">No custom callsign mappings defined. Standard airline default ICAO &rarr; IATA mappings (e.g. EZY&rarr;U2, BAW&rarr;BA, KLM&rarr;KL) will be used automatically.</p>
                            @endif

                            <!-- Add New Mapping Form -->
                            <div class="pt-2 border-t border-white/5">
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="w-36">
                                        <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Callsign Starts With</label>
                                        <x-input type="text" wire:model="newCallsignPrefix" maxlength="5" class="w-full bg-[#1e2532] border-gray-700 text-white uppercase text-xs font-mono font-bold" placeholder="e.g. EZY" />
                                    </div>
                                    <div class="pt-5 text-gray-500 font-bold">&rarr;</div>
                                    <div class="w-36">
                                        <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Flight # Prefix</label>
                                        <x-input type="text" wire:model="newFlightNumberPrefix" maxlength="5" class="w-full bg-[#1e2532] border-gray-700 text-white uppercase text-xs font-mono font-bold" placeholder="e.g. U2" />
                                    </div>
                                    <div class="pt-4">
                                        <button type="button" wire:click="addCallsignMapping" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-tenant-accent border border-tenant-accent/30 rounded-xl text-xs font-bold transition whitespace-nowrap shadow-sm">
                                            + Add Mapping Rule
                                        </button>
                                    </div>
                                </div>
                                <div class="flex gap-4 mt-1">
                                    <x-input-error for="newCallsignPrefix" class="text-red-400 text-xs" />
                                    <x-input-error for="newFlightNumberPrefix" class="text-red-400 text-xs" />
                                </div>
                            </div>
                        </div>

                        <!-- Base Theme Settings -->

                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 md:col-span-3">
                                <x-label for="accent_color" value="{{ __('Primary Accent Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="accent_color_picker" type="color" wire:model.live="accent_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="accent_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="accent_color" placeholder="#f97316" />
                                </div>
                                <x-input-error for="accent_color" class="mt-2" />
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="bg_color" value="{{ __('Main Page Background Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="bg_color_picker" type="color" wire:model.live="bg_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="bg_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="bg_color" placeholder="#0f1117" />
                                </div>
                                <x-input-error for="bg_color" class="mt-2" />
                                <p class="text-xs text-gray-400 mt-1">Controls the base body background of the airline portal.</p>
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="panel_bg_color" value="{{ __('Master Outer Card / Panel Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="panel_bg_color_picker" type="color" wire:model.live="panel_bg_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="panel_bg_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="panel_bg_color" placeholder="#f97316" />
                                </div>
                                <x-input-error for="panel_bg_color" class="mt-2" />
                                <p class="text-xs text-gray-400 mt-1">Controls the outer master background card panel behind all page elements.</p>
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="panel_text_color" value="{{ __('Master Outer Panel Text / Title Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="panel_text_color_picker" type="color" wire:model.live="panel_text_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="panel_text_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="panel_text_color" placeholder="#ffffff" />
                                </div>
                                <x-input-error for="panel_text_color" class="mt-2" />
                                <p class="text-xs text-gray-400 mt-1">Color of headings and text on the master outer background card.</p>
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="card_bg_color" value="{{ __('Inner Cards & Table Background Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="card_bg_color_picker" type="color" wire:model.live="card_bg_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="card_bg_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="card_bg_color" placeholder="#0f1117" />
                                </div>
                                <x-input-error for="card_bg_color" class="mt-2" />
                                <p class="text-xs text-gray-400 mt-1">Controls the background of inner cards, table headers, and form panels.</p>
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="card_text_color" value="{{ __('Inner Cards & Table Primary Text Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="card_text_color_picker" type="color" wire:model.live="card_text_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="card_text_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="card_text_color" placeholder="#ffffff" />
                                </div>
                                <x-input-error for="card_text_color" class="mt-2" />
                                <p class="text-xs text-gray-400 mt-1">Main text, pilot names, callsigns, and data inside cards and tables.</p>
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="card_muted_text_color" value="{{ __('Inner Cards & Table Muted / Subtitle Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="card_muted_text_color_picker" type="color" wire:model.live="card_muted_text_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="card_muted_text_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="card_muted_text_color" placeholder="#94a3b8" />
                                </div>
                                <x-input-error for="card_muted_text_color" class="mt-2" />
                                <p class="text-xs text-gray-400 mt-1">Table column headers, timestamps, subtitled metadata, and labels.</p>
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="button_bg_color" value="{{ __('Primary Button Background Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="button_bg_color_picker" type="color" wire:model.live="button_bg_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="button_bg_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="button_bg_color" placeholder="#f97316" />
                                </div>
                                <x-input-error for="button_bg_color" class="mt-2" />
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="button_text_color" value="{{ __('Primary Button Text Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="button_text_color_picker" type="color" wire:model.live="button_text_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="button_text_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="button_text_color" placeholder="#ffffff" />
                                </div>
                                <x-input-error for="button_text_color" class="mt-2" />
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="button_secondary_bg_color" value="{{ __('Secondary Button Background Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="button_secondary_bg_color_picker" type="color" wire:model.live="button_secondary_bg_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="button_secondary_bg_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="button_secondary_bg_color" placeholder="#1f2937" />
                                </div>
                                <x-input-error for="button_secondary_bg_color" class="mt-2" />
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="button_secondary_text_color" value="{{ __('Secondary Button Text Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="button_secondary_text_color_picker" type="color" wire:model.live="button_secondary_text_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="button_secondary_text_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="button_secondary_text_color" placeholder="#f3f4f6" />
                                </div>
                                <x-input-error for="button_secondary_text_color" class="mt-2" />
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="input_bg_color" value="{{ __('Text Field / Input Background Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="input_bg_color_picker" type="color" wire:model.live="input_bg_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="input_bg_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="input_bg_color" placeholder="#0a0d14" />
                                </div>
                                <x-input-error for="input_bg_color" class="mt-2" />
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="input_text_color" value="{{ __('Text Field / Input Text Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="input_text_color_picker" type="color" wire:model.live="input_text_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="input_text_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="input_text_color" placeholder="#ffffff" />
                                </div>
                                <x-input-error for="input_text_color" class="mt-2" />
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <x-label for="input_border_color" value="{{ __('Text Field / Input Border Color') }}" class="text-white" />
                                <div class="flex items-center space-x-3 mt-1">
                                    <input id="input_border_color_picker" type="color" wire:model.live="input_border_color" class="h-10 w-10 border-0 p-0 rounded cursor-pointer" />
                                    <x-input id="input_border_color" type="text" class="flex-1 block w-full bg-black/40 border border-white/10 text-white font-mono" wire:model.live="input_border_color" placeholder="#374151" />
                                </div>
                                <x-input-error for="input_border_color" class="mt-2" />
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

            <!-- Roles & Permissions Tab -->
            @if($activeTab === 'roles')
                <div>
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-bold flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                                <span>🛡️</span> Roles &amp; Permissions Management
                            </h3>
                            <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
                                Create custom staff and pilot roles with granular read or read-write permissions scoped strictly to this virtual airline.
                            </p>
                        </div>
                        <button wire:click="openCreateRoleModal" class="bg-tenant-accent text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md hover:opacity-90 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Create Role
                        </button>
                    </div>

                    @if (session()->has('role_message'))
                        <div class="mb-6 bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 px-4 py-3 rounded-xl flex items-center justify-between" role="alert">
                            <span class="text-sm font-medium">{{ session('role_message') }}</span>
                            <span class="text-emerald-400 text-lg">✓</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse($roles as $role)
                            <div class="va-card p-5 rounded-2xl border transition-all shadow-lg flex flex-col justify-between"
                                style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-text, #ffffff);">
                                <div>
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div>
                                            <h4 class="text-base font-bold flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                                                {{ $role->name }}
                                                @if($role->is_default)
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">Default</span>
                                                @endif
                                            </h4>
                                            <p class="text-xs font-mono mt-0.5" style="color: var(--tenant-card-muted, #94a3b8);">{{ $role->slug }}</p>
                                        </div>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $role->is_staff ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-gray-700/50 text-gray-300 border border-white/10' }}">
                                            {{ $role->is_staff ? 'Staff Role' : 'Pilot Role' }}
                                        </span>
                                    </div>

                                    @if($role->description)
                                        <p class="text-xs mb-3" style="color: var(--tenant-card-muted, #94a3b8);">{{ $role->description }}</p>
                                    @endif

                                    <!-- Honorary Rank Info -->
                                    <div class="p-2.5 rounded-xl border mb-3 text-xs" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.3)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                        <span style="color: var(--tenant-card-muted, #94a3b8);">Honorary Staff Rank:</span>
                                        @if($role->honorary_rank_string)
                                            <span class="font-bold text-tenant-accent ml-1 font-mono">⭐ {{ $role->honorary_rank_string }}</span>
                                        @else
                                            <span class="ml-1 italic" style="color: var(--tenant-card-muted, #64748b);">None (Uses standard flight-hour rank)</span>
                                        @endif
                                    </div>

                                    <!-- Permissions Summary -->
                                    <div class="mb-4">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider mb-1.5" style="color: var(--tenant-card-muted, #94a3b8);">Permissions Granted ({{ $role->permissions->count() }}):</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse($role->permissions->take(6) as $perm)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-mono border" style="background-color: var(--tenant-input-bg, rgba(255,255,255,0.05)); color: var(--tenant-card-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                                    {{ $perm->slug }}
                                                </span>
                                            @empty
                                                <span class="text-xs italic" style="color: var(--tenant-card-muted, #64748b);">No permissions assigned</span>
                                            @endforelse
                                            @if($role->permissions->count() > 6)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-tenant-accent/20 text-tenant-accent border border-tenant-accent/30">
                                                    +{{ $role->permissions->count() - 6 }} more
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-3 border-t flex items-center justify-between" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                    <span class="text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                                        <strong>{{ $role->users->count() }}</strong> assigned {{ Str::plural('pilot', $role->users->count()) }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <button wire:click="editRole({{ $role->id }})" class="px-3 py-1 rounded-lg text-xs font-semibold transition border hover:opacity-80"
                                            style="background-color: var(--tenant-button-secondary-bg, rgba(255,255,255,0.1)); color: var(--tenant-button-secondary-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                            Edit Role
                                        </button>
                                        <button wire:click="deleteRole({{ $role->id }})" 
                                                wire:confirm="Are you sure you want to delete role '{{ $role->name }}'? Pilots assigned this role will lose its permissions." 
                                                class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-600/20 hover:bg-red-600/40 text-red-300 transition">
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full py-12 text-center rounded-2xl border" style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                                <span class="text-4xl">🛡️</span>
                                <h4 class="font-bold text-base mt-2" style="color: var(--tenant-card-text, #ffffff);">No Custom Roles Created Yet</h4>
                                <p class="text-xs mt-1 max-w-md mx-auto" style="color: var(--tenant-card-muted, #94a3b8);">Create custom roles like "Route Manager", "Fleet Director", or "Chief Pilot" with granular read or read-write permissions.</p>
                                <button wire:click="openCreateRoleModal" class="mt-4 bg-tenant-accent text-white px-4 py-2 rounded-lg text-xs font-bold shadow-md hover:opacity-90 transition">
                                    + Create First Role
                                </button>
                            </div>
                        @endforelse
                    </div>

                    <!-- Airline Custom Permissions Management -->
                    <div class="mt-10 pt-8 border-t" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5">
                            <div>
                                <h4 class="text-base font-bold flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                                    <span>🔑</span> Virtual Airline Custom Permissions
                                </h4>
                                <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
                                    Create custom permissions tailored to your airline (e.g. Discord bot operations, livery approval, exam proctoring) and assign them to roles.
                                </p>
                            </div>
                            <button type="button" wire:click="openCreatePermissionModal" class="px-4 py-2 rounded-lg text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-1.5"
                                style="background-color: var(--tenant-button-secondary-bg, #374151); color: var(--tenant-button-secondary-text, #ffffff); border: 1px solid var(--tenant-input-border, rgba(255,255,255,0.15));">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Create Custom Permission
                            </button>
                        </div>

                        @if (session()->has('permission_message'))
                            <div class="mb-5 bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 px-4 py-3 rounded-xl flex items-center justify-between" role="alert">
                                <span class="text-sm font-medium">{{ session('permission_message') }}</span>
                                <span class="text-emerald-400 text-lg">✓</span>
                            </div>
                        @endif

                        @if($customPermissions->isNotEmpty())
                            <div class="rounded-xl border overflow-hidden" style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                <table class="min-w-full divide-y" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                    <thead style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.3));">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">Permission Name</th>
                                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">Slug / Key</th>
                                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">Group</th>
                                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">Roles Assigned</th>
                                            <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.05));">
                                        @foreach($customPermissions as $cp)
                                            <tr>
                                                <td class="px-5 py-3 text-xs">
                                                    <span class="font-bold block" style="color: var(--tenant-card-text, #ffffff);">{{ $cp->name }}</span>
                                                    @if($cp->description)
                                                        <span class="text-[11px]" style="color: var(--tenant-card-muted, #94a3b8);">{{ $cp->description }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-3 text-xs font-mono" style="color: var(--tenant-accent);">
                                                    {{ $cp->slug }}
                                                </td>
                                                <td class="px-5 py-3 text-xs">
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-medium" style="background-color: var(--tenant-input-bg, rgba(255,255,255,0.05)); color: var(--tenant-card-text, #ffffff);">
                                                        {{ $cp->group }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-3 text-xs" style="color: var(--tenant-card-muted, #94a3b8);">
                                                    {{ $cp->roles_count }} {{ Str::plural('role', $cp->roles_count) }}
                                                </td>
                                                <td class="px-5 py-3 text-right text-xs space-x-2">
                                                    <button type="button" wire:click="editCustomPermission({{ $cp->id }})" class="hover:underline font-semibold text-sky-400">
                                                        Edit
                                                    </button>
                                                    <button type="button" wire:click="deleteCustomPermission({{ $cp->id }})" wire:confirm="Are you sure you want to delete this custom permission? It will be unassigned from all roles." class="hover:underline font-semibold text-red-400">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-6 text-center rounded-xl border border-dashed text-xs" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1)); color: var(--tenant-card-muted, #94a3b8);">
                                No custom permissions created yet. You can create custom permissions to control special airline operations or internal tools.
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- User Management Tab -->
            @if($activeTab === 'users')
                <div>
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-bold flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                                <span>👥</span> User &amp; Staff Role Management
                            </h3>
                            <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
                                Manage pilots enrolled in this airline, assign custom airline roles, and view calculated ranks.
                            </p>
                        </div>
                        <button wire:click="openUserModal" class="bg-tenant-accent text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm hover:opacity-90 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add User
                        </button>
                    </div>

                    @if (session()->has('user_message'))
                        <div class="mb-6 bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 px-4 py-3 rounded-xl flex items-center justify-between" role="alert">
                            <span class="text-sm font-medium">{{ session('user_message') }}</span>
                            <span class="text-emerald-400 text-lg">✓</span>
                        </div>
                    @endif

                    <div class="overflow-x-auto -mx-6">
                        <table class="min-w-full divide-y" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                            <thead style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">Pilot</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">Callsign</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">Active Display Rank</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">Assigned Airline Roles</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider" style="color: var(--tenant-card-muted, #94a3b8);">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.05));">
                                @forelse($users as $user)
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $user->full_name }}</div>
                                        <div class="text-xs font-mono" style="color: var(--tenant-card-muted, #94a3b8);">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30">
                                            {{ $user->activeCallsign() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            @if($user->getDisplayRankImageUrl())
                                                <img src="{{ $user->getDisplayRankImageUrl() }}" alt="{{ $user->getDisplayRank() }}" class="w-[70px] h-[30px] object-contain rounded border border-white/10 bg-slate-900/60 shadow-sm" />
                                            @endif
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-tenant-accent/20 text-tenant-accent border border-tenant-accent/30">
                                                        {{ $user->getDisplayRank() }}
                                                    </span>
                                                    @if($user->isDisplayingHonoraryRank())
                                                        <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30" title="Displaying honorary staff rank">Honorary</span>
                                                    @endif
                                                </div>
                                                @php
                                                    $profile = $user->getPilotProfile();
                                                @endphp
                                                @if($profile && $profile->rank && $profile->honoraryRank)
                                                    <div class="text-[10px] text-slate-400 mt-1">
                                                        Regular: {{ $profile->rank->name }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-normal">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            @php
                                                $assignedRoles = $user->getRolesForAirline();
                                            @endphp
                                            @forelse($assignedRoles as $r)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                                    <span>{{ $r->name }}</span>
                                                    <button wire:click="removeQuickRole({{ $user->id }}, {{ $r->id }})" class="hover:text-red-400 text-[10px] ml-0.5" title="Remove role">✕</button>
                                                </span>
                                            @empty
                                                <span class="text-xs italic" style="color: var(--tenant-card-muted, #64748b);">No custom roles assigned</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button wire:click="openManageUserRolesModal({{ $user->id }})" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-purple-600/30 hover:bg-purple-600/50 text-purple-200 border border-purple-500/40 transition">
                                            🛡️ Roles
                                        </button>
                                        <button wire:click="editUser({{ $user->id }})" class="text-tenant-accent hover:opacity-80 transition-colors text-xs font-semibold">
                                            Edit
                                        </button>
                                        @if(auth()->id() !== $user->id)
                                            <button wire:click="deleteUser({{ $user->id }})" wire:confirm="Are you sure you want to remove this user from the airline?" class="text-red-400 hover:text-red-300 transition-colors text-xs font-semibold">
                                                Remove
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center" style="color: var(--tenant-card-muted, #94a3b8);">
                                        No users found for this virtual airline.
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

            <!-- PIREP Scoring Management Tab -->
            @if($activeTab === 'scoring')
                <div class="space-y-6">
                    <!-- Header with Actions -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        <div>
                            <h3 class="text-lg font-bold flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                                <span>🎯</span> PIREP Scoring Criteria &amp; Flight Evaluation Rules
                            </h3>
                            <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
                                Configure vertical touchdown rate (FPM) criteria, operational tolerances, fuel penalties, flight length bonuses, and automated rejection rules.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="resetScoringSettings" wire:confirm="Are you sure you want to reset all scoring criteria to system defaults?"
                                class="px-3.5 py-2 rounded-lg text-xs font-semibold transition"
                                style="background-color: var(--tenant-button-secondary-bg, #374151); color: var(--tenant-button-secondary-text, #ffffff); border: 1px solid var(--tenant-input-border, rgba(255,255,255,0.15));">
                                Reset to Defaults
                            </button>
                            <button type="button" wire:click="recalculateAirlinePireps" wire:confirm="Are you sure you want to recalculate all past PIREPs for this airline against your current scoring criteria? This will update flight scores and statistics for all pilots."
                                class="px-3.5 py-2 rounded-lg text-xs font-bold transition flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm"
                                title="Re-evaluate all past airline flights against your current criteria">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>Recalculate Past PIREPs</span>
                            </button>
                            <button type="button" wire:click="saveScoringSettings"
                                class="px-5 py-2 rounded-lg text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-1.5"
                                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Save Scoring Criteria
                            </button>
                        </div>
                    </div>

                    @if (session()->has('scoring_message'))
                        <div class="bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 px-4 py-3 rounded-xl flex items-center justify-between" role="alert">
                            <span class="text-sm font-medium">{{ session('scoring_message') }}</span>
                            <span class="text-emerald-400 text-lg">✓</span>
                        </div>
                    @endif

                    <!-- CARD 1: Touchdown Rate (FPM) Scoring (PRIMARY METRIC) -->
                    <div class="p-6 rounded-2xl border" style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div>
                                <h4 class="text-base font-bold flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                                    <span>🛬</span> Landing Evaluation — Touchdown Rate (FPM)
                                    <span class="text-[10px] px-2 py-0.5 rounded font-mono font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Primary Landing Metric</span>
                                </h4>
                                <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">
                                    Flight landing performance is graded and scored based on vertical descent rate at touchdown in Feet Per Minute (FPM). G-force is logged for telemetry only.
                                </p>
                            </div>
                        </div>

                        <!-- FPM Brackets Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                            <!-- Butter / Perfect -->
                            <div class="p-4 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.25)); border-color: rgba(52, 211, 153, 0.3);">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                                        <span>🧈</span> Butter / Perfect
                                    </span>
                                    <span class="text-[10px] font-mono text-emerald-300 bg-emerald-500/10 px-2 py-0.5 rounded font-bold">+{{ $fpm_butter_points }} pts</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Max FPM (&le;)</label>
                                        <x-input type="number" wire:model="fpm_butter_threshold" class="w-full text-xs font-mono font-bold" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Bonus Pts</label>
                                        <x-input type="number" wire:model="fpm_butter_points" class="w-full text-xs font-mono font-bold text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Smooth / Good -->
                            <div class="p-4 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.25)); border-color: rgba(56, 189, 248, 0.3);">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-sky-400 flex items-center gap-1.5">
                                        <span>✨</span> Smooth / Good
                                    </span>
                                    <span class="text-[10px] font-mono text-sky-300 bg-sky-500/10 px-2 py-0.5 rounded font-bold">+{{ $fpm_good_points }} pts</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Max FPM (&le;)</label>
                                        <x-input type="number" wire:model="fpm_good_threshold" class="w-full text-xs font-mono font-bold" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Bonus Pts</label>
                                        <x-input type="number" wire:model="fpm_good_points" class="w-full text-xs font-mono font-bold text-sky-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Normal / Fair -->
                            <div class="p-4 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.25)); border-color: rgba(251, 191, 36, 0.3);">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                                        <span>🛫</span> Normal / Fair
                                    </span>
                                    <span class="text-[10px] font-mono text-amber-300 bg-amber-500/10 px-2 py-0.5 rounded font-bold">+{{ $fpm_fair_points }} pts</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Max FPM (&le;)</label>
                                        <x-input type="number" wire:model="fpm_fair_threshold" class="w-full text-xs font-mono font-bold" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Bonus Pts</label>
                                        <x-input type="number" wire:model="fpm_fair_points" class="w-full text-xs font-mono font-bold text-amber-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Firm Landing -->
                            <div class="p-4 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.25)); border-color: rgba(249, 115, 22, 0.3);">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-orange-400 flex items-center gap-1.5">
                                        <span>⚠️</span> Firm Landing
                                    </span>
                                    <span class="text-[10px] font-mono text-orange-300 bg-orange-500/10 px-2 py-0.5 rounded font-bold">-{{ $fpm_firm_penalty }} pts</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Max FPM (&le;)</label>
                                        <x-input type="number" wire:model="fpm_firm_threshold" class="w-full text-xs font-mono font-bold" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Penalty Pts</label>
                                        <x-input type="number" wire:model="fpm_firm_penalty" class="w-full text-xs font-mono font-bold text-orange-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Hard Landing -->
                            <div class="p-4 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.25)); border-color: rgba(239, 68, 68, 0.3);">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-red-400 flex items-center gap-1.5">
                                        <span>🚨</span> Hard Landing
                                    </span>
                                    <span class="text-[10px] font-mono text-red-300 bg-red-500/10 px-2 py-0.5 rounded font-bold">-{{ $fpm_hard_penalty }} pts</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Max FPM (&le;)</label>
                                        <x-input type="number" wire:model="fpm_hard_threshold" class="w-full text-xs font-mono font-bold" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Penalty Pts</label>
                                        <x-input type="number" wire:model="fpm_hard_penalty" class="w-full text-xs font-mono font-bold text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Severe / Rejection Trigger -->
                            <div class="p-4 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.25)); border-color: rgba(220, 38, 38, 0.5);">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-red-400 flex items-center gap-1.5">
                                        <span>🛑</span> Rejection Trigger
                                    </span>
                                    <span class="text-[10px] font-mono text-red-300 bg-red-500/20 px-2 py-0.5 rounded font-bold">-{{ $fpm_reject_penalty }} pts (Reject)</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Threshold (&ge; FPM)</label>
                                        <x-input type="number" wire:model="fpm_reject_threshold" class="w-full text-xs font-mono font-bold text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Penalty Pts</label>
                                        <x-input type="number" wire:model="fpm_reject_penalty" class="w-full text-xs font-mono font-bold text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Danger / Invalidation Trigger -->
                            <div class="p-4 rounded-xl border space-y-2 col-span-1 md:col-span-2 lg:col-span-3" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.3)); border-color: rgba(239, 68, 68, 0.6);">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                    <div>
                                        <span class="text-xs font-bold text-red-400 flex items-center gap-1.5">
                                            <span>💥</span> Structural Danger / Invalidation Threshold
                                        </span>
                                        <p class="text-[11px] mt-0.5" style="color: var(--tenant-card-muted, #94a3b8);">
                                            Touchdown rates equal to or exceeding this threshold automatically invalidate the PIREP and award 0 hours / 0 points.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs font-semibold whitespace-nowrap" style="color: var(--tenant-card-muted, #94a3b8);">Invalidate at &ge;</label>
                                            <x-input type="number" wire:model="fpm_danger_threshold" class="w-24 text-xs font-mono font-bold text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                            <span class="text-xs font-mono" style="color: var(--tenant-card-muted, #94a3b8);">FPM</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs font-semibold whitespace-nowrap" style="color: var(--tenant-card-muted, #94a3b8);">Penalty</label>
                                            <x-input type="number" wire:model="fpm_danger_penalty" class="w-20 text-xs font-mono font-bold text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                            <span class="text-xs font-mono" style="color: var(--tenant-card-muted, #94a3b8);">pts</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: Base Starting Points & Invalidation Settings -->
                    <div class="p-6 rounded-2xl border" style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        <h4 class="text-base font-bold flex items-center gap-2 mb-4" style="color: var(--tenant-card-text, #ffffff);">
                            <span>⭐</span> Starting Points &amp; Automated Failure Rules
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                            <div>
                                <label class="font-semibold block mb-1.5" style="color: var(--tenant-card-text, #ffffff);">Base Starting Points</label>
                                <x-input type="number" wire:model="base_points" class="w-full font-mono font-bold text-tenant-accent" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Initial score every flight begins with before bonuses or penalties.</p>
                            </div>

                            <div>
                                <label class="font-semibold block mb-1.5" style="color: var(--tenant-card-text, #ffffff);">Max Allowed Sim Rate</label>
                                <x-input type="number" step="0.5" wire:model="max_sim_rate" class="w-full font-mono font-bold" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Time acceleration strictness (1.0x = Realtime only).</p>
                            </div>

                            <div>
                                <label class="font-semibold block mb-1.5" style="color: var(--tenant-card-text, #ffffff);">Max Allowed Bounces</label>
                                <x-input type="number" wire:model="max_bounces" class="w-full font-mono font-bold" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Landings exceeding this bounce count are held for review.</p>
                            </div>

                            <div class="flex items-center pt-5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="invalidate_on_negative_score" class="rounded border-gray-600 text-tenant-accent focus:ring-tenant-accent" style="background-color: var(--tenant-input-bg, #111827);">
                                    <span class="text-xs font-semibold" style="color: var(--tenant-card-text, #ffffff);">Invalidate on Negative Total Score</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 3: Operational Aircraft Tolerances (Engines & Flaps) -->
                    <div class="p-6 rounded-2xl border" style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        <h4 class="text-base font-bold flex items-center gap-2 mb-4" style="color: var(--tenant-card-text, #ffffff);">
                            <span>⚙️</span> Engine Procedures &amp; Flaps Tolerances
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                            <!-- Engine Warmup -->
                            <div class="p-3.5 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                <span class="font-bold block" style="color: var(--tenant-card-text, #ffffff);">Engine Warmup Requirement</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Min Seconds</label>
                                        <x-input type="number" wire:model="engine_warmup_seconds" class="w-full text-xs font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Penalty (pts)</label>
                                        <x-input type="number" wire:model="engine_warmup_penalty" class="w-full text-xs font-mono text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Engine Cooldown -->
                            <div class="p-3.5 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                <span class="font-bold block" style="color: var(--tenant-card-text, #ffffff);">Engine Cooldown Requirement</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Min Seconds</label>
                                        <x-input type="number" wire:model="engine_cooldown_seconds" class="w-full text-xs font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Penalty (pts)</label>
                                        <x-input type="number" wire:model="engine_cooldown_penalty" class="w-full text-xs font-mono text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Engine Start Interval -->
                            <div class="p-3.5 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                <span class="font-bold block" style="color: var(--tenant-card-text, #ffffff);">Engine Start Interval (Airbus)</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Min Interval (s)</label>
                                        <x-input type="number" wire:model="engine_start_interval_seconds" class="w-full text-xs font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Bonus (pts)</label>
                                        <x-input type="number" wire:model="engine_start_interval_bonus" class="w-full text-xs font-mono text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Engine Shutdown Clean Bonus -->
                            <div class="p-3.5 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                <span class="font-bold block" style="color: var(--tenant-card-text, #ffffff);">Clean Engine Shutdown Bonus</span>
                                <div>
                                    <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Bonus Points</label>
                                    <x-input type="number" wire:model="engines_shutdown_clean_bonus" class="w-full text-xs font-mono text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                </div>
                            </div>

                            <!-- Flaps Retracted Before Parking -->
                            <div class="p-3.5 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                <span class="font-bold block" style="color: var(--tenant-card-text, #ffffff);">Flaps Parking Procedure</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Bonus (pts)</label>
                                        <x-input type="number" wire:model="flaps_parking_bonus" class="w-full text-xs font-mono text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Violation Pen.</label>
                                        <x-input type="number" wire:model="flaps_retracted_violation_penalty" class="w-full text-xs font-mono text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Takeoff Flaps Unset Penalty -->
                            <div class="p-3.5 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                <span class="font-bold block" style="color: var(--tenant-card-text, #ffffff);">Takeoff Flaps Violation</span>
                                <div>
                                    <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Penalty Deducted</label>
                                    <x-input type="number" wire:model="takeoff_flaps_penalty" class="w-full text-xs font-mono text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 4: Fuel Operations -->
                    <div class="p-6 rounded-2xl border" style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        <h4 class="text-base font-bold flex items-center gap-2 mb-4" style="color: var(--tenant-card-text, #ffffff);">
                            <span>⛽</span> Fuel Planning &amp; Landing Limits
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <!-- Minimum Landing Fuel -->
                            <div class="p-4 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                <span class="font-bold block text-amber-300">Minimum Safe Landing Fuel</span>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Min Fuel (kg)</label>
                                        <x-input type="number" wire:model="min_landing_fuel_kg" class="w-full text-xs font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Low Fuel Penalty (pts)</label>
                                        <x-input type="number" wire:model="low_fuel_penalty" class="w-full text-xs font-mono text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>

                            <!-- Maximum Landing Fuel -->
                            <div class="p-4 rounded-xl border space-y-2" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                                <span class="font-bold block text-amber-300">Maximum Safe Landing Fuel (Overweight)</span>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Max Fuel (kg)</label>
                                        <x-input type="number" wire:model="max_landing_fuel_kg" class="w-full text-xs font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">Excess Penalty (pts)</label>
                                        <x-input type="number" wire:model="excess_fuel_penalty" class="w-full text-xs font-mono text-red-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 5: Bonuses & Flight Duration Tiers -->
                    <div class="p-6 rounded-2xl border" style="background-color: var(--tenant-card-bg, #181D29); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        <h4 class="text-base font-bold flex items-center gap-2 mb-4" style="color: var(--tenant-card-text, #ffffff);">
                            <span>🎖️</span> Operational Bonuses &amp; Flight Duration Tiers
                        </h4>

                        <!-- Special Bonuses -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs mb-6">
                            <div>
                                <label class="font-semibold block mb-1.5" style="color: var(--tenant-card-text, #ffffff);">Online Network Bonus (VATSIM / IVAO / POSCON)</label>
                                <x-input type="number" wire:model="online_network_bonus" class="w-full font-mono font-bold text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Points awarded when flying connected to an approved online ATC network.</p>
                            </div>
                            <div>
                                <label class="font-semibold block mb-1.5" style="color: var(--tenant-card-text, #ffffff);">Shared Cockpit Bonus</label>
                                <x-input type="number" wire:model="shared_cockpit_bonus" class="w-full font-mono font-bold text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Points awarded when flight is flown in multi-crew shared cockpit.</p>
                            </div>
                            <div>
                                <label class="font-semibold block mb-1.5" style="color: var(--tenant-card-text, #ffffff);">Realistic Preparation Time Bonus</label>
                                <x-input type="number" wire:model="prep_time_bonus" class="w-full font-mono font-bold text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Awarded when flight preparation is between 20 and 40 minutes.</p>
                            </div>
                        </div>

                        <!-- Flight Duration Tiers -->
                        <div class="pt-4 border-t" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                            <span class="text-xs font-semibold block mb-3" style="color: var(--tenant-card-text, #ffffff);">Flight Duration Points Awarded</span>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                                <div>
                                    <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">&lt; 1 Hour</label>
                                    <x-input type="number" wire:model="flight_time_bonus_under_1h" class="w-full text-xs font-mono font-bold text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                </div>
                                <div>
                                    <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">1 – 2 Hours</label>
                                    <x-input type="number" wire:model="flight_time_bonus_1_to_2h" class="w-full text-xs font-mono font-bold text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                </div>
                                <div>
                                    <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">2 – 3 Hours</label>
                                    <x-input type="number" wire:model="flight_time_bonus_2_to_3h" class="w-full text-xs font-mono font-bold text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                </div>
                                <div>
                                    <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">3 – 4 Hours</label>
                                    <x-input type="number" wire:model="flight_time_bonus_3_to_4h" class="w-full text-xs font-mono font-bold text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                </div>
                                <div>
                                    <label class="text-[11px] block mb-1" style="color: var(--tenant-card-muted, #94a3b8);">&gt; 4 Hours</label>
                                    <x-input type="number" wire:model="flight_time_bonus_over_4h" class="w-full text-xs font-mono font-bold text-emerald-400" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Save Button -->
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="resetScoringSettings" wire:confirm="Are you sure you want to reset all scoring criteria to system defaults?"
                            class="px-5 py-2.5 rounded-xl text-xs font-semibold transition"
                            style="background-color: var(--tenant-button-secondary-bg, #374151); color: var(--tenant-button-secondary-text, #ffffff); border: 1px solid var(--tenant-input-border, rgba(255,255,255,0.15));">
                            Reset to Defaults
                        </button>
                        <button type="button" wire:click="recalculateAirlinePireps" wire:confirm="Are you sure you want to recalculate all past PIREPs for this airline against your current scoring criteria? This will update flight scores and statistics for all pilots."
                            class="px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span>Recalculate Past PIREPs</span>
                        </button>
                        <button type="button" wire:click="saveScoringSettings"
                            class="px-6 py-2.5 rounded-xl text-xs font-bold shadow-md hover:opacity-90 transition flex items-center gap-2"
                            style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Save Scoring Criteria
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Role Creation & Edit Modal with Granular Permission Matrix -->
    <x-dialog-modal wire:model.live="showRoleModal" maxWidth="3xl">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <span>🛡️</span>
                <span>{{ $editingRoleId ? __('Edit Airline Role') : __('Create New Airline Role') }}</span>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-6">
                <!-- Basic Role Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-label for="roleName" value="{{ __('Role Name') }}" style="color: var(--tenant-card-text, #ffffff);" />
                        <x-input id="roleName" type="text" class="mt-1 block w-full" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" wire:model.live="roleName" placeholder="e.g. Flight Operations Manager" />
                        <x-input-error for="roleName" class="mt-1 text-red-400 text-xs" />
                    </div>

                    <div>
                        <x-label for="roleSlug" value="{{ __('Role Slug (Identifier)') }}" style="color: var(--tenant-card-text, #ffffff);" />
                        <x-input id="roleSlug" type="text" class="mt-1 block w-full font-mono" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" wire:model="roleSlug" placeholder="e.g. flight-ops-manager" />
                        <x-input-error for="roleSlug" class="mt-1 text-red-400 text-xs" />
                    </div>

                    <div>
                        <x-label for="roleHonoraryRank" value="{{ __('Honorary Staff Rank Title (Optional)') }}" style="color: var(--tenant-card-text, #ffffff);" />
                        <x-input id="roleHonoraryRank" type="text" class="mt-1 block w-full" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" wire:model="roleHonoraryRank" placeholder="e.g. Chief Pilot / VP Operations" />
                        <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Displayed when the user has "Prefer Honorary Rank" enabled in preferences.</p>
                        <x-input-error for="roleHonoraryRank" class="mt-1 text-red-400 text-xs" />
                    </div>

                    <div>
                        <x-label for="roleDescription" value="{{ __('Description (Optional)') }}" style="color: var(--tenant-card-text, #ffffff);" />
                        <x-input id="roleDescription" type="text" class="mt-1 block w-full" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" wire:model="roleDescription" placeholder="Short description of responsibilities" />
                        <x-input-error for="roleDescription" class="mt-1 text-red-400 text-xs" />
                    </div>
                </div>

                <!-- Role Toggles -->
                <div class="flex flex-wrap gap-6 p-4 rounded-xl border" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.3)); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="roleIsStaff" class="rounded border-gray-600 text-tenant-accent focus:ring-tenant-accent" style="background-color: var(--tenant-input-bg, #111827);">
                        <span class="text-sm font-semibold" style="color: var(--tenant-card-text, #ffffff);">Staff Role</span>
                        <span class="text-xs" style="color: var(--tenant-card-muted, #94a3b8);">(Designates managerial/administrative staff)</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="roleIsDefault" class="rounded border-gray-600 text-tenant-accent focus:ring-tenant-accent" style="background-color: var(--tenant-input-bg, #111827);">
                        <span class="text-sm font-semibold" style="color: var(--tenant-card-text, #ffffff);">Default Role</span>
                        <span class="text-xs" style="color: var(--tenant-card-muted, #94a3b8);">(Automatically assigned to new joining pilots)</span>
                    </label>
                </div>

                <!-- Granular Permissions Matrix -->
                <div class="space-y-4 pt-2">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b pb-3" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        <div>
                            <h4 class="text-sm font-bold uppercase tracking-wider flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                                <span>🔑</span> Granular Permissions Matrix
                            </h4>
                            <p class="text-xs" style="color: var(--tenant-card-muted, #94a3b8);">Configure Read vs. Read-Write access across each core module for this role.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="selectAllPermissions" class="px-2.5 py-1 rounded text-xs transition border hover:opacity-80"
                                style="background-color: var(--tenant-button-secondary-bg, rgba(255,255,255,0.1)); color: var(--tenant-button-secondary-text, #ffffff); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                Select All
                            </button>
                            <button type="button" wire:click="selectAllReadPermissions" class="px-2.5 py-1 rounded text-xs transition border hover:opacity-80 text-sky-300"
                                style="background-color: var(--tenant-button-secondary-bg, rgba(255,255,255,0.1)); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                Read-Only All
                            </button>
                            <button type="button" wire:click="clearPermissions" class="px-2.5 py-1 rounded text-xs transition border hover:opacity-80 text-red-300"
                                style="background-color: var(--tenant-button-secondary-bg, rgba(255,255,255,0.1)); border-color: var(--tenant-input-border, rgba(255,255,255,0.15));">
                                Clear
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                        @foreach($categories as $key => $category)
                            @php
                                $level = $this->getCategoryCurrentLevel($key);
                            @endphp
                            <div class="p-4 rounded-xl space-y-3 border" style="background-color: var(--tenant-card-bg, #141a24); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-lg">{{ $category['icon'] }}</span>
                                        <div>
                                            <h5 class="text-sm font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $category['name'] }}</h5>
                                            <p class="text-[11px]" style="color: var(--tenant-card-muted, #94a3b8);">{{ $category['description'] }}</p>
                                        </div>
                                    </div>

                                    <!-- Quick Level Selector -->
                                    <div class="inline-flex rounded-lg p-1 border text-xs" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.4)); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                                        <button type="button" wire:click="setCategoryPermissionLevel('{{ $key }}', 'none')" 
                                                class="px-2.5 py-1 rounded-md transition font-medium {{ $level === 'none' ? 'bg-red-500/20 text-red-300 font-bold' : 'text-gray-400 hover:text-white' }}">
                                            None
                                        </button>
                                        <button type="button" wire:click="setCategoryPermissionLevel('{{ $key }}', 'read')" 
                                                class="px-2.5 py-1 rounded-md transition font-medium {{ $level === 'read' ? 'bg-sky-500/20 text-sky-300 font-bold' : 'text-gray-400 hover:text-white' }}">
                                            Read
                                        </button>
                                        <button type="button" wire:click="setCategoryPermissionLevel('{{ $key }}', 'write')" 
                                                class="px-2.5 py-1 rounded-md transition font-medium {{ $level === 'write' ? 'bg-emerald-500/20 text-emerald-300 font-bold' : 'text-gray-400 hover:text-white' }}">
                                            Read-Write
                                        </button>
                                    </div>
                                </div>

                                <!-- Individual Checkboxes -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 border-t" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.05));">
                                    @foreach($category['all'] as $slug => $label)
                                        <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer border transition hover:border-white/10"
                                            style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); border-color: var(--tenant-input-border, rgba(255,255,255,0.05));">
                                            <input type="checkbox" value="{{ $slug }}" wire:model="selectedPermissions" class="rounded border-gray-600 text-tenant-accent focus:ring-tenant-accent" style="background-color: var(--tenant-input-bg, #111827);">
                                            <div class="text-xs">
                                                <span class="font-medium" style="color: var(--tenant-card-text, #ffffff);">{{ $label }}</span>
                                                <span class="text-[10px] block font-mono" style="color: var(--tenant-card-muted, #64748b);">{{ $slug }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showRoleModal', false)" wire:loading.attr="disabled" class="border-none"
                style="background-color: var(--tenant-button-secondary-bg, #374151); color: var(--tenant-button-secondary-text, #ffffff);">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveRole" wire:loading.attr="disabled" class="ml-3 px-5 py-2 rounded-lg text-sm font-bold shadow-md hover:opacity-90 transition"
                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                {{ $editingRoleId ? __('Save Role Changes') : __('Create Role') }}
            </button>
        </x-slot>
    </x-dialog-modal>

    <!-- User Role Assignment Modal -->
    <x-dialog-modal wire:model.live="showUserRolesModal">
        <x-slot name="title">
            <div class="flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                <span>🛡️</span>
                <span>Assign Roles - <strong class="text-tenant-accent">{{ $managingUserName }}</strong></span>
            </div>
        </x-slot>

        <x-slot name="content">
            <p class="text-xs mb-4" style="color: var(--tenant-card-muted, #94a3b8);">
                Select the custom roles to grant to this pilot for this specific virtual airline. Permissions will be aggregated automatically.
            </p>

            <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                @forelse($roles as $role)
                    <label class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition hover:border-white/20"
                        style="background-color: var(--tenant-card-bg, #141a24); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        <input type="checkbox" value="{{ $role->id }}" wire:model="userAssignedRoleIds" class="mt-1 rounded border-gray-600 text-tenant-accent focus:ring-tenant-accent" style="background-color: var(--tenant-input-bg, #111827);">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $role->name }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $role->is_staff ? 'bg-purple-500/20 text-purple-300' : 'bg-gray-700 text-gray-300' }}">
                                    {{ $role->is_staff ? 'Staff' : 'Pilot' }}
                                </span>
                            </div>
                            @if($role->honorary_rank_string)
                                <p class="text-xs font-mono text-tenant-accent mt-0.5">⭐ Honorary Rank: {{ $role->honorary_rank_string }}</p>
                            @endif
                            <p class="text-xs mt-1" style="color: var(--tenant-card-muted, #94a3b8);">{{ $role->description ?: 'No description provided.' }}</p>
                        </div>
                    </label>
                @empty
                    <div class="p-4 text-center text-xs rounded-xl border" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); color: var(--tenant-card-muted, #94a3b8); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        No custom airline roles created yet. Create roles in the "Roles &amp; Permissions" tab first.
                    </div>
                @endforelse
            </div>

            <!-- Honorary Rank Assignment -->
            <div class="mt-6 pt-5 border-t" style="border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                        <span>🎖️</span> Honorary Rank Assignment
                    </label>
                    <span class="text-[10px] text-slate-400 font-mono">Manual Recognition</span>
                </div>
                <p class="text-xs text-slate-400 mb-3">
                    Assign an honorary rank (e.g. Staff Team, Flight Instructor, Real-World Pilot). Pilots hold both regular and honorary ranks and can choose which to display.
                </p>

                <select wire:model="managingUserHonoraryRankId" class="w-full rounded-xl border border-white/10 text-white text-sm p-2.5 focus:border-amber-400 focus:ring-amber-400"
                    style="background-color: var(--tenant-input-bg, #111827);">
                    <option value="">-- No Honorary Rank Assigned (Standard Regular Progression) --</option>
                    @foreach($honoraryRanks as $hRank)
                        <option value="{{ $hRank->id }}">
                            {{ $hRank->name }} ({{ $hRank->abbreviation }})
                        </option>
                    @endforeach
                </select>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showUserRolesModal', false)" wire:loading.attr="disabled" class="border-none"
                style="background-color: var(--tenant-button-secondary-bg, #374151); color: var(--tenant-button-secondary-text, #ffffff);">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveUserRoles" wire:loading.attr="disabled" class="ml-3 px-5 py-2 rounded-lg text-sm font-bold shadow-md hover:opacity-90 transition"
                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                {{ __('Update Roles') }}
            </button>
        </x-slot>
    </x-dialog-modal>

    <!-- User Modal (Create/Edit user account) -->
    <x-dialog-modal wire:model.live="showUserModal">
        <x-slot name="title">
            <span style="color: var(--tenant-card-text, #ffffff);">{{ $editingUserId ? __('Edit User') : __('Add New User') }}</span>
        </x-slot>

        <x-slot name="content">
            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="userName" value="{{ __('Name') }}" style="color: var(--tenant-card-text, #ffffff);" />
                <x-input id="userName" type="text" class="mt-1 block w-full" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" wire:model="userName" />
                <x-input-error for="userName" class="mt-2 text-red-400 text-xs" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="userEmail" value="{{ __('Email') }}" style="color: var(--tenant-card-text, #ffffff);" />
                <x-input id="userEmail" type="email" class="mt-1 block w-full" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" wire:model="userEmail" />
                <x-input-error for="userEmail" class="mt-2 text-red-400 text-xs" />
            </div>

            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="userPassword" value="{{ $editingUserId ? __('Password (leave blank to keep current)') : __('Password') }}" style="color: var(--tenant-card-text, #ffffff);" />
                <x-input id="userPassword" type="password" class="mt-1 block w-full" style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);" wire:model="userPassword" />
                <x-input-error for="userPassword" class="mt-2 text-red-400 text-xs" />
            </div>

            <!-- Base System Role -->
            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label for="userRole" value="{{ __('Account Base Role') }}" style="color: var(--tenant-card-text, #ffffff);" />
                <select id="userRole" wire:model="userRole" class="mt-1 block w-full rounded-md shadow-sm"
                    style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);">
                    <option value="Pilot">Pilot (Standard Account)</option>
                    <option value="VA Owner">VA Owner (Airline Administrator)</option>
                </select>
                <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Default role hierarchy. VA Owners have full administrative control.</p>
                <x-input-error for="userRole" class="mt-1 text-red-400 text-xs" />
            </div>

            <!-- Custom Airline Roles -->
            <div class="col-span-6 sm:col-span-4 mb-4">
                <x-label value="{{ __('Assigned Airline Staff & Pilot Roles') }}" class="mb-1.5" style="color: var(--tenant-card-text, #ffffff);" />
                @if($roles->isNotEmpty())
                    <div class="space-y-2 max-h-48 overflow-y-auto p-3 rounded-xl border" style="background-color: var(--tenant-card-bg, #141a24); border-color: var(--tenant-input-border, rgba(255,255,255,0.1));">
                        @foreach($roles as $role)
                            <label class="flex items-start gap-2.5 p-2 rounded-lg cursor-pointer border transition hover:border-white/10"
                                style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); border-color: var(--tenant-input-border, rgba(255,255,255,0.05));">
                                <input type="checkbox" value="{{ $role->id }}" wire:model="userAssignedRoleIds" class="mt-0.5 rounded border-gray-600 text-tenant-accent focus:ring-tenant-accent" style="background-color: var(--tenant-input-bg, #111827);">
                                <div class="flex-1 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold" style="color: var(--tenant-card-text, #ffffff);">{{ $role->name }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] {{ $role->is_staff ? 'bg-purple-500/20 text-purple-300' : 'bg-gray-700 text-gray-300' }}">
                                            {{ $role->is_staff ? 'Staff' : 'Pilot' }}
                                        </span>
                                    </div>
                                    @if($role->honorary_rank_string)
                                        <span class="text-[11px] text-tenant-accent font-mono block mt-0.5">⭐ {{ $role->honorary_rank_string }}</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Select any custom roles (e.g. Route Manager, Chief Pilot) to grant to this user.</p>
                @else
                    <div class="p-3 rounded-xl text-xs border" style="background-color: var(--tenant-input-bg, rgba(0,0,0,0.2)); color: var(--tenant-card-muted, #94a3b8); border-color: var(--tenant-input-border, rgba(255,255,255,0.08));">
                        No custom airline roles created yet. You can create them in the <strong>Roles &amp; Permissions</strong> tab.
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showUserModal', false)" wire:loading.attr="disabled" class="border-none"
                style="background-color: var(--tenant-button-secondary-bg, #374151); color: var(--tenant-button-secondary-text, #ffffff);">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveUser" wire:loading.attr="disabled" class="ml-3 px-4 py-2 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition"
                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                {{ $editingUserId ? __('Save Changes') : __('Create User') }}
            </button>
        </x-slot>
    </x-dialog-modal>

    <!-- Custom Permission Modal -->
    <x-dialog-modal wire:model.live="showPermissionModal">
        <x-slot name="title">
            <div class="flex items-center gap-2" style="color: var(--tenant-card-text, #ffffff);">
                <span>🔑</span>
                <span>{{ $editingPermissionId ? __('Edit Custom Permission') : __('Create Custom Permission') }}</span>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4 text-xs">
                <div>
                    <x-label for="permissionName" value="{{ __('Permission Name') }}" style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="permissionName" type="text" class="mt-1 block w-full text-xs"
                        style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                        wire:model.live="permissionName" placeholder="e.g. Manage Discord Webhooks" />
                    <x-input-error for="permissionName" class="mt-1 text-red-400 text-xs" />
                </div>

                <div>
                    <x-label for="permissionSlug" value="{{ __('Permission Identifier (Slug)') }}" style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="permissionSlug" type="text" class="mt-1 block w-full font-mono text-xs"
                        style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                        wire:model="permissionSlug" placeholder="e.g. manage_discord_webhooks" />
                    <x-input-error for="permissionSlug" class="mt-1 text-red-400 text-xs" />
                    <p class="text-[11px] mt-1" style="color: var(--tenant-card-muted, #94a3b8);">Unique permission code checked in templates, policies, or middleware.</p>
                </div>

                <div>
                    <x-label for="permissionGroup" value="{{ __('Permission Group / Category') }}" style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="permissionGroup" type="text" class="mt-1 block w-full text-xs"
                        style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                        wire:model="permissionGroup" placeholder="e.g. Custom Operations, Integrations, Fleet" />
                    <x-input-error for="permissionGroup" class="mt-1 text-red-400 text-xs" />
                </div>

                <div>
                    <x-label for="permissionDescription" value="{{ __('Description (Optional)') }}" style="color: var(--tenant-card-text, #ffffff);" />
                    <x-input id="permissionDescription" type="text" class="mt-1 block w-full text-xs"
                        style="background-color: var(--tenant-input-bg, #212631); border-color: var(--tenant-input-border, #374151); color: var(--tenant-input-text, #ffffff);"
                        wire:model="permissionDescription" placeholder="Short description of what this permission enables" />
                    <x-input-error for="permissionDescription" class="mt-1 text-red-400 text-xs" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showPermissionModal', false)" wire:loading.attr="disabled" class="border-none"
                style="background-color: var(--tenant-button-secondary-bg, #374151); color: var(--tenant-button-secondary-text, #ffffff);">
                {{ __('Cancel') }}
            </x-secondary-button>

            <button wire:click="saveCustomPermission" wire:loading.attr="disabled" class="ml-3 px-5 py-2 rounded-lg text-xs font-bold shadow-md hover:opacity-90 transition"
                style="background-color: var(--tenant-button-bg, var(--tenant-accent)); color: var(--tenant-button-text, #ffffff);">
                {{ $editingPermissionId ? __('Save Permission') : __('Create Permission') }}
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
