<?php
namespace App\Livewire\Pilot;
use Livewire\Component;

class Statistics extends Component
{
    public function render()
    {
        return view('livewire.pilot.statistics')->layout('layouts.app');
    }
}
