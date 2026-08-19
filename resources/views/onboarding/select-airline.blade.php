<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airline Selection & Creation | Virtual Airline Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Outfit', sans-serif; } </style>
</head>
<body class="min-h-full bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-black p-6 md:p-12"
      x-data="{ showCreateModal: {{ ($errors->any() && (old('name') || old('icao') || session('show_create_modal'))) ? 'true' : 'false' }}, accentColor: '{{ old('accent_color', '#f97316') }}', bgColor: '{{ old('bg_color', '#1e1e1e') }}' }">

    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Header -->
        <div class="text-center space-y-3">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-sky-500/10 border border-sky-500/20 text-sky-400 text-xs font-semibold uppercase tracking-widest">
                <span class="w-2 h-2 rounded-full bg-sky-400 animate-ping"></span>
                Pilot Airline Selection
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-white">Select or Create Your Virtual Airline</h1>
            <p class="text-slate-400 text-sm md:text-base max-w-xl mx-auto">
                Welcome, <span class="text-white font-semibold">{{ $user->full_name }}</span>! Join one or more virtual airlines to fly immediately, or create your own virtual airline platform.
            </p>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm text-center">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('general'))
            <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm text-center">
                {{ $errors->first('general') }}
            </div>
        @endif

        <!-- Action Grid -->
        <div class="space-y-6">
            <!-- Create Airline Banner Card -->
            <div class="p-6 rounded-3xl bg-gradient-to-r from-purple-900/40 via-indigo-900/30 to-slate-900 border border-purple-500/30 shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="space-y-1 text-center md:text-left">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold uppercase tracking-wider">
                        ✨ Start Your Own Fleet
                    </div>
                    <h3 class="text-xl font-extrabold text-white">Want to manage your own Virtual Airline?</h3>
                    <p class="text-slate-300 text-sm max-w-lg">
                        Create your own airline in seconds. Set up custom branding, base hub, automatic ranks, routes, and manage pilots.
                    </p>
                </div>
                <button type="button" @click="showCreateModal = true"
                    class="py-3.5 px-6 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold shadow-lg shadow-purple-600/30 hover:shadow-purple-600/50 transition-all duration-200 whitespace-nowrap transform hover:-translate-y-0.5 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Create Virtual Airline
                </button>
            </div>

            <!-- Existing Airlines Header -->
            <div class="flex items-center justify-between pt-4">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <span>🛫</span> Available Virtual Airlines to Join
                </h2>
                <span class="text-xs text-slate-400 font-mono">{{ $airlines->count() }} Registered</span>
            </div>

            <!-- Join Form -->
            <form action="{{ route('onboarding.join') }}" method="POST" class="space-y-8">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse ($airlines as $airline)
                        @php
                            $isAlreadyJoined = in_array($airline->id, $joinedAirlineIds);
                        @endphp
                        <label class="relative block cursor-pointer group">
                            <input type="checkbox" name="airline_ids[]" value="{{ $airline->id }}" {{ $isAlreadyJoined ? 'checked disabled' : '' }} class="peer sr-only">
                            
                            <div class="h-full p-6 rounded-3xl bg-slate-900/80 border border-slate-800 peer-checked:border-sky-500 peer-checked:bg-slate-900 peer-checked:ring-2 peer-checked:ring-sky-500/30 transition-all duration-200 hover:border-slate-700 shadow-xl flex flex-col justify-between space-y-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-4">
                                        @if ($airline->logo_path)
                                            <img src="{{ asset('storage/' . $airline->logo_path) }}" alt="{{ $airline->name }}" class="w-12 h-12 object-contain rounded-xl bg-slate-950 p-2 border border-slate-800">
                                        @else
                                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-800 to-slate-950 flex items-center justify-center font-bold text-sky-400 border border-slate-700 font-mono">
                                                {{ strtoupper($airline->icao ?: 'VA') }}
                                            </div>
                                        @endif

                                        <div>
                                            <h3 class="text-lg font-bold text-white group-hover:text-sky-400 transition-colors">{{ $airline->name }}</h3>
                                            <p class="text-xs text-slate-400 font-mono">ICAO: {{ strtoupper($airline->icao ?: 'VA') }}</p>
                                        </div>
                                    </div>

                                    <div class="w-6 h-6 rounded-full border border-slate-700 peer-checked:border-sky-500 peer-checked:bg-sky-500 flex items-center justify-center transition-all">
                                        <svg class="w-4 h-4 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between text-xs">
                                    <span class="text-slate-400">Callsign Preview</span>
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-sky-400 font-mono font-semibold">
                                        {{ strtoupper($airline->icao ?: 'VA') }} (Auto-Assigned)
                                    </span>
                                </div>
                            </div>
                        </label>
                    @empty
                        <div class="md:col-span-2 p-12 text-center rounded-3xl bg-slate-900/40 border border-dashed border-slate-800 space-y-3">
                            <p class="text-slate-400 text-sm">No virtual airlines have been created yet.</p>
                            <button type="button" @click="showCreateModal = true"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold">
                                + Create the First Virtual Airline
                            </button>
                        </div>
                    @endforelse
                </div>

                @if($airlines->isNotEmpty())
                <div class="flex justify-center pt-4">
                    <button type="submit"
                        class="py-4 px-10 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 text-white font-bold shadow-xl shadow-sky-500/25 hover:shadow-sky-500/40 transition-all duration-200 transform hover:-translate-y-0.5">
                        Confirm & Enter Dashboard →
                    </button>
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Create Virtual Airline Modal -->
    <div x-show="showCreateModal" x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div @click="showCreateModal = false" class="fixed inset-0 transition-opacity" style="background-color: rgba(0, 0, 0, 0.85) !important; backdrop-filter: blur(8px);"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <!-- Modal Body (100% Solid Opaque Dark Background) -->
            <div class="inline-block align-bottom rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
                 style="background-color: #0f172a !important; color: #ffffff !important; border: 1px solid #334155 !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8) !important;">
                <form action="{{ route('onboarding.create-va') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="px-6 py-5 flex items-center justify-between" style="background-color: #020617 !important; border-bottom: 1px solid #1e293b !important;">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-2xl" style="background-color: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3);">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold" style="color: #ffffff !important;">Create New Virtual Airline</h3>
                                <p class="text-xs" style="color: #94a3b8 !important;">Configure your airline settings, base hub, and branding.</p>
                            </div>
                        </div>
                        <button type="button" @click="showCreateModal = false" class="transition-colors" style="color: #94a3b8;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#94a3b8'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto" style="background-color: #0f172a !important;">
                        <!-- Airline Name & ICAO -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    Airline Name <span style="color: #f87171;">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Delta Virtual Airlines"
                                    class="w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition-all"
                                    style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                                @error('name') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    Airline ICAO <span style="color: #f87171;">*</span>
                                </label>
                                <input type="text" name="icao" value="{{ old('icao') }}" required placeholder="e.g. DAL" maxlength="4"
                                    class="w-full rounded-xl px-3.5 py-2.5 font-mono uppercase text-sm outline-none transition-all"
                                    style="background-color: #1e293b !important; color: #38bdf8 !important; font-weight: 700; border: 1px solid #334155 !important;">
                                @error('icao') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Base Hub ICAO & SimBrief Format -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    Base Hub Airport (ICAO) <span style="color: #f87171;">*</span>
                                </label>
                                <input type="text" name="base_hub_icao" value="{{ old('base_hub_icao') }}" required placeholder="e.g. KATL, EGLL, EHAM" maxlength="4"
                                    class="w-full rounded-xl px-3.5 py-2.5 font-mono uppercase text-sm outline-none transition-all"
                                    style="background-color: #1e293b !important; color: #c084fc !important; font-weight: 700; border: 1px solid #334155 !important;">
                                <p class="text-[11px] mt-1" style="color: #94a3b8 !important;">Airport will be automatically imported as primary hub.</p>
                                @error('base_hub_icao') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    SimBrief OFP Format <span style="color: #f87171;">*</span>
                                </label>
                                <select name="default_simbrief_ofp_format" required
                                    class="w-full rounded-xl px-3.5 py-2.5 text-sm outline-none transition-all"
                                    style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                                    @foreach($simbriefFormats as $key => $label)
                                        <option value="{{ $key }}" {{ old('default_simbrief_ofp_format') == $key ? 'selected' : '' }} style="background-color: #1e293b; color: #ffffff;">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('default_simbrief_ofp_format') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Theme Colors -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl" style="background-color: #020617 !important; border: 1px solid #1e293b !important;">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    Accent Color <span style="color: #f87171;">*</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color" x-model="accentColor" name="accent_color" class="h-10 w-14 rounded-lg bg-transparent cursor-pointer p-1" style="border: 1px solid #334155 !important;">
                                    <input type="text" x-model="accentColor" class="w-full rounded-xl px-3 py-2 font-mono text-xs uppercase" readonly
                                        style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                                </div>
                                @error('accent_color') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                    Background Color <span style="color: #f87171;">*</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color" x-model="bgColor" name="bg_color" class="h-10 w-14 rounded-lg bg-transparent cursor-pointer p-1" style="border: 1px solid #334155 !important;">
                                    <input type="text" x-model="bgColor" class="w-full rounded-xl px-3 py-2 font-mono text-xs uppercase" readonly
                                        style="background-color: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important;">
                                </div>
                                @error('bg_color') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color: #cbd5e1 !important;">
                                Airline Logo (Optional)
                            </label>
                            <input type="file" name="logo" accept="image/*"
                                class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:cursor-pointer"
                                style="color: #94a3b8; background-color: #1e293b; border: 1px solid #334155; border-radius: 0.75rem; padding: 0.5rem;">
                            @error('logo') <span class="text-xs mt-1 block" style="color: #f87171 !important;">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="px-6 py-4 flex items-center justify-end gap-3" style="background-color: #020617 !important; border-top: 1px solid #1e293b !important;">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                            style="background-color: #1e293b; color: #94a3b8; border: 1px solid #334155;">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg transition-all flex items-center gap-2"
                            style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important; color: #ffffff !important; border: none;">
                            <span>Create & Launch Airline →</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
