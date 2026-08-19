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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('card_text_color', 7)->nullable()->after('card_bg_color');
            $table->string('card_muted_text_color', 7)->nullable()->after('card_text_color');
            $table->string('panel_text_color', 7)->nullable()->after('panel_bg_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'card_text_color',
                'card_muted_text_color',
                'panel_text_color',
            ]);
        });
    }
};
