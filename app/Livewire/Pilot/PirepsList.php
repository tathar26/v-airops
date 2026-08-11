<?php
namespace App\Livewire\Pilot;
use Livewire\Component;
use App\Models\Pirep;

class PirepsList extends Component
{
    public function render()
    {
        $pireps = Pirep::where('user_id', auth()->id())
            ->with(['route', 'airframe'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('livewire.pilot.pireps-list', [
            'pireps' => $pireps
        ])->layout('layouts.app');
    }
}
