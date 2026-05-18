<?php

namespace App\Http\Controllers;

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

        $totalSeconds = (int) $baseQuery->sum('duration_seconds');
        $focusSessions = (int) (clone $baseQuery)->count();
        $avgSessionSeconds = $focusSessions > 0
            ? (int) floor($totalSeconds / $focusSessions)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'total_seconds' => $totalSeconds,
                'focus_sessions' => $focusSessions,
                'avg_session_seconds' => $avgSessionSeconds,
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
            ->get(['started_at', 'duration_seconds']);
        
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
}
