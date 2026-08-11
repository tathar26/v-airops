<?php
namespace App\Livewire\Pilot;
use Livewire\Component;

class AccountSettings extends Component
{
    public function render()
    {
        return view('livewire.pilot.account-settings')->layout('layouts.app');
    }
}
