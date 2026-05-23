<?php

namespace App\Services;

use App\Helpers\TimeHelper;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * PRD §6 — Streak Service.
 *
 * Manages user streaks using the Unified Timezone System.
 * All date comparisons use the user's local timezone via TimeHelper.
 *
 * Streak rules:
 * - Streak increments when user logs activity on a new day (their timezone)
 * - Streak resets to 1 if they miss a day (unless they have a streak shield)
 * - Streak shield auto-applies on miss, preventing reset for that day
 * - Maximum streak shields: 2
 */
class StreakService
{
    /**
     * Update streak for the user based on the current activity.
     * Called when a session is completed or a habit is logged.
     *
     * @return array{streak_current: int, streak_longest: int, shield_used: bool}
     */
    public function updateStreak(User $user): array
    {
        $today = TimeHelper::todayForUser($user);
        $lastActive = $user->last_active_date?->toDateString();
        $shieldUsed = false;

        // Same day — no change to streak
        if ($lastActive === $today) {
            return [
                'streak_current' => $user->streak_current,
                'streak_longest' => $user->streak_longest,
                'shield_used' => false,
            ];
        }

        $yesterday = TimeHelper::yesterdayForUser($user);

        if ($lastActive === $yesterday) {
            // Consecutive day — increment streak
            $user->streak_current += 1;
        } elseif ($lastActive !== null) {
            // Missed day(s) — check for streak shield
            if ($user->streak_shield_count > 0) {
                $user->streak_shield_count -= 1;
                $user->streak_current += 1;
                $shieldUsed = true;
            } else {
                // Reset streak
                $user->streak_current = 1;
            }
        } else {
            // First ever activity
            $user->streak_current = 1;
        }

        $user->streak_longest = max($user->streak_longest, $user->streak_current);
        $user->last_active_date = $today;
        $user->save();

        // Bust gamification profile cache
        Cache::forget("gamification:profile:{$user->id}");

        return [
            'streak_current' => $user->streak_current,
            'streak_longest' => $user->streak_longest,
            'shield_used' => $shieldUsed,
        ];
    }

    /**
     * Recalculate streak for a specific user.
     * Called by the nightly timeflow:check-streaks command.
     *
     * If the user didn't log any activity "yesterday" (in their timezone),
     * and today they still haven't logged anything, apply shield or break streak.
     */
    public function recalculate(User $user): void
    {
        $today = TimeHelper::todayForUser($user);
        $lastActive = $user->last_active_date?->toDateString();

        // User was active today — nothing to do
        if ($lastActive === $today) {
            return;
        }

        $yesterday = TimeHelper::yesterdayForUser($user);

        // User was active yesterday — nothing to do yet (they still have today)
        if ($lastActive === $yesterday) {
            return;
        }

        // User missed yesterday AND today — streak should be broken
        // (unless they have a shield)
        if ($lastActive !== null && $user->streak_current > 0) {
            if ($user->streak_shield_count > 0) {
                $user->streak_shield_count -= 1;
                // Don't break streak, shield absorbed the miss
            } else {
                $user->streak_current = 0;
            }

            $user->save();
            Cache::forget("gamification:profile:{$user->id}");
        }
    }

    /**
     * Award a streak shield to the user.
     * Called when they complete 7 consecutive daily challenges.
     * Maximum 2 shields.
     */
    public function awardShield(User $user): bool
    {
        if ($user->streak_shield_count >= 2) {
            return false;
        }

        $user->streak_shield_count += 1;
        $user->save();

        Cache::forget("gamification:profile:{$user->id}");

        return true;
    }
}
