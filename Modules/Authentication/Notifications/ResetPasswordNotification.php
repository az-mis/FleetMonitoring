<?php

declare(strict_types=1);

namespace Modules\Authentication\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The "Forgot password?" email, worded for DA-MIMAROPA staff. The link opens password.reset with the token and
 * email on APP_URL, and expires with the password broker.
 */
class ResetPasswordNotification extends ResetPassword
{
    public const SUBJECT = 'Reset your Fleet Management System password';

    public function toMail($notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject(self::SUBJECT)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We received a request to reset the password of your Fleet Management System account ('.$notifiable->getEmailForPasswordReset().').')
            ->action('Set a new password', $this->resetUrl($notifiable))
            ->line("This link expires in {$minutes} minutes and works only once.")
            ->line("If you didn't ask for this, you can ignore this email; your password stays the same.")
            ->salutation('DA-MIMAROPA General Services Section');
    }

    /**
     * Always on APP_URL, never the host of the request that asked for it: a forged Host header must not be able
     * to send a real reset token to someone else's site.
     */
    protected function resetUrl($notifiable): string
    {
        return rtrim((string) config('app.url'), '/').route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);
    }
}
