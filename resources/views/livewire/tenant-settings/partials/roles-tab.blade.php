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
