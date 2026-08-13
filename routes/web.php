<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Web Fallback Route Aliases for FSACARS
|--------------------------------------------------------------------------
| Handles calls if org.cfg specifies URLs without /api prefix or with /fsacars path.
*/
$registerFsacarsWebRoutes = function (string $prefix = '') {
    Route::prefix($prefix)->group(function () {
        Route::match(['get', 'post'], '/userquery.php', [\App\Http\Controllers\Api\FsacarsController::class, 'authenticate']);
        Route::match(['get', 'post'], '/dispatch.php', [\App\Http\Controllers\Api\FsacarsController::class, 'dispatch']);
        Route::match(['get', 'post'], '/posrep.php', [\App\Http\Controllers\Api\FsacarsController::class, 'positionReport']);
        Route::match(['get', 'post'], '/pirep.php', [\App\Http\Controllers\Api\FsacarsController::class, 'submitPirep']);
        Route::match(['get', 'post'], '/pirep_mysql.php', [\App\Http\Controllers\Api\FsacarsController::class, 'submitPirep']);
    });
};

$registerFsacarsWebRoutes('api/acars');
$registerFsacarsWebRoutes('api/fsacars');
$registerFsacarsWebRoutes('acars');
$registerFsacarsWebRoutes('fsacars');
$registerFsacarsWebRoutes('userquery.php');
$registerFsacarsWebRoutes('dispatch.php');
$registerFsacarsWebRoutes('posrep.php');
$registerFsacarsWebRoutes('pirep.php');
$registerFsacarsWebRoutes('pirep_mysql.php');

// Airport Coordinate Lookup API
Route::get('/api/airport/{icao}', function ($icao) {
    $icao = strtoupper($icao);
    $airport = \App\Models\Airport::where('icao', $icao)->first();

    if ($airport) {
        return response()->json([
            'lat' => $airport->lat,
            'lon' => $airport->lon,
            'cached' => true
        ]);
    }

    // Not in DB, fetch from Nominatim
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(3)->withHeaders([
            'User-Agent' => 'V-Ops Virtual Airline System'
        ])->get("https://nominatim.openstreetmap.org/search", [
            'q' => $icao . ' airport',
            'format' => 'json',
            'limit' => 1
        ]);

        if ($response->successful() && !empty($response->json())) {
            $data = $response->json()[0];
            $lat = (float) $data['lat'];
            $lon = (float) $data['lon'];

            // Save to DB
            \App\Models\Airport::create([
                'icao' => $icao,
                'name' => $data['name'] ?? $icao,
                'lat' => $lat,
                'lon' => $lon
            ]);

            return response()->json([
                'lat' => $lat,
                'lon' => $lon,
                'cached' => false
            ]);
        }
    } catch (\Exception $e) {
        // Silently fail to 404
    }

    return response()->json(['error' => 'Airport not found'], 404);
})->name('api.airport');

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

    Route::get('/airports', \App\Livewire\AirportManager::class)
        ->middleware('role:VA Owner|Pilot')
        ->name('airports');

    Route::get('/global-network', \App\Livewire\GlobalNetworkImport::class)
        ->middleware('role:VA Owner')
        ->name('global-network');

    Route::get('/aircraft-types', \App\Livewire\AircraftTypeManager::class)
        ->middleware('role:VA Owner|Pilot')
        ->name('aircraft-types');

    Route::get('/settings', \App\Livewire\TenantSettings::class)
        ->middleware('role:VA Owner')
        ->name('settings');

    Route::get('/pireps', \App\Livewire\Admin\PirepsList::class)
        ->middleware('role:VA Owner')
        ->name('pireps');

    Route::get('/pireps/{pirep}', \App\Livewire\Admin\PirepDetail::class)
        ->middleware('role:VA Owner')
        ->name('pireps.show');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/dashboard', \App\Livewire\Pilot\Dashboard::class)->name('dashboard');
        Route::get('/map', \App\Livewire\Pilot\Map::class)->name('map');
        Route::get('/statistics', \App\Livewire\Pilot\Statistics::class)->name('statistics');
        Route::get('/awards', \App\Livewire\Pilot\Awards::class)->name('awards');
        Route::get('/pireps', \App\Livewire\Pilot\PirepsList::class)->name('pireps');
        Route::get('/pireps/{pirep}', \App\Livewire\Pilot\PirepDetail::class)->name('pireps.show');
        Route::get('/preferences', \App\Livewire\Pilot\Preferences::class)->name('preferences');
        Route::get('/account', \App\Livewire\Pilot\AccountSettings::class)->name('account');
        Route::get('/dispatch/{booking}', \App\Livewire\Pilot\Dispatch::class)->name('dispatch');
    });
    Route::prefix('flight-centre')->name('flight-centre.')->middleware('role:VA Owner|Pilot')->group(function () {
        Route::get('/', [\App\Http\Controllers\FlightCentreController::class, 'index'])->name('index');
        Route::get('/book', [\App\Http\Controllers\FlightCentreController::class, 'bookFlightMap'])->name('book');
        Route::get('/flights', [\App\Http\Controllers\FlightCentreController::class, 'flightsTable'])->name('flights');
        Route::get('/destinations', [\App\Http\Controllers\FlightCentreController::class, 'destinationMap'])->name('destinations');
    });

    Route::prefix('api/flight-centre')->group(function () {
        Route::get('/destinations', [\App\Http\Controllers\Api\FlightCentreApiController::class, 'destinations']);
        Route::get('/network', [\App\Http\Controllers\Api\FlightCentreApiController::class, 'network']);
        Route::post('/current-location', [\App\Http\Controllers\Api\FlightCentreApiController::class, 'updateLocation']);
        Route::post('/book', [\App\Http\Controllers\Api\FlightCentreApiController::class, 'book']);
    });
});
