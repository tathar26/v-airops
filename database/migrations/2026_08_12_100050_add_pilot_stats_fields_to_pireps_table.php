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
        Schema::table('pireps', function (Blueprint $table) {
            $table->string('network')->nullable();
            $table->string('simulator')->nullable();
            $table->boolean('is_day_takeoff')->default(true);
            $table->boolean('is_day_landing')->default(true);
            $table->boolean('is_event')->default(false);
            $table->integer('passengers')->nullable();
            $table->integer('freight')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pireps', function (Blueprint $table) {
            $table->dropColumn([
                'network', 'simulator', 'is_day_takeoff', 'is_day_landing', 'is_event', 'passengers', 'freight'
            ]);
        });
    }
};
