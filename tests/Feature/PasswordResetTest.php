<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Modules\Authentication\Notifications\ResetPasswordNotification;
use Modules\Authentication\Responses\PasswordResetLinkRequestResponse;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('password.request'), false)->assertSee('Forgot password?');
    }

    public function test_forgot_password_page_shows_the_form_when_mail_is_configured(): void
    {
        config(['mail.default' => 'smtp']);

        $this->get('/forgot-password')->assertOk()
            ->assertSee('Email Password Reset Link')
            ->assertDontSee('ask your Fleet Management administrator');
    }

    public function test_forgot_password_page_explains_when_mail_is_not_configured(): void
    {
        config(['mail.default' => 'log']);

        $this->get('/forgot-password')->assertOk()
            ->assertSee('ask your Fleet Management administrator')
            ->assertDontSee('Email Password Reset Link');
    }

    public function test_reset_link_is_emailed_and_response_does_not_reveal_unknown_accounts(): void
    {
        Notification::fake();
        $driver = $this->driver();

        $this->from('/forgot-password')->post('/forgot-password', ['email' => $driver->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', PasswordResetLinkRequestResponse::MESSAGE)
            ->assertSessionHasNoErrors();
        Notification::assertSentTo($driver, ResetPasswordNotification::class);

        // An unknown address gets exactly the same answer and no validation error
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'nobody@example.test'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', PasswordResetLinkRequestResponse::MESSAGE)
            ->assertSessionHasNoErrors();
    }

    public function test_the_reset_email_is_worded_for_the_agency_and_links_to_the_reset_page(): void
    {
        Notification::fake();
        config(['app.url' => 'https://fleet.da.gov.ph']);
        $driver = $this->driver('Palawan', ['name' => 'Ricky Espina']);

        // A forged Host header must not end up in the link
        $this->post('http://evil.example/forgot-password', ['email' => $driver->email]);

        Notification::assertSentTo($driver, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($driver) {
            $mail = $notification->toMail($driver);
            $text = implode(' ', array_merge($mail->introLines, $mail->outroLines));

            return $mail->subject === ResetPasswordNotification::SUBJECT
                && $mail->greeting === 'Hello Ricky Espina,'
                && $mail->actionText === 'Set a new password'
                && str_starts_with($mail->actionUrl, 'https://fleet.da.gov.ph/reset-password/'.$notification->token)
                && str_contains($mail->actionUrl, 'email='.urlencode($driver->email))
                && str_contains($text, 'expires in 60 minutes')
                && str_contains($text, 'your password stays the same');
        });
    }

    public function test_a_deactivated_account_gets_no_email_but_the_same_answer(): void
    {
        Notification::fake();
        $driver = $this->driver('Palawan', ['deactivated_at' => now()]);

        $this->from('/forgot-password')->post('/forgot-password', ['email' => $driver->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', PasswordResetLinkRequestResponse::MESSAGE)
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_user_can_choose_a_new_password_with_a_valid_token(): void
    {
        $admin = $this->admin();
        $token = Password::createToken($admin);

        $this->get("/reset-password/{$token}?email=".urlencode($admin->email))
            ->assertOk()
            ->assertSee($token, false);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertRedirect('/login')->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewSecurePass123!', $admin->fresh()->password));
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $admin = $this->admin();

        $this->from('/reset-password/bad-token')->post('/reset-password', [
            'token' => 'bad-token',
            'email' => $admin->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('NewSecurePass123!', $admin->fresh()->password));
    }

    public function test_confirm_password_page_renders_for_signed_in_users(): void
    {
        $this->actingAs($this->admin())->get('/user/confirm-password')->assertOk()->assertSee('Confirm Your Password');
    }
}
