<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Unique check for official identifiers (plate numbers, licence numbers) that ignores spacing,
 * dashes and letter case, so "QAA 1002", "QAA1002" and "qaa-1002" count as the same value.
 */
class UniqueIdentifier implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column,
        private readonly string $label,
        private readonly ?int $ignoreId = null,
    ) {}

    public static function normalize(mixed $value): string
    {
        return is_scalar($value) ? strtoupper(str_replace([' ', '-'], '', (string) $value)) : '';
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = self::normalize($value);
        if ($normalized === '') {
            return;
        }

        $existing = DB::table($this->table)
            ->whereRaw("REPLACE(REPLACE(UPPER({$this->column}), ' ', ''), '-', '') = ?", [$normalized])
            ->when($this->ignoreId, fn ($query) => $query->where('id', '!=', $this->ignoreId))
            ->value($this->column);

        if ($existing !== null) {
            $fail("This {$this->label} is already registered as {$existing}.");
        }
    }
}
