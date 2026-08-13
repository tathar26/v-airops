<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 text-white font-sans">
    
    @if (session()->has('message'))
        <div class="p-4 bg-green-500/20 border border-green-500 text-green-100 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <!-- Top Flight Header -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl p-6 shadow-xl space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">
                    {{ $booking->route->departure_icao }} <span class="text-gray-400 font-normal">→</span> {{ $booking->route->arrival_icao }}
                </h1>
                <span class="text-xs font-semibold px-2.5 py-1 rounded bg-white/10 text-gray-300">
                    {{ $booking->route->distance ?? 374 }} nm
                </span>
                <span class="text-xs font-semibold px-2.5 py-1 rounded bg-tenant-accent/20 text-tenant-accent font-mono">
                    {{ strtoupper($callsign) }}
                </span>
                <span class="text-xs font-semibold px-2.5 py-1 rounded bg-blue-500/20 text-blue-400">
                    Scheduled
                </span>
            </div>

            <div class="flex items-center gap-3 text-xs">
                <a href="https://www.flightradar24.com/data/flights/{{ strtolower($callsign) }}" target="_blank" class="px-3 py-1.5 bg-white/5 border border-white/10 rounded-md text-gray-300 hover:text-white hover:bg-white/10 transition">
                    Flight Radar 24
                </a>
                <a href="https://www.airnavradar.com" target="_blank" class="px-3 py-1.5 bg-white/5 border border-white/10 rounded-md text-gray-300 hover:text-white hover:bg-white/10 transition">
                    AirNav Radar
                </a>
                <button wire:click="$toggle('showRouteDetails')" class="text-gray-400 hover:text-white transition flex items-center gap-1">
                    Route details {{ $showRouteDetails ? '∧' : '∨' }}
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-6 text-sm text-gray-400 pt-2 border-t border-white/5">
            <div><span class="text-gray-500 uppercase text-xs font-bold mr-1">ETD</span> <span class="text-white font-medium">{{ $departure_time }}</span></div>
            <div><span class="text-gray-500 uppercase text-xs font-bold mr-1">ETA</span> <span class="text-white font-medium">{{ date('H:i', strtotime($departure_time . ' +1 hour 30 minutes')) }}</span></div>
            <div><span class="text-gray-500 uppercase text-xs font-bold mr-1">ETE</span> <span class="text-white font-medium">01:30</span></div>
            <div><span class="text-gray-500 uppercase text-xs font-bold mr-1">Operator</span> <span class="text-white font-medium">{{ $booking->route->operator ?? 'EZS / DS' }}</span></div>
        </div>

        @if($showRouteDetails)
            <div class="mt-4 p-4 bg-black/40 rounded-lg border border-white/5 space-y-2 text-xs text-gray-300">
                <p><strong class="text-white">Departure Airport:</strong> {{ $booking->route->departure_icao }}</p>
                <p><strong class="text-white">Arrival Airport:</strong> {{ $booking->route->arrival_icao }}</p>
                <p><strong class="text-white">Planned Route:</strong> {{ $routing ?: 'Direct / SimBrief Auto-routing' }}</p>
            </div>
        @endif
    </div>

    <!-- READY TO DISPATCH Main Banner -->
    <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-800 rounded-xl p-6 shadow-2xl space-y-5 border border-blue-400/30">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-blue-300 animate-pulse"></div>
                <h2 class="text-lg font-bold text-white tracking-wide uppercase">READY TO DISPATCH</h2>
            </div>
            <span class="text-xs font-medium text-blue-200">{{ date('d M Y') }}</span>
        </div>

        <!-- Parameters Pill Summary Bar -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-xs bg-black/40 p-4 rounded-xl border border-white/10">
            <div>
                <span class="text-blue-200 block text-[10px] uppercase font-semibold">Aircraft</span>
                <span class="font-bold text-white">{{ $selectedAirframe ? $selectedAirframe->registration : 'HB-AYE' }} · {{ $selectedAirframe ? $selectedAirframe->aircraftType->code : 'A20N' }}</span>
                <button wire:click="$set('showSectionAircraft', true)" class="text-blue-300 hover:text-white underline block text-[10px] mt-0.5">change</button>
            </div>

            <div>
                <span class="text-blue-200 block text-[10px] uppercase font-semibold">Callsign</span>
                <span class="font-bold text-white">{{ strtoupper($callsign) }} · Flight {{ strtoupper($flight_number) }}</span>
            </div>

            <div>
                <span class="text-blue-200 block text-[10px] uppercase font-semibold">Departure</span>
                <span class="font-bold text-white">{{ $departure_date }} {{ $departure_time }}</span>
                <button wire:click="$set('showSectionSchedule', true)" class="text-blue-300 hover:text-white underline block text-[10px] mt-0.5">adjust</button>
            </div>

            <div>
                <span class="text-blue-200 block text-[10px] uppercase font-semibold">Network</span>
                <span class="font-bold text-white">{{ $network }} · {{ $copilot_user_id ? 'Co-pilot' : 'Solo' }}</span>
            </div>

            <div>
                <span class="text-blue-200 block text-[10px] uppercase font-semibold">Payload</span>
                <span class="font-bold text-white">{{ number_format($estimated_zfw) }} kg ZFW</span>
            </div>

            <div>
                <span class="text-blue-200 block text-[10px] uppercase font-semibold">SimBrief</span>
                <span class="font-bold text-white">{{ $dispatch_via_simbrief ? 'On' : 'Off' }} · {{ $num_alternates }} alternate</span>
                <button wire:click="$set('showSectionAlternates', true)" class="text-blue-300 hover:text-white underline block text-[10px] mt-0.5">adjust</button>
            </div>
        </div>

        <div class="text-xs text-blue-200 italic">Fuel / TOW / LDW with fuel: run preview</div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-4 pt-2 flex-wrap sm:flex-nowrap">
            <button wire:click="createBooking" class="w-full sm:w-auto flex-1 bg-white text-blue-900 font-extrabold px-6 py-3 rounded-lg shadow-lg hover:bg-gray-100 transition text-center text-sm">
                Create Booking
            </button>
            
            <button wire:click="dispatchSimbriefPopup" class="w-full sm:w-auto bg-[#1C212E] hover:bg-[#283042] border border-blue-400/40 text-white font-bold px-6 py-3 rounded-lg shadow transition flex items-center justify-center gap-2 text-sm">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                Preview OFP (SimBrief Pop-up)
            </button>
        </div>
    </div>

    <!-- Hidden Form for SimBrief Pop-up Submission -->
    <form id="simbrief-popup-form" action="https://www.simbrief.com/system/dispatch.php" method="POST" target="_blank">
        <input type="hidden" name="newflight" value="1">
        <input type="hidden" name="type" value="{{ $simbriefParams['type'] }}">
        <input type="hidden" name="orig" value="{{ $simbriefParams['orig'] }}">
        <input type="hidden" name="dest" value="{{ $simbriefParams['dest'] }}">
        <input type="hidden" name="callsign" value="{{ $simbriefParams['callsign'] }}">
        <input type="hidden" name="fltnum" value="{{ $simbriefParams['fltnum'] }}">
        <input type="hidden" name="airline" value="{{ $simbriefParams['airline'] }}">
        <input type="hidden" name="reg" value="{{ $simbriefParams['reg'] }}">
        <input type="hidden" name="date" value="{{ $simbriefParams['date'] }}">
        <input type="hidden" name="deptime" value="{{ $simbriefParams['deptime'] }}">
        <input type="hidden" name="route" value="{{ $simbriefParams['route'] }}">
        <input type="hidden" name="fl" value="{{ $simbriefParams['fl'] }}">
        <input type="hidden" name="ci" value="{{ $simbriefParams['ci'] }}">
        <input type="hidden" name="altn" value="{{ $simbriefParams['altn'] }}">
        <input type="hidden" name="altn2" value="{{ $simbriefParams['altn2'] }}">
        <input type="hidden" name="pax" value="{{ $simbriefParams['pax'] }}">
        <input type="hidden" name="bag" value="{{ $simbriefParams['bag'] }}">
        <input type="hidden" name="units" value="KGS">
        <input type="hidden" name="planformat" value="{{ $simbriefParams['planformat'] }}">
        <input type="hidden" name="static_id" value="{{ $simbriefParams['static_id'] }}">
    </form>

    <!-- SECTION 1: Aircraft & Callsign -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-lg transition-all">
        <div wire:click="$toggle('showSectionAircraft')" class="px-6 py-4 bg-[#181D29] flex items-center justify-between cursor-pointer border-b border-white/5 hover:bg-[#1E2433]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center font-bold">
                    ✈
                </div>
                <div>
                    <h3 class="font-bold text-white text-base">Aircraft & Callsign</h3>
                    <p class="text-xs text-gray-400">{{ $selectedAirframe ? $selectedAirframe->registration : 'HB-AYE' }} · {{ strtoupper($callsign) }} · {{ strtoupper($flight_number) }} · SimBrief {{ $dispatch_via_simbrief ? 'on' : 'off' }}</p>
                </div>
            </div>
            <span class="text-gray-400">{{ $showSectionAircraft ? '∧' : '∨' }}</span>
        </div>

        @if($showSectionAircraft)
            <div class="p-6 space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <x-label for="airframe_id" value="{{ __('Aircraft') }}" class="text-white font-medium" />
                        <div class="flex gap-3 text-xs">
                            <a href="{{ route('fleet') }}" target="_blank" class="text-tenant-accent hover:underline">Aircraft Picker</a>
                            <a href="https://www.flightradar24.com" target="_blank" class="text-gray-400 hover:text-white underline">FR24 Lookup</a>
                        </div>
                    </div>
                    <select id="airframe_id" wire:model.live="airframe_id" class="w-full bg-[#1C212E] border border-gray-700 text-white rounded-lg p-2.5 text-sm focus:border-tenant-accent focus:ring-tenant-accent">
                        <option value="">Select Airframe...</option>
                        @foreach($fleet as $airframe)
                            <option value="{{ $airframe->id }}">{{ $airframe->registration }} | {{ $airframe->aircraftType->name ?? $airframe->aircraftType->code }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-label for="callsign" value="{{ __('Callsign') }}" class="text-white font-medium mb-1" />
                        <x-input id="callsign" type="text" wire:model="callsign" class="w-full bg-[#1C212E] border-gray-700 text-white uppercase text-sm" placeholder="e.g. EZS64HZ" />
                    </div>
                    <div>
                        <x-label for="flight_number" value="{{ __('Flight Number') }}" class="text-white font-medium mb-1" />
                        <x-input id="flight_number" type="text" wire:model="flight_number" class="w-full bg-[#1C212E] border-gray-700 text-white uppercase text-sm" placeholder="e.g. DS1181" />
                    </div>
                </div>

                <div class="flex items-center justify-between p-3 bg-black/30 rounded-lg border border-white/5">
                    <div>
                        <span class="text-sm font-semibold text-white block">Dispatch via SimBrief</span>
                        <span class="text-xs text-gray-400">Generate an OFP before creating booking</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="dispatch_via_simbrief" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-tenant-accent"></div>
                    </label>
                </div>
            </div>
        @endif
    </div>

    <!-- SECTION 2: Schedule & Route -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-lg transition-all">
        <div wire:click="$toggle('showSectionSchedule')" class="px-6 py-4 bg-[#181D29] flex items-center justify-between cursor-pointer border-b border-white/5 hover:bg-[#1E2433]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold">
                    📅
                </div>
                <div>
                    <h3 class="font-bold text-white text-base">Schedule & Route</h3>
                    <p class="text-xs text-gray-400">{{ $departure_date }} {{ $departure_time }} · FL {{ $flight_level ?: 'AUTO' }} · CI {{ $cost_index }}</p>
                </div>
            </div>
            <span class="text-gray-400">{{ $showSectionSchedule ? '∧' : '∨' }}</span>
        </div>

        @if($showSectionSchedule)
            <div class="p-6 space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <x-label value="{{ __('Your Departure Time UTC +0') }}" class="text-white font-medium" />
                        <div class="flex gap-3 text-xs text-tenant-accent">
                            <button type="button" wire:click="$set('departure_time', '13:25')" class="hover:underline">Route Time</button>
                            <button type="button" wire:click="$set('departure_time', '{{ date('H:i') }}')" class="hover:underline">Time Setter</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input type="date" wire:model="departure_date" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" />
                        <x-input type="time" wire:model="departure_time" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" />
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <x-label for="routing" value="{{ __('Routing') }}" class="text-white font-medium" />
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $routing }}')" class="text-xs text-gray-400 hover:text-white underline">Copy</button>
                    </div>
                    <textarea id="routing" wire:model="routing" rows="3" class="w-full bg-[#1C212E] border-gray-700 text-white rounded-lg p-2.5 text-sm font-mono" placeholder="Enter route string or leave empty for SimBrief auto-routing"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-label for="flight_level" value="{{ __('Flight Level') }}" class="text-white font-medium mb-1" />
                        <x-input id="flight_level" type="text" wire:model="flight_level" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" placeholder="SimBrief will generate one if left empty" />
                    </div>
                    <div>
                        <x-label for="cost_index" value="{{ __('Cost Index') }}" class="text-white font-medium mb-1" />
                        <div class="relative">
                            <x-input id="cost_index" type="number" wire:model="cost_index" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm pr-10" />
                            <span class="absolute right-3 top-2.5 text-gray-500">🔒</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- SECTION 3: Alternates -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-lg transition-all">
        <div wire:click="$toggle('showSectionAlternates')" class="px-6 py-4 bg-[#181D29] flex items-center justify-between cursor-pointer border-b border-white/5 hover:bg-[#1E2433]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-yellow-500/20 text-yellow-400 flex items-center justify-center font-bold">
                    🛬
                </div>
                <div>
                    <h3 class="font-bold text-white text-base">Alternates</h3>
                    <p class="text-xs text-gray-400">Alternate 1: {{ $alternate_1 ?: 'None' }} · Alternate 2: {{ $alternate_2 ?: 'None' }}</p>
                </div>
            </div>
            <span class="text-gray-400">{{ $showSectionAlternates ? '∧' : '∨' }}</span>
        </div>

        @if($showSectionAlternates)
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between p-3 bg-black/30 rounded-lg border border-white/5">
                    <div>
                        <span class="text-sm font-semibold text-white block">Auto-find Alternates</span>
                        <span class="text-xs text-gray-400">Automatically suggest nearby alternate airports</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="auto_find_alternates" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-tenant-accent"></div>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-label for="num_alternates" value="{{ __('Number of Alternates') }}" class="text-white font-medium mb-1" />
                        <select id="num_alternates" wire:model.live="num_alternates" class="w-full bg-[#1C212E] border-gray-700 text-white rounded-lg p-2.5 text-sm">
                            <option value="1">1 Alternate</option>
                            <option value="2">2 Alternates</option>
                            <option value="3">3 Alternates</option>
                        </select>
                    </div>
                    <div>
                        <x-label for="alternate_1" value="{{ __('Alternate 1 (ICAO)') }}" class="text-white font-medium mb-1" />
                        <x-input id="alternate_1" type="text" wire:model="alternate_1" class="w-full bg-[#1C212E] border-gray-700 text-white uppercase text-sm" placeholder="e.g. EDDH" />
                    </div>
                    @if($num_alternates >= 2)
                        <div>
                            <x-label for="alternate_2" value="{{ __('Alternate 2 (ICAO)') }}" class="text-white font-medium mb-1" />
                            <x-input id="alternate_2" type="text" wire:model="alternate_2" class="w-full bg-[#1C212E] border-gray-700 text-white uppercase text-sm" placeholder="e.g. EDDW" />
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- SECTION 4: Payload -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-lg transition-all">
        <div wire:click="$toggle('showSectionPayload')" class="px-6 py-4 bg-[#181D29] flex items-center justify-between cursor-pointer border-b border-white/5 hover:bg-[#1E2433]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-green-500/20 text-green-400 flex items-center justify-center font-bold">
                    👥
                </div>
                <div>
                    <h3 class="font-bold text-white text-base">Payload</h3>
                    <p class="text-xs text-gray-400">{{ $passengers }} pax · {{ $hold_bags }} bags · ZFW {{ number_format($estimated_zfw) }} kg</p>
                </div>
            </div>
            <span class="text-gray-400">{{ $showSectionPayload ? '∧' : '∨' }}</span>
        </div>

        @if($showSectionPayload)
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <x-label for="passengers" value="Passengers (max {{ $passengers_max }})" class="text-white font-medium" />
                            <button type="button" wire:click="generatePassengers" class="text-xs text-tenant-accent hover:underline flex items-center gap-1">↻ Generate</button>
                        </div>
                        <x-input id="passengers" type="number" min="0" max="{{ $passengers_max }}" wire:model.live="passengers" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" />
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <x-label for="hold_bags" value="Passengers with Hold Luggage (max {{ $hold_bags_max }})" class="text-white font-medium" />
                            <button type="button" wire:click="generateHoldBags" class="text-xs text-tenant-accent hover:underline flex items-center gap-1">↻ Generate</button>
                        </div>
                        <x-input id="hold_bags" type="number" min="0" max="{{ $hold_bags_max }}" wire:model.live="hold_bags" class="w-full bg-[#1C212E] border-gray-700 text-white text-sm" />
                    </div>
                </div>

                <div class="pt-3 border-t border-white/5 flex items-center justify-between text-sm">
                    <span class="text-gray-400">Estimated Zero Fuel Weight (ZFW):</span>
                    <span class="font-bold text-white font-mono text-base">{{ number_format($estimated_zfw) }} kg</span>
                </div>
            </div>
        @endif
    </div>

    <!-- SECTION 5: Network & Co-pilot -->
    <div class="bg-[#12161F] border border-white/10 rounded-xl overflow-hidden shadow-lg transition-all">
        <div wire:click="$toggle('showSectionNetwork')" class="px-6 py-4 bg-[#181D29] flex items-center justify-between cursor-pointer border-b border-white/5 hover:bg-[#1E2433]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold">
                    📡
                </div>
                <div>
                    <h3 class="font-bold text-white text-base">Network & Co-pilot</h3>
                    <p class="text-xs text-gray-400">{{ $network }} · {{ $copilot_user_id ? 'Shared Cockpit' : 'Solo' }}</p>
                </div>
            </div>
            <span class="text-gray-400">{{ $showSectionNetwork ? '∧' : '∨' }}</span>
        </div>

        @if($showSectionNetwork)
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <x-label for="network" value="{{ __('Network') }}" class="text-white font-medium" />
                            <div class="flex gap-2 text-xs">
                                <a href="{{ route('profile.preferences') }}" class="text-tenant-accent hover:underline">Preferred Network</a>
                            </div>
                        </div>
                        <select id="network" wire:model="network" class="w-full bg-[#1C212E] border-gray-700 text-white rounded-lg p-2.5 text-sm">
                            <option value="Offline">Offline</option>
                            <option value="VATSIM">VATSIM</option>
                            <option value="IVAO">IVAO</option>
                            <option value="POSCON">POSCON</option>
                            <option value="PILOTEDGE">PilotEdge</option>
                        </select>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <x-label for="copilot_user_id" value="{{ __('Shared Cockpit Co-Pilot') }}" class="text-white font-medium" />
                        </div>
                        <select id="copilot_user_id" wire:model="copilot_user_id" class="w-full bg-[#1C212E] border-gray-700 text-white rounded-lg p-2.5 text-sm">
                            <option value="">None (Solo Flight)</option>
                            @foreach($copilots as $pilot)
                                <option value="{{ $pilot->id }}">{{ $pilot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- JS Listener for SimBrief Pop-up Submission -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-simbrief-popup', () => {
                const form = document.getElementById('simbrief-popup-form');
                if (form) {
                    form.submit();
                }
            });
        });
    </script>
</div>
