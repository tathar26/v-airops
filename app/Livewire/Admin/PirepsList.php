<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Pirep;

class PirepsList extends Component
{
    public function render()
    {
        $pireps = Pirep::where('tenant_id', auth()->user()->tenant_id)
            ->with(['user', 'route', 'airframe'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('livewire.admin.pireps-list', [
            'pireps' => $pireps
        ])->layout('layouts.app');
    }
}
