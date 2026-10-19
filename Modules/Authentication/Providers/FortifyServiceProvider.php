<?php

declare(strict_types=1);

namespace Modules\Authentication\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Modules\Authentication\Actions\CreateNewUser;
use Modules\Authentication\Actions\ResetUserPassword;
use Modules\Authentication\Actions\UpdateUserPassword;
use Modules\Authentication\Actions\UpdateUserProfileInformation;
use Modules\Authentication\Responses\PasswordResetLinkRequestResponse;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Same response for known and unknown emails (prevents account enumeration)
        $this->app->bind(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkRequestResponse::class);
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkRequestResponse::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('authentication::login'));
        Fortify::requestPasswordResetLinkView(fn () => view('authentication::forgot-password'));
        Fortify::resetPasswordView(fn ($request) => view('authentication::reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(fn () => view('authentication::confirm-password'));

        // Deactivated accounts cannot sign in (decision D10); the message is shown only after a correct password
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', $request->input(Fortify::username()))->first();
            if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }
            if (! $user->isActive()) {
                throw ValidationException::withMessages([
                    Fortify::username() => User::DEACTIVATED_MESSAGE,
                ]);
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
