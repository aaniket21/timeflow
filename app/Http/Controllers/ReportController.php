<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateReportRequest;
use App\Models\Project;
use App\Models\Report;
use App\Models\TimeSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $reports = Report::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    public function destroy(Request $request, int $report): JsonResponse
    {
        $user = $request->user();
        $existing = Report::query()
            ->where('id', $report)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($existing->file_path && Storage::disk('local')->exists($existing->file_path)) {
            Storage::disk('local')->delete($existing->file_path);
        }

        $existing->delete();

        return response()->json([
            'success' => true,
        ]);
    }
    public function generate(GenerateReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $format = $request->input('format', 'pdf');
        if (! in_array($format, ['pdf', 'csv'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid report format.',
            ], 422);
        }

        $dateFrom = Carbon::parse($data['date_from'])->startOfDay();
        $dateTo = Carbon::parse($data['date_to'])->endOfDay();

        if (! empty($data['project_ids'])) {
            $ownedCount = Project::query()
                ->where('user_id', $user->id)
                ->whereIn('id', $data['project_ids'])
                ->count();

            if ($ownedCount !== count($data['project_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found.',
                ], 422);
            }
        }

        $query = TimeSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$dateFrom, $dateTo]);

        if (! empty($data['project_ids'])) {
            $query->whereIn('project_id', $data['project_ids']);
        }

        $sessions = $query->orderBy('started_at')->get();

        $report = Report::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'project_ids' => $data['project_ids'] ?? null,
            'file_path' => null,
            'share_token' => Str::random(48),
        ]);

        \App\Jobs\GenerateReportJob::dispatch($report->id, $format, $sessions->pluck('id')->toArray());

        return response()->json([
            'success' => true,
            'data' => [
                'report' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'date_from' => $report->date_from,
                    'date_to' => $report->date_to,
                    'project_ids' => $report->project_ids,
                    'file_path' => $report->file_path,
                    'share_token' => $report->share_token,
                    'format' => $format,
                    'status' => 'pending',
                ],
            ],
        ], 201);
    }

    public function download(Request $request, int $report)
    {
        $user = $request->user();
        $existing = Report::query()
            ->where('id', $report)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (! $existing->file_path || ! Storage::disk('local')->exists($existing->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Report file not found.',
            ], 404);
        }

        return Storage::disk('local')->download($existing->file_path);
    }

    public function share(string $token): JsonResponse
    {
        $report = Report::query()
            ->where('share_token', $token)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'title' => $report->title,
                'date_from' => $report->date_from,
                'date_to' => $report->date_to,
                'project_ids' => $report->project_ids,
                'file_path' => $report->file_path,
            ],
        ]);
    }

}
