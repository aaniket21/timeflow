<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\XpTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GamificationController extends Controller
{
    /**
     * Gamification profile — cached per user for 5 minutes.
     * Cache is busted on XP gain, streak update, or badge unlock.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = "gamification:profile:{$user->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($user) {
            $thresholds = $this->levelThresholds();
            $currentLevel = $user->level ?? 1;
            $nextLevel = $currentLevel + 1;
            $currentThreshold = $thresholds[$currentLevel] ?? 0;
            $nextThreshold = $thresholds[$nextLevel] ?? null;
            $progress = $nextThreshold
                ? max(0, min(1, ($user->xp_total - $currentThreshold) / max(1, $nextThreshold - $currentThreshold)))
                : 1;

            $badgeCount = UserBadge::query()
                ->where('user_id', $user->id)
                ->count();

            return [
                'xp_total' => $user->xp_total,
                'level' => $currentLevel,
                'next_level_xp' => $nextThreshold,
                'level_progress' => (float) $progress,
                'streak_current' => $user->streak_current,
                'streak_longest' => $user->streak_longest,
                'streak_shield_count' => $user->streak_shield_count,
                'badge_count' => $badgeCount,
                'last_active_date' => $user->last_active_date?->toDateString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * All badges with user's earned status — cached per user for 10 minutes.
     */
    public function badges(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = "gamification:badges:{$user->id}";

        $data = Cache::remember($cacheKey, 600, function () use ($user) {
            $earned = UserBadge::query()
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('badge_id');

            $badges = Badge::query()->orderBy('condition_type')->orderBy('id')->get();

            return $badges->map(function (Badge $badge) use ($earned) {
                $earnedRecord = $earned->get($badge->id);

                return [
                    'id' => $badge->id,
                    'slug' => $badge->slug,
                    'name' => $badge->name,
                    'description' => $badge->description,
                    'icon' => $badge->icon,
                    'condition_type' => $badge->condition_type,
                    'condition_value' => $badge->condition_value,
                    'earned' => $earnedRecord !== null,
                    'unlocked_at' => $earnedRecord?->unlocked_at,
                ];
            })->values();
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Weekly leaderboard — cached globally for 2 minutes (shared across users).
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $data = Cache::remember('gamification:leaderboard', 120, function () {
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();

            $rows = XpTransaction::query()
                ->join('users', 'users.id', '=', 'xp_transactions.user_id')
                ->where('users.leaderboard_opt_in', true)
                ->whereBetween('xp_transactions.created_at', [$weekStart, $weekEnd])
                ->groupBy('xp_transactions.user_id', 'users.name', 'users.leaderboard_alias')
                ->orderByDesc(DB::raw('SUM(xp_transactions.amount)'))
                ->limit(50)
                ->get([
                    'xp_transactions.user_id as user_id',
                    DB::raw('SUM(xp_transactions.amount) as xp'),
                    'users.name',
                    'users.leaderboard_alias',
                ]);

            return $rows->values()->map(function ($row, int $index) {
                $displayName = $row->leaderboard_alias ?: $row->name;

                return [
                    'rank' => $index + 1,
                    'user_id' => (int) $row->user_id,
                    'display_name' => $displayName,
                    'xp' => (int) $row->xp,
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function updateLeaderboardOptIn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'opt_in' => ['required', 'boolean'],
            'alias' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $alias = $data['opt_in'] ? ($data['alias'] ?? $user->leaderboard_alias) : null;

        if ($data['opt_in'] && ! $alias) {
            return response()->json([
                'success' => false,
                'message' => 'Leaderboard alias is required when opting in.',
            ], 422);
        }

        $user->forceFill([
            'leaderboard_opt_in' => $data['opt_in'],
            'leaderboard_alias' => $alias,
        ])->save();

        // Bust leaderboard cache on opt-in change
        Cache::forget('gamification:leaderboard');

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard_opt_in' => $user->leaderboard_opt_in,
                'leaderboard_alias' => $user->leaderboard_alias,
            ],
        ]);
    }

    private function levelThresholds(): array
    {
        return [
            1 => 0,
            2 => 200,
            3 => 600,
            4 => 1400,
            5 => 3000,
            6 => 6000,
            7 => 12000,
            8 => 25000,
        ];
    }
}
