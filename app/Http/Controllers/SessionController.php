<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartSessionRequest;
use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use App\Models\TimeSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
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
            'project_id' => $data['project_id'],
            'category_id' => $data['category_id'] ?? null,
            'started_at' => $startedAt,
            'ended_at' => null,
            'duration_seconds' => null,
            'notes' => $data['notes'] ?? null,
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
        $type = $data['type'] ?? 'manual';
        $startedAt = Carbon::parse($data['started_at']);
        $endedAt = Carbon::parse($data['ended_at']);
        $durationSeconds = $endedAt->diffInSeconds($startedAt);

        $session = TimeSession::create([
            'user_id' => $user->id,
            'project_id' => $data['project_id'],
            'category_id' => $data['category_id'] ?? null,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
            'notes' => $data['notes'] ?? null,
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
                    'ended_at' => Carbon::parse($session->ended_at)->toIso8601String(),
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

    public function stop(Request $request, int $session): JsonResponse
    {
        $user = $request->user();
        $timeSession = TimeSession::query()
            ->where('id', $session)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $endedAt = now();
        $durationSeconds = Carbon::parse($timeSession->started_at)->diffInSeconds($endedAt, true);

        $timeSession->update([
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
        ]);

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
}
