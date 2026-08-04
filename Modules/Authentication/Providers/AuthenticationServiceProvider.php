<?php

declare(strict_types=1);

namespace Modules\Authentication\Providers;

use Illuminate\Support\ServiceProvider;

class AuthenticationServiceProvider extends ServiceProvider
{
    protected string $moduleRoot = __DIR__ . '/../';

    public function register(): void
    {
        $this->app->register(FortifyServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom($this->moduleRoot . 'Routes/web.php');
        $this->loadViewsFrom($this->moduleRoot . 'Views', 'authentication');
    }
}