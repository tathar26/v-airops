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
        // 1. Add callsign & acars_password_hash to users table if missing
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'callsign')) {
                $table->string('callsign')->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'acars_password_hash')) {
                $table->string('acars_password_hash')->nullable()->after('password');
            }
        });

        // 2. Add FSACARS fields to pireps table
        Schema::table('pireps', function (Blueprint $table) {
            if (!Schema::hasColumn('pireps', 'flight_hash')) {
                $table->string('flight_hash')->nullable()->after('id');
            }
            if (!Schema::hasColumn('pireps', 'aircraft_title')) {
                $table->string('aircraft_title')->nullable()->after('airframe_id');
            }
            if (!Schema::hasColumn('pireps', 'atc_model')) {
                $table->string('atc_model')->nullable()->after('aircraft_title');
            }
            if (!Schema::hasColumn('pireps', 'time_out')) {
                $table->string('time_out', 4)->nullable();
                $table->string('time_off', 4)->nullable();
                $table->string('time_on', 4)->nullable();
                $table->string('time_in', 4)->nullable();
            }
            if (!Schema::hasColumn('pireps', 'air_time')) {
                $table->decimal('air_time', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('pireps', 'distance_nm')) {
                $table->decimal('distance_nm', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('pireps', 'fuel_start')) {
                $table->decimal('fuel_start', 10, 2)->nullable();
                $table->decimal('fuel_stop', 10, 2)->nullable();
                $table->decimal('fuel_used', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('pireps', 'takeoff_weight')) {
                $table->integer('takeoff_weight')->nullable();
                $table->integer('landing_weight')->nullable();
            }
            if (!Schema::hasColumn('pireps', 'landing_g')) {
                $table->decimal('landing_g', 4, 2)->nullable();
                $table->decimal('landing_kts', 6, 2)->nullable();
                $table->string('landing_flight_rules', 10)->nullable();
            }
            if (!Schema::hasColumn('pireps', 'has_crashed')) {
                $table->boolean('has_crashed')->default(false);
                $table->integer('overspeed_count')->default(0);
                $table->integer('pause_count')->default(0);
                $table->integer('slew_count')->default(0);
                $table->integer('stall_count')->default(0);
            }
            if (!Schema::hasColumn('pireps', 'raw_acars_log')) {
                $table->longText('raw_acars_log')->nullable();
            }
        });

        // 3. Create pirep_telemetries table for live position telemetry
        if (!Schema::hasTable('pirep_telemetries')) {
            Schema::create('pirep_telemetries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pirep_id')->nullable()->constrained('pireps')->nullOnDelete();
                $table->string('flight_hash')->index();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->integer('altitude_msl');
                $table->integer('ground_speed_kts');
                $table->integer('heading');
                $table->integer('elapsed_seconds');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pirep_telemetries');

        Schema::table('pireps', function (Blueprint $table) {
            $table->dropColumn([
                'flight_hash', 'aircraft_title', 'atc_model', 'time_out', 'time_off', 'time_on', 'time_in',
                'air_time', 'distance_nm', 'fuel_start', 'fuel_stop', 'fuel_used',
                'takeoff_weight', 'landing_weight', 'landing_g', 'landing_kts', 'landing_flight_rules',
                'has_crashed', 'overspeed_count', 'pause_count', 'slew_count', 'stall_count', 'raw_acars_log'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['callsign', 'acars_password_hash']);
        });
    }
};
