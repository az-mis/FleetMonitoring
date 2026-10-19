<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use Modules\Vehicle\Models\Vehicle;

/** "VEHICLE INVENTORY": the property record of every vehicle, with its accessories below it */
final class VehicleInventorySheet extends GssSheet
{
    public const HEADERS = ['NO.', 'ARTICLE', 'DESCRIPTION', 'UNIT OF MEASURE', 'QUANTITY', 'PAR NUMBER', 'PROPERTY NUMBER', 'PLATE NUMBER', 'LOCATION', 'DATE ACQUIRED', 'TOTAL VALUE', 'CUSTODIAN', 'For Reconciliation', 'STATUS', 'END USER', 'REMARKS', 'Condition', 'HISTORY'];

    public function key(): string
    {
        return 'vehicle-inventory';
    }

    public function title(): string
    {
        return 'VEHICLE INVENTORY';
    }

    public function heading(GssScope $scope): string
    {
        return 'INVENTORY OF VEHICLES AS OF '.strtoupper(now()->format('F j, Y'));
    }

    public function headers(GssScope $scope): array
    {
        return [self::HEADERS];
    }

    public function moneyColumns(): array
    {
        return [10];
    }

    public function rows(GssScope $scope): iterable
    {
        $vehicles = Vehicle::query()
            ->with(['endUserOffice', 'accessories'])
            ->when($scope->location, fn ($q) => $q->where('location', $scope->location))
            ->get()
            ->sortBy(fn (Vehicle $v) => [array_search($v->location, $scope->areas(), true), $v->plate_number])
            ->values();

        foreach ($vehicles as $i => $vehicle) {
            $description = implode("\n", array_filter([
                strtoupper(trim($vehicle->brand.' '.$vehicle->model)),
                $vehicle->year ? 'Year Model: '.$vehicle->year : null,
                $vehicle->color ? 'Color: '.$vehicle->color : null,
                $vehicle->engine_number ? 'Engine #: '.$vehicle->engine_number : null,
                $vehicle->chassis_number ? 'Frame #: '.$vehicle->chassis_number : null,
                $vehicle->conduction_sticker ? 'Conduction Sticker: '.$vehicle->conduction_sticker : null,
                'Plate#: '.$vehicle->plate_number,
            ]));

            yield ['cells' => [
                $i + 1,
                strtoupper((string) $vehicle->vehicle_type),
                $description,
                'unit',
                1,
                $vehicle->par_number,
                $vehicle->property_number,
                $vehicle->plate_number,
                $vehicle->location,
                $vehicle->acquisition_date,
                $vehicle->acquisition_cost !== null ? (float) $vehicle->acquisition_cost : null,
                $vehicle->custodian_name,
                strtoupper((string) $vehicle->book_status) === 'FOR RECONCILIATION' ? 'YES' : '',
                $vehicle->book_status,
                $vehicle->endUserOffice?->code,
                $vehicle->registry_remarks,
                $vehicle->condition,
                $vehicle->history,
            ]];

            foreach ($vehicle->accessories as $accessory) {
                yield ['cells' => [
                    '', 'ACCESSORY', $accessory->description, 'unit', 1, '', '', $vehicle->plate_number, $vehicle->location,
                    $accessory->date_acquired, $accessory->value !== null ? (float) $accessory->value : null,
                ]];
            }
        }
    }
}
