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
        Schema::table('pilot_profiles', function (Blueprint $table) {
            $table->boolean('use_imperial_units')->default(false)->after('points');
            $table->boolean('prefer_honorary_rank')->default(false)->after('use_imperial_units');
            $table->string('preferred_network')->nullable()->after('prefer_honorary_rank');
            $table->string('simbrief_ofp_format')->nullable()->after('preferred_network');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'use_imperial_units',
                'prefer_honorary_rank',
                'preferred_network',
                'simbrief_ofp_format'
            ]);
        });
    }
};
