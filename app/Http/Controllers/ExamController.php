<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::now()->toDateString();

        $exams = Exam::query()
            ->where('user_id', $user->id)
            ->where('exam_date', '>=', $today)
            ->orderBy('exam_date')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $exams,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:100'],
            'exam_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:280'],
        ]);

        $exam = Exam::create([
            'user_id' => $user->id,
            'subject' => $data['subject'],
            'exam_date' => $data['exam_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'exam' => $exam,
            ],
        ], 201);
    }

    public function destroy(Request $request, int $exam): JsonResponse
    {
        $user = $request->user();
        $existing = Exam::query()
            ->where('id', $exam)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $existing->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
