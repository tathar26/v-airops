<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Tenant;
use App\Models\User;
use App\Models\AirlineRole;
use App\Models\AirlinePermission;
use App\Models\Rank;
use App\Models\TenantHub;
use App\Livewire\Concerns\WithTenantGeneralSettings;
use App\Livewire\Concerns\WithTenantRolesAndPermissions;
use App\Livewire\Concerns\WithTenantUserManagement;
use App\Livewire\Concerns\WithTenantScoringSettings;

class TenantSettings extends Component
{
    use WithFileUploads;
    use WithTenantGeneralSettings;
    use WithTenantRolesAndPermissions;
    use WithTenantUserManagement;
    use WithTenantScoringSettings;

    public $activeTab = 'general';

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

        $this->mountGeneralSettings($tenant);

        if ($tenant) {
            $this->loadScoringSettings($tenant);
        }
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
            'pilotProfiles' => fn($q) => $q->where('tenant_id', $tenantId)->with(['rank', 'honoraryRank']),
        ])->get();

        $roles = AirlineRole::with(['permissions', 'users'])->where('tenant_id', $tenantId)->get();
        $hubs = TenantHub::with('airport')->where('tenant_id', $tenantId)->where('is_base', true)->get();
        $honoraryRanks = Rank::where('tenant_id', $tenantId)->honorary()->orderBy('position')->orderBy('name')->get();

        $allPermissions = AirlinePermission::whereNull('tenant_id')
            ->orWhere('tenant_id', $tenantId)
            ->get()
            ->groupBy('group');

        $customPermissions = AirlinePermission::where('tenant_id', $tenantId)
            ->withCount('roles')
            ->get();

        return view('livewire.tenant-settings', [
            'tenantId'          => $tenantId,
            'users'             => $users,
            'roles'             => $roles,
            'hubs'              => $hubs,
            'honoraryRanks'     => $honoraryRanks,
            'allPermissions'    => $allPermissions,
            'customPermissions' => $customPermissions,
            'categories'        => $this->getPermissionCategories(),
        ])->layout('layouts.app');
    }
}
