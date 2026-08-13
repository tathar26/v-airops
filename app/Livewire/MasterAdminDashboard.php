<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Pirep;
use App\Models\SystemGlobalFlight;
use App\Models\SystemGlobalAirline;
use App\Models\SystemGlobalAirport;
use App\Models\SystemGlobalAircraft;
use App\Models\SystemGlobalAirframe;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;

class MasterAdminDashboard extends Component
{
    public $batchId = null;

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
        $tenants = Tenant::withCount('users')->get();
        $totalUsers = User::count();
        $totalFlights = Pirep::count();

        $totalGlobalFlights  = SystemGlobalFlight::count();
        $totalGlobalAirlines = SystemGlobalAirline::count();

        return view('livewire.master-admin-dashboard', [
            'tenants'             => $tenants,
            'totalUsers'          => $totalUsers,
            'totalFlights'        => $totalFlights,
            'totalGlobalFlights'  => $totalGlobalFlights,
            'totalGlobalAirlines' => $totalGlobalAirlines,
            'currentBatch'        => $this->batch,
        ])->layout('layouts.app');
    }
}
