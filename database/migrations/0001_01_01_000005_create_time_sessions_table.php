<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD §16 — time_sessions table with full V2 schema.
     * Includes label/label_type fields, performance indexes, and FULLTEXT index on notes.
     */
    public function up(): void
    {
        Schema::create('time_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('label', 100)->nullable();
            $table->enum('label_type', ['focus_mode', 'project', 'activity', 'other'])->default('other');
            $table->text('notes')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('xp_earned')->default(0);
            $table->boolean('is_pomodoro')->default(false);
            $table->timestamps();

            // Performance indexes (PRD §5.3)
            $table->index(['user_id', 'started_at'], 'idx_sessions_user_date');
            $table->index(['user_id', 'ended_at'], 'idx_sessions_active');
            $table->index(['user_id', 'project_id', 'started_at'], 'idx_sessions_project');
            $table->fullText('notes', 'idx_sessions_notes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_sessions');
    }
};
