<?php

declare(strict_types=1);

namespace Modules\Driver\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Admin\Enums\UserRole;
use Modules\Driver\Repositories\DriverRepository;
use Modules\Driver\Repositories\DriverRepositoryInterface;

class DriverServiceProvider extends ServiceProvider
{
    protected string $moduleRoot = __DIR__ . '/../';

    public function register(): void
    {
        $this->app->bind(DriverRepositoryInterface::class, DriverRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom($this->moduleRoot . 'Routes/web.php');
        $this->loadMigrationsFrom($this->moduleRoot . 'Migrations');
        $this->loadViewsFrom($this->moduleRoot . 'Views', 'driver');

        // {driver} route parameters only resolve to driver accounts, so the driver screens
        // cannot be used to edit, reset or delete admin and super-admin accounts.
        Route::bind('driver', fn (string $value): User => User::query()
            ->where('role', UserRole::DRIVER)
            ->findOrFail($value));

        Gate::define('manage-drivers', fn (User $user): bool => in_array(
            $user->role,
            [UserRole::SUPER_ADMIN, UserRole::ADMIN],
            true,
        ));
    }
}
