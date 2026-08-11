<?php
namespace App\Livewire\Pilot;
use Livewire\Component;

class Preferences extends Component
{
    public function render()
    {
        return view('livewire.pilot.preferences')->layout('layouts.app');
    }
}
