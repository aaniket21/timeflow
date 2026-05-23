<?php

namespace App\Http\Controllers;

use App\Models\TimetableBlock;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $blocks = TimetableBlock::query()
            ->where('user_id', $user->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $blocks,
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = Carbon::parse($request->query('date', now()->toDateString()));
        $day = $date->dayOfWeekIso;

        $blocks = TimetableBlock::query()
            ->where('user_id', $user->id)
            ->where('day_of_week', $day)
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $blocks,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'size:7'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'is_recurring' => ['nullable', 'boolean'],
        ]);

        if ($data['start_time'] >= $data['end_time']) {
            return response()->json([
                'success' => false,
                'message' => 'End time must be after start time.',
            ], 422);
        }

        if ($this->hasConflict($user->id, $data['day_of_week'], $data['start_time'], $data['end_time'])) {
            return response()->json([
                'success' => false,
                'message' => 'Timetable block conflicts with existing schedule.',
            ], 422);
        }

        $block = TimetableBlock::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'type' => $data['type'],
            'color' => $data['color'],
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_recurring' => $data['is_recurring'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'block' => $block,
            ],
        ], 201);
    }

    public function update(Request $request, int $block): JsonResponse
    {
        $user = $request->user();
        $existing = TimetableBlock::query()
            ->where('id', $block)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'string', 'max:50'],
            'color' => ['sometimes', 'string', 'size:7'],
            'day_of_week' => ['sometimes', 'integer', 'min:1', 'max:7'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'is_recurring' => ['sometimes', 'boolean'],
        ]);

        $startTime = $data['start_time'] ?? $existing->start_time;
        $endTime = $data['end_time'] ?? $existing->end_time;
        $day = $data['day_of_week'] ?? $existing->day_of_week;

        if ($startTime >= $endTime) {
            return response()->json([
                'success' => false,
                'message' => 'End time must be after start time.',
            ], 422);
        }

        if ($this->hasConflict($user->id, $day, $startTime, $endTime, $existing->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Timetable block conflicts with existing schedule.',
            ], 422);
        }

        $existing->update($data);

        return response()->json([
            'success' => true,
            'data' => [
                'block' => $existing,
            ],
        ]);
    }

    public function destroy(Request $request, int $block): JsonResponse
    {
        $user = $request->user();
        $existing = TimetableBlock::query()
            ->where('id', $block)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $existing->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    private function hasConflict(int $userId, int $day, string $startTime, string $endTime, ?int $ignoreId = null): bool
    {
        $query = TimetableBlock::query()
            ->where('user_id', $userId)
            ->where('day_of_week', $day)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
