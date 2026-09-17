<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Settings\Models\FleetSetting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            try {
                $appLogo = FleetSetting::where('setting_key', 'app_logo')->value('setting_value');
                $appLogoUrl = !empty($appLogo['path'])
                    ? Storage::disk('public')->url($appLogo['path'])
                    : null;
            } catch (\Throwable $e) {
                $appLogoUrl = null;
            }
            $view->with('appLogoUrl', $appLogoUrl);
        });
    }
}
