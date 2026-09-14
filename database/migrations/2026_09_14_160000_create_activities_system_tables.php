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
        // 1. Core Activities Table
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->enum('type', [
                'event',
                'slotted_event',
                'focus_airport',
                'tour',
                'roster',
                'curated_roster',
                'community_goal',
                'community_challenge'
            ]);
            $table->enum('subtype', ['airport_based', 'route_based'])->default('airport_based');
            $table->longText('description')->nullable();
            $table->string('sidebar_title')->nullable();
            $table->text('sidebar_content')->nullable();
            $table->string('image_url')->nullable();
            $table->json('tags')->nullable();
            
            // Time Windows & Leeway
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->dateTime('show_from')->nullable();
            $table->unsignedSmallInteger('time_leeway_minutes')->default(0);
            $table->enum('time_award_scale', [
                'takeoff_only',
                'landing_only',
                'entire_flight',
                'any_part'
            ])->default('any_part');

            // Granular Restrictions & Flight Criteria
            $table->json('restrictions')->nullable();
            $table->json('event_criteria')->nullable();

            // Registration Settings
            $table->boolean('registration_required')->default(false);
            $table->dateTime('registration_start_at')->nullable();
            $table->dateTime('registration_end_at')->nullable();

            // Points & Rewards Configuration
            $table->enum('points_mode', ['fixed', 'percentage'])->default('fixed');
            $table->integer('points_value')->default(0);
            $table->enum('point_award_mode', [
                'per_leg',
                'all_on_completion',
                'final_leg_only'
            ])->default('per_leg');
            $table->boolean('allow_repeat')->default(false);
            $table->boolean('is_active')->default(true);

            // Community Goals / Challenges Configuration
            $table->enum('community_metric', [
                'passengers',
                'freight',
                'flights',
                'distance',
                'flight_time'
            ])->nullable();
            $table->unsignedBigInteger('community_target')->default(0);
            $table->unsignedBigInteger('community_current')->default(0);
            $table->integer('community_completion_points')->default(0);
            $table->boolean('community_tiered_multipliers')->default(false);
            $table->json('tier_multipliers')->nullable();

            // Slotted Event Configuration
            $table->string('slotted_departure_icao', 4)->nullable();
            $table->enum('slotted_callsign_system', [
                'manual',
                'username_a',
                'username_b',
                'generator'
            ])->default('username_a');
            $table->string('slotted_generator_pattern')->nullable();
            $table->unsignedSmallInteger('slotted_dispatch_window_before')->default(180);
            $table->unsignedSmallInteger('slotted_dispatch_window_after')->default(30);
            $table->json('slotted_networks')->nullable();

            $table->timestamps();

            // Indexes for fast lookup
            $table->index(['tenant_id', 'is_active', 'start_at', 'end_at'], 'activities_tenant_active_time_idx');
            $table->index(['tenant_id', 'type'], 'activities_tenant_type_idx');
        });

        // 2. Activity Legs (For Tours, Rosters, Curated Rosters)
        Schema::create('activity_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('dep_icao', 4);
            $table->string('arr_icao', 4);
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->foreignId('airframe_id')->nullable()->constrained('airframes')->nullOnDelete();
            $table->foreignId('aircraft_type_id')->nullable()->constrained('aircraft_types')->nullOnDelete();
            $table->dateTime('show_from')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'sequence'], 'activity_legs_sequence_idx');
        });

        // 3. Activity Waves (For Slotted Events)
        Schema::create('activity_waves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(1);
            $table->string('start_time', 10); // e.g. "12:00"
            $table->string('end_time', 10);   // e.g. "16:00"
            $table->unsignedInteger('interval_minutes')->default(15);
            $table->enum('interval_type', ['fixed', 'custom'])->default('fixed');
            $table->json('custom_minutes')->nullable();
            $table->enum('unlock_rule', [
                'always',
                'previous_wave_full',
                'scheduled'
            ])->default('always');
            $table->dateTime('scheduled_unlock_at')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'position'], 'activity_waves_pos_idx');
        });

        // 4. Activity Slots (For Slotted Events)
        Schema::create('activity_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wave_id')->constrained('activity_waves')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('network')->default('offline');
            $table->dateTime('slot_time');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('callsign_suffix')->nullable();
            $table->dateTime('booked_at')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'network', 'slot_time'], 'activity_slot_unique_time');
            $table->index(['activity_id', 'user_id'], 'activity_slots_user_idx');
        });

        // 5. Activity Teams (For Community Challenges: exactly 2 teams)
        Schema::create('activity_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('name');
            $table->enum('count_type', [
                'passengers',
                'freight',
                'flights',
                'distance',
                'flight_time'
            ]);
            $table->unsignedBigInteger('target_value')->default(0);
            $table->unsignedBigInteger('current_value')->default(0);
            $table->json('filters')->nullable();
            $table->timestamps();
        });

        // 6. Activity Registrations
        Schema::create('activity_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->integer('points_awarded')->default(0);
            $table->foreignId('team_id')->nullable()->constrained('activity_teams')->nullOnDelete();
            $table->foreignId('slot_id')->nullable()->constrained('activity_slots')->nullOnDelete();
            $table->dateTime('registered_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'user_id'], 'activity_registrations_user_unique');
            $table->index(['tenant_id', 'user_id', 'status'], 'activity_reg_tenant_user_idx');
        });

        // 7. Activity Leg Progress Logbook
        Schema::create('activity_leg_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('activity_registrations')->cascadeOnDelete();
            $table->foreignId('activity_leg_id')->constrained('activity_legs')->cascadeOnDelete();
            $table->foreignId('pirep_id')->nullable()->constrained('pireps')->nullOnDelete();
            $table->boolean('is_valid')->default(true);
            $table->dateTime('completed_at');
            $table->timestamps();

            $table->unique(['registration_id', 'activity_leg_id'], 'activity_leg_progress_unique');
        });

        // 8. Community Goal & Challenge Contributions
        Schema::create('activity_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('activity_teams')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pirep_id')->constrained('pireps')->cascadeOnDelete();
            $table->unsignedBigInteger('metric_value')->default(0);
            $table->integer('points_awarded')->default(0);
            $table->timestamps();

            $table->index(['activity_id', 'user_id'], 'activity_contrib_user_idx');
            $table->index(['activity_id', 'team_id'], 'activity_contrib_team_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_contributions');
        Schema::dropIfExists('activity_leg_progress');
        Schema::dropIfExists('activity_registrations');
        Schema::dropIfExists('activity_teams');
        Schema::dropIfExists('activity_slots');
        Schema::dropIfExists('activity_waves');
        Schema::dropIfExists('activity_legs');
        Schema::dropIfExists('activities');
    }
};
