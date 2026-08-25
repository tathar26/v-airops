<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Tenant;
use App\Models\User;
use App\Models\AirlineRole;
use App\Models\AirlinePermission;
use App\Models\UserAirlineRole;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantSettings extends Component
{
    use WithFileUploads;

    public $activeTab = 'general';

    // General Settings
    public $name = '';
    public $icao = '';
    public $secondary_icaos = [];
    public $newSecondaryIcao = '';
    public $callsign_mappings = [];
    public $newCallsignPrefix = '';
    public $newFlightNumberPrefix = '';
    public $newHubIcao = '';
    public $accent_color = '';
    public $bg_color = '';
    public $panel_bg_color = '';
    public $panel_text_color = '';
    public $card_bg_color = '';
    public $card_text_color = '';
    public $card_muted_text_color = '';
    public $button_bg_color = '';
    public $button_text_color = '';
    public $button_secondary_bg_color = '';
    public $button_secondary_text_color = '';
    public $input_bg_color = '';
    public $input_text_color = '';
    public $input_border_color = '';
    public $logo;
    public $default_simbrief_ofp_format = 'lido';

    // User Creation / Edit Modal
    public $showUserModal = false;
    public $editingUserId = null;
    public $userName = '';
    public $userEmail = '';
    public $userPassword = '';
    public $userRole = 'Pilot';

    // Role Management Modal
    public $showRoleModal = false;
    public $editingRoleId = null;
    public $roleName = '';
    public $roleSlug = '';
    public $roleDescription = '';
    public $roleHonoraryRank = '';
    public $roleIsStaff = false;
    public $roleIsDefault = false;
    public $selectedPermissions = [];

    // User Role Assignment Modal
    public $showUserRolesModal = false;
    public $managingUserId = null;
    public $managingUserName = '';
    public $userAssignedRoleIds = [];

    public $simbriefFormats = [];

    protected $queryString = [
        'activeTab' => ['except' => 'general', 'as' => 'tab'],
    ];

    public function mount($tab = null)
    {
        if ($tab) {
            $this->activeTab = $tab;
        }

        AirlinePermission::ensureDefaults();

        $tenant = auth()->user()->tenant;
        if (!$tenant) {
            $tenantId = auth()->user()->getActiveTenantId();
            $tenant = $tenantId ? Tenant::find($tenantId) : null;
        }

        if ($tenant) {
            $this->name = $tenant->name;
            $this->icao = $tenant->icao;
            $this->secondary_icaos = $tenant->secondary_icaos ?? [];
            $this->callsign_mappings = $tenant->getCallsignMappings();
            $this->accent_color = $tenant->accent_color ?? '#f97316';

            $this->bg_color = $tenant->bg_color ?? '#0f1117';
            $this->panel_bg_color = $tenant->panel_bg_color ?? $this->accent_color;
            $this->card_bg_color = $tenant->card_bg_color ?? $this->bg_color;

            $hexLuminance = function(?string $hex): float {
                if (!$hex) return 0.0;
                $hex = ltrim($hex, '#');
                if (strlen($hex) === 3) {
                    $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                }
                if (strlen($hex) !== 6) return 0.0;
                $r = hexdec(substr($hex, 0, 2)) / 255;
                $g = hexdec(substr($hex, 2, 2)) / 255;
                $b = hexdec(substr($hex, 4, 2)) / 255;
                return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
            };

            $this->panel_text_color = $tenant->panel_text_color ?? ($hexLuminance($this->panel_bg_color) > 0.5 ? '#0f172a' : '#f8fafc');
            $this->card_text_color = $tenant->card_text_color ?? ($hexLuminance($this->card_bg_color) > 0.5 ? '#0f172a' : '#ffffff');
            $this->card_muted_text_color = $tenant->card_muted_text_color ?? ($hexLuminance($this->card_bg_color) > 0.5 ? '#475569' : '#94a3b8');

            $this->button_bg_color = $tenant->button_bg_color ?? $this->accent_color;
            $this->button_text_color = $tenant->button_text_color ?? '#ffffff';
            $this->button_secondary_bg_color = $tenant->button_secondary_bg_color ?? '#1f2937';
            $this->button_secondary_text_color = $tenant->button_secondary_text_color ?? '#f3f4f6';
            $this->input_bg_color = $tenant->input_bg_color ?? '#0a0d14';
            $this->input_text_color = $tenant->input_text_color ?? '#ffffff';
            $this->input_border_color = $tenant->input_border_color ?? '#374151';
            $this->default_simbrief_ofp_format = $tenant->default_simbrief_ofp_format ?? 'lido';
        }

        // Fetch simbrief formats and cache for 24 hours
        $this->simbriefFormats = \Illuminate\Support\Facades\Cache::remember('simbrief_formats', 86400, function () {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get('http://www.simbrief.com/api/inputs.list.json');
                if ($response->successful()) {
                    $layouts = $response->json('layouts');
                    $formats = [];
                    foreach ($layouts as $key => $layout) {
                        $formats[$key] = $layout['name_long'];
                    }
                    asort($formats);
                    return $formats;
                }
            } catch (\Exception $e) {
                // Fallback
            }

            return [
                'lido' => 'LIDO - SimBrief Default',
                'ryr' => 'RYR - Ryanair',
                'aal' => 'AAL - American Airlines',
                'baw' => 'BAW - British Airways',
                'dal' => 'DAL - Delta Air Lines',
                'dlh' => 'DLH - Lufthansa',
                'ezy' => 'EZY - easyJet',
                'swa' => 'SWA - Southwest Airlines',
                'ual' => 'UAL - United Airlines',
            ];
        });
    }

    public function updatedRoleName($value)
    {
        if (!$this->editingRoleId && empty($this->roleSlug)) {
            $this->roleSlug = Str::slug($value);
        }
    }

    /* =========================================================================
     | Permission Matrix Category Definitions
     |======================================================================== */

    public function getPermissionCategories(): array
    {
        return [
            'fleet' => [
                'name' => 'Fleet Management',
                'description' => 'Aircraft types, airframes, and fleet configuration',
                'icon' => '✈️',
                'read' => ['view_fleet'],
                'write' => ['view_fleet', 'manage_fleet', 'manage_aircraft_types'],
                'all' => [
                    'view_fleet' => 'View Fleet & Airframes',
                    'manage_fleet' => 'Add/Edit/Delete Airframes',
                    'manage_aircraft_types' => 'Manage Aircraft Types & Specs',
                ],
            ],
            'routes' => [
                'name' => 'Route Network',
                'description' => 'Flight routes, schedules, and global imports',
                'icon' => '🗺️',
                'read' => ['view_routes'],
                'write' => ['view_routes', 'create_routes', 'edit_routes', 'delete_routes'],
                'all' => [
                    'view_routes' => 'View Routes & Schedules',
                    'create_routes' => 'Create Routes & Import Schedules',
                    'edit_routes' => 'Edit Routes & Assignments',
                    'delete_routes' => 'Delete Routes',
                ],
            ],
            'airports' => [
                'name' => 'Airports & Hubs',
                'description' => 'Operational base hubs and airport coordinates',
                'icon' => '🏢',
                'read' => ['view_airports'],
                'write' => ['view_airports', 'manage_airports'],
                'all' => [
                    'view_airports' => 'View Airport Network',
                    'manage_airports' => 'Add & Remove Base Hubs',
                ],
            ],
            'pireps' => [
                'name' => 'PIREPs (Pilot Reports)',
                'description' => 'Flight log submissions, review, and scoring',
                'icon' => '📋',
                'read' => ['view_pireps'],
                'write' => ['view_pireps', 'review_pireps', 'delete_pireps'],
                'all' => [
                    'view_pireps' => 'View Submitted Flight Reports',
                    'review_pireps' => 'Approve, Reject, or Score Reports',
                    'delete_pireps' => 'Delete Flight Reports',
                ],
            ],
            'notams' => [
                'name' => 'NOTAMs & Flight Bulletins',
                'description' => 'Pilot notices, operational bulletins, and flight restrictions',
                'icon' => '📢',
                'read' => ['view_notams'],
                'write' => ['view_notams', 'manage_notams'],
                'all' => [
                    'view_notams' => 'View NOTAMs & Bulletins',
                    'manage_notams' => 'Create, Edit & Delete NOTAMs',
                ],
            ],
            'settings' => [
                'name' => 'VA Settings & Administration',
                'description' => 'Branding, staff roles, pilot ranks, and airline configuration',
                'icon' => '⚙️',
                'read' => ['view_settings', 'view_finance'],
                'write' => ['view_settings', 'manage_airline_settings', 'manage_roles', 'manage_ranks', 'manage_pilots', 'view_finance'],
                'all' => [
                    'view_settings' => 'View Airline Configuration',
                    'manage_airline_settings' => 'Edit Airline Details & Theme Colors',
                    'manage_roles' => 'Create Custom Roles & Assign Permissions',
                    'manage_ranks' => 'Configure Pilot Ranks & Flight Hours',
                    'manage_pilots' => 'Manage Pilots & Callsigns',
                    'view_finance' => 'View Financial Statistics',
                ],
            ],
        ];
    }

    public function setCategoryPermissionLevel(string $categoryKey, string $level)
    {
        $categories = $this->getPermissionCategories();
        if (!isset($categories[$categoryKey])) {
            return;
        }

        $cat = $categories[$categoryKey];
        $allCategorySlugs = array_keys($cat['all']);

        // Remove existing category permissions from array
        $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $allCategorySlugs));

        if ($level === 'read') {
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $cat['read'])));
        } elseif ($level === 'write') {
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $cat['write'])));
        }
    }

    public function getCategoryCurrentLevel(string $categoryKey): string
    {
        $categories = $this->getPermissionCategories();
        if (!isset($categories[$categoryKey])) {
            return 'none';
        }

        $cat = $categories[$categoryKey];
        $selected = $this->selectedPermissions;

        $hasAllWrite = count(array_intersect($cat['write'], $selected)) === count($cat['write']);
        if ($hasAllWrite) {
            return 'write';
        }

        $hasAllRead = count(array_intersect($cat['read'], $selected)) === count($cat['read']);
        if ($hasAllRead && count(array_intersect(array_diff($cat['write'], $cat['read']), $selected)) === 0) {
            return 'read';
        }

        return 'custom';
    }

    public function selectAllPermissions()
    {
        $all = AirlinePermission::pluck('slug')->toArray();
        $this->selectedPermissions = array_values(array_unique($all));
    }

    public function selectAllReadPermissions()
    {
        $readSlugs = [];
        foreach ($this->getPermissionCategories() as $cat) {
            $readSlugs = array_merge($readSlugs, $cat['read']);
        }
        $this->selectedPermissions = array_values(array_unique($readSlugs));
    }

    public function clearPermissions()
    {
        $this->selectedPermissions = [];
    }

    /* =========================================================================
     | Role Management CRUD
     |======================================================================== */

    public function openCreateRoleModal()
    {
        $this->reset(['editingRoleId', 'roleName', 'roleSlug', 'roleDescription', 'roleHonoraryRank', 'roleIsStaff', 'roleIsDefault', 'selectedPermissions']);
        $this->resetErrorBag();
        $this->showRoleModal = true;
    }

    public function editRole(int $roleId)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $role = AirlineRole::with('permissions')
            ->where('tenant_id', $tenantId)
            ->findOrFail($roleId);

        $this->editingRoleId = $role->id;
        $this->roleName = $role->name;
        $this->roleSlug = $role->slug;
        $this->roleDescription = $role->description ?? '';
        $this->roleHonoraryRank = $role->honorary_rank_string ?? '';
        $this->roleIsStaff = (bool) $role->is_staff;
        $this->roleIsDefault = (bool) $role->is_default;
        $this->selectedPermissions = $role->permissions->pluck('slug')->toArray();

        $this->resetErrorBag();
        $this->showRoleModal = true;
    }

    public function saveRole()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;

        $this->validate([
            'roleName'         => 'required|string|max:100',
            'roleSlug'         => 'required|string|max:100|alpha_dash',
            'roleDescription'  => 'nullable|string|max:255',
            'roleHonoraryRank' => 'nullable|string|max:100',
            'roleIsStaff'      => 'boolean',
            'roleIsDefault'    => 'boolean',
        ]);

        $slug = Str::slug($this->roleSlug);

        // Check unique slug within this tenant
        $exists = AirlineRole::where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->when($this->editingRoleId, fn($q) => $q->where('id', '!=', $this->editingRoleId))
            ->exists();

        if ($exists) {
            $this->addError('roleSlug', "A role with the slug '{$slug}' already exists for this airline.");
            return;
        }

        if ($this->editingRoleId) {
            $role = AirlineRole::where('tenant_id', $tenantId)->findOrFail($this->editingRoleId);
            $role->update([
                'name'                 => $this->roleName,
                'slug'                 => $slug,
                'description'          => $this->roleDescription,
                'honorary_rank_string' => $this->roleHonoraryRank ?: null,
                'is_staff'             => (bool) $this->roleIsStaff,
                'is_default'           => (bool) $this->roleIsDefault,
            ]);
        } else {
            $role = AirlineRole::create([
                'tenant_id'            => $tenantId,
                'name'                 => $this->roleName,
                'slug'                 => $slug,
                'description'          => $this->roleDescription,
                'honorary_rank_string' => $this->roleHonoraryRank ?: null,
                'is_staff'             => (bool) $this->roleIsStaff,
                'is_default'           => (bool) $this->roleIsDefault,
            ]);
        }

        // If this role is set to default, unset others for this airline
        if ($this->roleIsDefault) {
            AirlineRole::where('tenant_id', $tenantId)
                ->where('id', '!=', $role->id)
                ->update(['is_default' => false]);
        }

        $role->syncPermissions($this->selectedPermissions);

        $this->showRoleModal = false;
        session()->flash('role_message', "Role '{$role->name}' saved successfully with updated permissions.");
    }

    public function deleteRole(int $roleId)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $role = AirlineRole::where('tenant_id', $tenantId)->findOrFail($roleId);

        $name = $role->name;
        $role->delete();

        session()->flash('role_message', "Role '{$name}' deleted successfully.");
    }

    /* =========================================================================
     | User & Staff Role Assignments
     |======================================================================== */

    public function openManageUserRolesModal(int $userId)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $user = User::findOrFail($userId);

        $this->managingUserId = $user->id;
        $this->managingUserName = $user->full_name;
        $this->userAssignedRoleIds = $user->getRolesForAirline($tenantId)->pluck('id')->map(fn($id) => (int)$id)->toArray();

        $this->showUserRolesModal = true;
    }

    public function saveUserRoles()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $user = User::findOrFail($this->managingUserId);

        // Delete current roles in this airline
        UserAirlineRole::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->delete();

        // Assign selected roles
        foreach ($this->userAssignedRoleIds as $roleId) {
            if ($roleId) {
                UserAirlineRole::create([
                    'user_id'   => $user->id,
                    'tenant_id' => $tenantId,
                    'role_id'   => (int) $roleId,
                ]);
            }
        }

        $this->showUserRolesModal = false;
        session()->flash('user_message', "Roles updated for {$user->full_name} successfully.");
    }

    public function removeQuickRole(int $userId, int $roleId)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        UserAirlineRole::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('role_id', $roleId)
            ->delete();

        session()->flash('user_message', 'Role unassigned successfully.');
    }

    /* =========================================================================
     | Standard Settings Methods
     |======================================================================== */

    public function addSecondaryIcao()
    {
        $this->validate([
            'newSecondaryIcao' => 'required|string|min:2|max:4|alpha',
        ]);

        $code = strtoupper(trim($this->newSecondaryIcao));
        $primary = strtoupper(trim($this->icao));

        if ($code === $primary) {
            $this->addError('newSecondaryIcao', "'{$code}' is already set as the primary airline ICAO.");
            return;
        }

        if (in_array($code, $this->secondary_icaos)) {
            $this->addError('newSecondaryIcao', "'{$code}' is already added as a secondary ICAO.");
            return;
        }

        $this->secondary_icaos[] = $code;
        $this->newSecondaryIcao = '';
        $this->resetErrorBag('newSecondaryIcao');
    }

    public function removeSecondaryIcao($index)
    {
        if (isset($this->secondary_icaos[$index])) {
            unset($this->secondary_icaos[$index]);
            $this->secondary_icaos = array_values($this->secondary_icaos);
        }
    }

    public function addCallsignMapping()
    {
        $this->validate([
            'newCallsignPrefix' => 'required|string|min:2|max:5|alpha_num',
            'newFlightNumberPrefix' => 'required|string|min:1|max:5|alpha_num',
        ]);

        $csPrefix = strtoupper(trim($this->newCallsignPrefix));
        $fnPrefix = strtoupper(trim($this->newFlightNumberPrefix));

        foreach ($this->callsign_mappings as $mapping) {
            if ($mapping['callsign_prefix'] === $csPrefix) {
                $this->addError('newCallsignPrefix', "Callsign prefix '{$csPrefix}' is already mapped to '{$mapping['flight_number_prefix']}'.");
                return;
            }
        }

        $this->callsign_mappings[] = [
            'callsign_prefix' => $csPrefix,
            'flight_number_prefix' => $fnPrefix,
        ];

        $this->newCallsignPrefix = '';
        $this->newFlightNumberPrefix = '';
        $this->resetErrorBag(['newCallsignPrefix', 'newFlightNumberPrefix']);
    }

    public function removeCallsignMapping($index)
    {
        if (isset($this->callsign_mappings[$index])) {
            unset($this->callsign_mappings[$index]);
            $this->callsign_mappings = array_values($this->callsign_mappings);
        }
    }

    public function saveSettings()
    {
        $tenant = auth()->user()->tenant;
        
        $this->validate([
            'name' => 'required|string|max:255',
            'icao' => 'required|string|min:2|max:4|alpha',
            'accent_color' => 'required|string|max:7',
            'bg_color' => 'required|string|max:7',
            'panel_bg_color' => 'nullable|string|max:7',
            'panel_text_color' => 'nullable|string|max:7',
            'card_bg_color' => 'nullable|string|max:7',
            'card_text_color' => 'nullable|string|max:7',
            'card_muted_text_color' => 'nullable|string|max:7',
            'button_bg_color' => 'nullable|string|max:7',
            'button_text_color' => 'nullable|string|max:7',
            'button_secondary_bg_color' => 'nullable|string|max:7',
            'button_secondary_text_color' => 'nullable|string|max:7',
            'input_bg_color' => 'nullable|string|max:7',
            'input_text_color' => 'nullable|string|max:7',
            'input_border_color' => 'nullable|string|max:7',
            'logo' => 'nullable|image|max:1024',
            'default_simbrief_ofp_format' => 'required|string|max:20',
        ]);

        $icaoUpper = strtoupper(trim($this->icao));

        $existing = Tenant::whereRaw('UPPER(icao) = ?', [$icaoUpper])
            ->where('id', '!=', $tenant->id)
            ->first();

        if ($existing) {
            $this->addError('icao', "The ICAO code '{$this->icao}' is already registered by '{$existing->name}'. Duplicate airlines are not permitted.");
            return;
        }

        $tenant->name = $this->name;
        $tenant->icao = $icaoUpper;
        $tenant->secondary_icaos = array_values(array_unique(array_filter($this->secondary_icaos)));
        $tenant->callsign_mappings = array_values($this->callsign_mappings);
        $tenant->accent_color = $this->accent_color;
        $tenant->bg_color = $this->bg_color;
        $tenant->panel_bg_color = $this->panel_bg_color ?: $this->accent_color;
        $tenant->panel_text_color = $this->panel_text_color ?: null;
        $tenant->card_bg_color = $this->card_bg_color ?: $this->bg_color;
        $tenant->card_text_color = $this->card_text_color ?: null;
        $tenant->card_muted_text_color = $this->card_muted_text_color ?: null;
        $tenant->button_bg_color = $this->button_bg_color ?: $this->accent_color;
        $tenant->button_text_color = $this->button_text_color ?: '#ffffff';
        $tenant->button_secondary_bg_color = $this->button_secondary_bg_color ?: '#1f2937';
        $tenant->button_secondary_text_color = $this->button_secondary_text_color ?: '#f3f4f6';
        $tenant->input_bg_color = $this->input_bg_color ?: '#0a0d14';
        $tenant->input_text_color = $this->input_text_color ?: '#ffffff';
        $tenant->input_border_color = $this->input_border_color ?: '#374151';
        $tenant->default_simbrief_ofp_format = $this->default_simbrief_ofp_format;

        if ($this->logo) {
            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $tenant->logo_path = $this->logo->store('logos', 'public');
        }

        $tenant->save();

        session()->flash('settings_message', 'Settings saved successfully.');
    }

    public function addHub()
    {
        $this->validate(['newHubIcao' => 'required|string|size:4']);
        $icao = strtoupper($this->newHubIcao);
        
        $airport = \App\Models\Airport::fetchAndCreate($icao);

        if ($airport) {
            \App\Models\TenantHub::firstOrCreate([
                'tenant_id' => auth()->user()->tenant_id,
                'airport_id' => $airport->id,
                'is_base' => true
            ]);
            $this->newHubIcao = '';
            session()->flash('hub_message', 'Base added successfully.');
        } else {
            $this->addError('newHubIcao', 'Airport not found or could not be fetched.');
        }
    }

    public function removeHub($hubId)
    {
        \App\Models\TenantHub::where('tenant_id', auth()->user()->tenant_id)
            ->where('id', $hubId)
            ->delete();
        session()->flash('hub_message', 'Base removed successfully.');
    }

    public function openUserModal()
    {
        $this->reset(['editingUserId', 'userName', 'userEmail', 'userPassword', 'userAssignedRoleIds']);
        $this->userRole = 'Pilot';
        $this->resetErrorBag();
        $this->showUserModal = true;
    }

    public function editUser($id)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $user = User::where(function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->orWhereHas('airlines', fn($sq) => $sq->where('tenants.id', $tenantId));
        })->findOrFail($id);

        $this->editingUserId = $user->id;
        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->userPassword = '';
        $this->userRole = $user->hasRole('VA Owner') ? 'VA Owner' : 'Pilot';
        $this->userAssignedRoleIds = $user->getRolesForAirline($tenantId)->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->resetErrorBag();
        $this->showUserModal = true;
    }

    public function saveUser()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;

        $rules = [
            'userName' => 'required|string|max:255',
            'userEmail' => 'required|email|max:255|unique:users,email' . ($this->editingUserId ? ',' . $this->editingUserId : ''),
            'userRole' => 'required|string|in:Pilot,VA Owner',
        ];

        if (!$this->editingUserId || $this->userPassword) {
            $rules['userPassword'] = 'required|string|min:8';
        }

        $this->validate($rules);

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->name = $this->userName;
            $user->email = $this->userEmail;
            if ($this->userPassword) {
                $user->password = Hash::make($this->userPassword);
            }
            $user->save();
            $user->syncRoles([$this->userRole]);
        } else {
            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => $this->userName,
                'email' => $this->userEmail,
                'password' => Hash::make($this->userPassword),
            ]);
            $user->assignRole($this->userRole);

            // Enroll in user_airlines
            \App\Models\UserAirline::firstOrCreate([
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
            ], [
                'callsign' => 'VOPS' . rand(100, 999),
                'join_date' => now(),
                'rank' => 'Cadet',
                'is_active' => true,
            ]);
        }

        // Sync custom airline roles
        UserAirlineRole::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->delete();

        foreach ($this->userAssignedRoleIds as $roleId) {
            if ($roleId) {
                UserAirlineRole::create([
                    'user_id'   => $user->id,
                    'tenant_id' => $tenantId,
                    'role_id'   => (int) $roleId,
                ]);
            }
        }

        $this->showUserModal = false;
        session()->flash('user_message', 'User saved successfully with assigned roles.');
    }

    public function deleteUser($id)
    {
        if (auth()->id() == $id) {
            session()->flash('user_message', 'You cannot delete yourself.');
            return;
        }

        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $user = User::findOrFail($id);
        
        // Remove from user_airlines and roles
        \App\Models\UserAirline::where('user_id', $user->id)->where('tenant_id', $tenantId)->delete();
        UserAirlineRole::where('user_id', $user->id)->where('tenant_id', $tenantId)->delete();

        if ($user->tenant_id === $tenantId) {
            $user->delete();
        }

        session()->flash('user_message', 'User removed from airline successfully.');
    }

    public function render()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;

        $users = User::where(function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->orWhereHas('airlines', fn($sq) => $sq->where('tenants.id', $tenantId));
        })->with([
            'airlineRoles' => fn($q) => $q->where('tenant_id', $tenantId),
            'userAirlines' => fn($q) => $q->where('tenant_id', $tenantId),
            'pilotProfiles' => fn($q) => $q->where('tenant_id', $tenantId),
        ])->get();

        $roles = AirlineRole::with(['permissions', 'users'])->where('tenant_id', $tenantId)->get();
        $hubs = \App\Models\TenantHub::with('airport')->where('tenant_id', $tenantId)->where('is_base', true)->get();
        $allPermissions = AirlinePermission::all()->groupBy('group');

        return view('livewire.tenant-settings', [
            'users'          => $users,
            'roles'          => $roles,
            'hubs'           => $hubs,
            'allPermissions' => $allPermissions,
            'categories'     => $this->getPermissionCategories(),
        ])->layout('layouts.app');
    }
}

