<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\FleetLocations;
use Carbon\CarbonInterface;
use Modules\VehicleRequest\Models\VehicleRequest;

/**
 * Issues trip ticket numbers in each area's own paper format (see FleetLocations::ticketFormat):
 * Quezon City 001-26, Palawan 26-10-001, the other areas 0001-10-2026.
 *
 * The running number restarts every year and continues from the highest number already stored for that
 * area and year, so declined trips and hand-edited numbers never cause a repeat. The month is only a label.
 * Older numbers in another format (e.g. FMS-ROM-2026-10-0001) are left as they are and do not count.
 * Must run inside a transaction; the caller retries on a duplicate-number race.
 */
class TripTicketNumberer
{
    public function next(string $location, ?CarbonInterface $at = null): string
    {
        $at ??= now();
        $format = FleetLocations::ticketFormat($location);

        $highest = VehicleRequest::query()
            ->where('location', $location)
            ->where('trip_ticket_number', 'like', $this->likePattern($format, $at))
            ->lockForUpdate()
            ->pluck('trip_ticket_number')
            ->map(fn (string $number) => preg_match($this->regex($format, $at), $number, $m) ? (int) $m['seq'] : 0)
            ->max() ?? 0;

        return $this->format($format, $at, $highest + 1);
    }

    public function format(string $format, CarbonInterface $at, int $sequence): string
    {
        return strtr($format, [
            '{seq3}' => str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
            '{seq4}' => str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            '{yyyy}' => $at->format('Y'),
            '{yy}' => $at->format('y'),
            '{mm}' => $at->format('m'),
        ]);
    }

    /** SQL LIKE pattern for every number of this year in the format, e.g. "%-__-2026" */
    private function likePattern(string $format, CarbonInterface $at): string
    {
        return strtr($format, ['{seq3}' => '%', '{seq4}' => '%', '{yyyy}' => $at->format('Y'), '{yy}' => $at->format('y'), '{mm}' => '__']);
    }

    /** Exact pattern for this year's numbers, capturing the running number as "seq" */
    private function regex(string $format, CarbonInterface $at): string
    {
        $pattern = preg_quote($format, '/');
        $pattern = strtr($pattern, [
            preg_quote('{seq3}', '/') => '(?<seq>\d{3,})',
            preg_quote('{seq4}', '/') => '(?<seq>\d{4,})',
            preg_quote('{yyyy}', '/') => $at->format('Y'),
            preg_quote('{yy}', '/') => $at->format('y'),
            preg_quote('{mm}', '/') => '\d{2}',
        ]);

        return '/^'.$pattern.'$/';
    }
}
