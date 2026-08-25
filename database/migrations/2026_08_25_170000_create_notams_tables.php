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
        // 1. NOTAMs table (Scoped per airline / tenant)
        Schema::create('notams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->enum('priority', ['Low', 'Medium', 'High'])->default('Medium');
            $table->string('category')->default('Operations'); // e.g. Operations, Fleet, Training, General, Briefings
            $table->timestamp('expires_at')->nullable(); // null = Permanent / Never expires
            $table->timestamp('posted_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performant scoped querying
            $table->index(['tenant_id', 'is_active', 'expires_at'], 'notams_tenant_active_expires_idx');
            $table->index(['tenant_id', 'posted_at'], 'notams_tenant_posted_idx');
        });

        // 2. NOTAM User Reads / Acknowledgements tracking table
        Schema::create('notam_user_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notam_id')
                ->constrained('notams')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            // A user can acknowledge each NOTAM once
            $table->unique(['notam_id', 'user_id'], 'notam_user_reads_unique');
            $table->index(['user_id', 'tenant_id'], 'notam_reads_user_tenant_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notam_user_reads');
        Schema::dropIfExists('notams');
    }
};
