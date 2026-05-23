<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PRD §16 — timetable_blocks table.
     * Type is a plain string (not enum) to allow flexible types like 'other'.
     */
    public function up(): void
    {
        Schema::create('timetable_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100);
            $table->string('type', 50)->default('other');
            $table->unsignedTinyInteger('day_of_week')->comment('1=Mon, 7=Sun');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('color', 7)->default('#6C63FF');
            $table->boolean('is_recurring')->default(true);
            $table->timestamps();

            // Performance index (PRD §5.3)
            $table->index(['user_id', 'day_of_week'], 'idx_timetable_user_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_blocks');
    }
};
