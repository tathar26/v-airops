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
        Schema::create('system_global_airframes', function (Blueprint $table) {
            $table->id();
            $table->string('registration', 20)->unique();
            $table->string('icao_code', 10)->index();
            $table->string('operator')->nullable()->index();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_global_airframes');
    }
};
