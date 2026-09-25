<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VehicleType extends Model
{
    /** Icons an admin can pick from when adding a type (key => label). */
    public const ICONS = [
        'car' => 'Car',
        'truck' => 'Truck',
        'van' => 'Van',
        'motorcycle' => 'Motorcycle',
        'bus' => 'Bus',
        'bicycle' => 'Bicycle',
    ];

    protected $fillable = ['name', 'slug', 'icon'];

    /** @var Collection<int, VehicleType>|null */
    private static ?Collection $registry = null;

    protected static function booted(): void
    {
        static::saved(fn () => self::flushRegistry());
        static::deleted(fn () => self::flushRegistry());
    }

    /** Label used on the category chips on the Vehicles page ("Car" -> "Cars"). */
    public function chipLabel(): string
    {
        return Str::plural($this->name);
    }

    /**
     * All types, oldest first. Memoised for the current request so the
     * list/grid rows can resolve their type without one query per vehicle.
     *
     * @return Collection<int, VehicleType>
     */
    public static function registry(): Collection
    {
        return self::$registry ??= self::query()->orderBy('id')->get();
    }

    public static function flushRegistry(): void
    {
        self::$registry = null;
    }

    /**
     * Map the raw string stored on a vehicle to a VehicleType.
     *
     * Exact (case-insensitive) name match first. Vehicles saved before this
     * table existed may hold values like "Sedan", "SUV" or "Pickup Truck", so
     * those fall back to the same grouping the chips always used.
     */
    public static function resolve(?string $raw): ?self
    {
        $types = self::registry();
        $needle = mb_strtolower(trim((string) $raw));

        if ($needle !== '') {
            $exact = $types->first(fn (self $type) => mb_strtolower($type->name) === $needle);
            if ($exact) {
                return $exact;
            }
        }

        $slug = match (true) {
            str_contains($needle, 'truck') => 'truck',
            str_contains($needle, 'van') => 'van',
            str_contains($needle, 'moto') => 'motorcycle',
            default => 'car',
        };

        return $types->firstWhere('slug', $slug);
    }
}
