<?php

declare(strict_types=1);

namespace App\Services\Gss;

use App\Support\FleetLocations;
use Carbon\CarbonImmutable;

/** What a GSS workbook export covers: the year, the area (null = all areas) and who made it */
final class GssScope
{
    public function __construct(
        public readonly int $year,
        public readonly ?string $location = null,
        public readonly string $generatedBy = '',
    ) {}

    /** The areas in workbook order (Quezon City first, as GSS keeps its sheets), or only the admin's own */
    public function areas(): array
    {
        if ($this->location) {
            return [$this->location];
        }

        return array_values(array_unique([FleetLocations::QUEZON_CITY, ...FleetLocations::ALL]));
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::create($this->year, 1, 1)->startOfDay();
    }

    public function end(): CarbonImmutable
    {
        return CarbonImmutable::create($this->year, 12, 31)->endOfDay();
    }

    public function label(): string
    {
        return $this->location ?: 'All areas';
    }
}
