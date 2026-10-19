<?php

declare(strict_types=1);

namespace App\Services\Gss;

use App\Services\Gss\Sheets\CmValueSheet;
use App\Services\Gss\Sheets\FuelWeeklyConsumptionSheet;
use App\Services\Gss\Sheets\IndexForFleetcardSheet;
use App\Services\Gss\Sheets\ListOfVehicleSheet;
use App\Services\Gss\Sheets\PetronFleetCardSheet;
use App\Services\Gss\Sheets\ProcurementSheet;
use App\Services\Gss\Sheets\RepairIndexSheet;
use App\Services\Gss\Sheets\TripTicketMonitoringSheet;
use App\Services\Gss\Sheets\VehicleInventorySheet;
use App\Services\Gss\Sheets\VehiclePmsSheet;
use App\Services\Gss\Sheets\VehicleRequestMonitoringSheet;

/** The GSS workbook export: the vehicle sheets of "GSS {year}.xlsx", built from the system's records */
final class GssExportService
{
    /** Sheets in workbook order */
    private const SHEETS = [
        PetronFleetCardSheet::class,
        VehicleInventorySheet::class,
        ListOfVehicleSheet::class,
        FuelWeeklyConsumptionSheet::class,
        RepairIndexSheet::class,
        IndexForFleetcardSheet::class,
        CmValueSheet::class,
        VehiclePmsSheet::class,
        TripTicketMonitoringSheet::class,
        VehicleRequestMonitoringSheet::class,
        ProcurementSheet::class,
    ];

    public function __construct(private readonly GssWorkbookWriter $writer) {}

    /** @return array<string, GssSheet> keyed by sheet key */
    public function sheets(): array
    {
        $sheets = [];
        foreach (self::SHEETS as $class) {
            $sheet = app($class);
            $sheets[$sheet->key()] = $sheet;
        }

        return $sheets;
    }

    /** @param list<string> $keys sheets to include (all when empty) */
    public function export(GssScope $scope, array $keys = []): string
    {
        $sheets = array_values(array_filter(
            $this->sheets(),
            fn (GssSheet $sheet) => $keys === [] || in_array($sheet->key(), $keys, true),
        ));

        return $this->writer->write($sheets ?: array_values($this->sheets()), $scope);
    }
}
