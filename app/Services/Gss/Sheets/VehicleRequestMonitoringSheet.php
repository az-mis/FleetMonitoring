<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use Modules\VehicleRequest\Models\VehicleRequest;

/** "RP VEHICLE REQUEST MONITORING": requests received in the year (declined ones left out) */
final class VehicleRequestMonitoringSheet extends GssSheet
{
    public const HEADERS = ['RP VEHICLE REQUEST NO.', 'DATE REQUEST', 'DRIVER', 'TYPE OF VEHICLE', 'DATE OF TRAVEL', 'PLATE NO.', 'PURPOSE OF TRAVEL', 'DESTINATION', 'REQUESTED BY'];

    public function key(): string
    {
        return 'vehicle-requests';
    }

    public function title(): string
    {
        return 'RP VEHICLE REQUEST MONITORING';
    }

    public function heading(GssScope $scope): string
    {
        return 'RP VEHICLE REQUEST MONITORING '.$scope->year;
    }

    public function headers(GssScope $scope): array
    {
        return [self::HEADERS];
    }

    public function rows(GssScope $scope): iterable
    {
        $requests = VehicleRequest::query()
            ->with(['vehicle', 'confirmedDriver'])
            ->where('status', '!=', 'declined')
            ->whereBetween('created_at', [$scope->start(), $scope->end()])
            ->when($scope->location, fn ($q) => $q->where('location', $scope->location))
            ->orderBy('created_at')
            ->get();

        foreach ($requests as $request) {
            yield ['cells' => [
                $request->reference_code,
                $request->created_at,
                $request->confirmedDriver?->name ?? $request->driver_name,
                strtoupper((string) $request->vehicle?->vehicle_type),
                $this->dateRange($request->travel_date, $request->travel_date_end),
                $request->vehicle?->plate_number,
                $request->purpose,
                $this->destination($request->destination),
                $request->requester_name,
            ]];
        }
    }
}
