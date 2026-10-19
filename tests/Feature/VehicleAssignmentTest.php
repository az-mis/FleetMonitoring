<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class VehicleAssignmentTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_admin_assigns_and_reassigns_a_driver_to_a_vehicle(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $first = $this->driver('Palawan');
        $second = $this->driver('Palawan');

        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $first->id])
            ->assertSessionHas('success');
        $this->assertSame($first->id, $vehicle->fresh()->assigned_driver_id);

        // Reassignment replaces the previous driver: a vehicle only ever has one driver
        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $second->id]);
        $this->assertSame($second->id, $vehicle->fresh()->assigned_driver_id);

        // Unassign
        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => null]);
        $this->assertNull($vehicle->fresh()->assigned_driver_id);
    }

    public function test_one_driver_may_hold_several_vehicles_by_design(): void
    {
        $admin = $this->admin('Palawan');
        $driver = $this->driver('Palawan');
        $hilux = $this->vehicle('Palawan');
        $van = $this->vehicle('Palawan');

        $this->actingAs($admin)->patch("/vehicle-assigning/{$hilux->id}", ['assigned_driver_id' => $driver->id]);
        $this->actingAs($admin)->patch("/vehicle-assigning/{$van->id}", ['assigned_driver_id' => $driver->id]);

        $this->assertSame(2, $driver->assignedVehicles()->count());
    }

    public function test_non_driver_accounts_cannot_be_assigned(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');

        $this->actingAs($admin)
            ->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $admin->id])
            ->assertSessionHas('error', 'The selected user is not a driver.');

        $this->assertNull($vehicle->fresh()->assigned_driver_id);
    }

    public function test_driver_from_another_location_cannot_be_assigned(): void
    {
        $vehicle = $this->vehicle('Palawan');

        $this->actingAs($this->admin('Palawan'))
            ->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $this->driver('Romblon')->id])
            ->assertSessionHas('error', 'The selected driver is not assigned to Palawan.');

        $this->assertNull($vehicle->fresh()->assigned_driver_id);
    }

    public function test_invalid_driver_id_is_rejected(): void
    {
        $vehicle = $this->vehicle('Palawan');

        $this->actingAs($this->admin('Palawan'))
            ->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => 999999])
            ->assertSessionHasErrors('assigned_driver_id');
    }

    public function test_unauthorised_users_cannot_assign(): void
    {
        $vehicle = $this->vehicle('Palawan');
        $driver = $this->driver('Palawan');

        $this->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $driver->id])->assertRedirect('/login');
        $this->actingAs($driver)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $driver->id])->assertForbidden();
        $this->actingAs($this->admin('Romblon'))->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $driver->id])->assertForbidden();
        $this->actingAs($this->admin('Palawan'))->patch('/vehicle-assigning/999999', ['assigned_driver_id' => $driver->id])->assertNotFound();

        $this->assertNull($vehicle->fresh()->assigned_driver_id);
    }
}
