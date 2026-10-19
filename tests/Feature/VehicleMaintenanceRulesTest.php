<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Vehicle\Models\VehicleAssignmentHistory;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * Agency decisions D4, D7 and D9 from the Phase 4 UAT report.
 */
class VehicleMaintenanceRulesTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_maintenance_over_an_approved_trip_is_saved_with_a_warning(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $date = now()->addDays(4)->toDateString();
        $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-801', 'travel_date' => $date, 'travel_date_end' => $date]);

        $this->actingAs($admin)->get('/maintenance')->assertOk()->assertSee('26-10-801');

        $payload = ['vehicle_id' => $vehicle->id, 'type' => 'Repair', 'priority' => 'normal', 'title' => 'Brake repair', 'status' => 'scheduled'];

        $this->actingAs($admin)->post('/maintenance', $payload + ['scheduled_date' => $date])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'approved trip 26-10-801'));

        $this->actingAs($admin)->post('/maintenance', $payload + ['scheduled_date' => now()->addDays(10)->toDateString()])
            ->assertSessionHas('success', 'Maintenance record added.');
    }

    public function test_only_a_super_admin_can_lower_the_odometer_when_editing(): void
    {
        $vehicle = $this->vehicle('Palawan', ['odometer' => 15000, 'plate_number' => 'ODO 1001']);
        $payload = fn (int $odometer) => [
            'plate_number' => 'ODO 1001', 'brand' => 'Toyota', 'model' => 'Hilux', 'color' => 'White',
            'odometer' => $odometer, 'vehicle_type' => 'Pick-up', 'location' => 'Palawan',
        ];

        $this->actingAs($this->admin('Palawan'))->put("/vehicles/{$vehicle->id}", $payload(14000))
            ->assertSessionHasErrors('odometer');
        $this->assertEquals(15000, $vehicle->fresh()->odometer);

        $this->actingAs($this->superAdmin())->put("/vehicles/{$vehicle->id}", $payload(14000))
            ->assertSessionHasNoErrors();
        $this->assertEquals(14000, $vehicle->fresh()->odometer);
    }

    public function test_assignment_changes_are_kept_as_history(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $juan = $this->driver('Palawan', ['name' => 'Juan Dela Cruz']);
        $maria = $this->driver('Palawan', ['name' => 'Maria Santos']);

        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $juan->id]);
        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $maria->id]);
        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => null]);

        $history = VehicleAssignmentHistory::where('vehicle_id', $vehicle->id)->orderBy('id')->get();
        $this->assertSame(['Juan Dela Cruz', 'Maria Santos'], $history->pluck('driver_name')->all());
        $this->assertTrue($history->every(fn ($entry) => $entry->assigned_at && $entry->unassigned_at));
        $this->assertSame($admin->id, $history->first()->assigned_by);

        // Re-saving the same driver does not add a row
        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $juan->id]);
        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $juan->id]);
        $this->assertSame(3, VehicleAssignmentHistory::where('vehicle_id', $vehicle->id)->count());

        $this->actingAs($admin)->get('/vehicle-assigning')->assertOk()
            ->assertSee('Assignment history (3)')
            ->assertSee('Maria Santos');
    }
}
