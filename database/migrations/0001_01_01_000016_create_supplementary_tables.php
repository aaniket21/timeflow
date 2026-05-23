<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additional tables from V1 that are still used by the app.
     * Not in PRD §16 schema but needed for backward compatibility.
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 100);
            $table->date('exam_date');
            $table->string('notes', 280)->nullable();
            $table->timestamps();
        });

        Schema::create('daily_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->json('tasks');
            $table->timestamps();

            $table->unique(['user_id', 'date'], 'unique_user_date');
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->date('date_from');
            $table->date('date_to');
            $table->json('project_ids')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('share_token', 64)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('daily_plans');
        Schema::dropIfExists('exams');
    }
};
