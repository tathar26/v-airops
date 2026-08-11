<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Rank;

class RankManager extends Component
{
    public $ranks;
    public $showModal = false;
    public $editingRankId = null;
    
    public $name = '';
    public $min_hours = 0;
    public $min_points = 0;
    
    public function mount()
    {
        $this->loadRanks();
    }

    public function loadRanks()
    {
        $this->ranks = Rank::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('min_hours')
            ->orderBy('min_points')
            ->get();
    }

    public function openModal()
    {
        $this->reset(['editingRankId', 'name', 'min_hours', 'min_points']);
        $this->showModal = true;
    }

    public function editRank($id)
    {
        $rank = Rank::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $this->editingRankId = $rank->id;
        $this->name = $rank->name;
        $this->min_hours = $rank->min_hours;
        $this->min_points = $rank->min_points;
        $this->showModal = true;
    }

    public function saveRank()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'min_hours' => 'required|integer|min:0',
            'min_points' => 'required|integer|min:0',
        ]);

        if ($this->editingRankId) {
            $rank = Rank::where('tenant_id', auth()->user()->tenant_id)->findOrFail($this->editingRankId);
            $rank->update([
                'name' => $this->name,
                'min_hours' => $this->min_hours,
                'min_points' => $this->min_points,
            ]);
        } else {
            Rank::create([
                'tenant_id' => auth()->user()->tenant_id,
                'name' => $this->name,
                'min_hours' => $this->min_hours,
                'min_points' => $this->min_points,
            ]);
        }

        $this->showModal = false;
        $this->loadRanks();
        session()->flash('rank_message', 'Rank saved successfully.');
    }

    public function deleteRank($id)
    {
        Rank::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id)->delete();
        $this->loadRanks();
        session()->flash('rank_message', 'Rank deleted successfully.');
    }

    public function render()
    {
        return view('livewire.rank-manager');
    }
}
