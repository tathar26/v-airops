<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add optimized composite indexes for high-throughput ACARS & booking API lookups.
     */
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'updated_at'], 'idx_bookings_user_status_updated');
            });
        }

        if (Schema::hasTable('acars_active_flights')) {
            Schema::table('acars_active_flights', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'id'], 'idx_acars_flights_user_status_id');
            });
        }

        if (Schema::hasTable('acars_events')) {
            Schema::table('acars_events', function (Blueprint $table) {
                $table->index(['flight_id', 'severity'], 'idx_acars_events_flight_severity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('acars_events')) {
            Schema::table('acars_events', function (Blueprint $table) {
                $table->dropIndex('idx_acars_events_flight_severity');
            });
        }

        if (Schema::hasTable('acars_active_flights')) {
            Schema::table('acars_active_flights', function (Blueprint $table) {
                $table->dropIndex('idx_acars_flights_user_status_id');
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropIndex('idx_bookings_user_status_updated');
            });
        }
    }
};
