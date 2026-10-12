<?php

namespace App\Exports;

use App\Models\Laporan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanExcelExport
{
    public function download(Collection $reports): StreamedResponse
    {
        $template = resource_path('templates/format-laporan-CMS-2026.xlsx');
        abort_unless(is_file($template), 500, 'Template ekspor laporan tidak ditemukan.');

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getActiveSheet();
        $row = 2;

        foreach ($reports as $report) {
            if ($row > 18) {
                $sheet->duplicateStyle($sheet->getStyle('C18:N18'), "C{$row}:N{$row}");
            }

            foreach ($this->rowValues($report) as $column => $value) {
                $sheet->setCellValueExplicit(
                    "{$column}{$row}",
                    (string) $value,
                    DataType::TYPE_STRING,
                );
            }

            $sheet->getStyle("C{$row}:N{$row}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);

            if ($row > 18) {
                $sheet->getRowDimension($row)->setRowHeight(42);
            }

            $row++;
        }

        $lastRow = max(1, $row - 1);
        $sheet->setAutoFilter("C1:N{$lastRow}");

        return response()->streamDownload(function () use ($spreadsheet): void {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'Laporan_CMS_' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function rowValues(Laporan $report): array
    {
        $realizationDate = $report->tanggal_realisasi;
        $leaders = $report->pimpinan_hadir ?? [];

        if ($report->pimpinan_terlibat !== null) {
            $leaders[] = 'Masukan pimpinan: ' . $this->answer($report->pimpinan_terlibat);
        }

        $evidence = collect($report->images ?: array_filter([$report->image]))
            ->map(fn (string $path) => rtrim((string) config('app.url'), '/') . '/storage/' . ltrim($path, '/'))
            ->all();

        if ($report->video_url) {
            $evidence[] = $report->video_url;
        }

        $realization = array_filter([
            'Tema kegiatan: ' . $report->nama,
            $report->alasan,
        ]);

        return [
            'C' => $report->created_at?->format('d/m/Y') ?? '',
            'D' => $report->user?->divisi?->divisi_alias
                ?: $report->user?->divisi?->nama_divisi
                ?? '',
            'E' => $report->programBudaya?->nama ?? '',
            'F' => $report->fokusArea?->nama ?? '',
            'G' => $realizationDate?->locale('id')->translatedFormat('F') ?? '',
            'H' => $realizationDate?->format('d/m/Y') ?? '',
            'I' => $report->programBudaya?->deskripsi ?? '',
            'J' => implode("\n", $realization),
            'K' => implode("\n", $leaders),
            'L' => $this->answer($report->change_agent_berpartisipasi),
            'M' => $this->answer($report->target_divisi_60_90_persen),
            'N' => implode("\n", $evidence),
        ];
    }

    private function answer(?bool $value): string
    {
        return match ($value) {
            true => 'Ya',
            false => 'Tidak',
            null => '-',
        };
    }
}