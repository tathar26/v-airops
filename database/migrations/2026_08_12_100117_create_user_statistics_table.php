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
        Schema::create('user_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('total_flights')->default(0);
            $table->integer('total_flight_time')->default(0); // minutes
            $table->integer('total_passengers')->default(0);
            $table->integer('total_freight')->default(0);
            $table->integer('total_block_fuel')->default(0);
            $table->integer('avg_landing_rate')->nullable();
            $table->json('aircraft_types_json')->nullable();
            $table->json('callsigns_json')->nullable();
            $table->json('networks_json')->nullable();
            $table->json('takeoffs_json')->nullable();
            $table->json('simulators_json')->nullable();
            $table->json('events_json')->nullable();
            $table->json('route_types_json')->nullable();
            $table->json('landings_json')->nullable();
            $table->json('flights_per_month_json')->nullable();
            $table->json('landing_rate_history_json')->nullable();
            $table->json('logbook_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_statistics');
    }
};
