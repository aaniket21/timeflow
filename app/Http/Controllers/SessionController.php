<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartSessionRequest;
use App\Http\Requests\StopSessionRequest;
use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use App\Models\Badge;
use App\Models\Category;
use App\Models\DailyChallenge;
use App\Models\Project;
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

        $completion = UserChallengeCompletion::firstOrCreate(
            ['user_id' => $userId, 'date' => Carbon::parse($date)->startOfDay()],
            ['challenge_id' => $challenge->id, 'completed_at' => now()]
        );

        if ($completion->wasRecentlyCreated) {
            return $this->grantXp($userId, (int) $challenge->xp_reward, 'daily_challenge', [
                'date' => $date,
                'challenge_id' => $challenge->id,
            ]);
        }

        return 0;
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
            'project_id' => $data['project_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'started_at' => $startedAt,
            'ended_at' => null,
            'duration_seconds' => null,
            'notes' => $data['notes'] ?? null,
            'label' => $data['label'] ?? null,
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
            'project_id' => $data['project_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
            'notes' => $data['notes'] ?? null,
            'label' => $data['label'] ?? null,
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

        if (array_key_exists('project_id', $data)) {
            $updates['project_id'] = $data['project_id'];
        }

        if (array_key_exists('category_id', $data)) {
            $updates['category_id'] = $data['category_id'];
        }

        if (array_key_exists('notes', $data)) {
            $updates['notes'] = $data['notes'];
        }

        if (array_key_exists('label', $data)) {
            $updates['label'] = $data['label'];
        }

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
                    'label' => $timeSession->label,
                    'is_pomodoro' => $timeSession->is_pomodoro,
                    'type' => $timeSession->type,
                ],
            ],
            'meta' => $meta,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = (int) $request->query('per_page', 15);
        $limit = max(1, min(50, $limit));

        $sessions = TimeSession::query()
            ->with(['project.category', 'category'])
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->orderByDesc('started_at')
            ->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $sessions->items(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    public function destroy(Request $request, int $session): JsonResponse
    {
        $user = $request->user();
        $timeSession = TimeSession::query()
            ->where('id', $session)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $timeSession->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session deleted successfully.',
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = (int) $request->query('limit', 5);
        $limit = max(1, min(20, $limit));

        $sessions = TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get(['id', 'project_id', 'category_id', 'started_at', 'duration_seconds', 'label']);

        $projectIds = $sessions->pluck('project_id')->filter()->unique();
        $projects = $projectIds->isNotEmpty()
            ? Project::query()
                ->whereIn('id', $projectIds)
                ->get(['id', 'name', 'color', 'category_id'])
                ->keyBy('id')
            : collect();

        $categoryIds = $sessions->pluck('category_id')->filter();
        if ($projects->isNotEmpty()) {
            $categoryIds = $categoryIds->merge($projects->pluck('category_id')->filter());
        }

        $categoryIds = $categoryIds->unique();
        $categories = $categoryIds->isNotEmpty()
            ? Category::query()
                ->whereIn('id', $categoryIds)
                ->get(['id', 'name', 'color'])
                ->keyBy('id')
            : collect();

        $data = $sessions->map(function (TimeSession $session) use ($projects, $categories) {
            $project = $session->project_id ? $projects->get($session->project_id) : null;
            $category = $session->category_id ? $categories->get($session->category_id) : null;
            $projectCategory = $project && $project->category_id
                ? $categories->get($project->category_id)
                : null;

            $label = $session->label ?? $project->name ?? $category->name ?? 'Session';
            $categoryLabel = $category->name ?? ($projectCategory?->name ?? 'General');
            $color = $project->color ?? $category->color ?? '#9ca3af';

            return [
                'id' => $session->id,
                'label' => $label,
                'category' => $categoryLabel,
                'color' => $color,
                'duration_seconds' => (int) ($session->duration_seconds ?? 0),
                'started_at' => Carbon::parse($session->started_at)->toIso8601String(),
                'type' => $session->type,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
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

        $project = null;
        $category = null;
        $projectCategory = null;

        if ($session && $session->project_id) {
            $project = Project::query()->find($session->project_id, ['id', 'name', 'color', 'category_id']);
            if ($project && $project->category_id) {
                $projectCategory = Category::query()->find($project->category_id, ['id', 'name', 'color']);
            }
        }

        if ($session && $session->category_id) {
            $category = Category::query()->find($session->category_id, ['id', 'name', 'color']);
        }

        $label = $session
            ? ($session->label ?? $project?->name ?? $category?->name ?? 'Session')
            : null;
        $categoryLabel = $session
            ? ($category?->name ?? ($projectCategory?->name ?? 'General'))
            : null;
        $color = $session
            ? ($project?->color ?? $category?->color ?? 'violet')
            : null;

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
                        'label' => $label,
                        'category' => $categoryLabel,
                        'color' => $color,
                    ]
                    : null,
            ],
        ]);
    }
}
