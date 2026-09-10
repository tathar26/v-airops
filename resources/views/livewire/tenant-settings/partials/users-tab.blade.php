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
                                            @if($user->getDisplayRankImageUrl($tenantId ?? null))
                                                <img src="{{ $user->getDisplayRankImageUrl($tenantId ?? null) }}" alt="{{ $user->getDisplayRank($tenantId ?? null) }}" class="w-[70px] h-[30px] object-contain rounded border border-white/10 bg-slate-900/60 shadow-sm" />
                                            @endif
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-tenant-accent/20 text-tenant-accent border border-tenant-accent/30">
                                                        {{ $user->getDisplayRank($tenantId ?? null) }}
                                                    </span>
                                                    @if($user->isDisplayingHonoraryRank($tenantId ?? null))
                                                        <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30" title="Displaying honorary staff rank">Honorary</span>
                                                    @endif
                                                </div>
                                                @php
                                                    $profile = $user->getPilotProfile($tenantId ?? null);
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
                                                $assignedRoles = $user->getRolesForAirline($tenantId ?? null);
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
