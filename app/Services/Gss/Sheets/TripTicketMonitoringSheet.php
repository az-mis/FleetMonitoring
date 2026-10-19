<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use App\Support\FleetLocations;
use Modules\VehicleRequest\Models\VehicleRequest;

/** "TRIP TICKET MONITORING": every approved trip ticket of the year, by area */
final class TripTicketMonitoringSheet extends GssSheet
{
    public const HEADERS = [
        ['TRIP TICKET NUMBER', 'DATE RECORDED', 'REQUESTER NAME OR PROGRAM', 'PASSENGERS NAME OR LISTING', 'DATE OF TRAVEL', 'DATE OF RETURN', 'PURPOSE OF TRAVEL', 'DESTINATION', 'VEHICLE PLATE NUMBER', 'DRIVER', 'SPEEDOMETER READING', ''],
        ['', '', '', '', '', '', '', '', '', '', 'BEFORE', 'AFTER'],
    ];

    public function key(): string
    {
        return 'trip-tickets';
    }

    public function title(): string
    {
        return 'TRIP TICKET MONITORING';
    }

    public function heading(GssScope $scope): string
    {
        return 'TRIP TICKET MONITORING '.$scope->year;
    }

    public function headers(GssScope $scope): array
    {
        return self::HEADERS;
    }

    public function rows(GssScope $scope): iterable
    {
        $trips = VehicleRequest::query()
            ->with(['vehicle', 'confirmedDriver', 'completion', 'office'])
            ->where('status', 'approved')
            ->whereNotNull('trip_ticket_number')
            ->whereBetween('travel_date', [$scope->start()->toDateString(), $scope->end()->toDateString()])
            ->when($scope->location, fn ($q) => $q->where('location', $scope->location))
            ->orderBy('travel_date')->orderBy('id')
            ->get()
            ->groupBy('location');

        foreach ($scope->areas() as $area) {
            if ($trips->get($area, collect())->isEmpty()) {
                continue;
            }
            yield ['group' => FleetLocations::areaHeading($area)];

            foreach ($trips[$area] as $trip) {
                $passengers = collect($trip->requester_is_passenger ? [$trip->requester_name] : [])->merge($trip->passengers ?? [])->filter()->implode(', ');

                yield ['cells' => [
                    $trip->trip_ticket_number,
                    $trip->approved_at,
                    $trip->office?->code ?? ($trip->requester_office ?: $trip->requester_name),
                    $passengers,
                    $trip->travel_date,
                    $trip->travel_date_end ?? $trip->travel_date,
                    $trip->purpose,
                    $this->destination($trip->destination),
                    $trip->vehicle?->plate_number,
                    $trip->confirmedDriver?->name,
                    $trip->completion ? (float) $trip->completion->odo_beginning : null,
                    $trip->completion ? (float) $trip->completion->odo_ending : null,
                ]];
            }
        }
    }
}
