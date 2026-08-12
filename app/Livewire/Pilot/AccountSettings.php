<?php
namespace App\Livewire\Pilot;
use Livewire\Component;

class AccountSettings extends Component
{
    public $activeTab = 'account';

    // Account fields
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $name = '';

    // Social/Network fields
    public $discord_id = '';
    public $vatsim_id = '';
    public $ivao_id = '';
    public $poscon_id = '';
    public $apoc_cid = '';
    public $twitch_username = '';
    public $youtube_username = '';

    public function mount()
    {
        $user = auth()->user();
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->name = $user->name;

        $this->discord_id = $user->discord_id;
        $this->vatsim_id = $user->vatsim_id;
        $this->ivao_id = $user->ivao_id;
        $this->poscon_id = $user->poscon_id;
        $this->apoc_cid = $user->apoc_cid;
        $this->twitch_username = $user->twitch_username;
        $this->youtube_username = $user->youtube_username;
    }

    public function saveAccount()
    {
        $this->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'name' => 'required|string|max:255',
        ]);

        auth()->user()->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'name' => $this->name,
        ]);

        session()->flash('account_message', 'Account details updated successfully.');
    }

    public function saveSocial()
    {
        $this->validate([
            'vatsim_id' => 'nullable|string|max:20',
            'ivao_id' => 'nullable|string|max:20',
            'poscon_id' => 'nullable|string|max:20',
            'apoc_cid' => 'nullable|string|max:20',
            'twitch_username' => 'nullable|string|max:255',
            'youtube_username' => 'nullable|string|max:255',
        ]);

        auth()->user()->update([
            'vatsim_id' => $this->vatsim_id,
            'ivao_id' => $this->ivao_id,
            'poscon_id' => $this->poscon_id,
            'apoc_cid' => $this->apoc_cid,
            'twitch_username' => $this->twitch_username,
            'youtube_username' => $this->youtube_username,
        ]);

        session()->flash('social_message', 'Social networks updated successfully.');
    }

    public function render()
    {
        return view('livewire.pilot.account-settings')->layout('layouts.app');
    }
}
