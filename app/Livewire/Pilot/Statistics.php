<?php
namespace App\Livewire\Pilot;
use Livewire\Component;

class Statistics extends Component
{
    public function render()
    {
        $stats = \App\Models\UserStatistic::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first();

        return view('livewire.pilot.statistics', [
            'stats' => $stats
        ])->layout('layouts.app');
    }
}
