<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Project;
use App\Models\TimeSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function daily(Request $request): JsonResponse
    {
        $user = $request->user();
        $dateInput = $request->query('date', now()->toDateString());
        $date = Carbon::parse($dateInput)->toDateString();

        $baseQuery = TimeSession::query()
            ->where('user_id', $user->id)
            ->whereDate('started_at', $date)
            ->whereNotNull('ended_at');

        $sessions = (clone $baseQuery)
            ->orderBy('started_at')
            ->get(['id', 'project_id', 'category_id', 'started_at', 'duration_seconds']);

        $totalSeconds = (int) $baseQuery->sum('duration_seconds');
        $focusSessions = (int) (clone $baseQuery)->count();
        $pomodoroCount = (int) (clone $baseQuery)->where('is_pomodoro', true)->count();
        $avgSessionSeconds = $focusSessions > 0
            ? (int) floor($totalSeconds / $focusSessions)
            : 0;

        $hourlyTotals = array_fill(0, 24, 0);
        $longestSessionSeconds = 0;

        foreach ($sessions as $session) {
            $hour = Carbon::parse($session->started_at)->hour;
            $hourlyTotals[$hour] += (int) $session->duration_seconds;
            $longestSessionSeconds = max($longestSessionSeconds, (int) $session->duration_seconds);
        }

        $hourlyBreakdown = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyBreakdown[] = [
                'hour' => $hour,
                'total_seconds' => (int) $hourlyTotals[$hour],
            ];
        }

        $sessionDisplay = $this->mapSessionDisplay($sessions);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'total_seconds' => $totalSeconds,
                'focus_sessions' => $focusSessions,
                'avg_session_seconds' => $avgSessionSeconds,
                'pomodoro_count' => $pomodoroCount,
                'longest_session_seconds' => $longestSessionSeconds,
                'hourly_breakdown' => $hourlyBreakdown,
                'sessions' => $sessionDisplay,
            ],
        ]);
    }
    
    public function weekly(Request $request): JsonResponse
    {
        $user = $request->user();
        $startInput = $request->query('start', now()->startOfWeek()->toDateString());
        $startDate = Carbon::parse($startInput)->startOfDay();
        $endDate = $startDate->copy()->addDays(6)->endOfDay();
        
        $sessions = TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$startDate, $endDate])
            ->get(['started_at', 'duration_seconds', 'project_id', 'category_id']);
        
        $dayTotals = [];
        
        foreach ($sessions as $session) {
            $dayKey = Carbon::parse($session->started_at)->toDateString();
            $dayTotals[$dayKey] = ($dayTotals[$dayKey] ?? 0) + (int) $session->duration_seconds;
        }
        
        $totalSeconds = array_sum($dayTotals);
        $daysLogged = count($dayTotals);
        $avgDailySeconds = $daysLogged > 0
            ? (int) floor($totalSeconds / $daysLogged)
            : 0;
        
        $bestDay = null;
        $worstDay = null;
        
        foreach ($dayTotals as $day => $seconds) {
            if ($bestDay === null || $seconds > $bestDay['total_seconds']) {
                $bestDay = ['date' => $day, 'total_seconds' => $seconds];
            }
            
            if ($worstDay === null || $seconds < $worstDay['total_seconds']) {
                $worstDay = ['date' => $day, 'total_seconds' => $seconds];
            }
        }

        $dailyGoalSeconds = (int) round($user->daily_goal_hours * 3600);
        $dailyTotals = [];
        $cursor = $startDate->copy();

        for ($i = 0; $i < 7; $i++) {
            $dayKey = $cursor->toDateString();
            $dailyTotals[] = [
                'date' => $dayKey,
                'total_seconds' => (int) ($dayTotals[$dayKey] ?? 0),
                'goal_seconds' => $dailyGoalSeconds,
            ];
            $cursor->addDay();
        }

        $categoryBreakdown = $this->buildCategoryBreakdown($sessions, $totalSeconds);
        
        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_seconds' => (int) $totalSeconds,
                'days_logged' => $daysLogged,
                'avg_daily_seconds' => $avgDailySeconds,
                'best_day' => $bestDay,
                'worst_day' => $worstDay,
                'daily_totals' => $dailyTotals,
                'category_breakdown' => $categoryBreakdown,
            ],
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        $user = $request->user();
        $monthInput = $request->query('month', now()->format('Y-m'));
        $monthStart = Carbon::parse($monthInput)->startOfMonth()->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

        $sessions = TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$monthStart, $monthEnd])
            ->get(['started_at', 'duration_seconds', 'project_id']);

        $dayTotals = [];
        $projectTotals = [];

        foreach ($sessions as $session) {
            $dayKey = Carbon::parse($session->started_at)->toDateString();
            $dayTotals[$dayKey] = ($dayTotals[$dayKey] ?? 0) + (int) $session->duration_seconds;

            if ($session->project_id) {
                $projectTotals[$session->project_id] = ($projectTotals[$session->project_id] ?? 0)
                    + (int) $session->duration_seconds;
            }
        }

        $totalSeconds = array_sum($dayTotals);
        $daysLogged = count($dayTotals);
        $avgDailySeconds = $daysLogged > 0
            ? (int) floor($totalSeconds / $daysLogged)
            : 0;

        $bestDay = null;
        $worstDay = null;

        foreach ($dayTotals as $day => $seconds) {
            if ($bestDay === null || $seconds > $bestDay['total_seconds']) {
                $bestDay = ['date' => $day, 'total_seconds' => $seconds];
            }

            if ($worstDay === null || $seconds < $worstDay['total_seconds']) {
                $worstDay = ['date' => $day, 'total_seconds' => $seconds];
            }
        }

        $topProject = null;

        if ($projectTotals !== []) {
            $topProjectId = array_key_first($projectTotals);
            $topProjectSeconds = $projectTotals[$topProjectId];

            foreach ($projectTotals as $projectId => $seconds) {
                if ($seconds > $topProjectSeconds) {
                    $topProjectId = $projectId;
                    $topProjectSeconds = $seconds;
                }
            }

            $projectName = Project::query()
                ->where('id', $topProjectId)
                ->value('name');

            if ($projectName !== null) {
                $topProject = [
                    'id' => $topProjectId,
                    'name' => $projectName,
                    'total_seconds' => (int) $topProjectSeconds,
                ];
            }
        }

        $dailyTotals = [];
        $cursor = $monthStart->copy();
        $daysInMonth = $monthStart->daysInMonth;

        for ($i = 0; $i < $daysInMonth; $i++) {
            $dayKey = $cursor->toDateString();
            $dailyTotals[] = [
                'date' => $dayKey,
                'total_seconds' => (int) ($dayTotals[$dayKey] ?? 0),
            ];
            $cursor->addDay();
        }

        $topProjects = $this->buildTopProjects($projectTotals);
        $streakDays = $this->buildStreakDays($dayTotals);

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $monthStart->format('Y-m'),
                'total_seconds' => (int) $totalSeconds,
                'days_logged' => $daysLogged,
                'avg_daily_seconds' => $avgDailySeconds,
                'best_day' => $bestDay,
                'worst_day' => $worstDay,
                'top_project' => $topProject,
                'daily_totals' => $dailyTotals,
                'top_projects' => $topProjects,
                'streak_days' => $streakDays,
            ],
        ]);
    }

    public function heatmap(Request $request): JsonResponse
    {
        $user = $request->user();
        $endDate = Carbon::parse($request->query('end', now()->toDateString()))->endOfDay();
        $startDate = $endDate->copy()->subDays(13)->startOfDay();

        $sessions = TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$startDate, $endDate])
            ->get(['started_at', 'duration_seconds']);

        $dayTotals = [];

        foreach ($sessions as $session) {
            $dayKey = Carbon::parse($session->started_at)->toDateString();
            $dayTotals[$dayKey] = ($dayTotals[$dayKey] ?? 0) + (int) $session->duration_seconds;
        }

        $days = [];
        $cursor = $startDate->copy();

        for ($i = 0; $i < 14; $i++) {
            $dayKey = $cursor->toDateString();
            $total = (int) ($dayTotals[$dayKey] ?? 0);

            $days[] = [
                'date' => $dayKey,
                'total_seconds' => $total,
                'level' => $this->heatmapLevel($total),
            ];

            $cursor->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $days,
            ],
        ]);
    }

    public function insights(Request $request): JsonResponse
    {
        $user = $request->user();
        $rangeEnd = now()->endOfDay();
        $rangeStart = now()->subDays(27)->startOfDay();

        $sessions = TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$rangeStart, $rangeEnd])
            ->get(['started_at', 'duration_seconds']);

        $insights = [];

        if ($sessions->isNotEmpty()) {
            $avgSeconds = (int) floor($sessions->avg('duration_seconds'));

            if ($avgSeconds > 0 && $avgSeconds < (25 * 60)) {
                $avgMinutes = (int) floor($avgSeconds / 60);
                $insights[] = [
                    'type' => 'short_sessions',
                    'message' => "Your average session is {$avgMinutes} minutes, below the 25-min Pomodoro minimum.",
                ];
            }

            $hourTotals = array_fill(0, 24, 0);
            foreach ($sessions as $session) {
                $hour = Carbon::parse($session->started_at)->hour;
                $hourTotals[$hour] += (int) $session->duration_seconds;
            }

            $bestHour = array_keys($hourTotals, max($hourTotals))[0] ?? null;

            if ($bestHour !== null && $bestHour >= 9 && $bestHour <= 11) {
                $insights[] = [
                    'type' => 'peak_hours',
                    'message' => 'You do your best work between 9-11 AM. Consider scheduling deep work then.',
                ];
            }

            $sundaysMissing = true;
            for ($weekOffset = 0; $weekOffset < 4; $weekOffset++) {
                $sunday = now()->subWeeks($weekOffset)->startOfWeek()->addDays(6)->toDateString();
                $hasSunday = $sessions->contains(function ($session) use ($sunday) {
                    return Carbon::parse($session->started_at)->toDateString() === $sunday;
                });
                if ($hasSunday) {
                    $sundaysMissing = false;
                    break;
                }
            }

            if ($sundaysMissing) {
                $insights[] = [
                    'type' => 'sunday_gap',
                    'message' => "You've logged 0 hours on Sundays for 4 weeks. Is that intentional rest or avoidance?",
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $insights,
        ]);
    }

    private function mapSessionDisplay($sessions): array
    {
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

        return $sessions->map(function (TimeSession $session) use ($projects, $categories) {
            $project = $session->project_id ? $projects->get($session->project_id) : null;
            $category = $session->category_id ? $categories->get($session->category_id) : null;
            $projectCategory = $project && $project->category_id
                ? $categories->get($project->category_id)
                : null;

            $label = $project->name ?? $category->name ?? 'Session';
            $categoryLabel = $category->name ?? ($projectCategory?->name ?? 'General');
            $color = $project->color ?? $category->color ?? '#9ca3af';

            return [
                'id' => $session->id,
                'label' => $label,
                'category' => $categoryLabel,
                'color' => $color,
                'started_at' => Carbon::parse($session->started_at)->toIso8601String(),
                'duration_seconds' => (int) $session->duration_seconds,
            ];
        })->values()->all();
    }

    private function buildCategoryBreakdown($sessions, int $totalSeconds): array
    {
        $projectIds = $sessions->pluck('project_id')->filter()->unique();
        $projects = $projectIds->isNotEmpty()
            ? Project::query()
                ->whereIn('id', $projectIds)
                ->get(['id', 'category_id'])
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

        $categoryTotals = [];

        foreach ($sessions as $session) {
            $project = $session->project_id ? $projects->get($session->project_id) : null;
            $categoryId = $session->category_id ?? ($project?->category_id ?? null);

            if (! $categoryId) {
                $categoryTotals['uncategorized'] = ($categoryTotals['uncategorized'] ?? 0) + (int) $session->duration_seconds;
                continue;
            }

            $categoryTotals[$categoryId] = ($categoryTotals[$categoryId] ?? 0) + (int) $session->duration_seconds;
        }

        arsort($categoryTotals);

        $breakdown = [];
        foreach ($categoryTotals as $categoryId => $seconds) {
            if ($categoryId === 'uncategorized') {
                $name = 'Uncategorized';
                $color = '#9ca3af';
                $id = null;
            } else {
                $category = $categories->get($categoryId);
                if (! $category) {
                    continue;
                }
                $name = $category->name;
                $color = $category->color;
                $id = $category->id;
            }

            $breakdown[] = [
                'id' => $id,
                'name' => $name,
                'color' => $color,
                'total_seconds' => (int) $seconds,
                'percent' => $totalSeconds > 0 ? (int) round(($seconds / $totalSeconds) * 100) : 0,
            ];
        }

        return $breakdown;
    }

    private function buildTopProjects(array $projectTotals): array
    {
        if ($projectTotals === []) {
            return [];
        }

        arsort($projectTotals);
        $topProjects = array_slice($projectTotals, 0, 3, true);
        $projectIds = array_keys($topProjects);

        $projects = Project::query()
            ->whereIn('id', $projectIds)
            ->get(['id', 'name', 'color'])
            ->keyBy('id');

        $data = [];
        foreach ($topProjects as $projectId => $seconds) {
            $project = $projects->get($projectId);
            if (! $project) {
                continue;
            }

            $data[] = [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'total_seconds' => (int) $seconds,
            ];
        }

        return $data;
    }

    private function buildStreakDays(array $dayTotals): array
    {
        $data = [];

        foreach ($dayTotals as $date => $seconds) {
            if ((int) $seconds <= 0) {
                continue;
            }

            $data[] = [
                'date' => $date,
                'total_seconds' => (int) $seconds,
            ];
        }

        return $data;
    }

    private function heatmapLevel(int $totalSeconds): int
    {
        if ($totalSeconds <= 0) {
            return 0;
        }

        if ($totalSeconds < 3600) {
            return 1;
        }

        if ($totalSeconds < 7200) {
            return 2;
        }

        if ($totalSeconds < 14400) {
            return 3;
        }

        return 4;
    }
}
