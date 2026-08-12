<?php

namespace App\Livewire\Pilot;

use Livewire\Component;
use App\Models\Booking;
use App\Services\SimBriefService;

class Dispatch extends Component
{
    public Booking $booking;
    
    public $airframe_id;
    public $cost_index = 'AUTO';
    public $pax = 'AUTO';
    public $freight = 'AUTO';
    public $altitude = 'AUTO';
    
    public $generating = false;

    public function mount(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $this->booking = $booking->load(['route', 'tenant']);
        $this->airframe_id = $booking->airframe_id;
    }

    public function generateSimbrief()
    {
        $this->validate([
            'airframe_id' => 'required|exists:airframes,id'
        ]);

        $this->generating = true;
        
        // SimBrief generation logic to be added
        
        $this->generating = false;
    }

    public function render()
    {
        $fleet = \App\Models\Airframe::where('tenant_id', auth()->user()->tenant_id)->get();
        return view('livewire.pilot.dispatch', compact('fleet'))
            ->layout('layouts.app');
    }
}
