<?php

namespace App\Http\Controllers;

use App\Models\DailyPlan;
use App\Models\XpTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyPlanController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = Carbon::parse($request->query('date', now()->toDateString()))->toDateString();

        $plan = DailyPlan::query()
            ->where('user_id', $user->id)
            ->where('date', $date)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $plan,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'tasks' => ['required', 'array', 'min:1', 'max:3'],
            'tasks.*.text' => ['required', 'string', 'max:140'],
            'tasks.*.done' => ['required', 'boolean'],
        ]);

        $date = Carbon::parse($data['date'] ?? now())->toDateString();

        $plan = DailyPlan::query()->updateOrCreate([
            'user_id' => $user->id,
            'date' => $date,
        ], [
            'tasks' => $data['tasks'],
        ]);

        $allDone = collect($data['tasks'])->every(function ($task) {
            return (bool) $task['done'];
        });

        $xpGained = 0;
        $newLevel = null;

        if ($allDone && ! $this->hasDailyXp($user->id, 'daily_plan_complete', $date)) {
            $xpGained += $this->grantXp($user->id, 30, 'daily_plan_complete', $date);

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

        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $plan,
            ],
            'meta' => [
                'xp_gained' => $xpGained,
                'new_level' => $newLevel,
            ],
        ]);
    }

    /**
     * Check if XP was already awarded for a specific reason and date.
     * Uses reference_type to store the date string (xp_transactions has no meta column).
     */
    private function hasDailyXp(int $userId, string $reason, string $date): bool
    {
        return XpTransaction::query()
            ->where('user_id', $userId)
            ->where('reason', $reason)
            ->where('reference_type', $date)
            ->exists();
    }

    /**
     * Grant XP using reference_type to store the date for deduplication.
     */
    private function grantXp(int $userId, int $amount, string $reason, string $date): int
    {
        if ($amount <= 0) {
            return 0;
        }

        XpTransaction::create([
            'user_id' => $userId,
            'amount' => $amount,
            'reason' => $reason,
            'reference_type' => $date,
        ]);

        return $amount;
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
