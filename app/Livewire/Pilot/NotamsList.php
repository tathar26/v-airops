<?php

namespace App\Livewire\Pilot;

use App\Models\Notam;
use Livewire\Component;
use Livewire\WithPagination;

class NotamsList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedCategory = 'all';
    public string $selectedPriority = 'all';
    public string $statusFilter = 'all'; // 'all', 'unread', 'read'
    public int $perPage = 10;

    public ?int $selectedNotamId = null;
    public bool $showNotamModal = false;

    protected $queryString = [
        'search'           => ['except' => ''],
        'selectedCategory' => ['except' => 'all'],
        'selectedPriority' => ['except' => 'all'],
        'statusFilter'     => ['except' => 'all'],
        'page'             => ['except' => 1],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory()
    {
        $this->resetPage();
    }

    public function updatingSelectedPriority()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function openNotam(int $id)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $notam = Notam::where('tenant_id', $tenantId)->findOrFail($id);

        $this->selectedNotamId = $notam->id;
        $this->showNotamModal = true;

        // Automatically record read when pilot opens the NOTAM
        $notam->markAsReadBy(auth()->id());
    }

    public function acknowledgeNotam(int $id)
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        $notam = Notam::where('tenant_id', $tenantId)->findOrFail($id);

        $notam->markAsReadBy(auth()->id());
        $this->showNotamModal = false;
        $this->selectedNotamId = null;

        session()->flash('notam_acknowledged', "NOTAM '{$notam->title}' acknowledged.");
    }

    public function closeModal()
    {
        $this->showNotamModal = false;
        $this->selectedNotamId = null;
    }

    public function render()
    {
        $user = auth()->user();
        $tenantId = $user->getActiveTenantId() ?? $user->tenant_id;

        $query = Notam::with(['reads' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }])
            ->where('tenant_id', $tenantId)
            ->active();

        // Search query
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('body', 'like', "%{$this->search}%")
                  ->orWhere('category', 'like', "%{$this->search}%");
            });
        }

        // Category filter
        if ($this->selectedCategory !== 'all') {
            $query->where('category', $this->selectedCategory);
        }

        // Priority filter
        if ($this->selectedPriority !== 'all') {
            $query->where('priority', $this->selectedPriority);
        }

        // Status filter (Read / Unread)
        if ($this->statusFilter === 'unread') {
            $query->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($this->statusFilter === 'read') {
            $query->whereHas('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Sorting: High priority first, then latest posted
        $notams = $query->orderByRaw("CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 ELSE 4 END")
            ->orderByDesc('posted_at')
            ->paginate($this->perPage);

        // Fetch unique categories for filter dropdown
        $categories = Notam::where('tenant_id', $tenantId)
            ->active()
            ->pluck('category')
            ->unique()
            ->sort()
            ->values();

        // Active selected NOTAM for modal display
        $selectedNotam = null;
        if ($this->selectedNotamId) {
            $selectedNotam = Notam::with(['reads' => fn($q) => $q->where('user_id', $user->id), 'author'])
                ->where('tenant_id', $tenantId)
                ->find($this->selectedNotamId);
        }

        $unreadCount = $user->getUnreadNotamsCount($tenantId);

        return view('livewire.pilot.notams-list', [
            'notams'        => $notams,
            'categories'    => $categories,
            'selectedNotam' => $selectedNotam,
            'unreadCount'   => $unreadCount,
        ])->layout('layouts.app');
    }
}
