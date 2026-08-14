<?php

use App\Http\Controllers\Api\FleetController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FlightController;
use App\Http\Controllers\Api\V1\AcarsController as V1AcarsController;
use App\Http\Controllers\Api\V1\PirepController as V1PirepController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| vPilot ACARS REST API v1
|--------------------------------------------------------------------------
| Conforming strictly to the REST schemas and endpoints in vops-acars
*/
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected ACARS Endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        
        // Flights
        Route::get('/flights/active', [FlightController::class, 'active']);

        // Telemetry & Events
        Route::post('/acars/position', [V1AcarsController::class, 'position']);
        Route::post('/acars/event', [V1AcarsController::class, 'event']);

        // PIREPs
        Route::post('/pireps/submit', [V1PirepController::class, 'submit']);
    });
});

/*
|--------------------------------------------------------------------------
| Virtual Airline Core API Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/fleet', [FleetController::class, 'index']);
    Route::post('/fleet', [FleetController::class, 'store']);

    Route::get('/routes', [RouteController::class, 'index']);
    Route::post('/routes', [RouteController::class, 'store']);
});
