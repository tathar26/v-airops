<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- TENANTS ---\n";
foreach (\App\Models\Tenant::all() as $t) {
    echo "ID: {$t->id} | Name: {$t->name} | ICAO: {$t->icao} | Domain: {$t->domain}\n";
}

echo "\n--- ALL BOOKINGS (NO SCOPE) ---\n";
foreach (\App\Models\Booking::withoutGlobalScopes()->with(['route', 'airframe.aircraftType'])->get() as $b) {
    $sb = $b->simbrief_data ?? [];
    $callsign = $sb['params']['callsign'] ?? ($sb['general']['flight_number'] ?? $b->route?->flight_number);
    $flightNum = $sb['general']['flight_number'] ?? $b->route?->flight_number;
    $dep = $sb['origin']['icao_code'] ?? $b->route?->departure_icao;
    $arr = $sb['destination']['icao_code'] ?? $b->route?->arrival_icao;
    $network = $sb['general']['network'] ?? 'Offline';
    $airframe = $b->airframe?->registration ?? ($sb['aircraft']['reg'] ?? 'G-DEMO');
    echo "Booking ID: {$b->id} | User: {$b->user_id} | Tenant: {$b->tenant_id} | Status: {$b->status} | {$dep} -> {$arr} | Callsign: {$callsign} | FlightNum: {$flightNum} | Airframe: {$airframe} | Network: {$network}\n";
}

echo "\n--- ALL ACARS ACTIVE FLIGHTS ---\n";
foreach (\App\Models\AcarsActiveFlight::with('positions')->get() as $a) {
    $latestPos = $a->positions->last();
    $posInfo = $latestPos ? "Lat: {$latestPos->latitude}, Lon: {$latestPos->longitude}, Alt: {$latestPos->altitude_ft}, Speed: {$latestPos->ground_speed_kt}, Phase: {$latestPos->flight_phase}" : "No pings";
    echo "ACARS ID: {$a->id} | User: {$a->user_id} | Status: {$a->status} | {$a->flight_number} | {$a->origin_icao} -> {$a->destination_icao} | OFP: {$a->simbrief_ofp_id} | {$posInfo}\n";
}

echo "\n--- USER AIRLINES ---\n";
foreach (\App\Models\UserAirline::all() as $ua) {
    echo "User: {$ua->user_id} | Tenant: {$ua->tenant_id} | Callsign: {$ua->callsign} | Status: {$ua->status}\n";
}
