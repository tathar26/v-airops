<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            if (!Schema::hasColumn('routes', 'callsign_icao')) {
                $table->string('callsign_icao', 10)->nullable()->after('flight_number');
            }
            if (!Schema::hasColumn('routes', 'callsign_suffix')) {
                $table->string('callsign_suffix', 10)->nullable()->after('callsign_icao');
            }
        });

        // Populate existing routes callsign_icao, callsign_suffix, callsign
        $routes = DB::table('routes')->get();
        foreach ($routes as $r) {
            $updates = [];
            $tenant = DB::table('tenants')->where('id', $r->tenant_id)->first();
            $defaultIcao = $tenant && !empty($tenant->icao) ? strtoupper($tenant->icao) : 'VOPS';

            $callsign = $r->callsign;
            $flightNum = strtoupper(trim($r->flight_number));

            if (empty($callsign)) {
                // Strip leading letters if any (e.g. U29999 -> 9999)
                $suffix = preg_replace('/^[A-Z0-9]{2,3}/', '', $flightNum) ?: $flightNum;
                $callsignIcao = !empty($r->operator) && strlen($r->operator) >= 3 ? strtoupper($r->operator) : $defaultIcao;
                $callsign = $callsignIcao . $suffix;
                
                $updates['callsign'] = $callsign;
                $updates['callsign_icao'] = $callsignIcao;
                $updates['callsign_suffix'] = $suffix;
            } else {
                $rawCs = strtoupper(trim($callsign));
                // Extract 3 or 4 letter ICAO prefix if present
                if (preg_match('/^([A-Z]{2,4})(.*)$/', $rawCs, $matches)) {
                    $updates['callsign_icao'] = $matches[1];
                    $updates['callsign_suffix'] = $matches[2];
                } else {
                    $updates['callsign_icao'] = $defaultIcao;
                    $updates['callsign_suffix'] = $rawCs;
                }
            }

            if (!empty($updates)) {
                DB::table('routes')->where('id', $r->id)->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['callsign_icao', 'callsign_suffix']);
        });
    }
};
