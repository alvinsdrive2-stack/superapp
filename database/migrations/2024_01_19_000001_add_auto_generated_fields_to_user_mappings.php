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
        Schema::table('user_mappings', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('auto_generated_email')->nullable()->after('is_active');
            $table->string('auto_generated_password')->nullable()->after('auto_generated_email');
            $table->timestamp('last_sync_at')->nullable()->after('updated_at');

            // Add indexes for better performance
            $table->index(['system_name', 'is_active']);
            $table->index(['email', 'system_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_mappings', function (Blueprint $table) {
            $table->dropIndex(['system_name', 'is_active']);
            $table->dropIndex(['email', 'system_name']);
            $table->dropColumn(['is_active', 'auto_generated_email', 'auto_generated_password', 'last_sync_at']);
        });
    }
};