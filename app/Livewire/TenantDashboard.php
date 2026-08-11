<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\Airframe;
use App\Models\Route;
use App\Models\Pirep;

class TenantDashboard extends Component
{
    public function render()
    {
        $tenantId = auth()->user()->tenant_id;
        
        $fleetCount = Airframe::where('tenant_id', $tenantId)->count();
        $routeCount = Route::where('tenant_id', $tenantId)->count();
        $recentPireps = Pirep::with(['user', 'route', 'airframe'])
                            ->where('tenant_id', $tenantId)
                            ->orderBy('created_at', 'desc')
                            ->take(5)
                            ->get();
        $totalPireps = Pirep::where('tenant_id', $tenantId)->count();

        return view('livewire.tenant-dashboard', [
            'fleetCount' => $fleetCount,
            'routeCount' => $routeCount,
            'totalPireps' => $totalPireps,
            'recentPireps' => $recentPireps,
        ]);
    }
}
