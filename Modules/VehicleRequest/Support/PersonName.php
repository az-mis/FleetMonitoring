<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Support;

/**
 * Normalises typed person names to Title Case so they print cleanly on Trip Tickets
 * ("juan DELA cruz" -> "Juan Dela Cruz"). Mirrored by toTitleCaseName() in
 * resources/views/public/request-vehicle.blade.php so the form shows the same result
 * before it is submitted.
 */
final class PersonName
{
    public static function titleCase(?string $name): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) $name));

        if ($name === '') {
            return '';
        }

        $name = mb_strtolower($name);

        // Capitalise the first letter of every word, including after "-", "/", "(" and "." (initials like J.R.).
        $name = (string) preg_replace_callback(
            '/(^|[\s\-\/(.])(\p{Ll})/u',
            fn (array $m): string => $m[1] . mb_strtoupper($m[2]),
            $name,
        );

        // O'Brien, D'Angelo
        $name = (string) preg_replace_callback(
            "/\b([OD])'(\p{Ll})/u",
            fn (array $m): string => $m[1] . "'" . mb_strtoupper($m[2]),
            $name,
        );

        // McDonald
        $name = (string) preg_replace_callback(
            '/\bMc(\p{Ll})/u',
            fn (array $m): string => 'Mc' . mb_strtoupper($m[1]),
            $name,
        );

        // Trailing generational suffixes: II, III, IV
        return (string) preg_replace_callback(
            '/\s(Ii|Iii|Iv)$/u',
            fn (array $m): string => ' ' . mb_strtoupper($m[1]),
            $name,
        );
    }

    /**
     * @param  array<int, mixed>  $names
     * @return array<int, string>
     */
    public static function titleCaseAll(array $names): array
    {
        return array_values(array_filter(
            array_map(fn ($name) => self::titleCase((string) $name), $names),
            fn (string $name) => $name !== '',
        ));
    }
}
