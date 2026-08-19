<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'input_bg_color')) {
                $table->string('input_bg_color', 7)->nullable()->after('button_secondary_text_color');
            }
            if (!Schema::hasColumn('tenants', 'input_text_color')) {
                $table->string('input_text_color', 7)->nullable()->after('input_bg_color');
            }
            if (!Schema::hasColumn('tenants', 'input_border_color')) {
                $table->string('input_border_color', 7)->nullable()->after('input_text_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'input_bg_color',
                'input_text_color',
                'input_border_color'
            ]);
        });
    }
};
