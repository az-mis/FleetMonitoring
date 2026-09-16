<?php

declare(strict_types=1);

namespace Modules\ApprovingOfficer\Providers;

use Illuminate\Support\ServiceProvider;

class ApprovingOfficerServiceProvider extends ServiceProvider
{
    protected string $moduleRoot = __DIR__ . '/../';

    public function boot(): void
    {
        $this->loadRoutesFrom($this->moduleRoot . 'Routes/web.php');
        $this->loadMigrationsFrom($this->moduleRoot . 'Migrations');
        $this->loadViewsFrom($this->moduleRoot . 'Views', 'approving-officer');
    }
}
