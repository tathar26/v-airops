<?php

namespace App\Livewire\Admin;

use App\Models\Notam;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class NotamManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedCategory = 'all';
    public string $selectedPriority = 'all';

    // Modal properties for Create/Edit
    public bool $showNotamModal = false;
    public ?int $editingNotamId = null;
    public string $title = '';
    public string $body = '';
    public string $priority = 'Medium';
    public string $category = 'Operations';
    public string $customCategory = '';
    public bool $isPermanent = true;
    public ?string $expiresAt = null;
    public bool $isActive = true;

    // Pilot Read Statistics Modal
    public bool $showReadsModal = false;
    public ?int $viewingNotamId = null;

    protected $rules = [
        'title'      => 'required|string|max:255',
        'body'       => 'required|string',
        'priority'   => 'required|in:Low,Medium,High',
        'category'   => 'required|string|max:100',
        'expiresAt'  => 'nullable|date',
        'isActive'   => 'boolean',
    ];

    public function mount()
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        if (!$user->hasAirlinePermission('manage_notams', $tenantId)) {
            abort(403, 'Unauthorized. You do not have permission to manage NOTAMs for this airline.');
        }
    }

    public function openCreateModal()
    {
        $this->reset([
            'editingNotamId',
            'title',
            'body',
            'priority',
            'category',
            'customCategory',
            'isPermanent',
            'expiresAt',
            'isActive',
        ]);
        $this->priority = 'Medium';
        $this->category = 'Operations';
        $this->isPermanent = true;
        $this->isActive = true;
        $this->resetErrorBag();
        $this->showNotamModal = true;
    }

    public function editNotam(int $id)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $notam = Notam::where('tenant_id', $tenantId)->findOrFail($id);

        $this->editingNotamId = $notam->id;
        $this->title = $notam->title;
        $this->body = $notam->body;
        $this->priority = $notam->priority;
        $this->category = in_array($notam->category, ['Operations', 'Fleet', 'Training', 'Briefings', 'General']) ? $notam->category : 'Other';
        if ($this->category === 'Other') {
            $this->customCategory = $notam->category;
        } else {
            $this->customCategory = '';
        }
        $this->isPermanent = is_null($notam->expires_at);
        $this->expiresAt = $notam->expires_at ? $notam->expires_at->format('Y-m-d\TH:i') : null;
        $this->isActive = $notam->is_active;

        $this->resetErrorBag();
        $this->showNotamModal = true;
    }

    public function saveNotam()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;

        $finalCategory = $this->category === 'Other' && !empty($this->customCategory)
            ? trim($this->customCategory)
            : $this->category;

        $this->category = $finalCategory;

        $this->validate();

        $expires = null;
        if (!$this->isPermanent && !empty($this->expiresAt)) {
            $expires = \Carbon\Carbon::parse($this->expiresAt);
        }

        if ($this->editingNotamId) {
            $notam = Notam::where('tenant_id', $tenantId)->findOrFail($this->editingNotamId);
            $notam->update([
                'title'      => $this->title,
                'body'       => $this->body,
                'priority'   => $this->priority,
                'category'   => $finalCategory,
                'expires_at' => $expires,
                'is_active'  => $this->isActive,
            ]);
            session()->flash('notam_manager_message', 'NOTAM updated successfully.');
        } else {
            Notam::create([
                'tenant_id'  => $tenantId,
                'author_id'  => auth()->id(),
                'title'      => $this->title,
                'body'       => $this->body,
                'priority'   => $this->priority,
                'category'   => $finalCategory,
                'expires_at' => $expires,
                'posted_at'  => now(),
                'is_active'  => $this->isActive,
            ]);
            session()->flash('notam_manager_message', 'New NOTAM published successfully.');
        }

        $this->showNotamModal = false;
        $this->reset([
            'editingNotamId',
            'title',
            'body',
            'priority',
            'category',
            'customCategory',
            'isPermanent',
            'expiresAt',
            'isActive',
        ]);
    }

    public function deleteNotam(int $id)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $notam = Notam::where('tenant_id', $tenantId)->findOrFail($id);

        $notam->delete();
        session()->flash('notam_manager_message', "NOTAM '{$notam->title}' deleted successfully.");
    }

    public function viewReads(int $id)
    {
        $this->viewingNotamId = $id;
        $this->showReadsModal = true;
    }

    public function render()
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;

        $query = Notam::with(['author', 'reads.user'])
            ->where('tenant_id', $tenantId);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('body', 'like', "%{$this->search}%")
                  ->orWhere('category', 'like', "%{$this->search}%");
            });
        }

        if ($this->selectedCategory !== 'all') {
            $query->where('category', $this->selectedCategory);
        }

        if ($this->selectedPriority !== 'all') {
            $query->where('priority', $this->selectedPriority);
        }

        $notams = $query->orderByDesc('posted_at')->paginate(12);

        // Pilot count for this virtual airline
        $totalPilots = User::where(function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->orWhereHas('airlines', fn($sq) => $sq->where('tenants.id', $tenantId));
        })->count();

        // Active viewing NOTAM for reads modal
        $viewingNotam = null;
        if ($this->viewingNotamId) {
            $viewingNotam = Notam::with(['reads.user'])->where('tenant_id', $tenantId)->find($this->viewingNotamId);
        }

        return view('livewire.admin.notam-manager', [
            'notams'       => $notams,
            'totalPilots'  => $totalPilots,
            'viewingNotam' => $viewingNotam,
        ])->layout('layouts.app');
    }
}
