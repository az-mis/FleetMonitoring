<?php

declare(strict_types=1);

namespace Modules\Authentication\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Modules\Authentication\Actions\CreateNewUser;
use Modules\Authentication\Actions\ResetUserPassword;
use Modules\Authentication\Actions\UpdateUserPassword;
use Modules\Authentication\Actions\UpdateUserProfileInformation;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn() => view('authentication::login'));
        Fortify::registerView(fn() => view('authentication::register'));
        Fortify::requestPasswordResetLinkView(fn() => view('authentication::forgot-password'));
        Fortify::resetPasswordView(fn($request) => view('authentication::reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(fn() => view('authentication::confirm-password'));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())) . '|' . $request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}