<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\VehicleRequest\Models\TripTicketCompletion;
use Modules\VehicleRequest\Models\VehicleRequest;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class TripWorkflowTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private function publicPayload(int $vehicleId, array $overrides = []): array
    {
        return array_merge([
            'form_token' => (string) Str::uuid(),
            'requesterName' => 'juan dela cruz',
            'location' => 'Palawan',
            'requesterContact' => '09171234567',
            'vehicleId' => $vehicleId,
            'destination' => 'City Hall',
            'travelDate' => now()->addDays(2)->toDateString(),
            'travelDateEnd' => now()->addDays(2)->toDateString(),
            'purpose' => 'Field validation',
        ], $overrides);
    }

    public function test_public_request_is_created_once_per_form_token(): void
    {
        $vehicle = $this->vehicle('Palawan');
        $payload = $this->publicPayload($vehicle->id);

        $response = $this->post('/request-vehicle', $payload);
        $request = VehicleRequest::first();

        $response->assertRedirect(route('check-status', ['code' => $request->reference_code]));
        $this->assertSame('pending', $request->status);
        $this->assertSame('Juan Dela Cruz', $request->requester_name);
        $this->assertSame(7, strlen($request->reference_code));

        // Resubmitting the same form does not create a duplicate
        $this->post('/request-vehicle', $payload)->assertSessionHasErrors('form');
        $this->assertSame(1, VehicleRequest::count());
    }

    public function test_public_request_validation(): void
    {
        $palawanVehicle = $this->vehicle('Palawan');
        $romblonVehicle = $this->vehicle('Romblon');

        $this->post('/request-vehicle', $this->publicPayload($palawanVehicle->id, ['travelDateEnd' => now()->toDateString(), 'travelDate' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('travelDateEnd');
        $this->post('/request-vehicle', $this->publicPayload($palawanVehicle->id, ['requesterContact' => '9171234567']))
            ->assertSessionHasErrors('requesterContact');
        $this->post('/request-vehicle', $this->publicPayload($palawanVehicle->id, ['travelDate' => now()->subDay()->toDateString(), 'travelDateEnd' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('travelDate');
        // A vehicle from another station cannot be booked under this location
        $this->post('/request-vehicle', $this->publicPayload($romblonVehicle->id))
            ->assertSessionHasErrors('vehicleId');
        $this->post('/request-vehicle', $this->publicPayload(999999))
            ->assertSessionHasErrors('vehicleId');

        $this->assertSame(0, VehicleRequest::count());
    }

    public function test_public_request_rejects_vehicle_under_maintenance(): void
    {
        $vehicle = $this->vehicle('Palawan');
        $this->maintenance($vehicle, ['scheduled_date' => now()->addDays(2)->toDateString()]);

        $this->post('/request-vehicle', $this->publicPayload($vehicle->id))->assertSessionHasErrors('vehicleId');
    }

    public function test_admin_approves_request_with_driver_and_ticket_number(): void
    {
        $admin = $this->admin('Romblon');
        $driver = $this->driver('Romblon');
        $trip = $this->tripRequest($this->vehicle('Romblon'));

        $this->actingAs($admin)
            ->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $driver->id])
            ->assertSessionHas('success');

        $trip->refresh();
        $this->assertSame('approved', $trip->status);
        $this->assertSame($driver->id, $trip->confirmed_driver_id);
        $this->assertSame($admin->id, $trip->approved_by);
        $this->assertSame('0001-'.now()->format('m-Y'), $trip->trip_ticket_number);
    }

    public function test_approval_without_a_chosen_driver_stores_the_vehicles_default_driver(): void
    {
        $driver = $this->driver('Palawan');
        $trip = $this->tripRequest($this->vehicle('Palawan', ['assigned_driver_id' => $driver->id]));

        $this->actingAs($this->admin('Palawan'))->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'approved']);

        $this->assertSame($driver->id, $trip->fresh()->confirmed_driver_id);
    }

    public function test_approval_rejects_admin_account_as_driver_and_other_location(): void
    {
        $admin = $this->admin('Palawan');
        $trip = $this->tripRequest($this->vehicle('Palawan'));

        $this->actingAs($admin)
            ->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $admin->id])
            ->assertSessionHasErrors('confirmed_driver_id');
        $this->actingAs($this->admin('Romblon'))
            ->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'approved'])
            ->assertForbidden();

        $this->assertSame('pending', $trip->fresh()->status);
    }

    public function test_vehicle_cannot_be_double_booked_on_approval(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['assigned_driver_id' => $this->driver('Palawan')->id]);
        $first = $this->tripRequest($vehicle);
        $second = $this->tripRequest($vehicle);

        $this->actingAs($admin)->patch("/vehicle-requests/{$first->id}/status", ['status' => 'approved']);
        $this->actingAs($admin)->patch("/vehicle-requests/{$second->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $this->driver('Palawan')->id])
            ->assertSessionHas('error');

        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('pending', $second->fresh()->status);
    }

    public function test_driver_cannot_be_on_two_overlapping_trips(): void
    {
        $admin = $this->admin('Palawan');
        $driver = $this->driver('Palawan');
        $first = $this->tripRequest($this->vehicle('Palawan'));
        $second = $this->tripRequest($this->vehicle('Palawan'));

        $this->actingAs($admin)->patch("/vehicle-requests/{$first->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $driver->id]);
        $this->actingAs($admin)->patch("/vehicle-requests/{$second->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $driver->id])
            ->assertSessionHas('error');

        $this->assertSame('pending', $second->fresh()->status);
    }

    public function test_ticket_numbers_stay_unique_after_a_decline(): void
    {
        $admin = $this->admin('Romblon');
        $trips = collect(range(1, 3))->map(fn () => $this->tripRequest($this->vehicle('Romblon', ['assigned_driver_id' => $this->driver('Romblon')->id])));

        $this->actingAs($admin)->patch("/vehicle-requests/{$trips[0]->id}/status", ['status' => 'approved']);
        $this->actingAs($admin)->patch("/vehicle-requests/{$trips[1]->id}/status", ['status' => 'approved']);
        $this->actingAs($admin)->patch("/vehicle-requests/{$trips[0]->id}/status", ['status' => 'declined', 'decline_reason' => 'Cancelled']);
        $this->actingAs($admin)->patch("/vehicle-requests/{$trips[2]->id}/status", ['status' => 'approved'])->assertSessionHas('success');

        $numbers = VehicleRequest::whereNotNull('trip_ticket_number')->pluck('trip_ticket_number');
        $this->assertCount(3, $numbers);
        $this->assertCount(3, $numbers->unique());
        $this->assertStringStartsWith('0003-', $trips[2]->fresh()->trip_ticket_number);
    }

    public function test_palawan_uses_yearly_sequence(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['assigned_driver_id' => $this->driver('Palawan')->id]);
        $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => now()->format('y').'-01-868', 'travel_date' => now()->subMonth()->toDateString(), 'travel_date_end' => now()->subMonth()->toDateString()]);
        $trip = $this->tripRequest($vehicle);

        $this->actingAs($admin)->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'approved']);

        $this->assertSame(now()->format('y-m').'-869', $trip->fresh()->trip_ticket_number);
    }

    public function test_declined_request_cannot_be_reapproved_and_driver_change_keeps_approver(): void
    {
        $approver = $this->admin('Palawan');
        $trip = $this->tripRequest($this->vehicle('Palawan', ['assigned_driver_id' => $this->driver('Palawan')->id]));

        $this->actingAs($approver)->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'approved']);
        $approvedAt = $trip->fresh()->approved_at;

        $this->travel(2)->hours();
        $newDriver = $this->driver('Palawan');
        $this->actingAs($this->admin('Palawan'))
            ->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $newDriver->id]);

        $trip->refresh();
        $this->assertSame($newDriver->id, $trip->confirmed_driver_id);
        $this->assertSame($approver->id, $trip->approved_by);
        $this->assertTrue($approvedAt->equalTo($trip->approved_at));

        $declined = $this->tripRequest($this->vehicle('Palawan'), ['status' => 'declined']);
        $this->actingAs($approver)->patch("/vehicle-requests/{$declined->id}/status", ['status' => 'approved'])->assertSessionHas('error');
        $this->assertSame('declined', $declined->fresh()->status);
    }

    public function test_trip_completion_rules(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['odometer' => 10000]);
        $past = ['travel_date' => now()->subDay()->toDateString(), 'travel_date_end' => now()->subDay()->toDateString()];
        $payload = ['odo_beginning' => 10000, 'odo_ending' => 10120, 'fuel_divisor' => 10];

        $pending = $this->tripRequest($vehicle, $past);
        $this->actingAs($admin)->postJson("/vehicle-requests/{$pending->id}/complete-trip", $payload)->assertStatus(422);

        $future = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => 'T-FUT']);
        $this->actingAs($admin)->postJson("/vehicle-requests/{$future->id}/complete-trip", $payload)->assertStatus(422);

        $approved = $this->tripRequest($vehicle, $past + ['status' => 'approved', 'trip_ticket_number' => 'T-PAST']);
        $this->actingAs($admin)->postJson("/vehicle-requests/{$approved->id}/complete-trip", ['odo_beginning' => 10000, 'odo_ending' => 9000])
            ->assertStatus(422)->assertJsonValidationErrors('odo_ending');

        $this->actingAs($admin)->postJson("/vehicle-requests/{$approved->id}/complete-trip", $payload)->assertOk();
        $this->actingAs($admin)->postJson("/vehicle-requests/{$approved->id}/complete-trip", $payload)->assertOk();

        $this->assertSame(1, TripTicketCompletion::where('vehicle_request_id', $approved->id)->count());
        $completion = TripTicketCompletion::where('vehicle_request_id', $approved->id)->first();
        $this->assertEquals(120, (float) $completion->distance_travelled);
        $this->assertEquals(12, (float) $completion->fuel_used_liters);
        $this->assertSame(10120, (int) $vehicle->fresh()->odometer);
        $this->assertSame(0, TripTicketCompletion::where('vehicle_request_id', $pending->id)->count());
    }

    public function test_manual_trip_creation_scopes_vehicle_and_driver_to_location(): void
    {
        $admin = $this->admin('Palawan');
        $base = [
            'requester_name' => 'Ana Reyes', 'requester_contact' => '09171234567', 'location' => 'Palawan',
            'destination' => 'City Hall', 'travel_date' => now()->addDays(4)->toDateString(),
            'travel_date_end' => now()->addDays(4)->toDateString(), 'purpose' => 'Inspection',
        ];

        $this->actingAs($admin)->post('/vehicle-requests/admin-create', $base + ['vehicle_id' => $this->vehicle('Romblon')->id, 'confirmed_driver_id' => $this->driver('Palawan')->id])
            ->assertSessionHasErrors('vehicle_id');
        $this->actingAs($admin)->post('/vehicle-requests/admin-create', $base + ['vehicle_id' => $this->vehicle('Palawan')->id, 'confirmed_driver_id' => $this->driver('Romblon')->id])
            ->assertSessionHasErrors('confirmed_driver_id');
        $this->actingAs($admin)->post('/vehicle-requests/admin-create', $base + ['vehicle_id' => $this->vehicle('Palawan')->id, 'confirmed_driver_id' => $admin->id])
            ->assertSessionHasErrors('confirmed_driver_id');

        $this->assertSame(0, VehicleRequest::count());

        $this->actingAs($admin)->post('/vehicle-requests/admin-create', $base + ['vehicle_id' => $this->vehicle('Palawan')->id, 'confirmed_driver_id' => $this->driver('Palawan')->id])
            ->assertSessionHas('manual_request_created');
        $this->assertSame('approved', VehicleRequest::first()->status);
    }

    public function test_trip_lookup_prefers_exact_ticket_number(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-0011']);
        $exact = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-001']);

        $this->actingAs($admin)->getJson('/vehicle-requests/lookup-trip?ticket_number=26-10-001')
            ->assertOk()->assertJsonPath('trip.id', $exact->id);
        $this->actingAs($admin)->getJson('/vehicle-requests/lookup-trip?ticket_number=26-10')
            ->assertStatus(409);
    }
}
