<?php

declare(strict_types=1);

namespace App\Services\Gss\Sheets;

use App\Services\Gss\GssScope;
use App\Services\Gss\GssSheet;
use App\Support\FleetLocations;
use Modules\FleetCard\Models\FleetCard;

/** "PETRON FLEET CARD": each Petron card's vehicle and allowed products, by area */
final class PetronFleetCardSheet extends GssSheet
{
    public const HEADERS = ['AREA', 'PLATE NUMBER / VEHICLE TYPE', 'ALLOWED PRODUCTS'];

    public function key(): string
    {
        return 'petron-fleet-card';
    }

    public function title(): string
    {
        return 'PETRON FLEET CARD';
    }

    public function heading(GssScope $scope): string
    {
        return 'PETRON FLEET CARD';
    }

    public function headers(GssScope $scope): array
    {
        return [self::HEADERS];
    }

    public function rows(GssScope $scope): iterable
    {
        $cards = FleetCard::query()
            ->with('vehicle')
            ->where('provider', 'Petron')
            ->when($scope->location, fn ($q) => $q->where('location', $scope->location))
            ->get()
            ->groupBy('location');

        foreach ($scope->areas() as $area) {
            if ($cards->get($area, collect())->isEmpty()) {
                continue;
            }
            yield ['group' => FleetLocations::areaHeading($area)];
            foreach ($cards[$area]->sortBy(fn ($card) => $card->vehicle?->plate_number) as $card) {
                $vehicle = $card->vehicle;
                $label = $vehicle
                    ? $vehicle->plate_number.' / '.strtoupper(trim($vehicle->brand.' '.$vehicle->model.' '.$vehicle->vehicle_type))
                    : $card->card_name.' ('.$card->card_number.')';

                yield ['cells' => ['', $label, $card->allowed_products]];
            }
        }
    }
}
