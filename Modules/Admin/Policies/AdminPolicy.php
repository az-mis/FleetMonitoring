<?php

declare(strict_types=1);

namespace Modules\Admin\Policies;

use App\Models\User;
use Modules\Admin\Enums\UserRole;

class AdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function update(User $user, User $target): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}