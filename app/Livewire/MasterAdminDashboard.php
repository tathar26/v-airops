<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Pirep;

class MasterAdminDashboard extends Component
{
    public function render()
    {
        $tenants = Tenant::withCount('users')->get();
        $totalUsers = User::count();
        $totalFlights = Pirep::count();

        return view('livewire.master-admin-dashboard', [
            'tenants' => $tenants,
            'totalUsers' => $totalUsers,
            'totalFlights' => $totalFlights,
        ])->layout('layouts.app');
    }
}
