<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Jobs\SendVirtualAirlineApprovedEmailJob;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Pirep;
use App\Services\ScheduleImportService;
use App\Services\VirtualAirlineCreationService;

class MasterAdminDashboard extends Component
{
    use WithFileUploads;

    // Create New VA Modal Properties
    public $showCreateVaModal = false;
    public $vaName = '';
    public $vaIcao = '';
    public $vaBaseHubIcao = '';
    public $vaAccentColor = '#f97316';
    public $vaBgColor = '#1e1e1e';
    public $vaSimbriefFormat = 'lido';
    public $vaLogo = null;

    public function openCreateVaModal()
    {
        $this->reset(['vaName', 'vaIcao', 'vaBaseHubIcao', 'vaLogo']);
        $this->vaAccentColor = '#f97316';
        $this->vaBgColor = '#1e1e1e';
        $this->vaSimbriefFormat = 'lido';
        $this->resetErrorBag();
        $this->showCreateVaModal = true;
    }

    public function closeCreateVaModal()
    {
        $this->showCreateVaModal = false;
        $this->resetErrorBag();
    }

    public function createVirtualAirline(?VirtualAirlineCreationService $vaService = null)
    {
        $vaService = $vaService ?: app(VirtualAirlineCreationService::class);

        $this->validate([
            'vaName' => 'required|string|max:255',
            'vaIcao' => 'required|string|min:2|max:4|alpha',
            'vaBaseHubIcao' => 'required|string|size:4|alpha',
            'vaAccentColor' => 'required|string|max:7',
            'vaBgColor' => 'required|string|max:7',
            'vaSimbriefFormat' => 'required|string|max:20',
            'vaLogo' => 'nullable|image|max:1024',
        ], [
            'vaName.required' => 'The Virtual Airline name is required.',
            'vaIcao.required' => 'The Airline ICAO code is required (e.g. EZY, BAW).',
            'vaBaseHubIcao.required' => 'A Base Hub airport ICAO code is required (e.g. EGLL, KJFK).',
            'vaBaseHubIcao.size' => 'The Base Hub ICAO code must be exactly 4 characters.',
        ]);

        try {
            $tenant = $vaService->createVirtualAirline([
                'name' => $this->vaName,
                'icao' => strtoupper($this->vaIcao),
                'base_hub_icao' => strtoupper($this->vaBaseHubIcao),
                'accent_color' => $this->vaAccentColor,
                'bg_color' => $this->vaBgColor,
                'default_simbrief_ofp_format' => $this->vaSimbriefFormat,
            ], auth()->user(), $this->vaLogo);

            $this->showCreateVaModal = false;
            $this->reset(['vaName', 'vaIcao', 'vaBaseHubIcao', 'vaLogo']);
            session()->flash('message', "Virtual Airline '{$tenant->name}' created successfully with full default settings and base hub!");
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $msg) {
                    $this->addError($field === 'base_hub_icao' ? 'vaBaseHubIcao' : ($field === 'icao' ? 'vaIcao' : $field), $msg);
                }
            }
        } catch (\Throwable $e) {
            $this->addError('general', 'Failed to create Virtual Airline: ' . $e->getMessage());
        }
    }

    public function approveVirtualAirline(int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->is_approved = true;
        $tenant->status = 'active';
        $tenant->approved_by = auth()->id();
        $tenant->approved_at = now();
        $tenant->save();

        if ($tenant->created_by) {
            $owner = User::find($tenant->created_by);
            if ($owner) {
                SendVirtualAirlineApprovedEmailJob::dispatch($tenant, $owner);
            }
        }

        session()->flash('message', "Virtual Airline '{$tenant->name}' ({$tenant->icao}) approved successfully! Notification email queued for owner.");
    }

    public function rejectVirtualAirline(int $tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->status = 'rejected';
        $tenant->is_approved = false;
        $tenant->save();

        session()->flash('message', "Virtual Airline '{$tenant->name}' ({$tenant->icao}) has been marked as rejected.");
    }

    public function render()
    {
        $pendingTenants = Tenant::with(['creator', 'hubs.airport'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $activeTenants = Tenant::withCount('users')
            ->where('status', '!=', 'pending')
            ->get();

        $totalUsers = User::count();
        $totalFlights = Pirep::count();

        // Query Live Global Network API Health & Statistics
        $globalNetworkStats = [
            'connected' => false,
            'active_schedules' => 0,
            'airports_count' => 0,
            'database_status' => 'Unknown',
            'api_url' => config('services.schedules_api.base_url', 'https://schedules.artmex-hosting.com'),
            'error' => null,
        ];

        try {
            $service = app(ScheduleImportService::class);
            $health = $service->healthCheck();
            $globalNetworkStats['connected'] = true;
            $globalNetworkStats['active_schedules'] = $health['active_flights_count'] ?? 0;
            $globalNetworkStats['airports_count'] = $health['airports_seeded'] ?? 0;
            $globalNetworkStats['database_status'] = $health['database'] ?? 'Connected';
        } catch (\Throwable $e) {
            $globalNetworkStats['connected'] = false;
            $globalNetworkStats['error'] = $e->getMessage();
        }

        $simbriefFormats = [
            'lido' => 'LIDO - SimBrief Default',
            'ryr'  => 'RYR - Ryanair',
            'aal'  => 'AAL - American Airlines',
            'baw'  => 'BAW - British Airways',
            'dal'  => 'DAL - Delta Air Lines',
            'dlh'  => 'DLH - Lufthansa',
            'ezy'  => 'EZY - easyJet',
            'swa'  => 'SWA - Southwest Airlines',
            'ual'  => 'UAL - United Airlines',
        ];

        return view('livewire.master-admin-dashboard', [
            'pendingTenants'      => $pendingTenants,
            'tenants'             => $activeTenants,
            'totalUsers'          => $totalUsers,
            'totalFlights'        => $totalFlights,
            'globalNetworkStats'  => $globalNetworkStats,
            'simbriefFormats'     => $simbriefFormats,
        ])->layout('layouts.app');
    }
}
