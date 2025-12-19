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
        Schema::table('sso_user_systems', function (Blueprint $table) {
            if (!Schema::hasColumn('sso_user_systems', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('sso_user_systems', 'system_role')) {
                $table->string('system_role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sso_user_systems', function (Blueprint $table) {
            if (Schema::hasColumn('sso_user_systems', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};