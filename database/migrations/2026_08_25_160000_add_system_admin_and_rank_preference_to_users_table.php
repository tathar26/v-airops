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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_system_admin')) {
                $table->boolean('is_system_admin')
                    ->default(false)
                    ->after('email')
                    ->index()
                    ->comment('Global System Administrator override flag across all airlines');
            }

            if (!Schema::hasColumn('users', 'prefer_honorary_rank')) {
                $table->boolean('prefer_honorary_rank')
                    ->default(false)
                    ->after('is_system_admin')
                    ->comment('Preference toggle: display staff honorary rank over flight-hour rank');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_system_admin', 'prefer_honorary_rank']);
        });
    }
};
