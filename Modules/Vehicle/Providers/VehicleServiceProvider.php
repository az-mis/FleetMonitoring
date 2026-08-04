<?php

declare(strict_types=1);

namespace Modules\Vehicle\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Vehicle\Repositories\VehicleRepository;
use Modules\Vehicle\Repositories\VehicleRepositoryInterface;

class VehicleServiceProvider extends ServiceProvider
{
    protected string $moduleRoot = __DIR__ . '/../';

    public function register(): void
    {
        $this->app->bind(VehicleRepositoryInterface::class, VehicleRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom($this->moduleRoot . 'Routes/web.php');
        $this->loadMigrationsFrom($this->moduleRoot . 'Migrations');
        $this->loadViewsFrom($this->moduleRoot . 'Views', 'vehicle');
    }
}
