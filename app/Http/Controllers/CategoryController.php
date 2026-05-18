<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $categories = Category::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['required', 'string', 'size:7'],
            'icon' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
            $parentOwned = Category::query()
                ->where('id', $data['parent_id'])
                ->where('user_id', $user->id)
                ->exists();

            if (! $parentOwned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent category not found.',
                ], 422);
            }
        }

        $category = Category::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'color' => $data['color'],
            'icon' => $data['icon'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'archived' => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
            ],
        ], 201);
    }

    public function update(Request $request, int $category): JsonResponse
    {
        $user = $request->user();
        $existing = Category::query()
            ->where('id', $category)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'color' => ['sometimes', 'string', 'size:7'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'archived' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
            $parentOwned = Category::query()
                ->where('id', $data['parent_id'])
                ->where('user_id', $user->id)
                ->exists();

            if (! $parentOwned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent category not found.',
                ], 422);
            }
        }

        $existing->update($data);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $existing,
            ],
        ]);
    }

    public function destroy(Request $request, int $category): JsonResponse
    {
        $user = $request->user();
        $existing = Category::query()
            ->where('id', $category)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $existing->forceFill([
            'archived' => true,
        ])->save();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $existing,
            ],
        ]);
    }
}
