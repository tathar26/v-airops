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

/*
|--------------------------------------------------------------------------
| Legacy FSACARS Client Integration Routes
|--------------------------------------------------------------------------
| FSACARS authenticates via request params (user, pass). No Sanctum Bearer header is sent.
| Supports /acars/*, /fsacars/*, root level, and legacy pirep_mysql.php script names.
*/
$registerFsacarsRoutes = function (string $prefix = '') {
    Route::prefix($prefix)->group(function () {
        Route::match(['get', 'post'], '/userquery.php', [\App\Http\Controllers\Api\FsacarsController::class, 'authenticate']);
        Route::match(['get', 'post'], '/dispatch.php', [\App\Http\Controllers\Api\FsacarsController::class, 'dispatch']);
        Route::match(['get', 'post'], '/posrep.php', [\App\Http\Controllers\Api\FsacarsController::class, 'positionReport']);
        Route::match(['get', 'post'], '/pirep.php', [\App\Http\Controllers\Api\FsacarsController::class, 'submitPirep']);
        Route::match(['get', 'post'], '/pirep_mysql.php', [\App\Http\Controllers\Api\FsacarsController::class, 'submitPirep']);
    });
};

$registerFsacarsRoutes('acars');
$registerFsacarsRoutes('fsacars');
$registerFsacarsRoutes('');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/fleet', [\App\Http\Controllers\Api\FleetController::class, 'index']);
    Route::post('/fleet', [\App\Http\Controllers\Api\FleetController::class, 'store']);

    Route::get('/routes', [\App\Http\Controllers\Api\RouteController::class, 'index']);
    Route::post('/routes', [\App\Http\Controllers\Api\RouteController::class, 'store']);

});
