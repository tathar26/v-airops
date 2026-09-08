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
        Schema::table('ranks', function (Blueprint $table) {
            if (!Schema::hasColumn('ranks', 'abbreviation')) {
                $table->string('abbreviation', 10)->nullable()->after('name');
            }
            if (!Schema::hasColumn('ranks', 'position')) {
                $table->integer('position')->default(1)->after('abbreviation');
            }
            if (!Schema::hasColumn('ranks', 'min_bonus_points')) {
                $table->integer('min_bonus_points')->default(0)->after('min_points');
            }
            if (!Schema::hasColumn('ranks', 'min_pireps')) {
                $table->integer('min_pireps')->default(0)->after('min_bonus_points');
            }
            if (!Schema::hasColumn('ranks', 'is_honorary')) {
                $table->boolean('is_honorary')->default(false)->after('min_pireps');
            }
            if (!Schema::hasColumn('ranks', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_honorary');
            }
            if (!Schema::hasColumn('ranks', 'image_path')) {
                $table->string('image_path')->nullable()->after('image_url');
            }
        });

        Schema::table('pilot_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('pilot_profiles', 'honorary_rank_id')) {
                $table->foreignId('honorary_rank_id')->nullable()->after('rank_id')->constrained('ranks')->nullOnDelete();
            }
            if (!Schema::hasColumn('pilot_profiles', 'bonus_points')) {
                $table->integer('bonus_points')->default(0)->after('points');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('pilot_profiles', 'honorary_rank_id')) {
                $table->dropForeign(['honorary_rank_id']);
                $table->dropColumn('honorary_rank_id');
            }
            if (Schema::hasColumn('pilot_profiles', 'bonus_points')) {
                $table->dropColumn('bonus_points');
            }
        });

        Schema::table('ranks', function (Blueprint $table) {
            $table->dropColumn([
                'abbreviation',
                'position',
                'min_bonus_points',
                'min_pireps',
                'is_honorary',
                'is_default',
                'image_path',
            ]);
        });
    }
};
