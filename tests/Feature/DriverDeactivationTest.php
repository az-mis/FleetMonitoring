<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Vehicle\Models\VehicleAssignmentHistory;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * Agency decision D10 from the Phase 4 UAT report: drivers who leave are deactivated, not deleted.
 */
class DriverDeactivationTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_deactivating_a_driver_frees_their_vehicles_and_blocks_sign_in(): void
    {
        $admin = $this->admin('Palawan');
        $driver = $this->driver('Palawan', ['name' => 'Pedro Santos']);
        $vehicle = $this->vehicle('Palawan');
        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $driver->id]);

        $this->actingAs($admin)->patch("/driver/{$driver->id}/deactivate")
            ->assertSessionHas('success', 'Pedro Santos deactivated. They can no longer sign in or be chosen for trips. 1 vehicle no longer has a default driver.');

        $this->assertFalse($driver->fresh()->isActive());
        $this->assertNull($vehicle->fresh()->assigned_driver_id);
        $this->assertNotNull(VehicleAssignmentHistory::where('driver_id', $driver->id)->value('unassigned_at'));

        auth()->logout();
        $this->post('/login', ['email' => $driver->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => User::DEACTIVATED_MESSAGE]);
        $this->assertGuest();

        // A wrong password still gets the normal message, so the account state is not revealed
        $this->post('/login', ['email' => $driver->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertNotSame(User::DEACTIVATED_MESSAGE, session('errors')->first('email'));
    }

    public function test_a_signed_in_driver_is_signed_out_once_deactivated(): void
    {
        $driver = $this->driver('Palawan');
        $driver->forceFill(['deactivated_at' => now()])->save();

        $this->actingAs($driver)->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => User::DEACTIVATED_MESSAGE]);
        $this->assertGuest();
    }

    public function test_deactivated_drivers_are_hidden_from_pickers_and_refused_for_new_work(): void
    {
        $admin = $this->admin('Palawan');
        $active = $this->driver('Palawan', ['name' => 'Active Driver']);
        $inactive = $this->driver('Palawan', ['name' => 'Gone Driver', 'deactivated_at' => now()]);
        $vehicle = $this->vehicle('Palawan');
        $trip = $this->tripRequest($vehicle);

        $this->actingAs($admin)->get('/vehicle-assigning')->assertOk()
            ->assertSee('Active Driver')->assertDontSee('Gone Driver');
        $this->actingAs($admin)->get('/vehicle-requests')->assertOk()
            ->assertSee('Active Driver')->assertDontSee('Gone Driver');

        $refusal = 'Gone Driver is deactivated. Reactivate the driver in Drivers or choose another driver.';
        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $inactive->id])
            ->assertSessionHas('error', $refusal);
        $this->actingAs($admin)->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $inactive->id])
            ->assertSessionHas('error', $refusal);

        $this->assertNull($vehicle->fresh()->assigned_driver_id);
        $this->assertSame('pending', $trip->fresh()->status);
        $this->assertTrue($active->isActive());
    }

    public function test_a_driver_with_an_upcoming_trip_cannot_be_deactivated(): void
    {
        $admin = $this->admin('Palawan');
        $driver = $this->driver('Palawan', ['name' => 'Pedro Santos']);
        $this->tripRequest($this->vehicle('Palawan'), [
            'status' => 'approved', 'trip_ticket_number' => '26-10-801', 'confirmed_driver_id' => $driver->id,
        ]);

        $this->actingAs($admin)->patch("/driver/{$driver->id}/deactivate")
            ->assertSessionHas('error', 'Pedro Santos still has upcoming trip 26-10-801. Choose another driver for these trips or cancel them first, then deactivate.');
        $this->assertTrue($driver->fresh()->isActive());
    }

    public function test_drivers_can_be_reactivated_and_filtered_by_status(): void
    {
        $admin = $this->admin('Palawan');
        $this->driver('Palawan', ['name' => 'Active Driver']);
        $inactive = $this->driver('Palawan', ['name' => 'Gone Driver', 'deactivated_at' => now()]);

        $this->actingAs($admin)->get('/driver?status=active')->assertOk()
            ->assertSee('Active Driver')->assertDontSee('Gone Driver');
        $this->actingAs($admin)->get('/driver?status=inactive')->assertOk()
            ->assertSee('Gone Driver')->assertDontSee('Active Driver');
        $this->actingAs($admin)->get('/driver')->assertOk()
            ->assertSee('Active Driver')->assertSee('Gone Driver')->assertSee('Inactive');

        $this->actingAs($admin)->patch("/driver/{$inactive->id}/reactivate")
            ->assertSessionHas('success', 'Gone Driver reactivated and can sign in again.');
        $this->assertTrue($inactive->fresh()->isActive());
    }

    public function test_admins_cannot_deactivate_drivers_from_another_location(): void
    {
        $driver = $this->driver('Romblon');

        $this->actingAs($this->admin('Palawan'))->patch("/driver/{$driver->id}/deactivate")->assertForbidden();
        $this->assertTrue($driver->fresh()->isActive());
    }
}
