<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use Modules\Maintenance\Models\Procurement;

/** "PR OF GOODS AND SERVICES": the year's purchase requests for vehicle work (column C is the category, unlabelled as in the workbook) */
final class ProcurementSheet extends GssSheet
{
    public const HEADERS = ['NO.', 'DESCRIPTION', '', 'PR NUMBER', 'DATE OF PR', 'AMOUNT', 'STATUS', 'REMARKS', 'REMARKS', 'JO/PO NUMBER/ DATE', 'COA DATE'];

    public function key(): string
    {
        return 'procurement';
    }

    public function title(): string
    {
        return 'PR OF GOODS AND SERVICES';
    }

    public function heading(GssScope $scope): string
    {
        return 'PR OF GOODS AND SERVICES (VEHICLES) '.$scope->year;
    }

    public function headers(GssScope $scope): array
    {
        return [self::HEADERS];
    }

    public function moneyColumns(): array
    {
        return [5];
    }

    public function rows(GssScope $scope): iterable
    {
        $requests = Procurement::query()
            ->with(['maintenance', 'vehicle'])
            ->where(fn ($q) => $q->whereBetween('pr_date', [$scope->start()->toDateString(), $scope->end()->toDateString()])
                ->orWhere(fn ($q2) => $q2->whereNull('pr_date')->whereBetween('created_at', [$scope->start(), $scope->end()])))
            ->when($scope->location, fn ($q) => $q->where('location', $scope->location))
            ->orderByRaw('COALESCE(pr_date, created_at)')
            ->get();

        yield ['group' => $scope->year.' PROCUREMENT'];
        foreach ($requests as $i => $pr) {
            $for = collect([$pr->maintenance?->title, $pr->vehicle?->plate_number ?? $pr->maintenance?->plate_number])->filter()->implode(' · ');

            yield ['cells' => [
                $i + 1,
                $pr->description,
                $pr->category,
                $pr->pr_number,
                $pr->pr_date,
                $pr->amount !== null ? (float) $pr->amount : null,
                $pr->status,
                $pr->remarks,
                $for ? 'For: '.$for : '',
                trim(($pr->jo_po_number ?? '').($pr->jo_po_date ? ' / '.$pr->jo_po_date->format('M j, Y') : ''), ' /'),
                $pr->coa_date,
            ]];
        }
    }
}
