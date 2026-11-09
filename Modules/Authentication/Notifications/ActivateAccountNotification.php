<?php

declare(strict_types=1);

namespace Modules\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Authentication\Support\AccountActivation;

/**
 * Sent when an admin gives a driver a new email address (or sends the link again from Edit Driver): the driver
 * opens it to create their own password and sign in.
 */
class ActivateAccountNotification extends Notification
{
    use Queueable;

    public const SUBJECT = 'Activate your Fleet Management System account';

    public function __construct(public readonly string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = AccountActivation::EXPIRES_DAYS;

        return (new MailMessage)
            ->subject(self::SUBJECT)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your fleet administrator has linked this email address ('.$notifiable->email.') to your driver account in the Fleet Management System of DA-MIMAROPA.')
            ->line('Click the button below to activate your account and create your own password.')
            ->action('Activate my account', $this->url)
            ->line("This link works for {$days} days and only once. If it expires, ask your fleet administrator to send a new one.")
            ->line("If you don't drive for DA-MIMAROPA, you can ignore this email.")
            ->salutation('DA-MIMAROPA General Services Section');
    }
}
