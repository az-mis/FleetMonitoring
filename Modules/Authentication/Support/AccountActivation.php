<?php

declare(strict_types=1);

namespace Modules\Authentication\Support;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Modules\Authentication\Notifications\ActivateAccountNotification;

/**
 * Account activation links: an admin gives a driver a real email address, and the driver opens the emailed link to
 * create their own password. The link is signed, expires after EXPIRES_DAYS, and carries a fingerprint of the
 * account's email and password, so it stops working once used, or once the email changes again.
 */
class AccountActivation
{
    public const EXPIRES_DAYS = 3;

    public static function fingerprint(User $user): string
    {
        return substr(hash_hmac('sha256', $user->id.'|'.strtolower($user->email).'|'.$user->password, (string) config('app.key')), 0, 32);
    }

    /** Always on APP_URL, never the host of the request, like the password reset link */
    public static function url(User $user): string
    {
        return rtrim((string) config('app.url'), '/').URL::temporarySignedRoute(
            'account.activate',
            now()->addDays(self::EXPIRES_DAYS),
            ['user' => $user->id, 'v' => self::fingerprint($user)],
            absolute: false,
        );
    }

    public static function send(User $user): void
    {
        $user->notify(new ActivateAccountNotification(self::url($user)));
    }
}
