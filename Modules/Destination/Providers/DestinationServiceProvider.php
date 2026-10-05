<?php

namespace Modules\Destination\Providers;

use Illuminate\Support\ServiceProvider;

class DestinationServiceProvider extends ServiceProvider
{
    protected string $moduleRoot = __DIR__ . '/../';

    public function boot(): void
    {
        $this->loadRoutesFrom($this->moduleRoot . 'Routes/web.php');
        $this->loadViewsFrom($this->moduleRoot . 'Views', 'destination');
    }
}
