<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Pirep;

class PirepDetail extends Component
{
    public $pirep;

    public function mount(Pirep $pirep)
    {
        if ($pirep->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        $this->pirep = $pirep;
    }

    public function render()
    {
        return view('livewire.admin.pirep-detail')->layout('layouts.app');
    }

    public $newComment = '';

    public function accept()
    {
        $this->pirep->update(['status' => 'Accepted']);
        \App\Jobs\RecalculatePilotStatistics::dispatch($this->pirep->user_id);
        session()->flash('message', 'PIREP has been Accepted.');
    }

    public function invalidate()
    {
        $this->pirep->update(['status' => 'Invalidated']);
        \App\Jobs\RecalculatePilotStatistics::dispatch($this->pirep->user_id);
        session()->flash('message', 'PIREP has been Invalidated.');
    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|string|max:1000'
        ]);

        \App\Models\PirepComment::create([
            'pirep_id' => $this->pirep->id,
            'user_id' => auth()->id(),
            'comment' => $this->newComment
        ]);

        $this->newComment = '';
        $this->pirep->load('comments.user');
        session()->flash('message', 'Comment added successfully.');
    }
}
