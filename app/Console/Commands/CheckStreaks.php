<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\StreakService;
use Illuminate\Console\Command;

/**
 * PRD §6 — Nightly streak recalculation.
 *
 * Runs at 01:00 UTC daily via scheduler. Iterates over all users
 * with an active streak and recalculates using StreakService.
 *
 * Usage: php artisan timeflow:check-streaks
 */
class CheckStreaks extends Command
{
    protected $signature = 'timeflow:check-streaks';

    protected $description = 'Recalculate streaks for users who may have missed a day';

    public function handle(StreakService $streakService): int
    {
        $users = User::query()
            ->where('streak_current', '>', 0)
            ->whereNotNull('last_active_date')
            ->cursor();

        $processed = 0;
        $broken = 0;
        $shielded = 0;

        foreach ($users as $user) {
            $beforeStreak = $user->streak_current;
            $beforeShield = $user->streak_shield_count;

            $streakService->recalculate($user);

            $user->refresh();

            if ($user->streak_current === 0 && $beforeStreak > 0) {
                $broken++;
            }

            if ($user->streak_shield_count < $beforeShield) {
                $shielded++;
            }

            $processed++;
        }

        $this->info("Processed {$processed} users. Broken: {$broken}. Shielded: {$shielded}.");

        return self::SUCCESS;
    }
}
