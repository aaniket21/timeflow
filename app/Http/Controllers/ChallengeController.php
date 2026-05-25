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

        $challenges = $this->resolveChallengesForDate($date);

        if (empty($challenges)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'challenges' => [],
                ],
            ]);
        }

        $completions = UserChallengeCompletion::query()
            ->where('user_id', $user->id)
            ->where('completed_on', $date)
            ->pluck('challenge_id')
            ->toArray();

        $formatted = array_map(function ($challenge) use ($completions) {
            return [
                'id' => $challenge->id,
                'title' => $challenge->title,
                'description' => $challenge->description,
                'type' => $challenge->condition_type,
                'target_value' => $challenge->condition_value,
                'xp_reward' => $challenge->xp_reward,
                'difficulty' => $challenge->difficulty,
                'completed' => in_array($challenge->id, $completions),
            ];
        }, $challenges);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'challenges' => $formatted,
            ],
        ]);
    }

    private function resolveChallengesForDate(string $date): array
    {
        $dayIndex = Carbon::parse($date)->dayOfYear - 1;

        $easy = DailyChallenge::where('difficulty', 'easy')->orderBy('id')->get();
        $medium = DailyChallenge::where('difficulty', 'medium')->orderBy('id')->get();
        $hard = DailyChallenge::where('difficulty', 'hard')->orderBy('id')->get();

        $selected = [];

        if ($easy->count() > 0) {
            $selected[] = $easy[$dayIndex % $easy->count()];
        }
        if ($medium->count() > 0) {
            $selected[] = $medium[$dayIndex % $medium->count()];
        }
        if ($hard->count() > 0) {
            $selected[] = $hard[$dayIndex % $hard->count()];
        }
        
        // Fallback for DBs without difficulties properly set yet
        if (empty($selected)) {
             $all = DailyChallenge::orderBy('id')->get();
             if ($all->count() > 0) {
                 $selected[] = $all[$dayIndex % $all->count()];
             }
        }

        return $selected;
    }
}
