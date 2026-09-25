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
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {
    }

    public function list(?string $search = null, ?string $location = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate(perPage: $perPage, search: $search, location: $location);
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
            ->when($location, fn ($q) => $q->where('location', $location))
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
