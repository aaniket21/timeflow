<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\HabitLog;
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
            $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('created_at')->get(),
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
                ->where('active', true)
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
            'active' => $isActive,
            'reminder_time' => $data['reminder_time'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'goal' => $goal,
            ],
        ], 201);
    }

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

        $date = Carbon::parse($data['date'] ?? now())->toDateString();

        $log = HabitLog::query()->firstOrNew([
            'user_id' => $user->id,
            'goal_id' => $habit->id,
            'date' => $date,
        ]);

        $previousDone = (bool) ($log->exists ? $log->done : false);
        $done = array_key_exists('done', $data) ? (bool) $data['done'] : ! $previousDone;

        $log->done = $done;
        $log->save();

        $xpGained = 0;
        $newLevel = null;

        if ($done && ! $previousDone && ! $this->hasHabitXp($user->id, $habit->id, $date, 'habit_complete')) {
            $xpGained += $this->grantXp($user->id, 5, 'habit_complete', [
                'goal_id' => $habit->id,
                'date' => $date,
            ]);
        }

        if ($done && $this->allHabitsCompleted($user->id, $date) && ! $this->hasDailyXp($user->id, 'habit_full_day', $date)) {
            $xpGained += $this->grantXp($user->id, 20, 'habit_full_day', [
                'date' => $date,
            ]);
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

    private function hasHabitXp(int $userId, int $goalId, string $date, string $reason): bool
    {
        return XpTransaction::query()
            ->where('user_id', $userId)
            ->where('reason', $reason)
            ->where('meta->goal_id', $goalId)
            ->where('meta->date', $date)
            ->exists();
    }

    private function hasDailyXp(int $userId, string $reason, string $date): bool
    {
        return XpTransaction::query()
            ->where('user_id', $userId)
            ->where('reason', $reason)
            ->where('meta->date', $date)
            ->exists();
    }

    private function allHabitsCompleted(int $userId, string $date): bool
    {
        $habitGoals = Goal::query()
            ->where('user_id', $userId)
            ->where('type', 'habit')
            ->where('active', true)
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
