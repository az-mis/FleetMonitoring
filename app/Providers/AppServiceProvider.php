<?php

namespace App\Providers;

use App\Support\FleetSettings;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // System Settings values, read once per request
        $this->app->scoped(FleetSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Every password set in the app (Admins, Drivers, My Profile, reset, fleet:create-super-admin):
        // at least 12 characters with letters and numbers
        Password::defaults(fn () => Password::min(12)->letters()->numbers());

        // Behind a proxy or load balancer that ends TLS (TRUSTED_PROXIES in .env): trust its forwarded https,
        // host and client address, so links stay https and rate limits count real visitors
        if (filled($proxies = config('app.trusted_proxies'))) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', (string) $proxies)));
        }
        // A production site on https always builds https links
        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceHttps();
        }

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
                $appLogo = app(FleetSettings::class)->get('app_logo');
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
