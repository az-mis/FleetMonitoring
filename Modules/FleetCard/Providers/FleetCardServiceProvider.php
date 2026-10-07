<?php

namespace Modules\FleetCard\Providers;

use Illuminate\Support\ServiceProvider;

class FleetCardServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'fleetcard');
    }

    public function register(): void
    {
        //
    }
}
