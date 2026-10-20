<?php

namespace Tests\Feature;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** php artisan fleet:mail-test: checks a server's mail settings before "Forgot password?" relies on them */
class SendTestMailCommandTest extends TestCase
{
    public function test_it_sends_one_test_email_to_the_given_address(): void
    {
        config(['mail.default' => 'array']);
        Event::fake([MessageSent::class]);

        $this->artisan('fleet:mail-test', ['email' => 'gss@da.gov.ph'])
            ->expectsOutputToContain('Test email sent to gss@da.gov.ph')
            ->assertSuccessful();

        Event::assertDispatchedTimes(MessageSent::class, 1);
        Event::assertDispatched(MessageSent::class, fn (MessageSent $event) => $event->message->getTo()[0]->getAddress() === 'gss@da.gov.ph');
    }

    public function test_it_refuses_something_that_is_not_an_email(): void
    {
        $this->artisan('fleet:mail-test', ['email' => 'not-an-email'])
            ->expectsOutputToContain('is not an email address')
            ->assertFailed();
    }

    public function test_it_reports_a_mail_server_it_cannot_reach(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 2,
        ]);

        $this->artisan('fleet:mail-test', ['email' => 'gss@da.gov.ph'])
            ->expectsOutputToContain('The email could not be sent')
            ->assertFailed();
    }
}
