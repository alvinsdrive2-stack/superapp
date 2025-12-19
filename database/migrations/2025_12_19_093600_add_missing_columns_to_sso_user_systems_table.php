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
            if (!Schema::hasColumn('sso_user_systems', 'connected_at')) {
                $table->timestamp('connected_at')->nullable();
            }
            if (!Schema::hasColumn('sso_user_systems', 'connected_by')) {
                $table->unsignedBigInteger('connected_by')->nullable();
            }
            if (!Schema::hasColumn('sso_user_systems', 'connection_status')) {
                $table->string('connection_status')->default('pending');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sso_user_systems', function (Blueprint $table) {
            //
        });
    }
};
