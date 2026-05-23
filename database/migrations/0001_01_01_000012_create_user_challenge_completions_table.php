<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD §16 — user_challenge_completions table.
     */
    public function up(): void
    {
        Schema::create('user_challenge_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('challenge_id')->constrained('daily_challenges')->cascadeOnDelete();
            $table->date('completed_on')->comment('User local date');
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'challenge_id', 'completed_on'], 'uk_completion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_challenge_completions');
    }
};
