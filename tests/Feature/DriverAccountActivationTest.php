<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Authentication\Notifications\ActivateAccountNotification;
use Modules\Authentication\Support\AccountActivation;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class DriverAccountActivationTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    /** The Edit Driver form, as the modal sends it */
    private function editForm(User $driver, array $changes = []): array
    {
        return array_merge([
            'name' => $driver->name,
            'email' => $driver->email,
            'role' => 'driver',
            'phone_number' => $driver->phone_number,
            'license_number' => $driver->license_number,
            'license_expiration_date' => $driver->license_expiration_date->toDateString(),
            '_original_email' => $driver->email,
        ], $changes);
    }

    /** Path and query of the emailed link, for requests in tests */
    private function link(User $driver): string
    {
        return (string) str(AccountActivation::url($driver))->after(rtrim((string) config('app.url'), '/'));
    }

    public function test_changing_a_drivers_email_sends_an_activation_link_to_the_new_address(): void
    {
        Notification::fake();
        $driver = $this->driver('Palawan', ['email' => 'juan.placeholder@fleet.local']);

        $this->actingAs($this->admin('Palawan'))->from('/driver')
            ->put(route('driver.update', $driver), $this->editForm($driver, ['email' => 'juan.delacruz@gmail.com']))
            ->assertRedirect()
            ->assertSessionHas('success', "{$driver->name} updated. An activation link was sent to juan.delacruz@gmail.com.");

        $this->assertSame('juan.delacruz@gmail.com', $driver->fresh()->email);
        Notification::assertSentTo($driver->fresh(), ActivateAccountNotification::class, function ($n, $channels, $notifiable) {
            $mail = $n->toMail($notifiable);

            return $notifiable->routeNotificationFor('mail') === 'juan.delacruz@gmail.com'
                && $mail->subject === ActivateAccountNotification::SUBJECT
                && str_starts_with($n->url, rtrim((string) config('app.url'), '/').'/account/activate/');
        });
    }

    public function test_saving_without_changing_the_email_sends_nothing(): void
    {
        Notification::fake();
        $driver = $this->driver('Palawan', ['email' => 'juan@fleet.local']);

        $this->actingAs($this->admin('Palawan'))->from('/driver')
            ->put(route('driver.update', $driver), $this->editForm($driver, ['email' => 'JUAN@fleet.local', 'name' => 'Juan D. Cruz']))
            ->assertSessionHas('success', 'Juan D. Cruz updated.');

        Notification::assertNothingSent();
    }

    public function test_the_drivers_page_lets_admins_edit_the_email_and_send_the_link(): void
    {
        $this->driver('Palawan');

        $this->actingAs($this->admin('Palawan'))->get('/driver')->assertOk()
            ->assertSee('id="send-activation-form"', false)
            ->assertSee('Send activation link')
            ->assertSee('oninput="onEditDriverEmailInput()"', false)
            ->assertSee('name="_original_email"', false);
    }

    public function test_the_link_lets_the_driver_create_a_password_and_signs_them_in_once(): void
    {
        $driver = $this->driver('Palawan', ['email' => 'juan@gmail.com', 'remember_token' => 'old-token']);
        $link = $this->link($driver);

        $this->get($link)->assertOk()->assertSee('Create Your Password')->assertSee('juan@gmail.com')
            ->assertSee('data-toggle-password="password"', false)
            ->assertSee('data-toggle-password="password_confirmation"', false);

        $this->post($link, ['password' => 'MyOwnPass2026', 'password_confirmation' => 'MyOwnPass2026'])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($driver);
        $fresh = $driver->fresh();
        $this->assertTrue(Hash::check('MyOwnPass2026', $fresh->password));
        $this->assertNotSame('old-token', $fresh->remember_token);
        $this->assertNotNull($fresh->email_verified_at);

        // Used once: the same link no longer works
        auth()->logout();
        $this->get($link)->assertOk()->assertSee('This Link No Longer Works')->assertDontSee('Create Your Password');
        $this->post($link, ['password' => 'Another2026Pass', 'password_confirmation' => 'Another2026Pass'])->assertSee('This Link No Longer Works');
        $this->assertTrue(Hash::check('MyOwnPass2026', $driver->fresh()->password));
    }

    public function test_the_new_password_must_follow_the_password_rules(): void
    {
        $driver = $this->driver('Palawan');
        $link = $this->link($driver);

        $this->from($link)->post($link, ['password' => 'short', 'password_confirmation' => 'short'])
            ->assertRedirect($link)->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_the_link_expires_after_three_days(): void
    {
        $driver = $this->driver('Palawan');
        $link = $this->link($driver);

        $this->travel(AccountActivation::EXPIRES_DAYS)->days();
        $this->travel(5)->minutes();

        $this->get($link)->assertSee('This Link No Longer Works');
    }

    public function test_an_older_link_stops_working_once_the_email_changes_again(): void
    {
        $driver = $this->driver('Palawan', ['email' => 'typo@gmial.com']);
        $link = $this->link($driver);

        $driver->forceFill(['email' => 'right@gmail.com'])->save();

        $this->get($link)->assertSee('This Link No Longer Works');
        $this->get($this->link($driver->fresh()))->assertSee('Create Your Password');
    }

    public function test_a_tampered_or_borrowed_link_does_not_work(): void
    {
        $driver = $this->driver('Palawan');
        $other = $this->driver('Palawan');
        $link = $this->link($driver);

        $this->get(str_replace("/account/activate/{$driver->id}?", "/account/activate/{$other->id}?", $link))->assertSee('This Link No Longer Works');
        $this->get(preg_replace('/signature=[a-f0-9]+/', 'signature='.str_repeat('0', 64), $link))->assertSee('This Link No Longer Works');
    }

    public function test_a_deactivated_driver_cannot_activate(): void
    {
        $driver = $this->driver('Palawan', ['deactivated_at' => now()]);

        $this->get($this->link($driver))->assertSee('This Link No Longer Works');
    }

    public function test_admins_can_send_the_link_again_only_for_their_own_stations_drivers(): void
    {
        Notification::fake();
        $driver = $this->driver('Palawan', ['email' => 'juan@gmail.com']);

        $this->actingAs($this->admin('Romblon'))->post(route('driver.activation', $driver))->assertForbidden();
        Notification::assertNothingSent();

        $this->actingAs($this->admin('Palawan'))->from('/driver')->post(route('driver.activation', $driver))
            ->assertRedirect('/driver')
            ->assertSessionHas('success', 'An activation link was sent to juan@gmail.com.');
        Notification::assertSentTo($driver, ActivateAccountNotification::class);

        $this->actingAs($this->driver('Palawan'))->post(route('driver.activation', $driver))->assertForbidden();
    }

    public function test_the_email_change_is_saved_even_when_the_email_cannot_be_sent(): void
    {
        config(['mail.default' => 'not-configured']);
        $driver = $this->driver('Palawan', ['email' => 'juan@fleet.local']);

        $this->actingAs($this->admin('Palawan'))->from('/driver')
            ->put(route('driver.update', $driver), $this->editForm($driver, ['email' => 'juan@gmail.com']))
            ->assertSessionHas('error');

        $this->assertSame('juan@gmail.com', $driver->fresh()->email);
    }
}
