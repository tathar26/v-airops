<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Pirep;
use App\Models\Airframe;
use App\Models\Route;

class PirepsList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterDepIcao = '';
    public $filterArrIcao = '';
    public $filterFleet = '';
    public $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterDepIcao' => ['except' => ''],
        'filterArrIcao' => ['except' => ''],
        'filterFleet' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterDepIcao()
    {
        $this->resetPage();
    }

    public function updatingFilterArrIcao()
    {
        $this->resetPage();
    }

    public function updatingFilterFleet()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterStatus', 'filterDepIcao', 'filterArrIcao', 'filterFleet']);
        $this->resetPage();
    }

    protected function getActiveTenantId(): int
    {
        $tenantId = auth()->user()->getActiveTenantId() ?? auth()->user()->tenant_id;
        if (!$tenantId) {
            $tenantId = \App\Models\Tenant::first()?->id ?? 1;
        }
        return (int) $tenantId;
    }

    public function render()
    {
        $tenantId = $this->getActiveTenantId();

        $query = Pirep::where('tenant_id', $tenantId)
            ->with(['user', 'route', 'airframe.aircraftType']);

        if (!empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%")
                  ->orWhere('touchdown_rate_fpm', 'like', "%{$s}%")
                  ->orWhereHas('user', function ($uq) use ($s) {
                      $uq->where('name', 'like', "%{$s}%")
                         ->orWhere('email', 'like', "%{$s}%")
                         ->orWhere('callsign', 'like', "%{$s}%");
                  })
                  ->orWhereHas('route', function ($rq) use ($s) {
                      $rq->where('flight_number', 'like', "%{$s}%")
                         ->orWhere('callsign', 'like', "%{$s}%")
                         ->orWhere('departure_icao', 'like', "%{$s}%")
                         ->orWhere('arrival_icao', 'like', "%{$s}%");
                  })
                  ->orWhereHas('airframe', function ($aq) use ($s) {
                      $aq->where('registration', 'like', "%{$s}%")
                         ->orWhere('name', 'like', "%{$s}%")
                         ->orWhereHas('aircraftType', function ($tq) use ($s) {
                             $tq->where('code', 'like', "%{$s}%")
                                ->orWhere('name', 'like', "%{$s}%");
                         });
                  });
            });
        }

        if (!empty($this->filterStatus)) {
            if ($this->filterStatus === 'accepted') {
                $query->whereIn('status', ['accepted', 'complete', 'approved']);
            } else {
                $query->where('status', $this->filterStatus);
            }
        }

        if (!empty($this->filterDepIcao)) {
            $dep = strtoupper(trim($this->filterDepIcao));
            $query->whereHas('route', function ($rq) use ($dep) {
                $rq->where('departure_icao', $dep);
            });
        }

        if (!empty($this->filterArrIcao)) {
            $arr = strtoupper(trim($this->filterArrIcao));
            $query->whereHas('route', function ($rq) use ($arr) {
                $rq->where('arrival_icao', $arr);
            });
        }

        if (!empty($this->filterFleet)) {
            $fleetId = $this->filterFleet;
            $query->where('airframe_id', $fleetId);
        }

        $pireps = $query->orderBy('created_at', 'desc')->paginate($this->perPage);
        $totalPirepsCount = Pirep::where('tenant_id', $tenantId)->count();

        // Autocomplete list
        $recentPireps = Pirep::where('tenant_id', $tenantId)
            ->with(['user', 'route', 'airframe'])
            ->latest()
            ->take(100)
            ->get();

        $autocompleteList = $recentPireps->pluck('user.name')
            ->merge($recentPireps->pluck('route.flight_number'))
            ->merge($recentPireps->pluck('route.callsign'))
            ->merge($recentPireps->pluck('route.departure_icao'))
            ->merge($recentPireps->pluck('route.arrival_icao'))
            ->merge($recentPireps->pluck('airframe.registration'))
            ->filter()
            ->unique()
            ->values()
            ->take(80)
            ->toArray();

        // Distinct list of Airframes and Route airports for filters
        $airframes = Airframe::where('tenant_id', $tenantId)->with('aircraftType')->orderBy('registration')->get();
        $allDepIcaos = Route::where('tenant_id', $tenantId)->distinct()->orderBy('departure_icao')->pluck('departure_icao');
        $allArrIcaos = Route::where('tenant_id', $tenantId)->distinct()->orderBy('arrival_icao')->pluck('arrival_icao');

        return view('livewire.admin.pireps-list', [
            'pireps' => $pireps,
            'totalPirepsCount' => $totalPirepsCount,
            'autocompleteList' => $autocompleteList,
            'airframes' => $airframes,
            'allDepIcaos' => $allDepIcaos,
            'allArrIcaos' => $allArrIcaos,
        ])->layout('layouts.app');
    }
}
