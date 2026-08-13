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
        Schema::create('system_global_aircraft', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_tenant_id')->nullable()->index(); // Nullable because data might come from OpenFlights
            $table->string('code')->index();
            $table->string('name');
            $table->timestamps();

            // Unique constraint on code so upserts don't duplicate
            $table->unique('code');
        });

        Schema::create('system_global_flights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_tenant_id')->nullable()->index();
            $table->string('flight_number')->nullable()->index(); // Some external routes might just be generic connections
            $table->string('operator')->nullable()->index();
            $table->string('departure_icao', 4)->index();
            $table->string('arrival_icao', 4)->index();
            $table->string('block_time')->nullable();
            $table->string('route_type')->nullable();
            $table->integer('distance')->nullable();
            $table->string('aircraft_types')->nullable(); // Comma-separated list of aircraft codes
            $table->timestamps();

            // A unique constraint is useful for upserting. 
            // A combination of departure, arrival, and operator/flight number makes a route unique.
            // But since flight_number and operator can be null, we might need a composite unique key.
            // For now, let's just make an index on the combo, but we can't use a simple unique constraint if parts are nullable in MariaDB (actually MariaDB treats NULLs as distinct).
            // To ensure upserts work smoothly, we can create a unique string hash of the route.
            $table->string('route_hash')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_global_flights');
        Schema::dropIfExists('system_global_aircraft');
    }
};
