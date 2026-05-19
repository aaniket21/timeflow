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

        $fileName = sprintf('reports/report-%d-%s.%s', $report->id, Str::random(8), $format);

        if ($format === 'csv') {
            $content = $this->buildCsv($sessions);
        } else {
            $content = $this->buildPdf($data['title'], $dateFrom, $dateTo, $sessions->count(), (int) $sessions->sum('duration_seconds'));
        }

        Storage::disk('local')->put($fileName, $content);

        $report->forceFill([
            'file_path' => $fileName,
        ])->save();

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

    private function buildCsv($sessions): string
    {
        $rows = [
            ['started_at', 'ended_at', 'duration_seconds', 'project_id', 'category_id', 'notes'],
        ];

        foreach ($sessions as $session) {
            $rows[] = [
                $session->started_at,
                $session->ended_at,
                $session->duration_seconds,
                $session->project_id,
                $session->category_id,
                $session->notes,
            ];
        }

        $lines = [];
        foreach ($rows as $row) {
            $escaped = array_map(function ($value) {
                $text = (string) ($value ?? '');
                $text = str_replace('"', '""', $text);
                return '"'.$text.'"';
            }, $row);
            $lines[] = implode(',', $escaped);
        }

        return implode("\n", $lines);
    }

    private function buildPdf(string $title, Carbon $dateFrom, Carbon $dateTo, int $sessionCount, int $totalSeconds): string
    {
        $hours = round($totalSeconds / 3600, 2);
        $lines = [
            "Report: {$title}",
            "Range: {$dateFrom->toDateString()} to {$dateTo->toDateString()}",
            "Sessions: {$sessionCount}",
            "Total hours: {$hours}",
        ];

        $content = "BT\n";
        $y = 760;
        foreach ($lines as $line) {
            $safe = str_replace(['(', ')'], ['[', ']'], $line);
            $content .= " /F1 14 Tf 50 {$y} Td ({$safe}) Tj\n";
            $y -= 20;
        }
        $content .= "ET";

        $stream = $content;
        $streamLength = strlen($stream);

        $objects = [];
        $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj";
        $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj";
        $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj";
        $objects[] = "4 0 obj << /Length {$streamLength} >> stream\n{$stream}\nendstream endobj";
        $objects[] = "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object."\n";
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 ".count($offsets)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < count($offsets); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        $pdf .= "trailer << /Size ".count($offsets)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefStart}\n%%EOF";

        return $pdf;
    }
}
