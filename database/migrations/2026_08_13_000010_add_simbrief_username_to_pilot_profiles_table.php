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
            if (!Schema::hasColumn('pilot_profiles', 'simbrief_username')) {
                $table->string('simbrief_username')->nullable()->after('simbrief_ofp_format');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('pilot_profiles', 'simbrief_username')) {
                $table->dropColumn('simbrief_username');
            }
        });
    }
};
