<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add index on airlines IATA for the join in GlobalNetworkImport
        Schema::table('system_global_airlines', function (Blueprint $table) {
            if (!Schema::hasIndex('system_global_airlines', 'system_global_airlines_iata_index')) {
                $table->index('iata');
            }
            if (!Schema::hasIndex('system_global_airlines', 'system_global_airlines_name_index')) {
                $table->index('name');
            }
        });

        // Add composite index on departure+arrival for fast filtering 
        Schema::table('system_global_flights', function (Blueprint $table) {
            $table->index(['departure_icao', 'arrival_icao'], 'sgf_dep_arr_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_global_airlines', function (Blueprint $table) {
            $table->dropIndex(['iata']);
            $table->dropIndex(['name']);
        });

        Schema::table('system_global_flights', function (Blueprint $table) {
            $table->dropIndex('sgf_dep_arr_idx');
        });
    }
};
