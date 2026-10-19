<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Support\FleetLocations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\VehicleRequest\Models\VehicleRequest;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * GSS workbook plan, phase 1: one offices list and one locations list for the whole system.
 */
class MasterDataTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_offices_are_seeded_and_feed_both_office_pickers(): void
    {
        $this->assertSame(51, Office::count());
        $this->assertSame(['value' => 'ORED', 'label' => 'Office of the Regional Executive Director (ORED)'], Office::options()[0]);

        Office::where('code', 'COA')->update(['is_active' => false]);
        $this->get('/request-vehicle')->assertOk()
            ->assertSee('Regional Agricultural Engineering Division (RAED)')
            ->assertDontSee('Commission on Audit (COA)');
        $this->actingAs($this->admin('Palawan'))->get('/vehicle-requests')->assertOk()
            ->assertSee('Regional Agricultural Engineering Division (RAED)');
    }

    public function test_a_request_is_linked_to_the_office_it_names(): void
    {
        $vehicle = $this->vehicle('Palawan');

        $this->post('/request-vehicle', [
            'form_token' => (string) Str::uuid(),
            'requesterName' => 'Ana Reyes',
            'requesterOffice' => 'Regional Agricultural Engineering Division (RAED)',
            'location' => 'Palawan',
            'requesterContact' => '09171234567',
            'vehicleId' => $vehicle->id,
            'destination' => 'City Hall',
            'travelDate' => now()->addDays(2)->toDateString(),
            'travelDateEnd' => now()->addDays(2)->toDateString(),
            'purpose' => 'Field validation',
        ])->assertSessionHasNoErrors();

        $request = VehicleRequest::firstOrFail();
        $this->assertSame('Regional Agricultural Engineering Division (RAED)', $request->requester_office);
        $this->assertSame('RAED', $request->office?->code);
    }

    public function test_every_location_has_an_abbreviation_heading_and_ticket_format(): void
    {
        $this->assertCount(6, FleetLocations::ALL);
        foreach (FleetLocations::ALL as $location) {
            $this->assertTrue(FleetLocations::isKnown($location));
            $this->assertNotSame('', FleetLocations::abbreviation($location));
            $this->assertSame($location, FleetLocations::fromLabel(FleetLocations::areaHeading($location)));
            $this->assertStringContainsString('{seq', FleetLocations::ticketFormat($location));
        }
        $this->assertSame(FleetLocations::QUEZON_CITY, FleetLocations::fromLabel(' quezon  city '));
        $this->assertSame(FleetLocations::OCCIDENTAL_MINDORO, FleetLocations::fromLabel('OCCI. MIN.'));
        $this->assertNull(FleetLocations::fromLabel('JANUARY'));
    }

    public function test_location_filters_list_the_six_locations(): void
    {
        $page = $this->actingAs($this->superAdmin())->get('/vehicles')->assertOk();
        foreach (FleetLocations::ALL as $location) {
            $page->assertSee($location);
        }
    }
}
