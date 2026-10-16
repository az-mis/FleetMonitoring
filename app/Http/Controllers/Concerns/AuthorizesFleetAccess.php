<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Modules\Admin\Enums\UserRole;

/**
 * Shared server-side checks for admin-managed fleet records.
 *
 * Super admins may act on every location. Admins may only act on records of their own
 * location, and an admin without a location is denied instead of being treated as global.
 */
trait AuthorizesFleetAccess
{
    protected function authorizeFleetAdmin(?User $user = null): User
    {
        $user ??= auth()->user();

        abort_unless($user && in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true), 403);
        abort_if($user->role === UserRole::ADMIN && blank($user->location), 403, 'Your account has no assigned location.');

        return $user;
    }

    protected function authorizeFleetLocation(?string $location, ?User $user = null): User
    {
        $user = $this->authorizeFleetAdmin($user);

        if ($user->role === UserRole::ADMIN) {
            abort_unless($location !== null && $location === $user->location, 403);
        }

        return $user;
    }

    /**
     * The location an admin is restricted to, or null for a super admin (all locations).
     */
    protected function fleetLocationScope(?User $user = null): ?string
    {
        $user = $this->authorizeFleetAdmin($user);

        return $user->role === UserRole::ADMIN ? $user->location : null;
    }
}
