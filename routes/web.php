<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Auth & Verification Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [\App\Http\Controllers\Auth\CustomAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Auth\CustomAuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Auth\CustomAuthController::class, 'login']);
    Route::post('/custom-login', [\App\Http\Controllers\Auth\CustomAuthController::class, 'login'])->name('custom-login');
});

Route::get('/verify-notice', [\App\Http\Controllers\Auth\CustomAuthController::class, 'showVerifyNotice'])->name('auth.verify-notice');
Route::get('/email/verify-notice', [\App\Http\Controllers\Auth\CustomAuthController::class, 'showVerifyNotice'])->name('verification.notice');
Route::post('/verify-email/resend', [\App\Http\Controllers\Auth\CustomAuthController::class, 'resendVerificationEmail'])->name('auth.verify.resend');
Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\CustomAuthController::class, 'resendVerificationEmail'])->name('verification.send');
Route::get('/verify-email', [\App\Http\Controllers\Auth\CustomAuthController::class, 'showVerifyPrompt'])->name('auth.verify');
Route::post('/verify-email', [\App\Http\Controllers\Auth\CustomAuthController::class, 'confirmVerifyEmail'])->name('auth.verify.confirm');
Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\CustomAuthController::class, 'showVerifyPrompt'])->name('verification.verify');
Route::post('/email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\CustomAuthController::class, 'confirmVerifyEmail'])->name('verification.verify.confirm');

// Onboarding & Session Switcher Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding/select-airline', [\App\Http\Controllers\AirlineOnboardingController::class, 'showSelectionPage'])->name('onboarding.select-airline');
    Route::post('/onboarding/join', [\App\Http\Controllers\AirlineOnboardingController::class, 'joinAirlines'])->name('onboarding.join');
    Route::post('/onboarding/create-airline', [\App\Http\Controllers\AirlineOnboardingController::class, 'createAirline'])->name('onboarding.create-va');

    Route::get('/session/select-airline', [\App\Http\Controllers\SessionAirlineController::class, 'showSelectActiveAirlinePage'])->name('session.select-airline');
    Route::post('/session/switch-airline', [\App\Http\Controllers\SessionAirlineController::class, 'switchActiveAirline'])->name('session.switch-airline');
});

// Resource & Client Downloads
Route::get('/resources/vpilot-acars', function () {
    return redirect()->away(config('services.vpilot_acars.releases_url', 'https://gitea.artmex-hosting.com/tathar26/vops-acars/releases/latest'));
})->name('resources.vpilot-acars');

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
    \App\Http\Middleware\EnsureActiveAirlineSelected::class,
])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->hasRole('Master Admin') && !auth()->user()->tenant_id) {
            return redirect()->route('admin.dashboard');
        }
        return view('dashboard');
    })->name('dashboard');

    Route::get('/admin', \App\Livewire\MasterAdminDashboard::class)
        ->middleware('role:Master Admin')
        ->name('admin.dashboard');

    Route::get('/admin/email-queue', \App\Livewire\Admin\EmailQueueManager::class)
        ->middleware('role:Master Admin|VA Owner')
        ->name('admin.email-queue');

    Route::get('/fleet', \App\Livewire\FleetManager::class)
        ->middleware('airline.can:view_fleet')
        ->name('fleet');

    Route::get('/routes', \App\Livewire\RouteManager::class)
        ->middleware('airline.can:view_routes')
        ->name('routes');

    Route::get('/airports', \App\Livewire\AirportManager::class)
        ->middleware('airline.can:view_airports')
        ->name('airports');

    Route::get('/global-network', \App\Livewire\GlobalNetworkImport::class)
        ->middleware('airline.can:create_routes|view_routes')
        ->name('global-network');

    Route::get('/aircraft-types', \App\Livewire\AircraftTypeManager::class)
        ->middleware('airline.can:view_fleet')
        ->name('aircraft-types');

    Route::get('/settings', \App\Livewire\TenantSettings::class)
        ->middleware('airline.can:view_settings|manage_airline_settings|manage_roles|manage_ranks|manage_pilots')
        ->name('settings');

    Route::get('/pireps', \App\Livewire\Admin\PirepsList::class)
        ->middleware('airline.can:view_pireps')
        ->name('pireps');

    Route::get('/admin/pireps', \App\Livewire\Admin\PirepsList::class)
        ->middleware('airline.can:view_pireps')
        ->name('admin.pireps');

    Route::get('/pireps/{pirep}', \App\Livewire\Admin\PirepDetail::class)
        ->middleware('airline.can:view_pireps')
        ->name('pireps.show');

    Route::get('/admin/pireps/{pirep}', \App\Livewire\Admin\PirepDetail::class)
        ->middleware('airline.can:view_pireps')
        ->name('admin.pireps.show');

    // NOTAMs (Pilot View & Staff Operations)
    Route::get('/notams', \App\Livewire\Pilot\NotamsList::class)
        ->name('notams');

    Route::get('/admin/notams', \App\Livewire\Admin\NotamManager::class)
        ->middleware('airline.can:manage_notams')
        ->name('admin.notams');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/dashboard', \App\Livewire\Pilot\Dashboard::class)->name('dashboard');
        Route::get('/map', \App\Livewire\Pilot\Map::class)->name('map');
        Route::get('/statistics', \App\Livewire\Pilot\Statistics::class)->name('statistics');
        Route::get('/awards', \App\Livewire\Pilot\Awards::class)->name('awards');
        Route::get('/pireps', \App\Livewire\Pilot\PirepsList::class)->name('pireps');
        Route::get('/pireps/{pirep}', \App\Livewire\Pilot\PirepDetail::class)->name('pireps.show');
        Route::get('/preferences', \App\Livewire\Pilot\Preferences::class)->name('preferences');
        Route::get('/account', \App\Livewire\Pilot\AccountSettings::class)->name('account');
        Route::get('/dispatch/{booking}', \App\Livewire\Pilot\Dispatch::class)->middleware('airline.notams')->name('dispatch');
    });

    Route::prefix('flight-centre')->name('flight-centre.')->middleware(['role:Master Admin|VA Owner|Pilot', 'airline.notams'])->group(function () {
        Route::get('/', [\App\Http\Controllers\FlightCentreController::class, 'index'])->name('index');
        Route::get('/book', [\App\Http\Controllers\FlightCentreController::class, 'bookFlightMap'])->name('book');
        Route::get('/flights', [\App\Http\Controllers\FlightCentreController::class, 'flightsTable'])->name('flights');
        Route::get('/destinations', [\App\Http\Controllers\FlightCentreController::class, 'destinationMap'])->name('destinations');
    });

    Route::prefix('api/flight-centre')->group(function () {
        Route::get('/destinations', [\App\Http\Controllers\Api\FlightCentreApiController::class, 'destinations']);
        Route::get('/network', [\App\Http\Controllers\Api\FlightCentreApiController::class, 'network']);
        Route::get('/live-flights', [\App\Http\Controllers\Api\FlightCentreApiController::class, 'liveFlights']);
        Route::post('/current-location', [\App\Http\Controllers\Api\FlightCentreApiController::class, 'updateLocation']);
        Route::post('/book', [\App\Http\Controllers\Api\FlightCentreApiController::class, 'book'])->middleware('airline.notams');
    });
});
