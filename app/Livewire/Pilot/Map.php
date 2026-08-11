<?php

namespace App\Livewire\Pilot;

use Livewire\Component;

class Map extends Component
{
    public function render()
    {
        return view('livewire.pilot.map')->layout('layouts.app');
    }
}
