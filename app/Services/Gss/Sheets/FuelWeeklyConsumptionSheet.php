<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use Modules\VehicleRequest\Models\VehicleRequest;

/**
 * "FUEL WEEKLY CONSUMPTION": every completed trip per vehicle (from the driver's Part B), with the fuel used
 * that week shown on the vehicle's first trip of the week.
 */
final class FuelWeeklyConsumptionSheet extends GssSheet
{
    public const HEADERS = [
        ['NO.', 'VEHICLE', 'NAME OF DRIVER', 'PLATE NUMBER', 'FUEL TYPE', 'DATE TRIP', 'TRIP TICKET NUMBER', 'BALANCE IN TANK (BEFORE TRAVEL)', 'DESTINATION', 'FUEL PURCHASED (liters)', 'BALANCE IN TANK (AFTER TRAVEL)', 'DISTANCE TRAVELED ( in kms)', 'FUEL CONSUMED PER WEEK (liters)', ''],
        ['', '', '', '', '', '', '', '', '', '', '', '', 'WEEKS', 'LITERS'],
    ];

    public function key(): string
    {
        return 'fuel-weekly';
    }

    public function title(): string
    {
        return 'FUEL WEEKLY CONSUMPTION';
    }

    public function heading(GssScope $scope): string
    {
        return 'FUEL WEEKLY CONSUMPTION '.$scope->year;
    }

    public function headers(GssScope $scope): array
    {
        return self::HEADERS;
    }

    public function rows(GssScope $scope): iterable
    {
        $trips = VehicleRequest::query()
            ->with(['vehicle', 'confirmedDriver', 'completion'])
            ->where('status', 'approved')
            ->whereHas('completion')
            ->whereBetween('travel_date', [$scope->start()->toDateString(), $scope->end()->toDateString()])
            ->when($scope->location, fn ($q) => $q->where('location', $scope->location))
            ->orderBy('travel_date')
            ->get()
            ->groupBy('vehicle_id');

        $no = 0;
        foreach ($trips as $vehicleTrips) {
            $vehicle = $vehicleTrips->first()->vehicle;
            $weekLiters = $vehicleTrips->groupBy(fn ($trip) => $trip->travel_date->format('o-W'))
                ->map(fn ($week) => round((float) $week->sum(fn ($trip) => (float) $trip->completion->fuel_used_liters), 2));
            $no++;

            foreach ($vehicleTrips->values() as $i => $trip) {
                $c = $trip->completion;
                $week = $trip->travel_date->format('o-W');
                $firstOfWeek = $vehicleTrips->first(fn ($t) => $t->travel_date->format('o-W') === $week)->is($trip);
                $weekStart = $trip->travel_date->copy()->startOfWeek();

                yield ['cells' => [
                    $i === 0 ? $no : '',
                    $i === 0 ? strtoupper(trim(($vehicle?->brand ?? '').' '.($vehicle?->model ?? ''))) : '',
                    $i === 0 ? $trip->confirmedDriver?->name : '',
                    $i === 0 ? $vehicle?->plate_number : '',
                    $i === 0 ? strtoupper((string) $vehicle?->fuel_type) : '',
                    $this->dateRange($trip->travel_date, $trip->travel_date_end),
                    $trip->trip_ticket_number,
                    (float) $c->fuel_balance_in_tank,
                    $this->destination($trip->destination),
                    // Fuel added for the trip: issued from office stock plus bought on the way
                    round((float) $c->fuel_issued_office + (float) $c->fuel_purchased_trip, 2),
                    (float) $c->fuel_ending_balance,
                    (float) $c->distance_travelled,
                    $firstOfWeek ? $weekStart->format('M j').' - '.$weekStart->copy()->endOfWeek()->format('M j') : '',
                    $firstOfWeek ? $weekLiters[$week] : null,
                ]];
            }
        }
    }
}
