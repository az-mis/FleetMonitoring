<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Admin\Repositories\AdminRepositoryInterface;

class AdminService
{
    public function __construct(
        private readonly AdminRepositoryInterface $repository,
    ) {
    }

    public function list(?string $search = null, ?string $location = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate(perPage: $perPage, search: $search, location: $location);
    }

    public function createAdmin(array $data): User
    {
        return $this->repository->create($data);
    }

    public function updateAdmin(User $admin, array $data): User
    {
        return $this->repository->update($admin, $data);
    }

    public function deleteAdmin(User $admin): bool
    {
        return $this->repository->delete($admin);
    }
}
