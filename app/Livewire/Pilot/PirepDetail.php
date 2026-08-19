<?php

namespace App\Livewire\Pilot;

use Livewire\Component;
use App\Models\Pirep;

class PirepDetail extends Component
{
    public $pirep;

    public function mount(Pirep $pirep)
    {
        if ($pirep->user_id !== auth()->id()) {
            abort(403);
        }
        $this->pirep = $pirep;
    }

    public $newComment = '';

    public function render()
    {
        return view('livewire.pilot.pirep-detail')->layout('layouts.app');
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

        // Leaving a PIREP Comment automatically sends PIREP for staff review
        $this->pirep->update(['status' => 'awaiting_review']);

        $this->newComment = '';
        $this->pirep->load('comments.user');
        session()->flash('message', 'Comment submitted. Your PIREP has been sent to VA staff for review.');
    }
}
