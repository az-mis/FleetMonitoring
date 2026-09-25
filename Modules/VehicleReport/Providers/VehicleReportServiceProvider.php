<?php

declare(strict_types=1);

namespace Modules\VehicleReport\Providers;

use Illuminate\Support\ServiceProvider;

class VehicleReportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'vehiclereport');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
