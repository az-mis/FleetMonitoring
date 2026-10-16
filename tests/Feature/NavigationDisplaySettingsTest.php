<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class NavigationDisplaySettingsTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_admin_sidebar_lists_grouped_pages_without_a_logout_button(): void
    {
        $html = $this->actingAs($this->admin())->get('/my-profile')->assertOk()->getContent();
        $sidebar = Str::between($html, 'id="sidebar"', '</aside>');

        foreach (['Operations', 'Fleet', 'Administration', 'Travel Requests', 'Vehicle Assigning', 'Vehicles', 'Vehicle Inspections', 'Fleet Cards', 'Reports', 'Approving Officers'] as $label) {
            $this->assertStringContainsString($label, $sidebar);
        }
        $this->assertStringNotContainsString('openLogoutModal', $sidebar, 'Log out belongs to the account menu only');
        $this->assertStringNotContainsString(route('my-profile'), $sidebar, 'The signed-in user card is display only');
        $this->assertStringNotContainsString('Admins', $sidebar, 'Only Super Admins manage admin accounts');
        $this->assertStringContainsString('openLogoutModal', $html);
    }

    public function test_driver_sidebar_shows_only_their_own_pages(): void
    {
        $html = $this->actingAs($this->driver())->get('/my-profile')->assertOk()->getContent();
        $sidebar = Str::between($html, 'id="sidebar"', '</aside>');

        $this->assertStringContainsString('Travel History', $sidebar);
        $this->assertStringContainsString('Report Vehicle Issue', $sidebar);
        $this->assertStringNotContainsString('Fleet Cards', $sidebar);
        $this->assertStringNotContainsString('Travel Requests', $sidebar);
    }

    public function test_admins_choose_text_size_in_system_settings_and_drivers_on_their_own_page(): void
    {
        foreach ([$this->admin(), $this->superAdmin()] as $user) {
            $menu = $this->accountMenu($this->actingAs($user)->get('/my-profile')->assertOk()->getContent());
            $this->assertStringContainsString(route('content-settings'), $menu);
            $this->assertStringNotContainsString('href="'.route('settings').'"', $menu, 'Text size now lives inside System Settings');

            $this->actingAs($user)->get('/content-settings')->assertOk()
                ->assertSee('Display &amp; Text Size', false)
                ->assertSeeInOrder(['Standard', 'Large', 'Larger', 'Largest']);
        }

        $driver = $this->driver();
        $menu = $this->accountMenu($this->actingAs($driver)->get('/my-profile')->assertOk()->getContent());
        $this->assertStringContainsString('href="'.route('settings').'"', $menu);
        $this->assertStringNotContainsString(route('content-settings'), $menu);
        $this->actingAs($driver)->get('/settings')->assertOk()
            ->assertSeeInOrder(['Standard', 'Large', 'Larger', 'Largest'])
            ->assertSee("localStorage.getItem('fleet_text_size')", false);
    }

    public function test_account_menu_shows_the_users_email_instead_of_the_role(): void
    {
        $admin = $this->admin();
        $menu = $this->accountMenu($this->actingAs($admin)->get('/my-profile')->getContent());

        $this->assertStringContainsString(e($admin->email), $menu);
        $this->assertStringContainsString(e($admin->name), $menu);
    }

    public function test_sign_in_page_applies_the_saved_text_size(): void
    {
        $this->get('/login')->assertOk()->assertSee("localStorage.getItem('fleet_text_size')", false);
    }

    /** The account dropdown (top right): from its Alpine root to the Log out button. */
    private function accountMenu(string $html): string
    {
        return Str::before(Str::after($html, 'x-data="{ open: false }"'), 'Log out');
    }
}
