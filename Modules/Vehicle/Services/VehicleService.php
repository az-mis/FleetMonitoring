<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Repositories\VehicleRepositoryInterface;

class VehicleService
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {
    }

    public function list(?string $search = null): LengthAwarePaginator
    {
        return $this->repository->paginate(search: $search);
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
}
