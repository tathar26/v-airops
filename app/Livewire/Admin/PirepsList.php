<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Pirep;

class PirepsList extends Component
{
    public function render()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $pireps = Pirep::where('tenant_id', $tenantId)
            ->with(['user', 'route', 'airframe'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('livewire.admin.pireps-list', [
            'pireps' => $pireps
        ])->layout('layouts.app');
    }
}
