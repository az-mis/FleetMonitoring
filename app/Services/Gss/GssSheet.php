<?php

declare(strict_types=1);

namespace App\Services\Gss;

use Carbon\CarbonInterface;
use Modules\Vehicle\Models\Vehicle;

/**
 * One sheet of the GSS workbook. The headers are the workbook's own, in the same order, so GSS can paste
 * the export straight into its monitoring files.
 *
 * rows() yields items the writer understands:
 *   ['cells' => [...]]                one data row (Carbon values become Excel dates)
 *   ['group' => 'QUEZON CITY']        an area or section heading across the table
 *   ['cells' => [...], 'bold' => true] a total or summary row
 */
abstract class GssSheet
{
    /** Short key used by the export form, e.g. "trip-tickets" */
    abstract public function key(): string;

    /** Tab name, as in the workbook (31 characters at most) */
    abstract public function title(): string;

    /** Title row text above the table */
    abstract public function heading(GssScope $scope): string;

    /** @return list<list<string>> one or two header rows */
    abstract public function headers(GssScope $scope): array;

    /** @return iterable<int, array<string, mixed>> */
    abstract public function rows(GssScope $scope): iterable;

    /** @return list<int> zero-based columns holding peso amounts */
    public function moneyColumns(): array
    {
        return [];
    }

    /** One line "TYPE - BRAND MODEL YEAR" as the workbook describes a vehicle */
    protected function vehicleDescription(?Vehicle $vehicle, bool $withYear = true): string
    {
        if (! $vehicle) {
            return '';
        }
        $name = trim(($vehicle->brand ?? '').' '.($vehicle->model ?? '').($withYear && $vehicle->year ? ' '.$vehicle->year : ''));

        return strtoupper(trim(($vehicle->vehicle_type ? $vehicle->vehicle_type.' - ' : '').$name, ' -'));
    }

    /** "Mar 16 - 17, 2026" for a range, or the single date */
    protected function dateRange(?CarbonInterface $start, ?CarbonInterface $end): CarbonInterface|string|null
    {
        if (! $start || ! $end || $start->isSameDay($end)) {
            return $start;
        }

        return $start->isSameMonth($end)
            ? $start->format('M j').' - '.$end->format('j, Y')
            : $start->format('M j').' - '.$end->format('M j, Y');
    }

    protected function destination(?string $destination): string
    {
        return str_contains((string) $destination, ';;')
            ? implode(', ', array_filter(array_map('trim', explode(';;', (string) $destination))))
            : (string) $destination;
    }
}
