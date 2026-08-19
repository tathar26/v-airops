<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ensure pirep_comments table exists with proper indexes.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pirep_comments')) {
            Schema::create('pirep_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pirep_id')->constrained('pireps')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('comment');
                $table->timestamps();

                $table->index(['pirep_id', 'created_at'], 'idx_pirep_comments_pirep_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pirep_comments');
    }
};
