<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VehicleRequest\Requests\StoreVehicleRequest;
use Tests\TestCase;

class PublicPagesLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_launcher_offers_request_status_lookup_and_staff_login(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Smart fleet operations &amp; monitoring', false)
            ->assertSee('href="'.route('request-vehicle').'"', false)
            ->assertSee('action="'.route('check-status').'"', false)
            ->assertSee('name="code"', false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('Ask the fleet office at your station')
            // The launcher already lists these pages, so home has no top links repeating them
            ->assertDontSee('aria-label="Public pages"', false);
        $this->get('/check-status')->assertOk()->assertSee('aria-label="Public pages"', false);
    }

    public function test_booking_calendar_only_offers_dates_that_can_be_requested(): void
    {
        $this->travelTo('2026-10-27 09:00');

        // Past days and days past the request window are greyed out and can't be clicked
        $this->get('/request-vehicle')->assertOk()
            ->assertSee("const firstBookable = '2026-10-27';", false)
            ->assertSee("const lastBookable = '".now()->addDays(StoreVehicleRequest::MAX_DAYS_AHEAD)->toDateString()."';", false)
            ->assertSee("closed ? ' disabled' : ''", false)
            ->assertSee("if (!btn || btn.disabled) return;", false);
    }

    public function test_inner_pages_link_back_home_and_keep_one_page_heading(): void
    {
        foreach (['/request-vehicle', '/check-status', '/login', '/forgot-password'] as $page) {
            $html = $this->get($page)->assertOk()->assertSee('href="'.url('/').'"', false)->getContent();
            $this->assertSame(1, substr_count($html, '<h1'), $page);
        }
    }
}
