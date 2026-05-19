<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\DailyPlanController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\GamificationController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TimetableController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

use App\Http\Middleware\EnsureTimezoneIsSet;

Route::middleware(['auth:sanctum', EnsureTimezoneIsSet::class])->group(function () {
    Route::get('/analytics/daily', [AnalyticsController::class, 'daily']);
    Route::get('/analytics/weekly', [AnalyticsController::class, 'weekly']);
    Route::get('/analytics/monthly', [AnalyticsController::class, 'monthly']);
    Route::get('/analytics/heatmap', [AnalyticsController::class, 'heatmap']);
    Route::get('/analytics/insights', [AnalyticsController::class, 'insights']);
    Route::get('/challenges/today', [ChallengeController::class, 'today']);
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/summary', [ProjectController::class, 'summary']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::get('/goals', [GoalController::class, 'index']);
    Route::get('/goals/summary', [GoalController::class, 'summary']);
    Route::post('/goals', [GoalController::class, 'store']);
    Route::put('/goals/{goal}', [GoalController::class, 'update']);
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy']);
    Route::get('/habits/week', [GoalController::class, 'weekHabits']);
    Route::get('/habits/today', [GoalController::class, 'todayHabits']);
    Route::post('/habits/{goal}/log', [GoalController::class, 'logHabit']);
    Route::get('/exams', [ExamController::class, 'index']);
    Route::post('/exams', [ExamController::class, 'store']);
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy']);
    Route::get('/daily-plans/today', [DailyPlanController::class, 'today']);
    Route::post('/daily-plans', [DailyPlanController::class, 'store']);
    Route::get('/timetable/blocks', [TimetableController::class, 'index']);
    Route::get('/timetable/today', [TimetableController::class, 'today']);
    Route::post('/timetable/blocks', [TimetableController::class, 'store']);
    Route::put('/timetable/blocks/{block}', [TimetableController::class, 'update']);
    Route::delete('/timetable/blocks/{block}', [TimetableController::class, 'destroy']);
    Route::get('/gamification/profile', [GamificationController::class, 'profile']);
    Route::get('/gamification/badges', [GamificationController::class, 'badges']);
    Route::get('/gamification/leaderboard', [GamificationController::class, 'leaderboard']);
    Route::put('/gamification/leaderboard-opt-in', [GamificationController::class, 'updateLeaderboardOptIn']);
    Route::get('/sessions/active', [SessionController::class, 'active']);
    Route::get('/sessions/recent', [SessionController::class, 'recent']);
    Route::get('/sessions', [SessionController::class, 'index']);
    Route::post('/sessions/start', [SessionController::class, 'start']);
    Route::post('/sessions', [SessionController::class, 'store']);
    Route::put('/sessions/{session}', [SessionController::class, 'update']);
    Route::post('/sessions/{session}/stop', [SessionController::class, 'stop']);
    Route::delete('/sessions/{session}', [SessionController::class, 'destroy']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports', [ReportController::class, 'generate']);
    Route::delete('/reports/{report}', [ReportController::class, 'destroy']);
    Route::get('/reports/{report}/download', [ReportController::class, 'download']);
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile']);
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications']);
    Route::put('/settings/pomodoro', [SettingsController::class, 'updatePomodoro']);
    Route::get('/settings/export', [SettingsController::class, 'exportData']);
    Route::delete('/settings/account', [SettingsController::class, 'deleteAccount']);
});
