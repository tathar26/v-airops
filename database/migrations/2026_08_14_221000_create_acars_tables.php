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
        // 1. Acars Active Flights
        if (!Schema::hasTable('acars_active_flights')) {
            Schema::create('acars_active_flights', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('flight_number', 20);
                $table->string('origin_icao', 4);
                $table->string('destination_icao', 4);
                $table->text('route')->nullable();
                $table->string('aircraft_type', 20)->default('A320');
                $table->unsignedInteger('planned_altitude')->default(34000);
                $table->decimal('planned_fuel_kg', 10, 2)->default(6500.00);
                $table->decimal('planned_zfw_kg', 10, 2)->default(58000.00);
                $table->string('simbrief_ofp_id', 50)->nullable();
                $table->string('status', 20)->default('active'); // active, completed, cancelled, archived
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        // 2. Acars Positions (Live telemetry)
        if (!Schema::hasTable('acars_positions')) {
            Schema::create('acars_positions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('flight_id')->constrained('acars_active_flights')->cascadeOnDelete();
                $table->timestamp('timestamp')->useCurrent();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->decimal('altitude_ft', 8, 2);
                $table->decimal('ground_speed_kt', 6, 2);
                $table->decimal('indicated_airspeed_kt', 6, 2);
                $table->decimal('vertical_speed_fpm', 7, 2);
                $table->decimal('pitch_deg', 5, 2)->default(0.0);
                $table->decimal('bank_deg', 5, 2)->default(0.0);
                $table->decimal('heading_deg', 5, 2);
                $table->decimal('fuel_qty_kg', 10, 2);
                $table->string('flight_phase', 30);
                $table->timestamps();

                $table->index(['flight_id', 'created_at']);
            });
        }

        // 3. Acars Events (Penalty & safety violations)
        if (!Schema::hasTable('acars_events')) {
            Schema::create('acars_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('flight_id')->constrained('acars_active_flights')->cascadeOnDelete();
                $table->timestamp('timestamp')->useCurrent();
                $table->string('event_type', 50); // TOUCHDOWN, OVERSPEED, LIGHTS_VIOLATION, SLEW_DETECTED, STALL
                $table->string('description', 255);
                $table->string('severity', 20)->default('WARNING'); // INFO, WARNING, CRITICAL
                $table->integer('penalty_points')->default(0);
                $table->json('telemetry_snapshot')->nullable();
                $table->timestamps();

                $table->index(['flight_id', 'event_type']);
            });
        }

        // 4. Acars Pireps (Final flight reports & score)
        if (!Schema::hasTable('acars_pireps')) {
            Schema::create('acars_pireps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('flight_id')->unique()->constrained('acars_active_flights')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('submitted_at')->useCurrent();
                $table->timestamp('block_off_time')->nullable();
                $table->timestamp('block_on_time')->nullable();
                $table->unsignedInteger('block_time_minutes')->default(0);
                $table->decimal('fuel_used_kg', 10, 2)->default(0.00);
                $table->decimal('touchdown_fpm', 7, 2);
                $table->decimal('touchdown_gforce', 4, 2)->default(1.00);
                $table->string('landing_grade', 30); // Butter / Soft, Good, Firm, Hard, Structural Danger
                $table->integer('total_score')->default(100);
                $table->json('flight_log_json')->nullable();
                $table->json('penalties_json')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'submitted_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acars_pireps');
        Schema::dropIfExists('acars_events');
        Schema::dropIfExists('acars_positions');
        Schema::dropIfExists('acars_active_flights');
    }
};
