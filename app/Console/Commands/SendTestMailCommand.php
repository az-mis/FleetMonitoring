<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Sends one test email with the server's mail settings (.env MAIL_*), so a new server can be checked before
 * anyone relies on "Forgot password?".
 */
class SendTestMailCommand extends Command
{
    protected $signature = 'fleet:mail-test {email : Address to send the test email to}';

    protected $description = 'Send a test email to check the server\'s mail settings';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->error("\"{$email}\" is not an email address.");

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $this->line("Mailer: {$mailer} · host: ".(config("mail.mailers.{$mailer}.host") ?? '—').' · port: '.(config("mail.mailers.{$mailer}.port") ?? '—')
            .' · from: '.(config('mail.from.address') ?? '—'));
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("MAIL_MAILER is \"{$mailer}\": the email is only written to the log, nobody receives it.");
        }

        try {
            Mail::raw(
                "This is a test email from the Fleet Management System.\n\nIf you can read this, the server can send email, and \"Forgot password?\" links will arrive.",
                fn (Message $message) => $message->to($email)->subject('Fleet Management System: test email'),
            );
        } catch (Throwable $e) {
            $this->error('The email could not be sent: '.$e->getMessage());
            $this->line('Check MAIL_HOST, MAIL_PORT, MAIL_SCHEME, MAIL_USERNAME and MAIL_PASSWORD in .env, then run php artisan config:clear.');

            return self::FAILURE;
        }

        $this->info("Test email sent to {$email}. Check that inbox (and its spam folder).");

        return self::SUCCESS;
    }
}
