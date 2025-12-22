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
        Schema::create('cache_scheduler_logs', function (Blueprint $table) {
            $table->id();
            $table->string('task_name');
            $table->string('status'); // success, failed, partial
            $table->decimal('execution_time', 10, 3)->nullable(); // in seconds
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // additional data
            $table->timestamps();

            $table->index(['task_name', 'created_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache_scheduler_logs');
    }
};
