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
