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
        Schema::create('user_airlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('callsign', 20);
            $table->timestamp('join_date')->useCurrent();
            $table->string('rank')->default('Cadet');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Prevent duplicate callsign within the exact same airline
            $table->unique(['tenant_id', 'callsign'], 'unique_tenant_callsign');
            
            // Prevent user from joining the same airline twice
            $table->unique(['user_id', 'tenant_id'], 'unique_user_tenant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_airlines');
    }
};
