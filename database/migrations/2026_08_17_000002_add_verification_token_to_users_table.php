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
            if (!Schema::hasColumn('users', 'verification_token')) {
                $table->string('verification_token', 80)->nullable()->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'verification_token_expires_at')) {
                $table->timestamp('verification_token_expires_at')->nullable()->after('verification_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verification_token', 'verification_token_expires_at']);
        });
    }
};
