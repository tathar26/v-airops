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
