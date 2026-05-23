<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectRequest;
use App\Models\Category;
use App\Models\Project;
use App\Models\TimeSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $projects = Project::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $projects = Project::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $projectIds = $projects->pluck('id');

        $sessions = $projectIds->isNotEmpty()
            ? TimeSession::query()
                ->where('user_id', $user->id)
                ->whereNotNull('ended_at')
                ->whereIn('project_id', $projectIds)
                ->get(['project_id', 'duration_seconds', 'ended_at'])
            : collect();

        $totals = [];
        $lastSessions = [];

        foreach ($sessions as $session) {
            $projectId = (int) $session->project_id;
            $totals[$projectId] = ($totals[$projectId] ?? 0) + (int) $session->duration_seconds;

            $endedAt = Carbon::parse($session->ended_at);
            if (! isset($lastSessions[$projectId]) || $endedAt->gt($lastSessions[$projectId])) {
                $lastSessions[$projectId] = $endedAt;
            }
        }

        $categoryIds = $projects->pluck('category_id')->filter()->unique();
        $categories = $categoryIds->isNotEmpty()
            ? Category::query()
                ->whereIn('id', $categoryIds)
                ->get(['id', 'name'])
                ->keyBy('id')
            : collect();

        $data = $projects->map(function (Project $project) use ($totals, $lastSessions, $categories) {
            $totalSeconds = (int) ($totals[$project->id] ?? 0);
            $budgetHours = $project->budget_hours !== null ? (float) $project->budget_hours : null;
            $progress = $budgetHours && $budgetHours > 0
                ? (int) round((($totalSeconds / 3600) / $budgetHours) * 100)
                : null;

            $category = $project->category_id ? $categories->get($project->category_id)?->name : null;
            $lastSessionAt = $lastSessions[$project->id] ?? null;

            return [
                'id' => $project->id,
                'name' => $project->name,
                'client_name' => $project->client_name,
                'category' => $category,
                'color' => $project->color,
                'budget_hours' => $budgetHours,
                'total_seconds' => $totalSeconds,
                'progress_percent' => $progress,
                'is_archived' => (bool) $project->is_archived,
                'last_session_at' => $lastSessionAt ? $lastSessionAt->toIso8601String() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(CreateProjectRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (array_key_exists('category_id', $data) && $data['category_id'] !== null) {
            $ownsCategory = Category::query()
                ->where('id', $data['category_id'])
                ->where('user_id', $user->id)
                ->exists();

            if (! $ownsCategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.',
                ], 422);
            }
        }

        $project = Project::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'color' => $data['color'],
            'client_name' => $data['client_name'] ?? null,
            'budget_hours' => $data['budget_hours'] ?? null,
            'is_archived' => $data['is_archived'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'project' => $project,
            ],
        ], 201);
    }

    public function update(Request $request, int $project): JsonResponse
    {
        $user = $request->user();
        $existing = Project::query()
            ->where('id', $project)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'color' => ['sometimes', 'string', 'size:7'],
            'client_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'budget_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'is_archived' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('category_id', $data) && $data['category_id'] !== null) {
            $ownsCategory = Category::query()
                ->where('id', $data['category_id'])
                ->where('user_id', $user->id)
                ->exists();

            if (! $ownsCategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.',
                ], 422);
            }
        }

        $existing->update($data);

        return response()->json([
            'success' => true,
            'data' => [
                'project' => $existing,
            ],
        ]);
    }

    public function destroy(Request $request, int $project): JsonResponse
    {
        $user = $request->user();
        $existing = Project::query()
            ->where('id', $project)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $existing->forceFill([
            'is_archived' => true,
        ])->save();

        return response()->json([
            'success' => true,
            'data' => [
                'project' => $existing,
            ],
        ]);
    }
}
