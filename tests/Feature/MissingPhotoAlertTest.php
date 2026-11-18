<?php

namespace Tests\Feature;

use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** Needs Attention: drivers and vehicles with no photo yet, each card opening the matching list */
class MissingPhotoAlertTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_needs_attention_counts_drivers_and_vehicles_without_a_photo(): void
    {
        $this->driver('Palawan', ['name' => 'No Photo Driver']);
        $this->driver('Palawan', ['avatar' => 'avatars/has.jpg']);
        $this->driver('Palawan', ['deactivated_at' => now()]);   // deactivated: not counted
        $this->driver('Marinduque');                              // other area: not counted
        $this->vehicle('Palawan', ['plate_number' => 'NOP 0001']);
        $this->vehicle('Palawan', ['photo' => 'vehicles/has.jpg']);

        $alerts = collect(app(DashboardService::class)->alerts('Palawan'));

        $drivers = $alerts->firstWhere('key', 'drivers_without_photo');
        $this->assertSame(1, $drivers['value']);
        $this->assertStringContainsString('photo=missing', $drivers['url']);

        $vehicles = $alerts->firstWhere('key', 'vehicles_without_photo');
        $this->assertSame(1, $vehicles['value']);
        $this->assertStringContainsString('photo=missing', $vehicles['url']);
    }

    public function test_no_card_when_every_record_has_a_photo(): void
    {
        $this->driver('Palawan', ['avatar' => 'avatars/has.jpg']);
        $this->vehicle('Palawan', ['photo' => 'vehicles/has.jpg']);

        $keys = collect(app(DashboardService::class)->alerts('Palawan'))->pluck('key');

        $this->assertNotContains('drivers_without_photo', $keys);
        $this->assertNotContains('vehicles_without_photo', $keys);
    }

    public function test_the_lists_filter_to_records_without_a_photo(): void
    {
        $this->driver('Palawan', ['name' => 'Pedro Nophoto']);
        $this->driver('Palawan', ['name' => 'Juan Withphoto', 'avatar' => 'avatars/has.jpg']);
        $this->vehicle('Palawan', ['plate_number' => 'NOP 0001']);
        $this->vehicle('Palawan', ['plate_number' => 'HAS 0002', 'photo' => 'vehicles/has.jpg']);

        $this->actingAs($this->superAdmin())->get('/driver?photo=missing')->assertOk()
            ->assertSee('Pedro Nophoto')->assertDontSee('Juan Withphoto')->assertSee('Show all drivers');

        $this->actingAs($this->superAdmin())->get('/vehicles?photo=missing')->assertOk()
            ->assertSee('NOP 0001')->assertDontSee('HAS 0002')->assertSee('No photo yet');
    }
}
