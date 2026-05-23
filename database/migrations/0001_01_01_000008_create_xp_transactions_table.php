<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD §16 — xp_transactions table (audit log for XP history chart).
     */
    public function up(): void
    {
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('amount');
            $table->string('reason', 64)->comment('session, daily_goal, challenge, streak_bonus');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            // Performance index (PRD §5.3)
            $table->index(['user_id', 'created_at'], 'idx_xp_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_transactions');
    }
};
