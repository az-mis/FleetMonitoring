<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehiclePmsItem;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class VehicleListFiltersTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    /** Plates on the list page (the notification bell can name other vehicles, so the page text is not enough) */
    private function listedPlates(string $url): array
    {
        return $this->get($url)->assertOk()->viewData('vehicles')->pluck('plate_number')->sort()->values()->all();
    }

    public function test_super_admin_can_list_vehicles_with_no_location(): void
    {
        $this->vehicle('Palawan', ['plate_number' => 'PAL 1001']);
        $this->vehicle('Palawan', ['plate_number' => 'NUL 2002', 'location' => null]);
        $this->vehicle('Palawan', ['plate_number' => 'BLK 3003', 'location' => '']);

        $this->actingAs($this->superAdmin())->get('/vehicles')->assertOk()
            ->assertSee('No Location')
            ->assertSee('location='.Vehicle::NO_LOCATION, false)
            ->assertSee('PAL 1001')->assertSee('NUL 2002');

        $this->get('/vehicles?location=none')->assertOk()
            ->assertSee('NUL 2002')->assertSee('BLK 3003')->assertDontSee('PAL 1001')
            ->assertSee('All Vehicles (2)');
    }

    public function test_admins_keep_their_own_location_and_get_no_no_location_option(): void
    {
        $this->vehicle('Palawan', ['plate_number' => 'PAL 1001']);
        $this->vehicle('Palawan', ['plate_number' => 'NUL 2002', 'location' => null]);

        $this->actingAs($this->admin('Palawan'))->get('/vehicles?location=none')->assertOk()
            ->assertSee('PAL 1001')->assertDontSee('NUL 2002')
            ->assertDontSee('No Location');
    }

    public function test_filter_by_assigned_driver(): void
    {
        $driver = $this->driver('Palawan');
        $this->vehicle('Palawan', ['plate_number' => 'HAS 1001', 'assigned_driver_id' => $driver->id]);
        $this->vehicle('Palawan', ['plate_number' => 'NOD 2002']);

        foreach ([$this->superAdmin(), $this->admin('Palawan')] as $user) {
            $this->actingAs($user)->get('/vehicles?driver=unassigned')->assertOk()
                ->assertSee('NOD 2002')->assertDontSee('HAS 1001')
                ->assertSee('Assigned driver: No driver assigned');

            $this->get('/vehicles?driver=assigned')->assertOk()
                ->assertSee('HAS 1001')->assertDontSee('NOD 2002');
        }
    }

    public function test_filter_by_health_score(): void
    {
        // Oil OK and nothing overdue: 100% (Good)
        $this->vehicle('Palawan', ['plate_number' => 'GUD 1001']);
        // Change oil overdue only: 50% (Fair)
        $this->vehicle('Palawan', ['plate_number' => 'FAR 2002', 'last_change_oil_odometer' => 1000]);
        // Change oil and a PMS item overdue: 33% (Poor)
        $poor = $this->vehicle('Palawan', ['plate_number' => 'PUR 3003', 'last_change_oil_odometer' => 1000]);
        VehiclePmsItem::create(['vehicle_id' => $poor->id, 'item_name' => 'Brake fluid', 'category' => 'Brakes', 'interval_km' => 5000, 'last_done_km' => 1000]);

        $this->actingAs($this->admin('Palawan'));
        $this->assertSame(['GUD 1001'], $this->listedPlates('/vehicles?health=good'));
        $this->assertSame(['FAR 2002'], $this->listedPlates('/vehicles?health=fair'));
        $this->assertSame(['PUR 3003'], $this->listedPlates('/vehicles?health=poor'));
    }

    public function test_filter_by_change_oil_status(): void
    {
        $this->vehicle('Palawan', ['plate_number' => 'OIL 1001']);
        $this->vehicle('Palawan', ['plate_number' => 'SON 2002', 'last_change_oil_odometer' => 5300]);
        $this->vehicle('Palawan', ['plate_number' => 'OVR 3003', 'last_change_oil_odometer' => 1000]);

        $this->actingAs($this->admin('Palawan'));
        $this->assertSame(['OVR 3003'], $this->listedPlates('/vehicles?oil=overdue'));
        $this->assertSame(['SON 2002'], $this->listedPlates('/vehicles?oil=due_soon'));
        $this->assertSame(['OIL 1001'], $this->listedPlates('/vehicles?oil=ok'));
    }

    public function test_filters_combine_with_location_and_search_and_ignore_unknown_values(): void
    {
        $driver = $this->driver('Palawan');
        $this->vehicle('Palawan', ['plate_number' => 'ABC 1001', 'brand' => 'Isuzu']);
        $this->vehicle('Palawan', ['plate_number' => 'ABC 2002', 'brand' => 'Toyota']);
        $this->vehicle('Palawan', ['plate_number' => 'ABC 3003', 'brand' => 'Isuzu', 'assigned_driver_id' => $driver->id]);
        $this->vehicle('Romblon', ['plate_number' => 'ROM 4004', 'brand' => 'Isuzu']);

        $this->actingAs($this->superAdmin())->get('/vehicles?location=Palawan&search=isuzu&driver=unassigned')->assertOk()
            ->assertSee('ABC 1001')->assertDontSee('ABC 2002')->assertDontSee('ABC 3003')->assertDontSee('ROM 4004');

        // An unknown value is not a filter: everything is listed and no chip is shown
        $this->get('/vehicles?health=excellent&driver=maybe')->assertOk()
            ->assertSee('ABC 1001')->assertSee('ROM 4004')->assertDontSee('Filtered by:');
    }

    public function test_no_match_shows_a_filter_specific_empty_state(): void
    {
        $this->vehicle('Palawan', ['plate_number' => 'GUD 1001']);

        $this->actingAs($this->admin('Palawan'))->get('/vehicles?health=poor')->assertOk()
            ->assertSee('No vehicles match the selected filters.')->assertDontSee('GUD 1001');
    }
}
