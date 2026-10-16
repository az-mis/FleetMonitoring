<?php

declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Builds the Reports downloads (Excel + Word) from the same context the Reports page uses.
 * Requires: composer require phpoffice/phpspreadsheet phpoffice/phpword
 */
class ReportExportService
{
    private const GREEN = '005A36';
    private const LIGHT = 'E8F5EE';

    /* ───────────────────────── shared helpers ───────────────────────── */

    /** A writable temp file inside storage/ (Windows/Herd system temp is not always writable). */
    private function tempFile(): string
    {
        $dir = storage_path('app/report-exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Clean up leftovers older than an hour
        foreach (glob($dir . DIRECTORY_SEPARATOR . 'rpt*') ?: [] as $old) {
            if (is_file($old) && filemtime($old) < time() - 3600) {
                @unlink($old);
            }
        }

        return $dir . DIRECTORY_SEPARATOR . 'rpt' . bin2hex(random_bytes(6));
    }

    private function headerImage(): ?string
    {
        $path = public_path('trip-ticket-header.png');

        return is_file($path) ? $path : null;
    }

    private function rangeLabel(array $ctx): string
    {
        return $ctx['rangeStart']->format('F j, Y') . ' – ' . $ctx['rangeEnd']->format('F j, Y');
    }

    private function vehicleLabel($request): string
    {
        $v = $request->vehicle;
        if (! $v) {
            return '';
        }
        $name = trim(($v->brand ?? '') . ' ' . ($v->model ?? ''));

        return $name !== '' ? "{$name}, {$v->plate_number}" : (string) $v->plate_number;
    }

    private function travelDates($request): string
    {
        $start = $request->travel_date?->format('M j, Y') ?? '';
        $end = $request->travel_date_end?->format('M j, Y');

        return ($end && $end !== $start) ? "{$start} – {$end}" : $start;
    }

    /** Rows shared by the Summary sheet and the Word document. */
    private function summaryRows(array $ctx): array
    {
        $s = $ctx['summary'];

        return [
            ['Total requests', $s['total']],
            ['Approved trips', $s['approved']],
            ['Pending requests', $s['pending']],
            ['Declined requests', $s['declined']],
            ['Approval rate', $s['approval_rate'] === null ? '—' : $s['approval_rate'] . '%'],
            ['Vehicles in fleet', $s['fleet']],
            ['Active maintenance (scheduled / ongoing)', $s['active_maintenance']],
            ['Completed maintenance in range', $s['maintenance_done']],
            ['Maintenance cost in range', '₱' . number_format($s['maintenance_cost'], 2)],
        ];
    }

    private function tripHeaders(array $ctx): array
    {
        $h = ['Trip Ticket No.', 'Travel Date', 'Requester'];
        if ($ctx['isSuperAdmin']) {
            $h[] = 'Location';
        }

        return array_merge($h, ['Vehicle', 'Driver', 'Destination', 'Purpose', 'Status']);
    }

    private function tripRows(array $ctx): array
    {
        return $ctx['tripLog']->map(function ($r) use ($ctx) {
            $row = [$r->trip_ticket_number ?: '—', $this->travelDates($r), $r->requester_name];
            if ($ctx['isSuperAdmin']) {
                $row[] = $r->location;
            }

            return array_merge($row, [
                $this->vehicleLabel($r),
                $r->confirmedDriver?->name ?? '',
                str_replace(';;', ', ', (string) $r->destination),
                $r->purpose,
                ucfirst((string) $r->status),
            ]);
        })->all();
    }

    private function maintenanceHeaders(array $ctx): array
    {
        $h = ['Completed', 'Vehicle', 'Type', 'Title', 'Vendor'];
        if ($ctx['isSuperAdmin']) {
            $h[] = 'Location';
        }

        return array_merge($h, ['Cost (PHP)']);
    }

    private function maintenanceRows(array $ctx): array
    {
        return $ctx['maintenanceLog']->map(function ($m) use ($ctx) {
            $row = [$m->completed_date?->format('M j, Y'), trim(($m->vehicle_label ?: '') . ' ' . $m->plate_number), $m->type, $m->title, $m->vendor ?: ''];
            if ($ctx['isSuperAdmin']) {
                $row[] = $m->location;
            }
            $row[] = (float) $m->cost;

            return $row;
        })->all();
    }

    private function locationHeaders(): array
    {
        return ['Location', 'Vehicles', 'Requests', 'Pending', 'Approved Trips', 'Declined', 'Approval Rate', 'Active Maintenance', 'Maintenance Cost (PHP)'];
    }

    private function locationRows(array $ctx): array
    {
        return collect($ctx['locationBreakdown'] ?? [])->map(fn ($r) => [
            $r['location'], $r['vehicles'], $r['requests'], $r['pending'], $r['trips'], $r['declined'],
            $r['approval_rate'] === null ? '—' : $r['approval_rate'] . '%',
            $r['maintenance_active'], (float) $r['maintenance_cost'],
        ])->all();
    }

    /* ───────────────────────── Excel ───────────────────────── */

    public function xlsx(array $ctx): string
    {
        $book = new Spreadsheet();
        $book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        // Sheet 1 — Summary (with letterhead)
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Summary');
        $sheet->setShowGridlines(false);

        $row = 1;
        if ($img = $this->headerImage()) {
            $drawing = new Drawing();
            $drawing->setName('Letterhead')->setPath($img)->setHeight(105)->setCoordinates('A1');
            $drawing->setWorksheet($sheet);
            $row = 8;
        }

        $sheet->setCellValue("A{$row}", 'FLEET MANAGEMENT REPORT');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::GREEN);
        $row++;
        $sheet->setCellValue("A{$row}", 'Office: ' . $ctx['scopeLabel']);
        $row++;
        $sheet->setCellValue("A{$row}", 'Period: ' . $this->rangeLabel($ctx));
        $row++;
        $sheet->setCellValue("A{$row}", 'Generated by ' . $ctx['generatedBy'] . ' on ' . $ctx['generatedAt']->format('F j, Y g:i A'));
        $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB('64748B');
        $row += 2;

        $row = $this->xlsxTable($sheet, $row, ['Indicator', 'Value'], $this->summaryRows($ctx));
        $sheet->getColumnDimension('A')->setWidth(44);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getStyle('B1:B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Sheet 2 — Trip log
        $this->xlsxSheet($book, 'Trip Log', $this->tripHeaders($ctx), $this->tripRows($ctx), $ctx);

        // Sheet 3 — Top destinations
        $dest = [];
        foreach ($ctx['topDestinations']['labels'] as $i => $label) {
            $dest[] = [$label, (int) $ctx['topDestinations']['data'][$i]];
        }
        $this->xlsxSheet($book, 'Destinations', ['Destination', 'Requests'], $dest, $ctx);

        // Sheet 4 — Vehicle usage
        $veh = [];
        foreach ($ctx['tripsPerVehicle']['labels'] as $i => $label) {
            $veh[] = [$label, (int) $ctx['tripsPerVehicle']['data'][$i]];
        }
        $this->xlsxSheet($book, 'Vehicle Usage', ['Plate Number', 'Approved Trips'], $veh, $ctx);

        // Sheet 5 — Maintenance
        $this->xlsxSheet($book, 'Maintenance', $this->maintenanceHeaders($ctx), $this->maintenanceRows($ctx), $ctx, moneyColumn: true);

        // Sheet 6 — Super Admin only, all offices
        if (! empty($ctx['locationBreakdown'])) {
            $this->xlsxSheet($book, 'Office Comparison', $this->locationHeaders(), $this->locationRows($ctx), $ctx, moneyColumn: true);
        }

        $book->setActiveSheetIndex(0);

        $path = $this->tempFile();
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return $path;
    }

    private function xlsxSheet(Spreadsheet $book, string $title, array $headers, array $rows, array $ctx, bool $moneyColumn = false): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle($title);

        $sheet->setCellValue('A1', strtoupper($title) . ' — ' . $ctx['scopeLabel']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::GREEN);
        $sheet->setCellValue('A2', $this->rangeLabel($ctx));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('64748B');

        $end = $this->xlsxTable($sheet, 4, $headers, $rows);

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        for ($c = 1; $c <= count($headers); $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
        if ($moneyColumn && $rows) {
            $sheet->getStyle($lastCol . '5:' . $lastCol . ($end - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet->freezePane('A5');
        if ($rows) {
            $sheet->setAutoFilter('A4:' . $lastCol . ($end - 1));
        }
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getSheetView()->setZoomScale(100);
    }

    /** Writes a styled table, returns the next free row. */
    private function xlsxTable($sheet, int $startRow, array $headers, array $rows): int
    {
        $cols = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex($cols);

        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, $startRow], $h);
        }
        $sheet->getStyle("A{$startRow}:{$lastCol}{$startRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($startRow)->setRowHeight(22);

        $r = $startRow + 1;
        if (! $rows) {
            $sheet->setCellValue("A{$r}", 'No records for this period.');
            $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->getColor()->setRGB('94A3B8');

            return $r + 1;
        }

        foreach ($rows as $n => $row) {
            foreach (array_values($row) as $i => $value) {
                // Text such as requester names or purposes comes from the public form. Writing it
                // explicitly as a string stops values like "=HYPERLINK(...)" becoming live formulas.
                if (is_string($value)) {
                    $sheet->setCellValueExplicit([$i + 1, $r], $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue([$i + 1, $r], $value);
                }
            }
            if ($n % 2 === 1) {
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }
            $r++;
        }

        $sheet->getStyle("A{$startRow}:{$lastCol}" . ($r - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
        $sheet->getStyle("A" . ($startRow + 1) . ":{$lastCol}" . ($r - 1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

        return $r;
    }

    /* ───────────────────────── Word ───────────────────────── */

    public function docx(array $ctx): string
    {
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

        // PhpWord builds the .docx in its own temp folder; point it at a writable one (Windows/Herd).
        $tmp = storage_path('app/report-exports/phpword');
        if (! is_dir($tmp)) {
            mkdir($tmp, 0775, true);
        }
        \PhpOffice\PhpWord\Settings::setTempDir($tmp);

        $word = new PhpWord();
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(10);
        $word->setDefaultParagraphStyle(['spaceAfter' => 0]);

        $section = $word->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => Converter::cmToTwip(29.7),
            'pageSizeH' => Converter::cmToTwip(21.0),
            'marginLeft' => Converter::cmToTwip(1.5),
            'marginRight' => Converter::cmToTwip(1.5),
            'marginTop' => Converter::cmToTwip(1.0),
            'marginBottom' => Converter::cmToTwip(1.5),
        ]);

        if ($img = $this->headerImage()) {
            $section->addImage($img, ['width' => 560, 'alignment' => Jc::CENTER]);
        }

        $section->addTextBreak(1);
        $section->addText('FLEET MANAGEMENT REPORT', ['bold' => true, 'size' => 18, 'color' => self::GREEN], ['alignment' => Jc::CENTER]);
        $section->addText('Office: ' . $ctx['scopeLabel'], ['size' => 11], ['alignment' => Jc::CENTER]);
        $section->addText('Period: ' . $this->rangeLabel($ctx), ['size' => 11], ['alignment' => Jc::CENTER]);
        $section->addText(
            'Generated by ' . $ctx['generatedBy'] . ' on ' . $ctx['generatedAt']->format('F j, Y g:i A'),
            ['italic' => true, 'size' => 9, 'color' => '64748B'],
            ['alignment' => Jc::CENTER]
        );
        $section->addTextBreak(1);

        // Summary
        $this->docHeading($section, 'Summary');
        $this->docTable($section, ['Indicator', 'Value'], $this->summaryRows($ctx), [8.0, 4.0], [1 => Jc::END]);

        // Super Admin only
        if (! empty($ctx['locationBreakdown'])) {
            $section->addTextBreak(1);
            $this->docHeading($section, 'Office Comparison');
            $rows = collect($this->locationRows($ctx))->map(function ($r) {
                $r[8] = number_format((float) $r[8], 2);

                return $r;
            })->all();
            $this->docTable($section, $this->locationHeaders(), $rows, [5.0, 1.8, 2.0, 1.8, 2.6, 2.0, 2.6, 3.3, 3.6]);
        }

        // Trip log
        $section->addPageBreak();
        $this->docHeading($section, 'Trip Log (' . count($ctx['tripLog']) . ')');
        $widths = $ctx['isSuperAdmin']
            ? [3.4, 3.0, 2.8, 3.0, 3.6, 2.4, 3.0, 3.2, 1.8]
            : [3.6, 3.2, 3.0, 4.0, 2.6, 3.6, 4.0, 2.0];
        $this->docTable($section, $this->tripHeaders($ctx), $this->tripRows($ctx), $widths, [], 8);

        // Maintenance
        $section->addTextBreak(1);
        $this->docHeading($section, 'Completed Maintenance (' . count($ctx['maintenanceLog']) . ')');
        $mRows = collect($this->maintenanceRows($ctx))->map(function ($r) {
            $last = count($r) - 1;
            $r[$last] = number_format((float) $r[$last], 2);

            return $r;
        })->all();
        $mWidths = $ctx['isSuperAdmin'] ? [3.0, 4.6, 3.0, 5.6, 3.2, 3.6, 3.0] : [3.0, 5.0, 3.4, 6.6, 4.2, 3.4];
        $this->docTable($section, $this->maintenanceHeaders($ctx), $mRows, $mWidths, [count($mWidths) - 1 => Jc::END], 8);

        // Footer
        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Doc. No.: DAMIMAROPA-F083-2023   |   Rev. No.: 0   |   Issued Date: 10/23/23      Page {PAGE} of {NUMPAGES}',
            ['size' => 8, 'color' => '64748B'],
            ['alignment' => Jc::CENTER]
        );

        $path = $this->tempFile();
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($path);

        return $path;
    }

    private function docHeading($section, string $text): void
    {
        $section->addText($text, ['bold' => true, 'size' => 12, 'color' => self::GREEN], ['spaceAfter' => 80, 'keepNext' => true]);
    }

    /** $widths in cm, $align = [columnIndex => Jc::*] */
    private function docTable($section, array $headers, array $rows, array $widths, array $align = [], int $fontSize = 9): void
    {
        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => 'CBD5E1',
            'cellMargin' => 60,
            'alignment' => Jc::CENTER,
        ]);

        $table->addRow(null, ['tblHeader' => true]);
        foreach ($headers as $i => $h) {
            $table->addCell(Converter::cmToTwip($widths[$i] ?? 3.0), ['bgColor' => self::GREEN, 'valign' => 'center'])
                ->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => $fontSize], ['alignment' => $align[$i] ?? Jc::START]);
        }

        if (! $rows) {
            $table->addRow();
            $table->addCell(Converter::cmToTwip(array_sum($widths)), ['gridSpan' => count($headers)])
                ->addText('No records for this period.', ['italic' => true, 'color' => '94A3B8', 'size' => $fontSize]);

            return;
        }

        foreach ($rows as $n => $row) {
            $table->addRow(null, ['cantSplit' => true]);
            foreach (array_values($row) as $i => $value) {
                $cell = $table->addCell(
                    Converter::cmToTwip($widths[$i] ?? 3.0),
                    $n % 2 === 1 ? ['bgColor' => 'F8FAFC'] : []
                );
                $cell->addText((string) ($value ?? ''), ['size' => $fontSize], ['alignment' => $align[$i] ?? Jc::START]);
            }
        }
    }
}
