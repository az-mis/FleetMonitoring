<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class ProfileFieldsByRoleTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_super_admin_can_edit_their_own_email_and_sees_no_managed_by_notes(): void
    {
        $this->actingAs($this->superAdmin())->get('/my-profile')->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('You sign in with this address.')
            ->assertSee('As Super Admin you see and manage every location.')
            ->assertDontSee('Managed by your fleet administrator')
            ->assertDontSee("Driver's License", false)
            ->assertDontSee('is managed by');
    }

    public function test_super_admin_email_change_is_saved_and_must_be_unique(): void
    {
        $super = $this->superAdmin();
        $taken = $this->admin();

        $form = ['name' => $super->name, 'phone_number' => '(048) 433-1234'];

        $this->actingAs($super)->from('/my-profile')->put('/my-profile', $form + ['email' => $taken->email])
            ->assertRedirect('/my-profile')->assertSessionHasErrors('email');

        $this->actingAs($super)->from('/my-profile')->put('/my-profile', $form + ['email' => 'chief@da.gov.ph'])
            ->assertRedirect('/my-profile')->assertSessionHas('success');
        $this->assertSame('chief@da.gov.ph', $super->fresh()->email);
    }

    public function test_admins_and_drivers_cannot_change_their_email_from_my_profile(): void
    {
        foreach ([$this->admin(), $this->driver()] as $user) {
            $email = $user->email;

            $this->actingAs($user)->put('/my-profile', [
                'name' => $user->name,
                'phone_number' => '09171234567',
                'email' => 'someone-else@example.com',
            ])->assertSessionHas('success');

            $this->assertSame($email, $user->fresh()->email);
        }
    }

    public function test_admins_see_the_super_admin_as_the_manager_and_no_license_panel(): void
    {
        $this->actingAs($this->admin())->get('/my-profile')->assertOk()
            ->assertDontSee('name="email"', false)
            ->assertSee('Your sign-in email is managed by the Super Admin.')
            ->assertSee('Your office assignment is managed by the Super Admin.')
            ->assertDontSee("Driver's License", false);
    }

    public function test_drivers_see_their_license_panel_and_their_fleet_administrator_as_the_manager(): void
    {
        $this->actingAs($this->driver())->get('/my-profile')->assertOk()
            ->assertDontSee('name="email"', false)
            ->assertSee('Your sign-in email is managed by your fleet administrator.')
            ->assertSee("Driver's License", false)
            ->assertSee('Managed by your fleet administrator');
    }
}
