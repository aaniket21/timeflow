<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PRD §16 — users table with all V2 gamification, timezone, and admin fields.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_admin')->default(false);

            // Two-factor auth (Fortify)
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // Gamification
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedInteger('xp_total')->default(0);
            $table->unsignedSmallInteger('streak_current')->default(0);
            $table->unsignedSmallInteger('streak_longest')->default(0);
            $table->unsignedTinyInteger('streak_shield_count')->default(0);
            $table->date('last_active_date')->nullable();

            // Settings
            $table->string('timezone', 64)->default('UTC');
            $table->decimal('daily_goal_hours', 4, 2)->default(6.00);
            $table->string('theme', 16)->default('dark');
            $table->string('locale', 8)->default('en');

            // Pomodoro preferences
            $table->unsignedTinyInteger('pomodoro_work_min')->default(25);
            $table->unsignedTinyInteger('pomodoro_break_min')->default(5);

            // Notification preferences
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('email_digest_enabled')->default(true);

            // Leaderboard
            $table->boolean('leaderboard_opt_in')->default(false);
            $table->string('leaderboard_alias', 50)->nullable();

            // Profile
            $table->string('avatar_url', 500)->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes (PRD §5.3)
            $table->index('xp_total', 'idx_users_xp');
            $table->index('streak_current', 'idx_users_streak');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
