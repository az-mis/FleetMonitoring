<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use App\Support\FleetLocations;
use Modules\Maintenance\Models\VehicleMaintenance;

/** "INDEX FOR REPAIR OF VEHICLES": repairs of the year (preventive work is on the Vehicle PMS sheet), with the year-end summary */
final class RepairIndexSheet extends GssSheet
{
    public const HEADERS = ['NO.', 'AREA', 'REQUISITIONER/END-USER', 'DATE PRE-REPAIR', 'VEHICLE TYPE/ MODEL/ YEAR', 'PLATE NUMBER', 'PROPERTY NUMBER', 'DRIVER ASSIGNED', 'DATE OF POST-REPAIR', 'NATURE OF DEFECT', 'PARTS NEED TO BE REPLACE', 'REPAIR COST', 'STATUS', 'REMARKS'];

    /** Maintenance types the workbook keeps on its PMS sheet instead */
    public const PMS_TYPES = ['Preventive', 'Oil Change'];

    public function key(): string
    {
        return 'repair-index';
    }

    public function title(): string
    {
        return 'INDEX FOR REPAIR OF VEHICLES';
    }

    public function heading(GssScope $scope): string
    {
        return 'INDEX FOR REPAIR OF VEHICLES '.$scope->year;
    }

    public function headers(GssScope $scope): array
    {
        return [self::HEADERS];
    }

    public function moneyColumns(): array
    {
        return [11];
    }

    public function rows(GssScope $scope): iterable
    {
        $records = VehicleMaintenance::query()
            ->with(['vehicle.assignedDriver', 'vehicle.endUserOffice', 'requisitionerOffice', 'procurements'])
            ->whereNotIn('type', self::PMS_TYPES)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('scheduled_date', [$scope->start()->toDateString(), $scope->end()->toDateString()])
            ->when($scope->location, fn ($q) => $q->where('location', $scope->location))
            ->orderBy('scheduled_date')
            ->get()
            ->sortBy(fn ($r) => [array_search($r->location, $scope->areas(), true), $r->scheduled_date->timestamp])
            ->values();

        foreach ($records as $i => $record) {
            yield ['cells' => [
                $i + 1,
                FleetLocations::areaHeading((string) $record->location),
                $record->requisitionerOffice?->code ?? $record->vehicle?->endUserOffice?->code,
                $record->pre_inspection_date,
                $this->vehicleDescription($record->vehicle) ?: $record->vehicle_label,
                $record->plate_number,
                $record->vehicle?->property_number,
                $record->vehicle?->assignedDriver?->name,
                $record->post_inspection_date ?? $record->completed_date,
                $record->defect_nature ?: $record->title,
                $record->parts_replaced,
                $record->cost !== null ? (float) $record->cost : null,
                $record->procurements->first()?->status ?? ucfirst((string) $record->status),
                $record->notes,
            ]];
        }

        $completed = $records->where('status', 'completed');
        yield ['cells' => []];
        yield ['cells' => ['', 'SUMMARY ( BY THE END OF THE YEAR)'], 'bold' => true];
        yield ['cells' => ['', 'Total Units Repaired:', '', $completed->pluck('vehicle_id')->unique()->count()]];
        yield ['cells' => ['', 'Total Repair Cost:', '', round((float) $records->sum(fn ($r) => (float) $r->cost), 2)], 'money' => [3]];
        yield ['cells' => ['', 'Pending Repairs:', '', $records->whereIn('status', ['scheduled', 'ongoing'])->count()]];
        yield ['cells' => ['', 'Completed Repairs:', '', $completed->count()]];
    }
}
