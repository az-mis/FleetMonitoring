<?php

declare(strict_types=1);

namespace Modules\Vehicle\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Vehicle\Models\Vehicle;

interface VehicleRepositoryInterface
{
    public function paginate(int $perPage = 10, ?string $search = null, ?string $location = null): LengthAwarePaginator;

    public function create(array $data): Vehicle;

    public function update(Vehicle $vehicle, array $data): Vehicle;

    public function delete(Vehicle $vehicle): bool;
}
