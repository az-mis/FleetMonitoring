<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FleetCard\Models\FleetCardLog;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * Agency decisions D5 and D11 from the Phase 4 UAT report.
 */
class PrivacyAndFleetCardRulesTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_public_vehicle_availability_does_not_reveal_drivers(): void
    {
        $driver = $this->driver('Palawan', ['name' => 'Juan Dela Cruz', 'avatar' => 'avatars/juan.png']);
        $this->vehicle('Palawan', ['assigned_driver_id' => $driver->id]);
        $this->vehicle('Palawan');

        $response = $this->getJson('/request-vehicle/station-availability?location=Palawan')->assertOk();

        $this->assertSame([true, false], collect($response->json('vehicles'))->pluck('has_driver')->all());
        $this->assertStringNotContainsString('Juan Dela Cruz', $response->getContent());
        $this->assertStringNotContainsString('juan.png', $response->getContent());
        foreach ($response->json('vehicles') as $vehicle) {
            $this->assertArrayNotHasKey('driver', $vehicle);
            $this->assertArrayNotHasKey('driver_id', $vehicle);
            $this->assertArrayNotHasKey('driver_photo', $vehicle);
        }
    }

    public function test_only_approved_trips_can_be_charged_to_a_fleet_card(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $card = $this->fleetCard($vehicle);
        $pending = $this->tripRequest($vehicle, ['reference_code' => 'PENDNG1']);
        $approved = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-901', 'reference_code' => 'APPROV1']);

        $this->actingAs($admin)->get('/fleet-cards')->assertOk()
            ->assertSee('APPROV1')
            ->assertDontSee('PENDNG1');

        $expense = ['distance_source' => 'manual', 'distance_km' => 10];

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", $expense + ['vehicle_request_id' => $pending->id])
            ->assertSessionHasErrors(['vehicle_request_id' => "Only approved trips from this card's location can be charged."]);
        $this->assertSame(0, FleetCardLog::count());

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", $expense + ['vehicle_request_id' => $approved->id])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, FleetCardLog::where('vehicle_request_id', $approved->id)->count());
    }
}
