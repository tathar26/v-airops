<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'panel_bg_color')) {
                $table->string('panel_bg_color', 7)->nullable()->after('bg_color');
            }
            if (!Schema::hasColumn('tenants', 'card_bg_color')) {
                $table->string('card_bg_color', 7)->nullable()->after('panel_bg_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['panel_bg_color', 'card_bg_color']);
        });
    }
};
