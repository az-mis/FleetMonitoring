<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use Modules\Maintenance\Models\VehicleMaintenance;

/** "VEHICLE PRE & POST REPAIR CM VALUE": inspections and current market values recorded on the year's work */
final class CmValueSheet extends GssSheet
{
    public const HEADERS = ['DATE OF REQUEST', 'NAME OF REQUESTER', 'PLATE NO.', 'TYPE OF VEHICLE', 'MODEL', 'END-USER', 'PRE INSPECTION DATE', 'COMPLAINTS, OR DEFFECTS TO BE DONE FOR REPAIR OR REPLACEMENT', 'POST INSPECTION', 'POST INSPECTION DATE', 'ACQUISITION DATE/YEAR', 'ACQUISITION COST', 'C . M. VALUE', 'MARKET VALUE DATE OF ISSUANCE', 'ISSUED BY'];

    public function key(): string
    {
        return 'cm-value';
    }

    public function title(): string
    {
        return 'VEHICLE PRE & POST REPAIR CM VA';
    }

    public function heading(GssScope $scope): string
    {
        return 'VEHICLE PRE & POST REPAIR AND CURRENT MARKET VALUE '.$scope->year;
    }

    public function headers(GssScope $scope): array
    {
        return [self::HEADERS];
    }

    public function moneyColumns(): array
    {
        return [11, 12];
    }

    public function rows(GssScope $scope): iterable
    {
        $records = VehicleMaintenance::query()
            ->with(['vehicle.endUserOffice', 'requisitionerOffice'])
            ->where(fn ($q) => $q->whereNotNull('pre_inspection_date')->orWhereNotNull('post_inspection_date')
                ->orWhereNotNull('current_market_value')->orWhereNotNull('defect_nature'))
            ->where('status', '!=', 'cancelled')
            ->whereBetween('scheduled_date', [$scope->start()->toDateString(), $scope->end()->toDateString()])
            ->when($scope->location, fn ($q) => $q->where('location', $scope->location))
            ->orderBy('created_at')
            ->get();

        foreach ($records as $record) {
            $vehicle = $record->vehicle;

            yield ['cells' => [
                $record->created_at,
                $record->requisitionerOffice?->code ?? $record->created_by_name,
                $record->plate_number,
                strtoupper(trim(($vehicle?->brand ?? '').' '.($vehicle?->model ?? '').' '.($vehicle?->vehicle_type ?? ''))) ?: $record->vehicle_label,
                $vehicle?->year,
                $vehicle?->endUserOffice?->code,
                $record->pre_inspection_date,
                $record->defect_nature,
                $record->post_inspection_findings,
                $record->post_inspection_date,
                $vehicle?->acquisition_date,
                $vehicle?->acquisition_cost !== null ? (float) $vehicle->acquisition_cost : null,
                $record->current_market_value !== null ? (float) $record->current_market_value : null,
                $record->cmv_issued_at,
                $record->cmv_issued_by,
            ]];
        }
    }
}
