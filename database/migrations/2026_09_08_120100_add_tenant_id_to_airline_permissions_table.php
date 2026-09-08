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
        Schema::table('airline_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('airline_permissions', 'tenant_id')) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();
            }
        });

        // Drop the global unique constraint on slug if present
        try {
            Schema::table('airline_permissions', function (Blueprint $table) {
                $table->dropUnique('airline_permissions_slug_unique');
            });
        } catch (\Throwable $e) {
            // Catch for different database drivers or already dropped
        }

        try {
            Schema::table('airline_permissions', function (Blueprint $table) {
                $table->unique(['tenant_id', 'slug'], 'airline_perm_tenant_slug_unique');
            });
        } catch (\Throwable $e) {
            // Catch if composite unique already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airline_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('airline_permissions', 'tenant_id')) {
                $table->dropConstrainedForeignId('tenant_id');
            }
        });
    }
};
