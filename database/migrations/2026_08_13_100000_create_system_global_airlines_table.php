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
        Schema::create('system_global_airlines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iata', 3)->nullable()->index();
            $table->string('icao', 4)->nullable()->index();
            $table->string('callsign')->nullable();
            $table->string('country')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // We can use a unique constraint on name + iata + icao to prevent duplicates
            $table->string('hash')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_global_airlines');
    }
};
