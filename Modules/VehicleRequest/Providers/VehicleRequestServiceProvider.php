<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\VehicleRequest\Controllers\VehicleRequestController;

class VehicleRequestServiceProvider extends ServiceProvider
{
    protected string $moduleRoot = __DIR__ . '/../';

    public function boot(): void
    {
        $this->loadRoutesFrom($this->moduleRoot . 'Routes/web.php');
        $this->loadMigrationsFrom($this->moduleRoot . 'Migrations');
        $this->loadViewsFrom($this->moduleRoot . 'Views', 'vehicle-request');
    }
}
