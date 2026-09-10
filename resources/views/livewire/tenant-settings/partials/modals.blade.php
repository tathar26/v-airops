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
