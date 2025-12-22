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
        Schema::create('dashboard_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique();
            $table->longText('cache_data');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['expires_at']);
            $table->index(['cache_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_cache');
    }
};
