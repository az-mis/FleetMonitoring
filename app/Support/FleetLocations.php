<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The agency's six locations (stations), in one place. Each has the abbreviation used in references,
 * the area heading the GSS workbook uses, and the trip ticket number format that area keeps on paper.
 *
 * Ticket format tokens: {seq3} / {seq4} running number for the year, {yy} / {yyyy} year, {mm} month.
 */
final class FleetLocations
{
    public const ORIENTAL_MINDORO = 'Oriental Mindoro';

    public const OCCIDENTAL_MINDORO = 'Occidental Mindoro';

    public const MARINDUQUE = 'Marinduque';

    public const PALAWAN = 'Palawan';

    public const ROMBLON = 'Romblon';

    public const QUEZON_CITY = 'Quezon City Satellite Office';

    /** Display order used by every location filter and picker */
    public const ALL = [
        self::ORIENTAL_MINDORO,
        self::OCCIDENTAL_MINDORO,
        self::MARINDUQUE,
        self::PALAWAN,
        self::ROMBLON,
        self::QUEZON_CITY,
    ];

    private const DETAILS = [
        self::ORIENTAL_MINDORO => ['abbr' => 'OR-MIN', 'heading' => 'ORIENTAL MINDORO', 'ticket' => '{seq4}-{mm}-{yyyy}'],
        self::OCCIDENTAL_MINDORO => ['abbr' => 'OC-MIN', 'heading' => 'OCCIDENTAL MINDORO', 'ticket' => '{seq4}-{mm}-{yyyy}'],
        self::MARINDUQUE => ['abbr' => 'MAR', 'heading' => 'MARINDUQUE', 'ticket' => '{seq4}-{mm}-{yyyy}'],
        self::PALAWAN => ['abbr' => 'PAL', 'heading' => 'PALAWAN', 'ticket' => '{yy}-{mm}-{seq3}'],
        self::ROMBLON => ['abbr' => 'ROM', 'heading' => 'ROMBLON', 'ticket' => '{seq4}-{mm}-{yyyy}'],
        self::QUEZON_CITY => ['abbr' => 'QC', 'heading' => 'QUEZON CITY', 'ticket' => '{seq3}-{yy}'],
    ];

    /** Other spellings found in the GSS workbook, mapped to the location */
    private const ALIASES = [
        'QC' => self::QUEZON_CITY,
        'Q.C.' => self::QUEZON_CITY,
        'QUEZON CITY' => self::QUEZON_CITY,
        'QUEZON CITY SATELLITE OFFICE' => self::QUEZON_CITY,
        'SATELLITE QC' => self::QUEZON_CITY,
        'ORMIN' => self::ORIENTAL_MINDORO,
        'OR. MIN.' => self::ORIENTAL_MINDORO,
        'OCCIMIN' => self::OCCIDENTAL_MINDORO,
        'OCCI. MIN.' => self::OCCIDENTAL_MINDORO,
        'OCC. MIN.' => self::OCCIDENTAL_MINDORO,
    ];

    public static function isKnown(?string $location): bool
    {
        return $location !== null && isset(self::DETAILS[$location]);
    }

    public static function abbreviation(string $location): string
    {
        return self::DETAILS[$location]['abbr'] ?? strtoupper(substr((string) preg_replace('/[^a-z0-9]/i', '', $location), 0, 6));
    }

    /** The heading the GSS workbook uses for this area, e.g. "QUEZON CITY" */
    public static function areaHeading(string $location): string
    {
        return self::DETAILS[$location]['heading'] ?? strtoupper($location);
    }

    public static function ticketFormat(string $location): string
    {
        return self::DETAILS[$location]['ticket'] ?? '{seq4}-{mm}-{yyyy}';
    }

    /** Resolve a location from a workbook heading or alias ("QUEZON CITY", "OCCI. MIN."), or null */
    public static function fromLabel(?string $label): ?string
    {
        $key = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $label)));
        if ($key === '') {
            return null;
        }
        foreach (self::DETAILS as $location => $details) {
            if ($key === strtoupper($location) || $key === $details['heading']) {
                return $location;
            }
        }

        return self::ALIASES[$key] ?? null;
    }
}
