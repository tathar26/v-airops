<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Jobs\SendVirtualAirlineApprovedEmailJob;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Pirep;
use App\Models\SystemGlobalFlight;
use App\Models\SystemGlobalAirline;
use App\Models\SystemGlobalAirport;
use App\Models\SystemGlobalAircraft;
use App\Models\SystemGlobalAirframe;
use App\Services\VirtualAirlineCreationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;

class MasterAdminDashboard extends Component
{
    use WithFileUploads;

    public $batchId = null;

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

    public function clearGlobalNetwork()
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('system_global_flights')->truncate();
            DB::table('system_global_airlines')->truncate();
            DB::table('system_global_airports')->truncate();
            DB::table('system_global_aircraft')->truncate();
            DB::table('system_global_airframes')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            session()->flash('message', 'Global network database has been completely cleared.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to clear global network database: ' . $e->getMessage());
        }
    }

    public function rebuildGlobalNetwork()
    {
        try {
            Artisan::call('system:aggregate-airline-data');
            
            // Look for the latest batch dispatched with name 'Aggregate Global Network Data'
            $batchRecord = DB::table('job_batches')
                ->where('name', 'Aggregate Global Network Data')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($batchRecord) {
                $this->batchId = $batchRecord->id;
            }

            session()->flash('message', 'Global network build job dispatched successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to start rebuild job: ' . $e->getMessage());
        }
    }

    public function getBatchProperty()
    {
        if (!$this->batchId) {
            return null;
        }

        return Bus::findBatch($this->batchId);
    }

    public function updateBatchProgress()
    {
        $batch = $this->batch;
        
        if ($batch && $batch->finished()) {
            $this->batchId = null;
            session()->flash('message', 'Global network build completed successfully!');
        }
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

        $totalGlobalFlights  = SystemGlobalFlight::count();
        $totalGlobalAirlines = SystemGlobalAirline::count();

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
            'totalGlobalFlights'  => $totalGlobalFlights,
            'totalGlobalAirlines' => $totalGlobalAirlines,
            'currentBatch'        => $this->batch,
            'simbriefFormats'     => $simbriefFormats,
        ])->layout('layouts.app');
    }
}
