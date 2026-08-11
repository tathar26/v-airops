<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->hasRole('Master Admin')) {
            return redirect()->route('admin.dashboard');
        }
        return view('dashboard');
    })->name('dashboard');

    Route::get('/admin', \App\Livewire\MasterAdminDashboard::class)
        ->middleware('role:Master Admin')
        ->name('admin.dashboard');

    Route::get('/fleet', \App\Livewire\FleetManager::class)
        ->middleware('role:VA Owner|Pilot')
        ->name('fleet');

    Route::get('/routes', \App\Livewire\RouteManager::class)
        ->middleware('role:VA Owner|Pilot')
        ->name('routes');

    Route::get('/aircraft-types', \App\Livewire\AircraftTypeManager::class)
        ->middleware('role:VA Owner|Pilot')
        ->name('aircraft-types');

    Route::get('/settings', \App\Livewire\TenantSettings::class)
        ->middleware('role:VA Owner')
        ->name('settings');
});
