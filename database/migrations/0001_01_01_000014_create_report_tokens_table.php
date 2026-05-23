<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD §16 — report_tokens table.
     * Supports async report generation with status polling.
     */
    public function up(): void
    {
        Schema::create('report_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->date('date_from');
            $table->date('date_to');
            $table->enum('status', ['queued', 'generating', 'ready', 'failed'])->default('queued');
            $table->string('token', 64)->unique();
            $table->string('file_path', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_tokens');
    }
};
