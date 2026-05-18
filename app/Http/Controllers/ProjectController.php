<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectRequest;
use App\Models\Category;
use App\Models\Project;
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
            'archived' => $data['archived'] ?? false,
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
            'archived' => ['sometimes', 'boolean'],
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
            'archived' => true,
        ])->save();

        return response()->json([
            'success' => true,
            'data' => [
                'project' => $existing,
            ],
        ]);
    }
}
