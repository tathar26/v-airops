<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enforce strict tenant isolation on ACARS active flights.
     */
    public function up(): void
    {
        if (Schema::hasTable('acars_active_flights')) {
            Schema::table('acars_active_flights', function (Blueprint $table) {
                if (!Schema::hasColumn('acars_active_flights', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->after('user_id')->constrained('tenants')->nullOnDelete();
                    $table->index(['tenant_id', 'status', 'updated_at'], 'idx_acars_flights_tenant_status_updated');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('acars_active_flights') && Schema::hasColumn('acars_active_flights', 'tenant_id')) {
            Schema::table('acars_active_flights', function (Blueprint $table) {
                $table->dropIndex('idx_acars_flights_tenant_status_updated');
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
