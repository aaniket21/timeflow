<?php

namespace App\Jobs;

use App\Models\Report;
use App\Models\TimeSession;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    private $reportId;
    private $format;
    private $sessionIds;

    /**
     * Create a new job instance.
     */
    public function __construct(int $reportId, string $format, array $sessionIds)
    {
        $this->reportId = $reportId;
        $this->format = $format;
        $this->sessionIds = $sessionIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $report = Report::find($this->reportId);
        if (!$report) {
            return;
        }

        $sessions = TimeSession::query()
            ->whereIn('id', $this->sessionIds)
            ->orderBy('started_at')
            ->get();

        $fileName = sprintf('reports/report-%d-%s.%s', $report->id, Str::random(8), $this->format);

        if ($this->format === 'csv') {
            $content = $this->buildCsv($sessions);
        } else {
            $content = $this->buildPdf(
                $report->title,
                Carbon::parse($report->date_from),
                Carbon::parse($report->date_to),
                $sessions->count(),
                (int) $sessions->sum('duration_seconds')
            );
        }

        Storage::disk('local')->put($fileName, $content);

        $report->forceFill([
            'file_path' => $fileName,
        ])->save();
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
