<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'button_bg_color')) {
                $table->string('button_bg_color', 7)->nullable()->after('card_bg_color');
            }
            if (!Schema::hasColumn('tenants', 'button_text_color')) {
                $table->string('button_text_color', 7)->nullable()->after('button_bg_color');
            }
            if (!Schema::hasColumn('tenants', 'button_secondary_bg_color')) {
                $table->string('button_secondary_bg_color', 7)->nullable()->after('button_text_color');
            }
            if (!Schema::hasColumn('tenants', 'button_secondary_text_color')) {
                $table->string('button_secondary_text_color', 7)->nullable()->after('button_secondary_bg_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'button_bg_color',
                'button_text_color',
                'button_secondary_bg_color',
                'button_secondary_text_color'
            ]);
        });
    }
};
