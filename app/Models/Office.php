<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An office / division / program ("end user" in the GSS workbook), e.g. ORED, RAED, AMAD.
 */
class Office extends Model
{
    protected $fillable = ['code', 'name', 'location', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('name');
    }

    /**
     * Active offices as the [value, label] pairs the office pickers use.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return static::query()->active()->get(['code', 'name'])
            ->map(fn (self $office) => ['value' => $office->code, 'label' => $office->name])
            ->all();
    }

    /** The office whose name matches the free-text value a form submitted, if any */
    public static function idForName(?string $name): ?int
    {
        return filled($name) ? static::query()->where('name', trim($name))->value('id') : null;
    }
}
