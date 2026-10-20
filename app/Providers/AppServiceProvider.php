<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder as QueryBuilder;
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
        // Plate searches ignore spaces and dashes: "SNE6227", "SNE 6227" and "sne-6227" all find SNE 6227
        QueryBuilder::macro('wherePlateLike', function (string $column, ?string $term, string $boolean = 'and') {
            /** @var QueryBuilder $this */
            $key = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $term));
            if ($key === '') {
                return $this->where($column, 'like', '%'.$term.'%', $boolean);
            }
            $wrapped = $this->getGrammar()->wrap($column);

            return $this->whereRaw("REPLACE(REPLACE(UPPER({$wrapped}), ' ', ''), '-', '') LIKE ?", ['%'.$key.'%'], $boolean);
        });
        QueryBuilder::macro('orWherePlateLike', function (string $column, ?string $term) {
            /** @var QueryBuilder $this */
            return $this->wherePlateLike($column, $term, 'or');
        });

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
