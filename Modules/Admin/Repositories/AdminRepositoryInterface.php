<?php

declare(strict_types=1);

namespace Modules\Admin\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminRepositoryInterface
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    public function create(array $data): User;

    public function update(User $admin, array $data): User;

    public function delete(User $admin): bool;
}