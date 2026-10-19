<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use Modules\Maintenance\Models\VehicleMaintenance;

/** "VEHICLE PMS": preventive maintenance and oil changes of the year */
final class VehiclePmsSheet extends GssSheet
{
    public const HEADERS = [
        ['PM REQUEST NO.', 'DATE REQUESTED', 'NAME OF DRIVER', 'REQUISITIONER/ END-USER', 'VEHICLE DESCRIPTION (TYPE/MODEL/YEAR)', 'PLATE NO.', 'Current Odometer', '', '', 'DATE COMPLETED', 'Total Cost (₱)', 'Status', 'Remarks'],
        ['', '', '', '', '', '', 'KM', 'LTR', 'DATE READ', '', '', '', ''],
    ];

    public function key(): string
    {
        return 'vehicle-pms';
    }

    public function title(): string
    {
        return 'VEHICLE PMS';
    }

    public function heading(GssScope $scope): string
    {
        return 'VEHICLE PREVENTIVE MAINTENANCE '.$scope->year;
    }

    public function headers(GssScope $scope): array
    {
        return self::HEADERS;
    }

    public function moneyColumns(): array
    {
        return [10];
    }

    public function rows(GssScope $scope): iterable
    {
        $records = VehicleMaintenance::query()
            ->with(['vehicle.assignedDriver', 'vehicle.endUserOffice', 'requisitionerOffice', 'procurements'])
            ->where(fn ($q) => $q->whereIn('type', RepairIndexSheet::PMS_TYPES)->orWhereNotNull('pm_request_no'))
            ->where('status', '!=', 'cancelled')
            ->whereBetween('scheduled_date', [$scope->start()->toDateString(), $scope->end()->toDateString()])
            ->when($scope->location, fn ($q) => $q->where('location', $scope->location))
            ->orderBy('scheduled_date')
            ->get();

        foreach ($records as $i => $record) {
            yield ['cells' => [
                $record->pm_request_no ?: $i + 1,
                $record->created_at,
                $record->vehicle?->assignedDriver?->name,
                $record->requisitionerOffice?->code ?? $record->vehicle?->endUserOffice?->code,
                $this->vehicleDescription($record->vehicle) ?: $record->vehicle_label,
                $record->plate_number,
                $record->odometer,
                null,
                $record->odometer ? $record->scheduled_date : null,
                $record->completed_date,
                $record->cost !== null ? (float) $record->cost : null,
                $record->procurements->first()?->status ?? ucfirst((string) $record->status),
                $record->notes,
            ]];
        }
    }
}
