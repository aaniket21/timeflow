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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url', 500)->nullable();
            $table->string('timezone', 50)->default('Asia/Kolkata');
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedInteger('xp_total')->default(0);
            $table->unsignedSmallInteger('streak_current')->default(0);
            $table->unsignedSmallInteger('streak_longest')->default(0);
            $table->unsignedTinyInteger('streak_shield')->default(0);
            $table->date('last_active_date')->nullable();
            $table->decimal('daily_goal_hours', 4, 2)->default(6.00);
            $table->unsignedTinyInteger('pomodoro_work_min')->default(25);
            $table->unsignedTinyInteger('pomodoro_break_min')->default(5);
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('leaderboard_opt_in')->default(false);
            $table->string('leaderboard_alias', 50)->nullable();
            $table->boolean('email_digest_enabled')->default(true);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_url',
                'timezone',
                'level',
                'xp_total',
                'streak_current',
                'streak_longest',
                'streak_shield',
                'last_active_date',
                'daily_goal_hours',
                'pomodoro_work_min',
                'pomodoro_break_min',
                'notifications_enabled',
                'leaderboard_opt_in',
                'leaderboard_alias',
                'email_digest_enabled',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
