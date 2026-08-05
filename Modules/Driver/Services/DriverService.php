<?php

declare(strict_types=1);

namespace Modules\Driver\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Driver\Repositories\DriverRepositoryInterface;

class DriverService
{
    public function __construct(
        private readonly DriverRepositoryInterface $repository,
    ) {
    }

    public function list(?string $search = null): LengthAwarePaginator
    {
        return $this->repository->paginate(search: $search);
    }

    public function createDriver(array $data): User
    {
        return $this->repository->create($data);
    }

    public function updateDriver(User $driver, array $data): User
    {
        return $this->repository->update($driver, $data);
    }

    public function deleteDriver(User $driver): bool
    {
        return $this->repository->delete($driver);
    }
}
