<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Repositories\VehicleRepositoryInterface;

class VehicleService
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
    ) {
    }

    public function createVehicle(array $data): Vehicle
    {
        return $this->repository->create($data);
    }
}
