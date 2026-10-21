<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Vehicle\Models\Vehicle;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** Every driver and vehicle belongs to an area, so the area checks on trips always apply */
class AreaRequiredTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private function driverPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lorna Reyes',
            'email' => 'lorna@fleet.test',
            'password' => 'Fleet#Secure2026',
            'password_confirmation' => 'Fleet#Secure2026',
            'role' => 'driver',
            'phone_number' => '09175550103',
            'license_number' => 'X09-99-9999999',
            'license_expiration_date' => now()->addYear()->toDateString(),
        ], $overrides);
    }

    private function vehiclePayload(array $overrides = []): array
    {
        return array_merge([
            'plate_number' => 'NBC 4521',
            'brand' => 'Toyota',
            'model' => 'Hiace',
            'color' => 'Silver',
            'odometer' => 1200,
            'vehicle_type' => 'Van',
            'fuel_type' => 'Diesel',
        ], $overrides);
    }

    public function test_a_super_admin_must_choose_a_drivers_area(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->post('/driver', $this->driverPayload())
            ->assertSessionHasErrors(['location' => 'Choose the area this driver belongs to.']);
        $this->assertFalse(User::where('email', 'lorna@fleet.test')->exists());

        $this->actingAs($super)->post('/driver', $this->driverPayload(['location' => 'Romblon']))->assertSessionHasNoErrors();
        $driver = User::where('email', 'lorna@fleet.test')->firstOrFail();
        $this->assertSame('Romblon', $driver->location);

        $this->actingAs($super)->put("/driver/{$driver->id}", $this->driverPayload(['location' => '', 'password' => null, 'password_confirmation' => null]))
            ->assertSessionHasErrors('location');
        $this->assertSame('Romblon', $driver->fresh()->location);
    }

    public function test_an_area_admins_drivers_get_the_admins_area_without_choosing(): void
    {
        $admin = $this->admin('Palawan');

        $this->actingAs($admin)->post('/driver', $this->driverPayload(['location' => 'Romblon']))->assertSessionHasNoErrors();
        $driver = User::where('email', 'lorna@fleet.test')->firstOrFail();
        $this->assertSame('Palawan', $driver->location);

        $this->actingAs($admin)->put("/driver/{$driver->id}", $this->driverPayload(['password' => null, 'password_confirmation' => null]))->assertSessionHasNoErrors();
        $this->assertSame('Palawan', $driver->fresh()->location);
    }

    public function test_only_a_super_admin_can_open_the_area_dropdown(): void
    {
        $locked = "New drivers join your area. Only a Super Admin can change a driver's area.";

        $dropdown = '/id="add-driver-location"\s+disabled/';

        $page = $this->actingAs($this->admin('Palawan'))->get('/driver')->assertOk()->assertSee($locked);
        $this->assertMatchesRegularExpression($dropdown, $page->getContent());

        $page = $this->actingAs($this->superAdmin())->get('/driver')->assertOk()->assertDontSee($locked);
        $this->assertDoesNotMatchRegularExpression($dropdown, $page->getContent());
    }

    public function test_a_vehicle_needs_an_area(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->post('/vehicles', $this->vehiclePayload())
            ->assertSessionHasErrors(['location' => 'Choose the area this vehicle belongs to.']);

        // An area admin's vehicle takes the admin's area
        $this->actingAs($this->admin('Palawan'))->post('/vehicles', $this->vehiclePayload())->assertSessionHasNoErrors();
        $vehicle = Vehicle::where('plate_number', 'NBC 4521')->firstOrFail();
        $this->assertSame('Palawan', $vehicle->location);

        // An old vehicle with no area must get one the next time a Super Admin saves it
        $vehicle->forceFill(['location' => null])->save();
        $this->actingAs($super)->put("/vehicles/{$vehicle->id}", $this->vehiclePayload(['location' => '']))->assertSessionHasErrors('location');
        $this->actingAs($super)->put("/vehicles/{$vehicle->id}", $this->vehiclePayload(['location' => 'Marinduque']))->assertSessionHasNoErrors();
        $this->assertSame('Marinduque', $vehicle->fresh()->location);
    }
}
