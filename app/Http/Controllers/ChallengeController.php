<?php

namespace App\Http\Controllers;

use App\Helpers\TimeHelper;
use App\Models\DailyChallenge;
use App\Models\UserChallengeCompletion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    /**
     * PRD §6 — Today's challenge uses TimeHelper::todayForUser() as default date.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();

        // PRD §6 — Use timezone-aware "today" as default
        $dateInput = $request->query('date', TimeHelper::todayForUser($user));
        $date = Carbon::parse($dateInput)->toDateString();

        $challenge = $this->resolveChallengeForDate($date);

        if (! $challenge) {
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'challenge' => null,
                    'completed' => false,
                ],
            ]);
        }

        // V2: completed_on column instead of date
        $completed = UserChallengeCompletion::query()
            ->where('user_id', $user->id)
            ->where('completed_on', $date)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'challenge' => [
                    'id' => $challenge->id,
                    'title' => $challenge->title,
                    'description' => $challenge->description,
                    'type' => $challenge->condition_type,
                    'target_value' => $challenge->condition_value,
                    'xp_reward' => $challenge->xp_reward,
                ],
                'completed' => $completed,
            ],
        ]);
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
}
