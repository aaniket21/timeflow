<?php

namespace App\Http\Controllers;

use App\Helpers\TimeHelper;
use App\Models\Goal;
use App\Models\HabitLog;
use App\Models\TimeSession;
use App\Models\XpTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->query('type');
        $active = $request->query('active');

        $query = Goal::query()->where('user_id', $user->id);

        if ($type) {
            $query->where('type', $type);
        }

        if ($active !== null) {
            $query->where('is_active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('created_at')->get(),
        ]);
    }

    /**
     * PRD §6 — Summary uses TimeHelper for timezone-aware date and UTC bounds.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        // PRD §6 — Use timezone-aware "today" as default
        $dateString = $request->query('date', TimeHelper::todayForUser($user));
        [$dayStart, $dayEnd] = TimeHelper::dateBoundsUtc($user, $dateString);
        [$weekStart, $weekEnd] = TimeHelper::weekBoundsUtc($user, $dateString);

        $dailySeconds = (int) TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$dayStart, $dayEnd])
            ->sum('duration_seconds');

        $weeklySeconds = (int) TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$weekStart, $weekEnd])
            ->sum('duration_seconds');

        $tz = $user->timezone ?? 'UTC';
        $weekStartDate = Carbon::parse($dateString, $tz)->startOfWeek()->toDateString();
        $weekEndDate = Carbon::parse($dateString, $tz)->endOfWeek()->toDateString();

        $goals = Goal::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereIn('type', ['daily_hours', 'weekly_hours', 'focus_hours'])
            ->orderByDesc('created_at')
            ->get();

        $data = $goals->map(function (Goal $goal) use ($dailySeconds, $weeklySeconds) {
            $target = (float) $goal->target_value;
            $currentSeconds = $goal->type === 'weekly_hours' ? $weeklySeconds : $dailySeconds;
            $currentValue = round($currentSeconds / 3600, 2);
            $progress = $target > 0 ? (int) round(($currentValue / $target) * 100) : 0;

            return [
                'id' => $goal->id,
                'title' => $goal->title,
                'type' => $goal->type,
                'target_value' => $target,
                'current_value' => $currentValue,
                'progress_percent' => $progress,
                'hit' => $target > 0 ? $currentValue >= $target : false,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $dateString,
                'week_start' => $weekStartDate,
                'week_end' => $weekEndDate,
                'goals' => $data,
            ],
        ]);
    }

    /**
     * PRD §6 — weekHabits uses TimeHelper for timezone-aware week start.
     */
    public function weekHabits(Request $request): JsonResponse
    {
        $user = $request->user();
        $tz = $user->timezone ?? 'UTC';

        // PRD §6 — Default start is timezone-aware start of week
        $startInput = $request->query('start');
        $start = $startInput
            ? Carbon::parse($startInput, $tz)->startOfDay()
            : Carbon::now($tz)->startOfWeek()->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $streakDate = $request->query('date', TimeHelper::todayForUser($user));

        $habits = Goal::query()
            ->where('user_id', $user->id)
            ->where('type', 'habit')
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get();

        $habitIds = $habits->pluck('id');
        $logs = $habitIds->isNotEmpty()
            ? HabitLog::query()
                ->where('user_id', $user->id)
                ->whereIn('goal_id', $habitIds)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get()
            : collect();

        $logMap = [];
        foreach ($logs as $log) {
            $dateKey = $log->date instanceof Carbon ? $log->date->toDateString() : (string) $log->date;
            $logMap[$log->goal_id][$dateKey] = (bool) $log->done;
        }

        $checksTotal = 0;
        $longestStreak = 0;

        $data = $habits->map(function (Goal $habit) use (&$checksTotal, &$longestStreak, $logMap, $start, $streakDate, $user) {
            $checks = [];
            $cursor = $start->copy();

            for ($i = 0; $i < 7; $i++) {
                $dateKey = $cursor->toDateString();
                $done = (bool) ($logMap[$habit->id][$dateKey] ?? false);
                $checks[] = $done ? 1 : 0;
                if ($done) {
                    $checksTotal++;
                }
                $cursor->addDay();
            }

            $streak = $this->calculateHabitStreak($user->id, $habit->id, $streakDate);
            $longestStreak = max($longestStreak, $streak);

            return [
                'id' => $habit->id,
                'title' => $habit->title,
                'checks' => $checks,
                'streak_current' => $streak,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'habits' => $data,
                'stats' => [
                    'active_habits' => $habits->count(),
                    'checks_total' => $checksTotal,
                    'longest_streak' => $longestStreak,
                ],
            ],
        ]);
    }

    /**
     * PRD §6 — todayHabits uses TimeHelper::todayForUser() as default date.
     */
    public function todayHabits(Request $request): JsonResponse
    {
        $user = $request->user();

        // PRD §6 — Use timezone-aware "today" as default
        $date = $request->query('date', TimeHelper::todayForUser($user));

        $habits = Goal::query()
            ->where('user_id', $user->id)
            ->where('type', 'habit')
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get();

        $logs = HabitLog::query()
            ->where('user_id', $user->id)
            ->whereIn('goal_id', $habits->pluck('id'))
            ->where('date', $date)
            ->get()
            ->keyBy('goal_id');

        $data = $habits->map(function (Goal $habit) use ($logs, $date, $user) {
            $log = $logs->get($habit->id);
            $done = $log ? (bool) $log->done : false;
            $streak = $this->calculateHabitStreak($user->id, $habit->id, $date);

            return [
                'id' => $habit->id,
                'title' => $habit->title,
                'target_value' => (float) $habit->target_value,
                'done' => $done,
                'streak_current' => $streak,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'habits' => $data,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'type' => ['required', 'in:daily_hours,weekly_hours,focus_hours,habit'],
            'title' => ['required', 'string', 'max:100'],
            'target_value' => ['required', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'reminder_time' => ['nullable', 'date_format:H:i'],
        ]);

        $isActive = $data['active'] ?? true;

        if ($data['type'] === 'habit' && $isActive) {
            $habitCount = Goal::query()
                ->where('user_id', $user->id)
                ->where('type', 'habit')
                ->where('is_active', true)
                ->count();

            if ($habitCount >= 6) {
                return response()->json([
                    'success' => false,
                    'message' => 'Habit limit reached.',
                ], 422);
            }
        }

        $goal = Goal::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'target_value' => $data['target_value'],
            'is_active' => $isActive,
            'reminder_time' => $data['reminder_time'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'goal' => $goal,
            ],
        ], 201);
    }

    public function update(Request $request, int $goal): JsonResponse
    {
        $user = $request->user();
        $existing = Goal::query()
            ->where('id', $goal)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:100'],
            'target_value' => ['sometimes', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $updates = [];
        if (array_key_exists('title', $data)) {
            $updates['title'] = $data['title'];
        }
        if (array_key_exists('target_value', $data)) {
            $updates['target_value'] = $data['target_value'];
        }
        if (array_key_exists('active', $data)) {
            $updates['is_active'] = $data['active'];
        }

        $existing->update($updates);

        return response()->json([
            'success' => true,
            'data' => [
                'goal' => $existing,
            ],
        ]);
    }

    public function destroy(Request $request, int $goal): JsonResponse
    {
        $user = $request->user();
        $existing = Goal::query()
            ->where('id', $goal)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $existing->forceFill(['is_active' => false])->save();

        return response()->json([
            'success' => true,
            'data' => [
                'goal' => $existing,
            ],
        ]);
    }

    /**
     * PRD §6 — logHabit uses TimeHelper::todayForUser() as default date.
     */
    public function logHabit(Request $request, int $goal): JsonResponse
    {
        $user = $request->user();
        $habit = Goal::query()
            ->where('id', $goal)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($habit->type !== 'habit') {
            return response()->json([
                'success' => false,
                'message' => 'Goal is not a habit.',
            ], 422);
        }

        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'done' => ['nullable', 'boolean'],
        ]);

        // PRD §6 — Default to timezone-aware "today"
        $date = isset($data['date'])
            ? Carbon::parse($data['date'])->toDateString()
            : TimeHelper::todayForUser($user);

        $log = HabitLog::query()
            ->where('user_id', $user->id)
            ->where('goal_id', $habit->id)
            ->where('date', $date)
            ->first();

        if (!$log) {
            $log = new HabitLog([
                'user_id' => $user->id,
                'goal_id' => $habit->id,
                'date' => $date,
            ]);
        }

        $previousDone = (bool) ($log->exists ? $log->done : false);
        $done = array_key_exists('done', $data) ? (bool) $data['done'] : ! $previousDone;
        $log->done = $done;

        try {
            $log->save();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                $log = HabitLog::query()
                    ->where('user_id', $user->id)
                    ->where('goal_id', $habit->id)
                    ->where('date', $date)
                    ->firstOrFail();
                    
                $previousDone = (bool) $log->done;
                $log->done = $done;
                $log->save();
            } else {
                throw $e;
            }
        }

        $xpGained = 0;
        $newLevel = null;

        if ($done && ! $previousDone && ! $this->hasHabitXp($user->id, $habit->id, $date, 'habit_complete')) {
            $xpGained += $this->grantXp($user->id, 5, 'habit_complete', $habit->id, "date:{$date}");
        }

        if ($done && $this->allHabitsCompleted($user->id, $date) && ! $this->hasDailyXp($user->id, 'habit_full_day', $date)) {
            $xpGained += $this->grantXp($user->id, 20, 'habit_full_day', null, "date:{$date}");
        }

        if ($xpGained > 0) {
            $newXpTotal = $user->xp_total + $xpGained;
            $calculatedLevel = $this->determineLevel($newXpTotal);

            if ($calculatedLevel > $user->level) {
                $newLevel = $calculatedLevel;
            }

            $user->forceFill([
                'xp_total' => $newXpTotal,
                'level' => $calculatedLevel,
            ])->save();
        }

        $streak = $this->calculateHabitStreak($user->id, $habit->id, $date);

        return response()->json([
            'success' => true,
            'data' => [
                'log' => [
                    'id' => $log->id,
                    'goal_id' => $log->goal_id,
                    'date' => $log->date,
                    'done' => $log->done,
                ],
                'streak_current' => $streak,
            ],
            'meta' => [
                'xp_gained' => $xpGained,
                'new_level' => $newLevel,
            ],
        ]);
    }

    /**
     * V2: XP transactions use reference_id/reference_type instead of meta JSON.
     */
    private function grantXp(int $userId, int $amount, string $reason, ?int $referenceId = null, ?string $referenceType = null): int
    {
        if ($amount <= 0) {
            return 0;
        }

        XpTransaction::create([
            'user_id' => $userId,
            'amount' => $amount,
            'reason' => $reason,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);

        return $amount;
    }

    private function hasHabitXp(int $userId, int $goalId, string $date, string $reason): bool
    {
        return XpTransaction::query()
            ->where('user_id', $userId)
            ->where('reason', $reason)
            ->where('reference_id', $goalId)
            ->where('reference_type', "date:{$date}")
            ->exists();
    }

    private function hasDailyXp(int $userId, string $reason, string $date): bool
    {
        return XpTransaction::query()
            ->where('user_id', $userId)
            ->where('reason', $reason)
            ->where('reference_type', "date:{$date}")
            ->exists();
    }

    private function allHabitsCompleted(int $userId, string $date): bool
    {
        $habitGoals = Goal::query()
            ->where('user_id', $userId)
            ->where('type', 'habit')
            ->where('is_active', true)
            ->pluck('id');

        if ($habitGoals->isEmpty()) {
            return false;
        }

        $completed = HabitLog::query()
            ->where('user_id', $userId)
            ->whereIn('goal_id', $habitGoals)
            ->where('date', $date)
            ->where('done', true)
            ->count();

        return $completed === $habitGoals->count();
    }

    private function calculateHabitStreak(int $userId, int $goalId, string $date): int
    {
        $streak = 0;
        $cursor = Carbon::parse($date);

        for ($i = 0; $i < 60; $i++) {
            $done = HabitLog::query()
                ->where('user_id', $userId)
                ->where('goal_id', $goalId)
                ->where('date', $cursor->toDateString())
                ->where('done', true)
                ->exists();

            if (! $done) {
                break;
            }

            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

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
}
