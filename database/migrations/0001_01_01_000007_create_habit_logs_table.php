<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD §16 — habit_logs table.
     * UNIQUE KEY (goal_id, date) prevents race conditions at DB level (P1.3).
     */
    public function up(): void
    {
        Schema::create('habit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('goals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date')->comment('User local date from TimeHelper::todayForUser');
            $table->boolean('done')->default(false);
            $table->timestamps();

            // PRD §5.3 — race condition prevention
            $table->unique(['goal_id', 'date'], 'uk_habit_logs_goal_date');
            $table->index(['user_id', 'date'], 'idx_habit_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_logs');
    }
};
