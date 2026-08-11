<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('acars')->group(function () {
    Route::post('/connect', [\App\Http\Controllers\Api\AcarsController::class, 'connect']);
    Route::post('/{pirepId}/telemetry', [\App\Http\Controllers\Api\AcarsController::class, 'telemetry']);
    Route::post('/{pirepId}/file', [\App\Http\Controllers\Api\AcarsController::class, 'filePirep']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/fleet', [\App\Http\Controllers\Api\FleetController::class, 'index']);
    Route::post('/fleet', [\App\Http\Controllers\Api\FleetController::class, 'store']);

    Route::get('/routes', [\App\Http\Controllers\Api\RouteController::class, 'index']);
    Route::post('/routes', [\App\Http\Controllers\Api\RouteController::class, 'store']);
});
