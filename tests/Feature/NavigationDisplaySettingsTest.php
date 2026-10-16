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

    public function test_every_role_can_choose_a_text_size(): void
    {
        foreach ([$this->driver(), $this->admin(), $this->superAdmin()] as $user) {
            $this->actingAs($user)->get('/my-profile')->assertOk()
                ->assertSee(route('settings'), false)
                ->assertSee('Display &amp; Text Size', false)
                ->assertSee("localStorage.getItem('fleet_text_size')", false);

            $this->actingAs($user)->get('/settings')->assertOk()
                ->assertSeeInOrder(['Standard', 'Large', 'Larger', 'Largest']);
        }
    }

    public function test_sign_in_page_applies_the_saved_text_size(): void
    {
        $this->get('/login')->assertOk()->assertSee("localStorage.getItem('fleet_text_size')", false);
    }
}
