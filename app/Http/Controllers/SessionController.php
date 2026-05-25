<?php

namespace App\Http\Controllers;

use App\Helpers\TimeHelper;
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
use App\Services\StreakService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(
        private readonly StreakService $streakService,
    ) {}

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

    /**
     * V2: XP transactions use reference_id/reference_type instead of meta JSON.
     * reference_type stores a context key like 'date:2026-05-21' for daily dedup.
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

    /**
     * Check if a date-specific XP reward was already given today.
     * Uses reference_type = 'date:{YYYY-MM-DD}' for daily dedup.
     */
    private function hasDailyXp(int $userId, string $reason, string $date): bool
    {
        return XpTransaction::query()
            ->where('user_id', $userId)
            ->where('reason', $reason)
            ->where('reference_type', "date:{$date}")
            ->exists();
    }

    /**
     * PRD §6 — Timezone-aware daily totals.
     * Uses TimeHelper::dateBoundsUtc() to query sessions within the user's
     * local day, NOT whereDate() which is timezone-unaware.
     */
    private function dailyTotals($user, string $activityDateString): array
    {
        [$dayStart, $dayEnd] = TimeHelper::dateBoundsUtc($user, $activityDateString);

        $baseQuery = TimeSession::query()
            ->where('user_id', $user->id)
            ->whereBetween('started_at', [$dayStart, $dayEnd])
            ->whereNotNull('ended_at');

        $totalSeconds = (int) $baseQuery->sum('duration_seconds');
        $pomodoroCount = (int) (clone $baseQuery)->where('is_pomodoro', true)->count();

        return [$totalSeconds, $pomodoroCount];
    }

    /**
     * PRD §6 — Timezone-aware weekly total.
     * Uses TimeHelper::weekBoundsUtc() instead of raw Carbon start/endOfWeek.
     */
    private function weeklyTotalSeconds($user, string $activityDateString): int
    {
        [$weekStart, $weekEnd] = TimeHelper::weekBoundsUtc($user, $activityDateString);

        return (int) TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$weekStart, $weekEnd])
            ->sum('duration_seconds');
    }

    private function resolveChallengeForDate(string $date): ?\App\Models\DailyChallenge
    {
        // Kept for backward compatibility if needed, but not used now
        return null;
    }

    private function applyDailyChallenge(int $userId, string $date, int $dailyTotalSeconds, int $dailyPomodoros): int
    {
        $challenges = $this->resolveChallengesForDate($date);

        if (empty($challenges)) {
            return 0;
        }

        $totalXpGained = 0;

        foreach ($challenges as $challenge) {
            $meetsTarget = false;

            if ($challenge->condition_type === 'hours_logged') {
                $meetsTarget = $dailyTotalSeconds >= (int) round($challenge->condition_value * 3600);
            }

            if ($challenge->condition_type === 'pomodoros') {
                $meetsTarget = $dailyPomodoros >= (int) $challenge->condition_value;
            }

            if (! $meetsTarget) {
                continue;
            }

            $completion = \App\Models\UserChallengeCompletion::firstOrCreate(
                ['user_id' => $userId, 'daily_challenge_id' => $challenge->id, 'completed_on' => \Carbon\Carbon::parse($date)->toDateString()],
            );

            if ($completion->wasRecentlyCreated) {
                $completions = \App\Models\UserChallengeCompletion::where('user_id', $userId)->count();
                if ($completions > 0 && $completions % 7 === 0) {
                    app(\App\Services\StreakService::class)->awardShield(\App\Models\User::find($userId));
                }
                $totalXpGained += $this->grantXp($userId, (int) $challenge->xp_reward, 'daily_challenge', $challenge->id, "date:{$date}");
            }
        }

        return $totalXpGained;
    }

    private function resolveChallengesForDate(string $date): array
    {
        $dayIndex = \Carbon\Carbon::parse($date)->dayOfYear - 1;

        $easy = \App\Models\DailyChallenge::where('difficulty', 'easy')->orderBy('id')->get();
        $medium = \App\Models\DailyChallenge::where('difficulty', 'medium')->orderBy('id')->get();
        $hard = \App\Models\DailyChallenge::where('difficulty', 'hard')->orderBy('id')->get();

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

        if (empty($selected)) {
            $all = \App\Models\DailyChallenge::orderBy('id')->get();
            if ($all->count() > 0) {
                $selected[] = $all[$dayIndex % $all->count()];
            }
        }

        return $selected;
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
            'unlocked_at' => now(),
        ]);

        $badgesEarned[] = [
            'id' => $badge->id,
            'slug' => $badge->slug,
            'name' => $badge->name,
            'icon' => $badge->icon,
        ];
    }

    private function hasDailyGoalStreak(int $userId, string $date, int $days): bool
    {
        $dates = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $dates[] = 'date:' . Carbon::parse($date)->subDays($offset)->toDateString();
        }

        $count = XpTransaction::query()
            ->where('user_id', $userId)
            ->where('reason', 'daily_goal')
            ->whereIn('reference_type', $dates)
            ->count();

        return $count === $days;
    }

    /**
     * PRD §6 — Apply gamification with timezone-aware date logic.
     *
     * Key changes from V1:
     * - Streak is delegated to StreakService::updateStreak()
     * - Daily/weekly totals use TimeHelper bounds (not whereDate)
     * - Activity date derived from session started_at in user's timezone
     */
    private function applyGamification($user, TimeSession $session, Carbon $activityDate): array
    {
        $xpGained = 0;
        $badgesEarned = [];
        $newLevel = null;

        // PRD §6.6 — Activity date is the day the session started in user's timezone
        $tz = $user->timezone ?? 'UTC';
        $activityDateString = Carbon::parse($session->started_at)
            ->setTimezone($tz)
            ->toDateString();

        // Delegate streak to StreakService (PRD §6)
        $streakResult = $this->streakService->updateStreak($user);
        $user->refresh();

        $streakCurrent = $streakResult['streak_current'];
        $isNewDay = $user->last_active_date
            ? $user->last_active_date->toDateString() === TimeHelper::todayForUser($user)
            : false;

        // Check if this is the first session of a new day by looking at XP records
        $hasFirstSessionXp = $this->hasDailyXp($user->id, 'first_session', $activityDateString);

        if (! $hasFirstSessionXp) {
            $xpGained += $this->grantXp($user->id, 5, 'first_session', $session->id, "date:{$activityDateString}");

            $multiplier = $this->streakMultiplier($streakCurrent);
            $streakXp = (int) round(5 * $multiplier);
            $xpGained += $this->grantXp($user->id, $streakXp, 'streak_daily', null, "date:{$activityDateString}");
        }
        $this->awardBadge($user->id, 'session_count', $badgesEarned, $xpGained);

        if ($session->duration_seconds >= 7200) {
            $this->awardBadge($user->id, 'deep_work', $badgesEarned, $xpGained);
        }

        // PRD §6 — Use timezone-aware daily totals
        [$dailyTotalSeconds, $dailyPomodoros] = $this->dailyTotals($user, $activityDateString);

        $dailyGoalSeconds = (int) round($user->daily_goal_hours * 3600);
        $dailyGoalAchieved = $dailyGoalSeconds > 0 && $dailyTotalSeconds >= $dailyGoalSeconds;

        if ($dailyGoalAchieved && ! $this->hasDailyXp($user->id, 'daily_goal', $activityDateString)) {
            $xpGained += $this->grantXp($user->id, 25, 'daily_goal', null, "date:{$activityDateString}");
        }

        if ($dailyTotalSeconds >= (8 * 3600) && ! $this->hasDailyXp($user->id, 'daily_8_hours', $activityDateString)) {
            $xpGained += $this->grantXp($user->id, 100, 'daily_8_hours', null, "date:{$activityDateString}");
        }

        $xpGained += $this->applyDailyChallenge($user->id, $activityDateString, $dailyTotalSeconds, $dailyPomodoros);

        if (! $hasFirstSessionXp) {
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

        // PRD §6 — Use timezone-aware weekly total
        $weeklySeconds = $this->weeklyTotalSeconds($user, $activityDateString);
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
            'started_at' => $startedAt,
            'ended_at' => null,
            'duration_seconds' => null,
            'notes' => $data['notes'] ?? null,
            'label' => $data['label'] ?? null,
            'is_pomodoro' => $type === 'pomodoro',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'project_id' => $session->project_id,
                    'started_at' => Carbon::parse($session->started_at)->toIso8601String(),
                    'ended_at' => $session->ended_at,
                    'duration_seconds' => $session->duration_seconds,
                    'notes' => $session->notes,
                    'label' => $session->label,
                    'is_pomodoro' => $session->is_pomodoro,
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
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
            'notes' => $data['notes'] ?? null,
            'label' => $data['label'] ?? null,
            'is_pomodoro' => $type === 'pomodoro',
        ]);

        $meta = $this->applyGamification($user, $session, $startedAt);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'project_id' => $session->project_id,
                    'started_at' => Carbon::parse($session->started_at)->toIso8601String(),
                    'ended_at' => Carbon::parse($session->ended_at)->toIso8601String(),
                    'duration_seconds' => $session->duration_seconds,
                    'notes' => $session->notes,
                    'label' => $session->label,
                    'is_pomodoro' => $session->is_pomodoro,
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

        $timeSession->update($updates);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $timeSession->id,
                    'project_id' => $timeSession->project_id,
                    'started_at' => Carbon::parse($timeSession->started_at)->toIso8601String(),
                    'ended_at' => $timeSession->ended_at
                        ? Carbon::parse($timeSession->ended_at)->toIso8601String()
                        : null,
                    'duration_seconds' => $timeSession->duration_seconds,
                    'notes' => $timeSession->notes,
                    'label' => $timeSession->label,
                    'is_pomodoro' => $timeSession->is_pomodoro,
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

        // If it's a pomodoro session, grant any remaining interval XP
        if ($timeSession->is_pomodoro && $user->pomodoro_work_min > 0) {
            $intervals = floor($timeSession->duration_seconds / ($user->pomodoro_work_min * 60));
            $alreadyAwarded = \App\Models\XpTransaction::query()
                ->where('user_id', $user->id)
                ->where('reason', 'pomodoro_complete')
                ->where('reference_id', $timeSession->id)
                ->count();
            
            $toAward = $intervals - $alreadyAwarded;
            if ($toAward > 0) {
                $xpGained = 0;
                $badgesEarned = $meta['badges_earned'] ?? [];
                
                for ($i = 0; $i < $toAward; $i++) {
                    $xpGained += $this->grantXp($user->id, 10, 'pomodoro_complete', $timeSession->id);
                }
                $this->awardBadge($user->id, 'tomato_head', $badgesEarned, $xpGained);
                
                $meta['xp_gained'] = ($meta['xp_gained'] ?? 0) + $xpGained;
                $meta['badges_earned'] = $badgesEarned;
                
                $newXpTotal = $user->xp_total + $xpGained;
                $calculatedLevel = $this->determineLevel($newXpTotal);
                $newLevel = $calculatedLevel > $user->level ? $calculatedLevel : null;
                
                if ($xpGained > 0) {
                    $user->forceFill([
                        'xp_total' => $newXpTotal,
                        'level' => $calculatedLevel,
                    ])->save();
                }
                if ($newLevel) {
                    $meta['new_level'] = $newLevel;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $timeSession->id,
                    'project_id' => $timeSession->project_id,
                    'started_at' => Carbon::parse($timeSession->started_at)->toIso8601String(),
                    'ended_at' => Carbon::parse($timeSession->ended_at)->toIso8601String(),
                    'duration_seconds' => $timeSession->duration_seconds,
                    'notes' => $timeSession->notes,
                    'label' => $timeSession->label,
                    'is_pomodoro' => $timeSession->is_pomodoro,
                ],
            ],
            'meta' => $meta,
        ]);
    }

    public function pomodoroInterval(Request $request, int $session): JsonResponse
    {
        $user = $request->user();
        $timeSession = TimeSession::query()
            ->where('id', $session)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $xpGained = 0;
        $badgesEarned = [];

        $xpGained += $this->grantXp($user->id, 10, 'pomodoro_complete', $timeSession->id);
        $this->awardBadge($user->id, 'tomato_head', $badgesEarned, $xpGained);

        $newXpTotal = $user->xp_total + $xpGained;
        $calculatedLevel = $this->determineLevel($newXpTotal);
        $newLevel = $calculatedLevel > $user->level ? $calculatedLevel : null;

        $user->forceFill([
            'xp_total' => $newXpTotal,
            'level' => $calculatedLevel,
        ])->save();

        return response()->json([
            'success' => true,
            'meta' => [
                'xp_gained' => $xpGained,
                'new_level' => $newLevel,
                'badges_earned' => $badgesEarned,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = (int) $request->query('per_page', 15);
        $limit = max(1, min(50, $limit));

        $sessions = TimeSession::query()
            ->with(['project.category'])
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
            ->get(['id', 'project_id', 'started_at', 'duration_seconds', 'label']);

        $projectIds = $sessions->pluck('project_id')->filter()->unique();
        $projects = $projectIds->isNotEmpty()
            ? Project::query()
                ->whereIn('id', $projectIds)
                ->get(['id', 'name', 'color', 'category_id'])
                ->keyBy('id')
            : collect();

        $categoryIds = $projects->isNotEmpty()
            ? $projects->pluck('category_id')->filter()->unique()
            : collect();

        $categories = $categoryIds->isNotEmpty()
            ? Category::query()
                ->whereIn('id', $categoryIds)
                ->get(['id', 'name', 'color'])
                ->keyBy('id')
            : collect();

        $data = $sessions->map(function (TimeSession $session) use ($projects, $categories) {
            $project = $session->project_id ? $projects->get($session->project_id) : null;
            $projectCategory = $project && $project->category_id
                ? $categories->get($project->category_id)
                : null;

            $label = $session->label ?? $project?->name ?? 'Session';
            $categoryLabel = $projectCategory?->name ?? 'General';
            $color = $project?->color ?? '#9ca3af';

            return [
                'id' => $session->id,
                'label' => $label,
                'category' => $categoryLabel,
                'color' => $color,
                'duration_seconds' => (int) ($session->duration_seconds ?? 0),
                'started_at' => Carbon::parse($session->started_at)->toIso8601String(),
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
        $projectCategory = null;

        if ($session && $session->project_id) {
            $project = Project::query()->find($session->project_id, ['id', 'name', 'color', 'category_id']);
            if ($project && $project->category_id) {
                $projectCategory = Category::query()->find($project->category_id, ['id', 'name', 'color']);
            }
        }

        $label = $session
            ? ($session->label ?? $project?->name ?? 'Session')
            : null;
        $categoryLabel = $session
            ? ($projectCategory?->name ?? 'General')
            : null;
        $color = $session
            ? ($project?->color ?? 'violet')
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session
                    ? [
                        'id' => $session->id,
                        'project_id' => $session->project_id,
                        'started_at' => Carbon::parse($session->started_at)->toIso8601String(),
                        'ended_at' => $session->ended_at,
                        'duration_seconds' => $session->duration_seconds,
                        'notes' => $session->notes,
                        'is_pomodoro' => $session->is_pomodoro,
                        'label' => $label,
                        'category' => $categoryLabel,
                        'color' => $color,
                    ]
                    : null,
            ],
        ]);
    }
}
