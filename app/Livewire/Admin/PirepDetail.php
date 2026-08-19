<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Pirep;
use App\Jobs\RecalculatePilotStatistics;

class PirepDetail extends Component
{
    public $pirep;
    public $newComment = '';
    public $requestReplyReason = '';

    public function mount(Pirep $pirep)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        if ($pirep->tenant_id !== $tenantId && !auth()->user()->is_admin) {
            abort(403);
        }
        $this->pirep = $pirep;
    }

    public function render()
    {
        return view('livewire.admin.pirep-detail')->layout('layouts.app');
    }

    public function accept()
    {
        $this->pirep->update(['status' => 'accepted']);
        RecalculatePilotStatistics::dispatchSync($this->pirep->user_id);
        $this->pirep->refresh();
        session()->flash('message', 'PIREP has been Accepted. Hours and Points have been awarded.');
    }

    public function reject()
    {
        $this->pirep->update(['status' => 'rejected']);
        RecalculatePilotStatistics::dispatchSync($this->pirep->user_id);
        $this->pirep->refresh();
        session()->flash('message', 'PIREP has been Rejected. Flight hours awarded, but 0 points awarded.');
    }

    public function invalidate()
    {
        $this->pirep->update(['status' => 'invalidated']);
        RecalculatePilotStatistics::dispatchSync($this->pirep->user_id);
        $this->pirep->refresh();
        session()->flash('message', 'PIREP has been Invalidated. No hours and no points awarded.');
    }

    public function requestReply()
    {
        $this->validate([
            'requestReplyReason' => 'required|string|max:1000'
        ]);

        \App\Models\PirepComment::create([
            'pirep_id' => $this->pirep->id,
            'user_id' => auth()->id(),
            'comment' => '[STAFF ACTION: Reply Needed] ' . $this->requestReplyReason
        ]);

        $this->pirep->update(['status' => 'reply_needed']);
        $this->requestReplyReason = '';
        $this->pirep->load('comments.user');
        session()->flash('message', 'PIREP marked as Reply Needed. Pilot must respond before starting new flights.');
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
