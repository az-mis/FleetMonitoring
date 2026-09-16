<?php

declare(strict_types=1);

namespace Modules\Driver\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DriverRepositoryInterface
{
    public function paginate(int $perPage = 10, ?string $search = null, ?string $location = null): LengthAwarePaginator;

    public function create(array $data): User;

    public function update(User $driver, array $data): User;

    public function delete(User $driver): bool;
}
