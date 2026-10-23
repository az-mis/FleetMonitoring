<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleType;
use Modules\Vehicle\Repositories\VehicleRepositoryInterface;

class VehicleService
{
    /** Vehicles list filters: query key => [value => label] (the Filters menu and its chips use the labels) */
    public const LIST_FILTERS = [
        'driver' => ['unassigned' => 'No driver assigned', 'assigned' => 'Has a driver'],
        'health' => ['good' => 'Good (80–100%)', 'fair' => 'Fair (50–79%)', 'poor' => 'Poor (below 50%)'],
        'oil' => ['overdue' => 'Change oil overdue', 'due_soon' => 'Change oil due soon', 'ok' => 'Oil OK'],
        'papers' => ['due' => 'Registration or insurance due'],
    ];

    /** Names of the filter groups, in menu order */
    public const LIST_FILTER_GROUPS = [
        'driver' => 'Assigned driver',
        'health' => 'Health score',
        'oil' => 'Change oil',
        'papers' => 'Papers',
    ];

    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {
    }

    /**
     * @param  array<string, string>  $filters  Validated list filters (see listFilters())
     */
    public function list(?string $search = null, ?string $location = null, int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate(perPage: $perPage, search: $search, location: $location, filters: $filters);
    }

    /**
     * The known list filters present in the input; unknown keys and values are dropped.
     *
     * @return array<string, string>
     */
    public function listFilters(array $input): array
    {
        $filters = [];
        foreach (self::LIST_FILTERS as $key => $options) {
            $value = $input[$key] ?? null;
            if (is_string($value) && array_key_exists($value, $options)) {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    public function createVehicle(array $data): Vehicle
    {
        return $this->repository->create($data);
    }

    public function updateVehicle(Vehicle $vehicle, array $data): Vehicle
    {
        return $this->repository->update($vehicle, $data);
    }

    public function deleteVehicle(Vehicle $vehicle): bool
    {
        return $this->repository->delete($vehicle);
    }

    /**
     * @return Collection<int, VehicleType>
     */
    public function vehicleTypes(): Collection
    {
        return VehicleType::registry();
    }

    /**
     * Vehicle counts per type slug (for the category chips), plus the overall total.
     *
     * @return array{counts: array<string, int>, total: int}
     */
    public function typeCounts(?string $location = null): array
    {
        $counts = VehicleType::registry()->mapWithKeys(fn (VehicleType $type) => [$type->slug => 0])->all();
        $total = 0;

        $types = Vehicle::query()
            ->atLocation($location)
            ->pluck('vehicle_type');

        foreach ($types as $rawType) {
            $total++;

            $slug = VehicleType::resolve($rawType)?->slug;
            if ($slug !== null) {
                $counts[$slug]++;
            }
        }

        return ['counts' => $counts, 'total' => $total];
    }

    public function createVehicleType(array $data): VehicleType
    {
        $base = Str::slug($data['name']) ?: 'type';
        $slug = $base;
        $i = 2;

        while (VehicleType::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return VehicleType::create([
            'name' => $data['name'],
            'slug' => $slug,
            'icon' => $data['icon'],
        ]);
    }
}
