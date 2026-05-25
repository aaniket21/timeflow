<?php

namespace App\Http\Controllers;

use App\Helpers\TimeHelper;
use App\Models\Category;
use App\Models\DailyPlan;
use App\Models\Exam;
use App\Models\Goal;
use App\Models\HabitLog;
use App\Models\Project;
use App\Models\ReportToken;
use App\Models\TimeSession;
use App\Models\UserBadge;
use App\Models\UserChallengeCompletion;
use App\Models\XpTransaction;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
        ]);

        $user->forceFill($data)->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'timezone' => $user->timezone,
            ],
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'plan_auto_rollover' => ['required', 'boolean'],
        ]);

        $user->forceFill($data)->save();

        return response()->json([
            'success' => true,
            'data' => [
                'plan_auto_rollover' => $user->plan_auto_rollover,
            ],
        ]);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'notifications_enabled' => ['required', 'boolean'],
            'email_digest_enabled' => ['required', 'boolean'],
        ]);

        $user->forceFill($data)->save();

        return response()->json([
            'success' => true,
            'data' => [
                'notifications_enabled' => $user->notifications_enabled,
                'email_digest_enabled' => $user->email_digest_enabled,
            ],
        ]);
    }

    public function updatePomodoro(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'pomodoro_work_min' => ['required', 'integer', 'min:15', 'max:120'],
            'pomodoro_break_min' => ['required', 'integer', 'min:3', 'max:30'],
        ]);

        $user->forceFill($data)->save();

        return response()->json([
            'success' => true,
            'data' => [
                'pomodoro_work_min' => $user->pomodoro_work_min,
                'pomodoro_break_min' => $user->pomodoro_break_min,
            ],
        ]);
    }

    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'timezone' => $user->timezone,
                ],
                'projects' => Project::query()->where('user_id', $user->id)->get(),
                'categories' => Category::query()->where('user_id', $user->id)->get(),
                'sessions' => TimeSession::query()->where('user_id', $user->id)->get(),
                'goals' => Goal::query()->where('user_id', $user->id)->get(),
                'habit_logs' => HabitLog::query()->where('user_id', $user->id)->get(),
                'exams' => Exam::query()->where('user_id', $user->id)->get(),
                'daily_plans' => DailyPlan::query()->where('user_id', $user->id)->get(),
                'reports' => ReportToken::query()->where('user_id', $user->id)->get(),
                'badges' => UserBadge::query()->where('user_id', $user->id)->get(),
                'challenges' => UserChallengeCompletion::query()->where('user_id', $user->id)->get(),
                'xp_transactions' => XpTransaction::query()->where('user_id', $user->id)->get(),
            ],
        ]);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $email = $user->email;
        $name = $user->name;

        DB::transaction(function () use ($user) {
            $user->delete();
        });

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\AccountDeleted($name));
        } catch (\Exception $e) {
            // Log or ignore
        }

        if ($request->hasSession()) {
            auth('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
