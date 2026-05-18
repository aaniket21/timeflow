<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartSessionRequest;
use App\Http\Requests\StopSessionRequest;
use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use App\Models\Badge;
use App\Models\DailyChallenge;
use App\Models\TimeSession;
use App\Models\UserBadge;
use App\Models\UserChallengeCompletion;
use App\Models\XpTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    private function determineLevel(int $xpTotal): int
    {
        $thresholds = [
            1 => 0,
            2 => 200,
            3 => 600,
            4 => 1400,
            5 => 3000,
            6 => 6000,
            7 => 12000,
            8 => 25000,
        ];

        $level = 1;

        foreach ($thresholds as $candidateLevel => $threshold) {
            if ($xpTotal >= $threshold) {
                $level = $candidateLevel;
            }
        }

        return $level;
    }

    private function streakMultiplier(int $streakCurrent): float
    {
        if ($streakCurrent >= 30) {
            return 3.0;
        }

        if ($streakCurrent >= 14) {
            return 2.0;
        }

        if ($streakCurrent >= 7) {
            return 1.5;
        }

        return 1.0;
    }

    private function grantXp(int $userId, int $amount, string $reason, array $meta = []): int
    {
        if ($amount <= 0) {
            return 0;
        }

        XpTransaction::create([
            'user_id' => $userId,
            'amount' => $amount,
            'reason' => $reason,
            'meta' => $meta,
        ]);

        return $amount;
    }

    private function hasDailyXp(int $userId, string $reason, string $date): bool
    {
        return XpTransaction::query()
            ->where('user_id', $userId)
            ->where('reason', $reason)
            ->where('meta->date', $date)
            ->exists();
    }

    private function dailyTotals(int $userId, string $date): array
    {
        $baseQuery = TimeSession::query()
            ->where('user_id', $userId)
            ->whereDate('started_at', $date)
            ->whereNotNull('ended_at');

        $totalSeconds = (int) $baseQuery->sum('duration_seconds');
        $pomodoroCount = (int) (clone $baseQuery)->where('is_pomodoro', true)->count();

        return [$totalSeconds, $pomodoroCount];
    }

    private function weeklyTotalSeconds(int $userId, Carbon $date): int
    {
        $start = $date->copy()->startOfWeek()->startOfDay();
        $end = $date->copy()->endOfWeek()->endOfDay();

        return (int) TimeSession::query()
            ->where('user_id', $userId)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$start, $end])
            ->sum('duration_seconds');
    }

    private function resolveChallengeForDate(string $date): ?DailyChallenge
    {
        $count = DailyChallenge::query()->count();

        if ($count === 0) {
            return null;
        }

        $dayIndex = Carbon::parse($date)->dayOfYear - 1;
        $offset = $count > 0 ? ($dayIndex % $count) : 0;

        return DailyChallenge::query()
            ->orderBy('id')
            ->skip($offset)
            ->first();
    }

    private function applyDailyChallenge(int $userId, string $date, int $dailyTotalSeconds, int $dailyPomodoros): int
    {
        $challenge = $this->resolveChallengeForDate($date);

        if (! $challenge) {
            return 0;
        }

        $alreadyCompleted = UserChallengeCompletion::query()
            ->where('user_id', $userId)
            ->where('date', $date)
            ->exists();

        if ($alreadyCompleted) {
            return 0;
        }

        $meetsTarget = false;

        if ($challenge->type === 'hours_logged') {
            $meetsTarget = $dailyTotalSeconds >= (int) round($challenge->target_value * 3600);
        }

        if ($challenge->type === 'pomodoros') {
            $meetsTarget = $dailyPomodoros >= (int) $challenge->target_value;
        }

        if (! $meetsTarget) {
            return 0;
        }

        UserChallengeCompletion::create([
            'user_id' => $userId,
            'challenge_id' => $challenge->id,
            'date' => $date,
            'completed_at' => now(),
        ]);

        return $this->grantXp($userId, (int) $challenge->xp_reward, 'daily_challenge', [
            'date' => $date,
            'challenge_id' => $challenge->id,
        ]);
    }

    private function awardBadge(int $userId, string $slug, array &$badgesEarned, int &$xpGained): void
    {
        $badge = Badge::query()->where('slug', $slug)->first();

        if (! $badge) {
            return;
        }

        $alreadyEarned = UserBadge::query()
            ->where('user_id', $userId)
            ->where('badge_id', $badge->id)
            ->exists();

        if ($alreadyEarned) {
            return;
        }

        UserBadge::create([
            'user_id' => $userId,
            'badge_id' => $badge->id,
            'earned_at' => now(),
        ]);

        $badgesEarned[] = [
            'id' => $badge->id,
            'slug' => $badge->slug,
            'name' => $badge->name,
            'icon' => $badge->icon,
        ];

        if ($badge->xp_reward > 0) {
            $xpGained += $this->grantXp($userId, (int) $badge->xp_reward, 'badge_reward', [
                'badge_id' => $badge->id,
                'slug' => $badge->slug,
            ]);
        }
    }

    private function hasDailyGoalStreak(int $userId, string $date, int $days): bool
    {
        $dates = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $dates[] = Carbon::parse($date)->subDays($offset)->toDateString();
        }

        $count = XpTransaction::query()
            ->where('user_id', $userId)
            ->where('reason', 'daily_goal')
            ->whereIn('meta->date', $dates)
            ->count();

        return $count === $days;
    }

    private function applyGamification($user, TimeSession $session, Carbon $activityDate): array
    {
        $xpGained = 0;
        $badgesEarned = [];
        $newLevel = null;
        $activityDateString = $activityDate->toDateString();

        $lastActive = $user->last_active_date
            ? Carbon::parse($user->last_active_date)->toDateString()
            : null;
        $streakCurrent = $user->streak_current;
        $isNewDay = $lastActive !== $activityDateString;

        if ($isNewDay) {
            $isYesterday = $lastActive === Carbon::parse($activityDateString)->subDay()->toDateString();
            $streakCurrent = $isYesterday ? $user->streak_current + 1 : 1;

            $xpGained += $this->grantXp($user->id, 5, 'first_session', [
                'date' => $activityDateString,
                'session_id' => $session->id,
            ]);

            $multiplier = $this->streakMultiplier($streakCurrent);
            $streakXp = (int) round(5 * $multiplier);
            $xpGained += $this->grantXp($user->id, $streakXp, 'streak_daily', [
                'date' => $activityDateString,
                'streak_day' => $streakCurrent,
                'multiplier' => $multiplier,
            ]);
        }

        if ($session->is_pomodoro && $session->duration_seconds >= ($user->pomodoro_work_min * 60)) {
            $xpGained += $this->grantXp($user->id, 10, 'pomodoro_complete', [
                'session_id' => $session->id,
            ]);
            $this->awardBadge($user->id, 'tomato_head', $badgesEarned, $xpGained);
        }

        [$dailyTotalSeconds, $dailyPomodoros] = $this->dailyTotals($user->id, $activityDateString);

        $dailyGoalSeconds = (int) round($user->daily_goal_hours * 3600);
        $dailyGoalAchieved = $dailyGoalSeconds > 0 && $dailyTotalSeconds >= $dailyGoalSeconds;

        if ($dailyGoalAchieved && ! $this->hasDailyXp($user->id, 'daily_goal', $activityDateString)) {
            $xpGained += $this->grantXp($user->id, 25, 'daily_goal', [
                'date' => $activityDateString,
                'total_seconds' => $dailyTotalSeconds,
            ]);
        }

        if ($dailyTotalSeconds >= (8 * 3600) && ! $this->hasDailyXp($user->id, 'daily_8_hours', $activityDateString)) {
            $xpGained += $this->grantXp($user->id, 100, 'daily_8_hours', [
                'date' => $activityDateString,
                'total_seconds' => $dailyTotalSeconds,
            ]);
        }

        $xpGained += $this->applyDailyChallenge($user->id, $activityDateString, $dailyTotalSeconds, $dailyPomodoros);

        if ($isNewDay) {
            if ($streakCurrent >= 3) {
                $this->awardBadge($user->id, 'first_flame', $badgesEarned, $xpGained);
            }
            if ($streakCurrent >= 7) {
                $this->awardBadge($user->id, 'week_warrior', $badgesEarned, $xpGained);
            }
            if ($streakCurrent >= 30) {
                $this->awardBadge($user->id, 'mountain_climber', $badgesEarned, $xpGained);
            }
            if ($streakCurrent >= 100) {
                $this->awardBadge($user->id, 'centurion', $badgesEarned, $xpGained);
            }
        }

        $cumulativeSeconds = (int) TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->sum('duration_seconds');

        if ($cumulativeSeconds >= 3600) {
            $this->awardBadge($user->id, 'first_hour', $badgesEarned, $xpGained);
        }
        if ($cumulativeSeconds >= 360000) {
            $this->awardBadge($user->id, 'hundred_hours', $badgesEarned, $xpGained);
        }
        if ($cumulativeSeconds >= 3600000) {
            $this->awardBadge($user->id, 'time_lord', $badgesEarned, $xpGained);
        }

        $weeklySeconds = $this->weeklyTotalSeconds($user->id, $activityDate);
        if ($weeklySeconds >= 36000) {
            $this->awardBadge($user->id, 'ten_hour_club', $badgesEarned, $xpGained);
        }

        if ($session->duration_seconds >= (4 * 3600)) {
            $this->awardBadge($user->id, 'deep_diver', $badgesEarned, $xpGained);
        }

        if ($dailyGoalAchieved && $this->hasDailyGoalStreak($user->id, $activityDateString, 5)) {
            $this->awardBadge($user->id, 'sniper', $badgesEarned, $xpGained);
        }

        $newXpTotal = $user->xp_total + $xpGained;
        $calculatedLevel = $this->determineLevel($newXpTotal);

        if ($calculatedLevel > $user->level) {
            $newLevel = $calculatedLevel;
        }

        $user->forceFill([
            'last_active_date' => $activityDateString,
            'streak_current' => $streakCurrent,
            'streak_longest' => max($user->streak_longest, $streakCurrent),
            'xp_total' => $newXpTotal,
            'level' => $calculatedLevel,
        ])->save();

        return [
            'xp_gained' => $xpGained,
            'new_level' => $newLevel,
            'badges_earned' => $badgesEarned,
            'streak' => $user->streak_current,
        ];
    }

    public function start(StartSessionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $type = $data['type'] ?? 'timer';
        $startedAt = isset($data['started_at'])
            ? Carbon::parse($data['started_at'])
            : now();

        $session = TimeSession::create([
            'user_id' => $user->id,
            'project_id' => $data['project_id'],
            'category_id' => $data['category_id'] ?? null,
            'started_at' => $startedAt,
            'ended_at' => null,
            'duration_seconds' => null,
            'notes' => $data['notes'] ?? null,
            'is_pomodoro' => $type === 'pomodoro',
            'type' => $type,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'project_id' => $session->project_id,
                    'category_id' => $session->category_id,
                    'started_at' => Carbon::parse($session->started_at)->toIso8601String(),
                    'ended_at' => $session->ended_at,
                    'duration_seconds' => $session->duration_seconds,
                    'notes' => $session->notes,
                    'is_pomodoro' => $session->is_pomodoro,
                    'type' => $session->type,
                ],
            ],
            'meta' => [
                'xp_gained' => 0,
                'new_level' => null,
                'badges_earned' => [],
                'streak' => null,
            ],
        ], 201);
    }

    public function store(StoreSessionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $type = $data['type'] ?? $request->input('type', 'manual');
        $startedAt = Carbon::parse($data['started_at']);
        $endedAt = Carbon::parse($data['ended_at']);
        $durationSeconds = (int) $endedAt->diffInSeconds($startedAt, true);
        $session = TimeSession::create([
            'user_id' => $user->id,
            'project_id' => $data['project_id'],
            'category_id' => $data['category_id'] ?? null,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
            'notes' => $data['notes'] ?? null,
            'is_pomodoro' => $type === 'pomodoro',
            'type' => $type,
        ]);

        $meta = $this->applyGamification($user, $session, $endedAt);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'project_id' => $session->project_id,
                    'category_id' => $session->category_id,
                    'started_at' => Carbon::parse($session->started_at)->toIso8601String(),
                    'ended_at' => Carbon::parse($session->ended_at)->toIso8601String(),
                    'duration_seconds' => $session->duration_seconds,
                    'notes' => $session->notes,
                    'is_pomodoro' => $session->is_pomodoro,
                    'type' => $session->type,
                ],
            ],
            'meta' => $meta,
        ], 201);
    }

    public function update(UpdateSessionRequest $request, int $session): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $timeSession = TimeSession::query()
            ->where('id', $session)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $updates = $data;
        $startedAt = array_key_exists('started_at', $data)
            ? Carbon::parse($data['started_at'])
            : Carbon::parse($timeSession->started_at);
        $endedAt = array_key_exists('ended_at', $data)
            ? Carbon::parse($data['ended_at'])
            : ($timeSession->ended_at ? Carbon::parse($timeSession->ended_at) : null);

        if (array_key_exists('started_at', $data)) {
            $updates['started_at'] = $startedAt;
        }

        if (array_key_exists('ended_at', $data)) {
            $updates['ended_at'] = $endedAt;
        }

        if ($endedAt) {
            $updates['duration_seconds'] = $endedAt->diffInSeconds($startedAt, true);
        }

        if (array_key_exists('type', $data)) {
            $updates['is_pomodoro'] = $data['type'] === 'pomodoro';
        }

        $timeSession->update($updates);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $timeSession->id,
                    'project_id' => $timeSession->project_id,
                    'category_id' => $timeSession->category_id,
                    'started_at' => Carbon::parse($timeSession->started_at)->toIso8601String(),
                    'ended_at' => $timeSession->ended_at
                        ? Carbon::parse($timeSession->ended_at)->toIso8601String()
                        : null,
                    'duration_seconds' => $timeSession->duration_seconds,
                    'notes' => $timeSession->notes,
                    'is_pomodoro' => $timeSession->is_pomodoro,
                    'type' => $timeSession->type,
                ],
            ],
            'meta' => [
                'xp_gained' => 0,
                'new_level' => null,
                'badges_earned' => [],
                'streak' => null,
            ],
        ]);
    }

    public function stop(StopSessionRequest $request, int $session): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $timeSession = TimeSession::query()
            ->where('id', $session)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $endedAt = now();
        $durationSeconds = Carbon::parse($timeSession->started_at)->diffInSeconds($endedAt, true);
        $updates = [
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
        ];

        if (array_key_exists('notes', $data)) {
            $updates['notes'] = $data['notes'];
        }

        $timeSession->update($updates);

        $meta = $this->applyGamification($user, $timeSession, $endedAt);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $timeSession->id,
                    'project_id' => $timeSession->project_id,
                    'category_id' => $timeSession->category_id,
                    'started_at' => Carbon::parse($timeSession->started_at)->toIso8601String(),
                    'ended_at' => Carbon::parse($timeSession->ended_at)->toIso8601String(),
                    'duration_seconds' => $timeSession->duration_seconds,
                    'notes' => $timeSession->notes,
                    'is_pomodoro' => $timeSession->is_pomodoro,
                    'type' => $timeSession->type,
                ],
            ],
            'meta' => $meta,
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        $session = TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session
                    ? [
                        'id' => $session->id,
                        'project_id' => $session->project_id,
                        'category_id' => $session->category_id,
                        'started_at' => Carbon::parse($session->started_at)->toIso8601String(),
                        'ended_at' => $session->ended_at,
                        'duration_seconds' => $session->duration_seconds,
                        'notes' => $session->notes,
                        'is_pomodoro' => $session->is_pomodoro,
                        'type' => $session->type,
                    ]
                    : null,
            ],
        ]);
    }
}
