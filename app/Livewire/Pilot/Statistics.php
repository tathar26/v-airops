<?php
namespace App\Livewire\Pilot;

use Livewire\Component;
use App\Jobs\RecalculatePilotStatistics;
use App\Models\UserStatistic;

class Statistics extends Component
{
    public function mount()
    {
        // Automatically sync and recalculate pilot statistics with fresh data
        if (auth()->check()) {
            RecalculatePilotStatistics::dispatchSync(auth()->id());
        }
    }

    public function refreshStatistics()
    {
        if (auth()->check()) {
            RecalculatePilotStatistics::dispatchSync(auth()->id());
            session()->flash('message', 'Statistics refreshed successfully.');
        }
    }

    public function render()
    {
        $stats = UserStatistic::where('user_id', auth()->id())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first();

        return view('livewire.pilot.statistics', [
            'stats' => $stats
        ])->layout('layouts.app');
    }
}
