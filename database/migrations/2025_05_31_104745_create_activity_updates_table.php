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
        Schema::create('activity_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled']);
            $table->text('remarks')->nullable();
            $table->json('previous_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamp('update_time')->default(now());
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_updates');
    }
};
