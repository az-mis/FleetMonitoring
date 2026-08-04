<?php

declare(strict_types=1);

namespace Modules\Admin\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Admin\Policies\AdminPolicy;
use Modules\Admin\Repositories\AdminRepository;
use Modules\Admin\Repositories\AdminRepositoryInterface;

class AdminServiceProvider extends ServiceProvider
{
    protected string $moduleRoot = __DIR__ . '/../';

    public function register(): void
    {
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom($this->moduleRoot . 'Routes/web.php');
        $this->loadMigrationsFrom($this->moduleRoot . 'Migrations');
        $this->loadViewsFrom($this->moduleRoot . 'Views', 'admin');

        Gate::policy(User::class, AdminPolicy::class);
    }
}