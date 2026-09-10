<?php

namespace App\Livewire\Concerns;

use App\Models\PilotProfile;
use App\Models\User;
use App\Models\UserAirline;
use App\Models\UserAirlineRole;
use Illuminate\Support\Facades\Hash;

trait WithTenantUserManagement
{
    // User Creation / Edit Modal
    public $showUserModal = false;
    public $editingUserId = null;
    public $userName = '';
    public $userEmail = '';
    public $userPassword = '';
    public $userRole = 'Pilot';

    // User Role Assignment Modal
    public $showUserRolesModal = false;
    public $managingUserId = null;
    public $managingUserName = '';
    public $userAssignedRoleIds = [];
    public $managingUserHonoraryRankId = null;

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

        $profile = PilotProfile::where('user_id', $user->id)->where('tenant_id', $tenantId)->first();
        $this->managingUserHonoraryRankId = $profile?->honorary_rank_id;

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

        // Save honorary rank on pilot profile
        $profile = PilotProfile::where('user_id', $user->id)->where('tenant_id', $tenantId)->first();
        if ($profile) {
            $profile->honorary_rank_id = $this->managingUserHonoraryRankId ? (int)$this->managingUserHonoraryRankId : null;
            $profile->save();
        }

        $this->showUserRolesModal = false;
        session()->flash('user_message', "Roles and honorary rank updated for {$user->full_name} successfully.");
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
            UserAirline::firstOrCreate([
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
        UserAirline::where('user_id', $user->id)->where('tenant_id', $tenantId)->delete();
        UserAirlineRole::where('user_id', $user->id)->where('tenant_id', $tenantId)->delete();

        if ($user->tenant_id === $tenantId) {
            $user->delete();
        }

        session()->flash('user_message', 'User removed from airline successfully.');
    }
}
