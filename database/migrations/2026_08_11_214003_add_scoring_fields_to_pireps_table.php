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
            $table->integer('flight_time')->nullable()->after('status'); // in minutes
            $table->integer('points_awarded')->default(0)->after('flight_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pireps', function (Blueprint $table) {
            $table->dropColumn(['flight_time', 'points_awarded']);
        });
    }
};
