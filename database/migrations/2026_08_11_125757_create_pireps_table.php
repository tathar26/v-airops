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
        Schema::create('pireps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('airframe_id')->nullable()->constrained()->onDelete('set null');
            
            $table->integer('block_fuel')->nullable();
            $table->integer('zfw')->nullable();
            $table->integer('cost_index')->nullable();
            $table->integer('touchdown_rate_fpm')->nullable();
            $table->string('status')->default('filed'); // filed, accepted, rejected, flying
            
            $table->json('flight_log')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pireps');
    }
};
