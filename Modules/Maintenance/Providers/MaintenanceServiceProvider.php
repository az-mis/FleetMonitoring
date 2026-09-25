<?php

declare(strict_types=1);

namespace Modules\Maintenance\Providers;

use Illuminate\Support\ServiceProvider;

class MaintenanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'maintenance');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
