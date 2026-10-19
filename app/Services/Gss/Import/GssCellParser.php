<?php

declare(strict_types=1);

namespace App\Services\Gss\Import;

use App\Support\FleetLocations;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Reads the hand-typed cells of the GSS workbook: Excel serial dates and dates typed as text ("January 5 - 9, 2026",
 * "24-02-2026", "Sept. 6, 2026"), peso and kilometre amounts, double plates and area names.
 */
final class GssCellParser
{
    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    public static function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $value));

        return $text === '' || $text === '-' || $text === '—' ? null : $text;
    }

    /** 1,234.50 / ₱ 15,000 / 96KMS / 14,228 kms → float; anything else → null */
    public static function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $text = self::text($value);
        if ($text === null) {
            return null;
        }
        $clean = preg_replace('/(₱|php|pesos?|kms?\.?|ltrs?|liters?|,|\s)/i', '', $text);

        return is_numeric($clean) ? (float) $clean : null;
    }

    /**
     * A date or date range.
     *
     * @return array{start: CarbonImmutable, end: CarbonImmutable, guessed: bool}|null
     */
    public static function date(mixed $value, ?int $year = null): ?array
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)))) {
            $serial = (float) $value;
            // A bare year such as 2017 (acquisition year): January 1, flagged
            if ($serial >= 1900 && $serial <= 2100 && floor($serial) === $serial) {
                $date = CarbonImmutable::create((int) $serial, 1, 1);

                return ['start' => $date, 'end' => $date, 'guessed' => true];
            }
            if ($serial < 20000 || $serial > 80000) {
                return null;
            }
            $date = CarbonImmutable::parse(ExcelDate::excelToDateTimeObject($serial)->format('Y-m-d'));

            return ['start' => $date, 'end' => $date, 'guessed' => false];
        }

        $text = self::text($value);
        if ($text === null) {
            return null;
        }
        $t = strtolower(str_replace(['–', '—', '.'], ['-', '-', ' '], $text));
        $t = trim((string) preg_replace('/\s+/', ' ', $t));
        $t = (string) preg_replace('/\s*,\s*/', ', ', $t);
        $month = '([a-z]{3,9})';

        // January 5 - 9, 2026 / March 2- 6, 2026 / June 29- July 1, 2026 / January 5 to 9, 2026
        if (preg_match("/^{$month} (\d{1,2}) ?(?:-|to) ?(?:{$month} )?(\d{1,2}),? (\d{4})$/", $t, $m)) {
            $m1 = self::month($m[1]);
            $m2 = $m[3] !== '' ? self::month($m[3]) : $m1;
            if ($m1 && $m2 && checkdate($m1, (int) $m[2], (int) $m[5]) && checkdate($m2, (int) $m[4], (int) $m[5])) {
                return self::range(CarbonImmutable::create((int) $m[5], $m1, (int) $m[2]), CarbonImmutable::create((int) $m[5], $m2, (int) $m[4]));
            }
        }
        // September 14, 2026 / Sept 6 2026
        if (preg_match("/^{$month} (\d{1,2}),? (\d{4})$/", $t, $m) && ($mo = self::month($m[1])) && checkdate($mo, (int) $m[2], (int) $m[3])) {
            $d = CarbonImmutable::create((int) $m[3], $mo, (int) $m[2]);

            return ['start' => $d, 'end' => $d, 'guessed' => false];
        }
        // 24-02-2026 / 23/10/2018 / 6 3 26: day first, as GSS types them; flagged when day and month could swap
        if (preg_match('/^(\d{1,2})[-\/ ](\d{1,2})[-\/ ](\d{2}|\d{4})$/', $t, $m)) {
            [$a, $b, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            $y = $y < 100 ? 2000 + $y : $y;
            [$day, $mo] = $b > 12 ? [$b, $a] : [$a, $b];
            if (checkdate($mo, $day, $y)) {
                $d = CarbonImmutable::create($y, $mo, $day);

                return ['start' => $d, 'end' => $d, 'guessed' => $a <= 12 && $b <= 12 && $a !== $b];
            }
        }
        // August 2026: the first of the month, flagged
        if (preg_match("/^{$month},? (\d{4})$/", $t, $m) && ($mo = self::month($m[1]))) {
            $d = CarbonImmutable::create((int) $m[2], $mo, 1);

            return ['start' => $d, 'end' => $d, 'guessed' => true];
        }
        // 2017: a year only, flagged
        if (preg_match('/^(19|20)\d{2}$/', $t)) {
            $d = CarbonImmutable::create((int) $t, 1, 1);

            return ['start' => $d, 'end' => $d, 'guessed' => true];
        }
        // March 16: the workbook's year, flagged
        if ($year && preg_match("/^{$month} (\d{1,2})$/", $t, $m) && ($mo = self::month($m[1])) && checkdate($mo, (int) $m[2], $year)) {
            $d = CarbonImmutable::create($year, $mo, (int) $m[2]);

            return ['start' => $d, 'end' => $d, 'guessed' => true];
        }

        return null;
    }

    /** Month number from a month word ("Sept", "NOVEMBER", "Novemeber") */
    public static function month(string $word): ?int
    {
        return self::MONTHS[substr(strtolower(trim($word)), 0, 3)] ?? null;
    }

    public static function isMonthHeading(?string $text): bool
    {
        return $text !== null && preg_match('/^[a-z]+$/i', $text) === 1 && strlen($text) >= 3 && self::month($text) !== null
            && in_array(strtolower($text), ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december', 'sept'], true);
    }

    /** SNE-6224 / sne 6224 → SNE6224 */
    public static function plateKey(string $plate): string
    {
        return strtoupper((string) preg_replace('/[^a-z0-9]/i', '', $plate));
    }

    /** SNE6224 → "SNE 6224", A8X654 → "A8X 654" (the way the system writes plates) */
    public static function formatPlate(string $plate): string
    {
        $key = self::plateKey($plate);

        return preg_match('/^([A-Z][A-Z0-9]{2})(\d{3,4})$/', $key, $m) ? "{$m[1]} {$m[2]}" : strtoupper(trim($plate));
    }

    /**
     * The registered plate and the old/conduction one from cells like "A8X654 ( SNE 6227 )", "SNE 8486 (OY5094)",
     * "AIS 661/SDA 1850". Government plates are the S-series (SNE, SND, SKP…).
     *
     * @return array{plate: ?string, old: ?string, ambiguous: bool}
     */
    public static function plates(mixed $value): array
    {
        $text = self::text($value);
        if ($text === null || preg_match('/no plate|^n\/?a$/i', $text)) {
            return ['plate' => null, 'old' => null, 'ambiguous' => false];
        }
        $parts = array_values(array_filter(array_map(
            // "Conduction sticker# KOY 803", "CS# U3-Z1Z9", "Plate No: SJX-209" → the number only
            fn ($p) => trim((string) preg_replace('/^\s*(conduction\s*sticker|cs|plate)\s*(no\.?|number)?\s*[#:.]*\s*/i', '', $p)),
            preg_split('/[\/()]/', $text) ?: [],
        ), fn ($p) => preg_match('/[0-9]/', $p) && strlen(self::plateKey($p)) >= 4 && strlen(self::plateKey($p)) <= 9));
        if ($parts === []) {
            return ['plate' => null, 'old' => null, 'ambiguous' => false];
        }
        if (count($parts) === 1) {
            return ['plate' => self::formatPlate($parts[0]), 'old' => null, 'ambiguous' => false];
        }

        $government = array_values(array_filter($parts, fn ($p) => preg_match('/^S[A-Z]{2}\d{3,4}$/', self::plateKey($p))));
        $plate = $government[0] ?? $parts[0];
        $old = collect($parts)->first(fn ($p) => $p !== $plate);

        return ['plate' => self::formatPlate($plate), 'old' => $old ? self::formatPlate($old) : null, 'ambiguous' => count($government) !== 1];
    }

    /** The system location for an area name as typed in the workbook ("Q.C. Satellite Office", "ORMIN", "OCCI. MIN.") */
    public static function location(mixed $value): ?string
    {
        $text = self::text($value);
        if ($text === null) {
            return null;
        }
        if ($known = FleetLocations::fromLabel($text)) {
            return $known;
        }
        $t = ' '.strtoupper((string) preg_replace('/[^a-z ]/i', ' ', $text)).' ';
        $t = (string) preg_replace('/\s+/', ' ', $t);

        return match (true) {
            str_contains($t, 'OCCI') || str_contains($t, ' OCC ') || str_contains($t, 'SAN JOSE') => FleetLocations::OCCIDENTAL_MINDORO,
            str_contains($t, 'ORIENTAL') || str_contains($t, 'ORMIN') || str_contains($t, ' OR MIN') || str_contains($t, 'CALAPAN') || str_contains($t, 'ORMAES') => FleetLocations::ORIENTAL_MINDORO,
            // Palawan stations: PRES, its centres (LRC, DPDC, PAC, Abo-Abo) and Brooke's Point
            str_contains($t, 'PALAWAN') || str_contains($t, 'PUERTO PRINCESA') || preg_match('/ (DA )?PRES | DPDC | LRC | PAC | SADDL |BROOKE|ABO ABO/', $t) => FleetLocations::PALAWAN,
            str_contains($t, 'MARINDUQUE') || str_contains($t, 'BOAC') => FleetLocations::MARINDUQUE,
            str_contains($t, 'ROMBLON') || str_contains($t, 'ODIONGAN') => FleetLocations::ROMBLON,
            str_contains($t, 'QUEZON') || str_contains($t, ' Q C ') || str_contains($t, ' QC ') || str_contains($t, 'SATELLITE') => FleetLocations::QUEZON_CITY,
            default => null,
        };
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable, guessed: bool} */
    private static function range(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return $end->lt($start)
            ? ['start' => $start, 'end' => $start, 'guessed' => true]
            : ['start' => $start, 'end' => $end, 'guessed' => false];
    }
}
