<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Prune unused and redundant legacy tables.
     */
    public function up(): void
    {
        // Drop legacy unused pirep_telemetries (live telemetry is handled in acars_positions)
        if (Schema::hasTable('pirep_telemetries')) {
            Schema::dropIfExists('pirep_telemetries');
        }

        // Drop legacy unused pirep_comments
        if (Schema::hasTable('pirep_comments')) {
            Schema::dropIfExists('pirep_comments');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('pirep_comments')) {
            Schema::create('pirep_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pirep_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('comment');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pirep_telemetries')) {
            Schema::create('pirep_telemetries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pirep_id')->constrained('pireps')->cascadeOnDelete();
                $table->string('flight_hash')->nullable();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->integer('altitude_msl');
                $table->integer('ground_speed_kts');
                $table->integer('heading');
                $table->integer('elapsed_seconds')->default(0);
                $table->timestamps();
            });
        }
    }
};
