<?php

namespace App\Livewire\Concerns;

use App\Models\AirlinePermission;
use App\Models\AirlineRole;
use Illuminate\Support\Str;

trait WithTenantRolesAndPermissions
{
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

    // Custom Permissions Management Modal
    public $showPermissionModal = false;
    public $editingPermissionId = null;
    public $permissionName = '';
    public $permissionSlug = '';
    public $permissionGroup = 'Custom Operations';
    public $permissionDescription = '';

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
            'pireps' => [
                'name' => 'PIREP Operations',
                'description' => 'Pilot reports, manual submissions, and validation',
                'icon' => '📋',
                'read' => ['view_pireps'],
                'write' => ['view_pireps', 'file_pireps', 'validate_pireps', 'delete_pireps'],
                'all' => [
                    'view_pireps' => 'View PIREPs & Flight Logs',
                    'file_pireps' => 'Submit Manual PIREPs',
                    'validate_pireps' => 'Approve/Reject Pending PIREPs',
                    'delete_pireps' => 'Delete Filed PIREPs',
                ],
            ],
            'live_flights' => [
                'name' => 'Live Flight Operations',
                'description' => 'Real-time ACARS tracking radar and flight monitor',
                'icon' => '📡',
                'read' => ['view_live_flights'],
                'write' => ['view_live_flights'],
                'all' => [
                    'view_live_flights' => 'Monitor Real-Time Active ACARS Flights',
                ],
            ],
            'notams' => [
                'name' => 'NOTAM Bulletins',
                'description' => 'Operational notices, company bulletins, and safety alerts',
                'icon' => '📢',
                'read' => ['view_notams'],
                'write' => ['view_notams', 'manage_notams'],
                'all' => [
                    'view_notams' => 'Read Company NOTAMs',
                    'manage_notams' => 'Create, Edit & Delete NOTAMs',
                ],
            ],
            'settings' => [
                'name' => 'Administration & Configuration',
                'description' => 'Airline identity, scoring rules, roles, and ranks',
                'icon' => '⚙️',
                'read' => ['view_settings', 'view_finance'],
                'write' => ['view_settings', 'manage_airline_settings', 'manage_scoring', 'manage_roles', 'manage_ranks', 'manage_pilots', 'view_finance'],
                'all' => [
                    'view_settings' => 'View Airline Configuration',
                    'manage_airline_settings' => 'Edit Airline Details & Theme Colors',
                    'manage_scoring' => 'Configure PIREP Scoring Rules',
                    'manage_roles' => 'Create Custom Roles & Assign Permissions',
                    'manage_ranks' => 'Configure Pilot Ranks & Flight Hours',
                    'manage_pilots' => 'Manage Pilots & Callsigns',
                    'view_finance' => 'View Financial Statistics',
                ],
            ],
        ];

        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        if ($tenantId) {
            $customPerms = AirlinePermission::where('tenant_id', $tenantId)->get();
            if ($customPerms->isNotEmpty()) {
                $customAll = [];
                $customSlugs = [];
                foreach ($customPerms as $cp) {
                    $customAll[$cp->slug] = $cp->name . ($cp->description ? " - {$cp->description}" : '');
                    $customSlugs[] = $cp->slug;
                }
                $categories['custom'] = [
                    'name' => 'Custom Airline Permissions',
                    'description' => 'Custom granular permissions created specifically for your airline',
                    'icon' => '✨',
                    'read' => [],
                    'write' => $customSlugs,
                    'all' => $customAll,
                ];
            }
        }

        return $categories;
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
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $all = AirlinePermission::where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id');
            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId);
            }
        })->pluck('slug')->toArray();
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
     | Custom Permission Management
     |======================================================================== */

    public function updatedPermissionName($value)
    {
        if (!$this->editingPermissionId && empty($this->permissionSlug)) {
            $this->permissionSlug = Str::slug($value, '_');
        }
    }

    public function openCreatePermissionModal()
    {
        $this->reset(['editingPermissionId', 'permissionName', 'permissionSlug', 'permissionGroup', 'permissionDescription']);
        $this->permissionGroup = 'Custom Operations';
        $this->resetErrorBag();
        $this->showPermissionModal = true;
    }

    public function editCustomPermission(int $permissionId)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $permission = AirlinePermission::where('tenant_id', $tenantId)->findOrFail($permissionId);

        $this->editingPermissionId = $permission->id;
        $this->permissionName = $permission->name;
        $this->permissionSlug = $permission->slug;
        $this->permissionGroup = $permission->group ?? 'Custom Operations';
        $this->permissionDescription = $permission->description ?? '';

        $this->resetErrorBag();
        $this->showPermissionModal = true;
    }

    public function saveCustomPermission()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;

        $this->validate([
            'permissionName'        => 'required|string|max:100',
            'permissionSlug'        => 'required|string|max:100|alpha_dash',
            'permissionGroup'       => 'required|string|max:50',
            'permissionDescription' => 'nullable|string|max:255',
        ]);

        $slug = Str::slug($this->permissionSlug, '_');

        // Check if slug conflicts with global system permissions
        $globalConflict = AirlinePermission::whereNull('tenant_id')
            ->where('slug', $slug)
            ->exists();

        if ($globalConflict) {
            $this->addError('permissionSlug', "The slug '{$slug}' is a reserved system permission name.");
            return;
        }

        // Check unique within this tenant
        $tenantConflict = AirlinePermission::where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->when($this->editingPermissionId, fn($q) => $q->where('id', '!=', $this->editingPermissionId))
            ->exists();

        if ($tenantConflict) {
            $this->addError('permissionSlug', "A custom permission with slug '{$slug}' already exists in your airline.");
            return;
        }

        if ($this->editingPermissionId) {
            $permission = AirlinePermission::where('tenant_id', $tenantId)->findOrFail($this->editingPermissionId);
            $permission->update([
                'name'        => $this->permissionName,
                'slug'        => $slug,
                'group'       => $this->permissionGroup,
                'description' => $this->permissionDescription,
            ]);
            $msg = "Custom permission '{$permission->name}' updated successfully.";
        } else {
            $permission = AirlinePermission::create([
                'tenant_id'   => $tenantId,
                'name'        => $this->permissionName,
                'slug'        => $slug,
                'group'       => $this->permissionGroup,
                'description' => $this->permissionDescription,
            ]);
            $msg = "Custom permission '{$permission->name}' created successfully.";
        }

        $this->showPermissionModal = false;
        session()->flash('permission_message', $msg);
    }

    public function deleteCustomPermission(int $permissionId)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $permission = AirlinePermission::where('tenant_id', $tenantId)->findOrFail($permissionId);

        $name = $permission->name;
        $permission->roles()->detach();
        $permission->delete();

        session()->flash('permission_message', "Custom permission '{$name}' deleted successfully.");
    }
}
