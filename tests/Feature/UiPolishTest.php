<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Vehicle\Models\VehicleType;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** Pre-deployment UI check: labels that read right, and trips a driver can read on a phone */
class UiPolishTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_vehicle_type_chips_read_naturally(): void
    {
        $this->assertSame('SUVs', (new VehicleType(['name' => 'SUV']))->chipLabel());
        $this->assertSame('Cars', (new VehicleType(['name' => 'Car']))->chipLabel());
        $this->assertSame('Pick-ups', (new VehicleType(['name' => 'Pick-up']))->chipLabel());
    }

    public function test_a_drivers_trips_show_as_cards_on_phones_and_a_table_on_larger_screens(): void
    {
        $driver = $this->driver('Palawan');
        $vehicle = $this->vehicle('Palawan', ['plate_number' => 'QAA 1001']);
        $this->tripRequest($vehicle, ['status' => 'approved', 'confirmed_driver_id' => $driver->id, 'trip_ticket_number' => '26-10-001', 'destination' => 'Narra']);

        $page = $this->actingAs($driver)->get('/travel-history')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="space-y-2 md:hidden"/', $page);
        $this->assertMatchesRegularExpression('/class="hidden overflow-x-auto[^"]*md:block"/', $page);
        // The card list comes before the table and already shows the trip
        $cards = substr($page, strpos($page, 'class="space-y-2 md:hidden"'));
        $this->assertStringContainsString('Narra', substr($cards, 0, strpos($cards, 'md:block')));
    }

    public function test_page_wording(): void
    {
        $this->actingAs($this->superAdmin())->get('/vehicle-requests')->assertOk()
            ->assertSee('submitted through the request form')->assertDontSee('QR code');
        $this->actingAs($this->superAdmin())->get('/vehicle-reports')->assertOk()->assertDontSee('report(s)');
    }

    public function test_public_pages_show_the_office_footer(): void
    {
        $footer = '© '.date('Y').' DA MIMAROPA · Powered by MIS · Developed by Alvin Zoleta';

        foreach (['/', '/request-vehicle', '/check-status', '/login', '/forgot-password'] as $page) {
            $this->get($page)->assertOk()->assertSee($footer, false);
        }
    }
}
