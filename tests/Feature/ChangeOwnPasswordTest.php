<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Responses\PasswordUpdateResponse;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class ChangeOwnPasswordTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_profile_page_shows_the_change_password_form_for_every_role(): void
    {
        foreach ([$this->superAdmin(), $this->admin(), $this->driver()] as $user) {
            $this->actingAs($user)->get('/my-profile')->assertOk()
                ->assertSee('Change Password')
                ->assertSee(route('user-password.update'), false)
                ->assertSee('name="current_password"', false)
                ->assertSee('name="password_confirmation"', false);
        }
    }

    public function test_each_role_can_change_their_own_password(): void
    {
        foreach ([$this->superAdmin(), $this->admin(), $this->driver()] as $user) {
            $this->actingAs($user)->from('/my-profile')->put('/user/password', [
                'current_password' => 'password',
                'password' => 'NewSecret123',
                'password_confirmation' => 'NewSecret123',
            ])->assertRedirect('/my-profile')
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success', PasswordUpdateResponse::MESSAGE);

            $this->assertTrue(Hash::check('NewSecret123', $user->fresh()->password), $user->role->value);
        }
    }

    public function test_the_success_message_is_shown_on_the_profile_page(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver)->from('/my-profile')->followingRedirects()->put('/user/password', [
            'current_password' => 'password',
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertOk()->assertSee('Your password has been changed.');
    }

    public function test_wrong_current_password_is_rejected_and_shown_on_the_form(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->from('/my-profile')->put('/user/password', [
            'current_password' => 'not-my-password',
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertRedirect('/my-profile')
            ->assertSessionHasErrorsIn('updatePassword', ['current_password'])
            ->assertSessionMissing('success');

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));

        $this->from('/my-profile')->followingRedirects()->put('/user/password', [
            'current_password' => 'not-my-password',
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertOk()->assertSee('The provided password does not match your current password.');
    }

    public function test_mismatched_confirmation_is_rejected(): void
    {
        $driver = $this->driver();

        $this->actingAs($driver)->from('/my-profile')->put('/user/password', [
            'current_password' => 'password',
            'password' => 'NewSecret123',
            'password_confirmation' => 'Different123',
        ])->assertRedirect('/my-profile')
            ->assertSessionHasErrorsIn('updatePassword', ['password'])
            ->assertSessionMissing('success');

        $this->assertTrue(Hash::check('password', $driver->fresh()->password));

        $this->from('/my-profile')->followingRedirects()->put('/user/password', [
            'current_password' => 'password',
            'password' => 'NewSecret123',
            'password_confirmation' => 'Different123',
        ])->assertOk()->assertSee('confirmation does not match');
    }

    public function test_guests_cannot_change_a_password(): void
    {
        $this->put('/user/password', [
            'current_password' => 'password',
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertRedirect('/login');
    }
}
