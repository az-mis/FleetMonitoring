<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use Modules\Vehicle\Models\Vehicle;

/** "List of Vehicle": drivers, assignment, paperwork and equipment of every vehicle */
final class ListOfVehicleSheet extends GssSheet
{
    private const OR_CR = ['Original on file' => 'YES', 'Copy only' => 'COPY ONLY', 'No copy' => 'NO COPY'];

    public static function headerRow(int $year): array
    {
        return ['NO.', 'NAME OF DRIVER', 'MOBILE NUMBER', 'PLACE OF ASSIGNMENT', 'FLEET CARD ALLOWANCE', 'PLATE NO.', 'DIVISION/END USER', 'NAME OF PERSON ASSIGNED VEHICLE', 'VEHICLE TYPE', 'YEAR MODEL', 'STATUS', 'OR CR COPY ORIG. (Y/N)', 'PLACE OF REGISTRATION AND RENEWAL', 'TIRE TYPE', 'INSURANCE STATUS', 'REGISTRATION STATUS '.$year, 'SCHEDULE OF RENEWAL REGISTRATION', 'Early Warning Devce', 'TOOLS'];
    }

    public function key(): string
    {
        return 'list-of-vehicle';
    }

    public function title(): string
    {
        return 'List of Vehicle';
    }

    public function heading(GssScope $scope): string
    {
        return 'LIST OF VEHICLES '.$scope->year;
    }

    public function headers(GssScope $scope): array
    {
        return [self::headerRow($scope->year)];
    }

    public function moneyColumns(): array
    {
        return [4];
    }

    public function rows(GssScope $scope): iterable
    {
        $vehicles = Vehicle::query()
            ->with(['assignedDriver', 'endUserOffice'])
            ->when($scope->location, fn ($q) => $q->where('location', $scope->location))
            ->get()
            ->sortBy(fn (Vehicle $v) => [array_search($v->location, $scope->areas(), true), $v->plate_number])
            ->values();

        foreach ($vehicles as $i => $vehicle) {
            $insurance = $vehicle->insurance_type || $vehicle->insurance_to
                ? trim(strtoupper((string) $vehicle->insurance_type).' - '.strtoupper(($vehicle->insurance_from?->format('M j, Y') ?? '').' TO '.($vehicle->insurance_to?->format('M j, Y') ?? '')), ' -')
                : '';

            yield ['cells' => [
                $i + 1,
                $vehicle->assignedDriver?->name ?? 'No Driver',
                $vehicle->assignedDriver?->phone_number,
                $vehicle->location,
                $vehicle->fleet_card_allowance !== null ? (float) $vehicle->fleet_card_allowance : null,
                $vehicle->old_plate_number ? "{$vehicle->old_plate_number} ({$vehicle->plate_number})" : $vehicle->plate_number,
                $vehicle->endUserOffice?->code,
                $vehicle->assigned_person,
                strtoupper(trim($vehicle->brand.' '.$vehicle->model)) ?: strtoupper((string) $vehicle->vehicle_type),
                $vehicle->year,
                strtoupper((string) $vehicle->condition),
                self::OR_CR[$vehicle->or_cr_status] ?? '',
                strtoupper((string) $vehicle->registration_place),
                $vehicle->tire_type,
                $insurance,
                $vehicle->registration_expiry ? $vehicle->registrationStatus()['label'] : '',
                strtoupper((string) $vehicle->renewal_schedule),
                $vehicle->has_ewd === null ? '' : ($vehicle->has_ewd ? 'YES' : 'NO'),
                $vehicle->tools,
            ]];
        }
    }
}
