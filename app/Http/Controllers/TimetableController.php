<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TimetableBlock;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $active = $request->query('active');

        $query = TimetableBlock::query()->where('user_id', $user->id);

        if ($active !== null) {
            $query->where('active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('start_time')->get(),
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = Carbon::parse($request->query('date', now()->toDateString()));
        $day = $date->dayOfWeekIso;

        $blocks = TimetableBlock::query()
            ->where('user_id', $user->id)
            ->where('active', true)
            ->whereJsonContains('days_of_week', $day)
            ->where(function ($query) use ($date) {
                $query->whereNull('semester_end')
                    ->orWhere('semester_end', '>=', $date->toDateString());
            })
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
            'type' => ['required', 'in:class,study,break,personal,other'],
            'color' => ['required', 'string', 'size:7'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'active' => ['nullable', 'boolean'],
            'semester_end' => ['nullable', 'date'],
        ]);

        if ($data['start_time'] >= $data['end_time']) {
            return response()->json([
                'success' => false,
                'message' => 'End time must be after start time.',
            ], 422);
        }

        if (array_key_exists('project_id', $data) && $data['project_id'] !== null) {
            $ownsProject = Project::query()
                ->where('id', $data['project_id'])
                ->where('user_id', $user->id)
                ->exists();

            if (! $ownsProject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found.',
                ], 422);
            }
        }

        if ($this->hasConflict($user->id, $data['days_of_week'], $data['start_time'], $data['end_time'])) {
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
            'project_id' => $data['project_id'] ?? null,
            'days_of_week' => $data['days_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'active' => $data['active'] ?? true,
            'semester_end' => $data['semester_end'] ?? null,
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
            'type' => ['sometimes', 'in:class,study,break,personal,other'],
            'color' => ['sometimes', 'string', 'size:7'],
            'project_id' => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'days_of_week' => ['sometimes', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'min:1', 'max:7'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'active' => ['sometimes', 'boolean'],
            'semester_end' => ['sometimes', 'nullable', 'date'],
        ]);

        $startTime = $data['start_time'] ?? $existing->start_time;
        $endTime = $data['end_time'] ?? $existing->end_time;
        $days = $data['days_of_week'] ?? $existing->days_of_week;

        if ($startTime >= $endTime) {
            return response()->json([
                'success' => false,
                'message' => 'End time must be after start time.',
            ], 422);
        }

        if (array_key_exists('project_id', $data) && $data['project_id'] !== null) {
            $ownsProject = Project::query()
                ->where('id', $data['project_id'])
                ->where('user_id', $user->id)
                ->exists();

            if (! $ownsProject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found.',
                ], 422);
            }
        }

        if ($this->hasConflict($user->id, $days, $startTime, $endTime, $existing->id)) {
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

        $existing->forceFill([
            'active' => false,
        ])->save();

        return response()->json([
            'success' => true,
            'data' => [
                'block' => $existing,
            ],
        ]);
    }

    private function hasConflict(int $userId, array $days, string $startTime, string $endTime, ?int $ignoreId = null): bool
    {
        foreach ($days as $day) {
            $query = TimetableBlock::query()
                ->where('user_id', $userId)
                ->where('active', true)
                ->whereJsonContains('days_of_week', $day)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime);

            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }
}
