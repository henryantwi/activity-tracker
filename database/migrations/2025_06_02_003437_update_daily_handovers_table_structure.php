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
        Schema::table('daily_handovers', function (Blueprint $table) {
            // Add new columns
            $table->date('handover_date')->after('to_user_id');
            $table->text('shift_summary')->nullable()->after('activities_data');
            $table->text('pending_tasks')->nullable()->after('shift_summary');
            $table->text('important_notes')->nullable()->after('pending_tasks');
            $table->boolean('is_acknowledged')->default(false)->after('important_notes');
            
            // Rename handover_time to handover_time (keep it as timestamp for exact time)
            // Remove the old notes column and replace with specific fields
            $table->dropColumn('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_handovers', function (Blueprint $table) {
            // Reverse the changes
            $table->dropColumn(['handover_date', 'shift_summary', 'pending_tasks', 'important_notes', 'is_acknowledged']);
            $table->text('notes')->nullable();
        });
    }
};
