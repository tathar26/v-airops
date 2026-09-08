<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Rank;
use App\Models\Tenant;
use App\Services\RankProgressionService;

class RankManager extends Component
{
    use WithFileUploads;

    public $regularRanks;
    public $honoraryRanks;
    public $showModal = false;
    public $editingRankId = null;

    // Form inputs
    public $name = '';
    public $abbreviation = '';
    public $position = 1;
    public $min_hours = 0;
    public $min_points = 0;
    public $min_bonus_points = 0;
    public $min_pireps = 0;
    public $is_honorary = false;
    public $is_default = false;
    public $image_path = '';
    public $epaulette_upload = null;
    public $selected_builtin_epaulette = '';

    public function mount()
    {
        $this->ensureDefaultRanks();
        $this->loadRanks();
    }

    public function ensureDefaultRanks()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant && Rank::where('tenant_id', $tenantId)->count() === 0) {
                RankProgressionService::seedDefaultRanks($tenant);
            }
        }
    }

    public function loadRanks()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;

        $this->regularRanks = Rank::where('tenant_id', $tenantId)
            ->where('is_honorary', false)
            ->orderBy('position', 'asc')
            ->orderBy('min_hours', 'asc')
            ->orderBy('min_points', 'asc')
            ->get();

        $this->honoraryRanks = Rank::where('tenant_id', $tenantId)
            ->where('is_honorary', true)
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function openModal($isHonorary = false)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $nextPos = (int) (Rank::where('tenant_id', $tenantId)->where('is_honorary', $isHonorary)->max('position') ?? 0) + 1;

        $this->reset([
            'editingRankId',
            'name',
            'abbreviation',
            'min_hours',
            'min_points',
            'min_bonus_points',
            'min_pireps',
            'epaulette_upload',
            'image_path',
        ]);

        $this->is_honorary = (bool) $isHonorary;
        $this->is_default = false;
        $this->position = $nextPos;
        $this->selected_builtin_epaulette = $isHonorary ? 'epaulettes/epaulette-staff.png' : 'epaulettes/epaulette-01.png';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function editRank($id)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $rank = Rank::where('tenant_id', $tenantId)->findOrFail($id);

        $this->editingRankId = $rank->id;
        $this->name = $rank->name;
        $this->abbreviation = $rank->abbreviation ?? '';
        $this->position = $rank->position;
        $this->min_hours = $rank->min_hours;
        $this->min_points = $rank->min_points;
        $this->min_bonus_points = $rank->min_bonus_points;
        $this->min_pireps = $rank->min_pireps;
        $this->is_honorary = (bool) $rank->is_honorary;
        $this->is_default = (bool) $rank->is_default;
        $this->image_path = $rank->image_path ?? '';
        $this->selected_builtin_epaulette = $rank->image_path ?? '';
        $this->epaulette_upload = null;

        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function saveRank()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;

        $this->validate([
            'name'             => 'required|string|max:100',
            'abbreviation'     => 'nullable|string|max:10',
            'position'         => 'required|integer|min:1|max:9999',
            'min_hours'        => 'required|integer|min:0',
            'min_points'       => 'required|integer|min:0',
            'min_bonus_points' => 'required|integer|min:0',
            'min_pireps'       => 'required|integer|min:0',
            'is_honorary'      => 'required|boolean',
            'epaulette_upload' => 'nullable|image|max:2048',
        ]);

        $imagePath = $this->image_path;

        // Custom image upload
        if ($this->epaulette_upload) {
            $imagePath = $this->epaulette_upload->store('epaulettes/custom', 'public');
        } elseif ($this->selected_builtin_epaulette) {
            $imagePath = $this->selected_builtin_epaulette;
        }

        if (!$imagePath) {
            $imagePath = $this->is_honorary 
                ? 'epaulettes/epaulette-staff.png' 
                : 'epaulettes/epaulette-01.png';
        }

        if ($this->editingRankId) {
            $rank = Rank::where('tenant_id', $tenantId)->findOrFail($this->editingRankId);
            
            $rank->update([
                'name'             => $this->name,
                'abbreviation'     => strtoupper(trim($this->abbreviation)),
                'position'         => $this->position,
                'min_hours'        => $this->is_honorary ? 0 : $this->min_hours,
                'min_points'       => $this->is_honorary ? 0 : $this->min_points,
                'min_bonus_points' => $this->is_honorary ? 0 : $this->min_bonus_points,
                'min_pireps'       => $this->is_honorary ? 0 : $this->min_pireps,
                'is_honorary'      => $rank->is_default ? $rank->is_honorary : $this->is_honorary,
                'image_path'       => $imagePath,
            ]);

            $msg = "Rank '{$rank->name}' updated successfully.";
        } else {
            $rank = Rank::create([
                'tenant_id'        => $tenantId,
                'name'             => $this->name,
                'abbreviation'     => strtoupper(trim($this->abbreviation)),
                'position'         => $this->position,
                'min_hours'        => $this->is_honorary ? 0 : $this->min_hours,
                'min_points'       => $this->is_honorary ? 0 : $this->min_points,
                'min_bonus_points' => $this->is_honorary ? 0 : $this->min_bonus_points,
                'min_pireps'       => $this->is_honorary ? 0 : $this->min_pireps,
                'is_honorary'      => $this->is_honorary,
                'is_default'       => false,
                'image_path'       => $imagePath,
            ]);

            $msg = "Rank '{$rank->name}' created successfully.";
        }

        $this->showModal = false;
        $this->loadRanks();
        session()->flash('rank_message', $msg);
    }

    public function deleteRank($id)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $rank = Rank::where('tenant_id', $tenantId)->findOrFail($id);

        if ($rank->is_default) {
            session()->flash('rank_error', "Default rank '{$rank->name}' cannot be deleted as it is required for airline operations.");
            return;
        }

        $name = $rank->name;
        $rank->delete();
        $this->loadRanks();
        session()->flash('rank_message', "Rank '{$name}' deleted successfully.");
    }

    public function moveRankUp($id)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $current = Rank::where('tenant_id', $tenantId)->findOrFail($id);

        $previous = Rank::where('tenant_id', $tenantId)
            ->where('is_honorary', $current->is_honorary)
            ->where('position', '<', $current->position)
            ->orderBy('position', 'desc')
            ->first();

        if ($previous) {
            $prevPos = $previous->position;
            $previous->position = $current->position;
            $current->position = $prevPos;
            $previous->save();
            $current->save();
            $this->loadRanks();
        }
    }

    public function moveRankDown($id)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $current = Rank::where('tenant_id', $tenantId)->findOrFail($id);

        $next = Rank::where('tenant_id', $tenantId)
            ->where('is_honorary', $current->is_honorary)
            ->where('position', '>', $current->position)
            ->orderBy('position', 'asc')
            ->first();

        if ($next) {
            $nextPos = $next->position;
            $next->position = $current->position;
            $current->position = $nextPos;
            $next->save();
            $current->save();
            $this->loadRanks();
        }
    }

    public function render()
    {
        return view('livewire.rank-manager', [
            'builtinEpaulettes' => Rank::availableBuiltinEpaulettes(),
        ]);
    }
}
