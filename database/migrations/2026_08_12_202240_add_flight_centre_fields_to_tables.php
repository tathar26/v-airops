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
        Schema::table('airports', function (Blueprint $table) {
            if (!Schema::hasColumn('airports', 'elevation')) {
                $table->integer('elevation')->nullable()->after('lon');
            }
        });

        Schema::table('routes', function (Blueprint $table) {
            if (!Schema::hasColumn('routes', 'distance')) {
                $table->integer('distance')->nullable()->after('arrival_icao');
            }
            if (!Schema::hasColumn('routes', 'callsign')) {
                $table->string('callsign')->nullable()->after('flight_number');
            }
            if (!Schema::hasColumn('routes', 'remarks')) {
                $table->text('remarks')->nullable()->after('distance');
            }
            if (!Schema::hasColumn('routes', 'operator')) {
                $table->string('operator')->nullable()->after('callsign');
            }
        });

        Schema::table('pilot_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('pilot_profiles', 'current_airport_id')) {
                $table->foreignId('current_airport_id')->nullable()->constrained('airports')->nullOnDelete()->after('tenant_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropColumn('elevation');
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['distance', 'callsign', 'remarks', 'operator']);
        });

        Schema::table('pilot_profiles', function (Blueprint $table) {
            $table->dropForeign(['current_airport_id']);
            $table->dropColumn('current_airport_id');
        });
    }
};
