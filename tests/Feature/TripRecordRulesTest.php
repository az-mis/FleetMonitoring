<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * Agency decisions D1, D2, D3 and D12 from the Phase 4 UAT report.
 */
class TripRecordRulesTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_driver_with_expired_licence_cannot_be_assigned_or_dispatched(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $expired = $this->driver('Palawan', ['name' => 'Lorna Reyes', 'license_expiration_date' => now()->subMonth()->toDateString()]);

        $this->actingAs($admin)->patch("/vehicle-assigning/{$vehicle->id}", ['assigned_driver_id' => $expired->id])
            ->assertSessionHas('error', fn ($message) => str_contains($message, "Lorna Reyes's driver's licence expired on"));
        $this->assertNull($vehicle->fresh()->assigned_driver_id);

        $request = $this->tripRequest($vehicle);
        $this->actingAs($admin)->patch("/vehicle-requests/{$request->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $expired->id])
            ->assertSessionHas('error');
        $this->assertSame('pending', $request->fresh()->status);

        $this->actingAs($admin)->post('/vehicle-requests/admin-create', [
            'requester_name' => 'Liza Ramos',
            'requester_contact' => '09175551234',
            'location' => 'Palawan',
            'vehicle_id' => $vehicle->id,
            'confirmed_driver_id' => $expired->id,
            'destination' => 'City Hall',
            'travel_date' => now()->addDays(2)->toDateString(),
            'travel_date_end' => now()->addDays(2)->toDateString(),
            'purpose' => 'Field validation',
        ])->assertSessionHasErrors('confirmed_driver_id');
    }

    public function test_licence_must_stay_valid_until_the_trip_ends(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $expiresSoon = $this->driver('Palawan', ['license_expiration_date' => now()->addDays(3)->toDateString()]);

        $longTrip = $this->tripRequest($vehicle, ['travel_date' => now()->addDays(2)->toDateString(), 'travel_date_end' => now()->addDays(5)->toDateString()]);
        $this->actingAs($admin)->patch("/vehicle-requests/{$longTrip->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $expiresSoon->id])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'before this trip ends'));

        $shortTrip = $this->tripRequest($vehicle, ['travel_date' => now()->addDays(2)->toDateString(), 'travel_date_end' => now()->addDays(2)->toDateString()]);
        $this->actingAs($admin)->patch("/vehicle-requests/{$shortTrip->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $expiresSoon->id])
            ->assertSessionHas('success');
        $this->assertSame('approved', $shortTrip->fresh()->status);
    }

    public function test_declining_requires_a_reason_that_the_requester_sees(): void
    {
        $admin = $this->admin('Palawan');
        $request = $this->tripRequest($this->vehicle('Palawan'));

        $this->actingAs($admin)->patch("/vehicle-requests/{$request->id}/status", ['status' => 'declined'])
            ->assertSessionHasErrors('decline_reason');
        $this->assertSame('pending', $request->fresh()->status);

        $this->actingAs($admin)->patch("/vehicle-requests/{$request->id}/status", ['status' => 'declined', 'decline_reason' => 'Vehicle reserved for an inspection.'])
            ->assertSessionHas('success');
        $this->get('/check-status?code='.$request->reference_code)
            ->assertSee('Request declined')
            ->assertSee('Vehicle reserved for an inspection.');
    }

    public function test_approved_trip_can_be_cancelled_with_a_reason(): void
    {
        $admin = $this->admin('Palawan');
        $trip = $this->tripRequest($this->vehicle('Palawan'), ['status' => 'approved', 'trip_ticket_number' => '26-10-701', 'confirmed_driver_id' => $this->driver('Palawan')->id]);

        $this->actingAs($admin)->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'declined'])
            ->assertSessionHasErrors('decline_reason');
        $this->assertSame('approved', $trip->fresh()->status);

        $this->actingAs($admin)->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'declined', 'decline_reason' => 'The activity was postponed.'])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Trip 26-10-701 cancelled'));
        $this->assertSame('declined', $trip->fresh()->status);

        $this->get('/check-status?code='.$trip->reference_code)
            ->assertSee('Trip cancelled')
            ->assertSee('The activity was postponed.');
    }

    public function test_approved_trip_cannot_be_cancelled_after_its_start_date(): void
    {
        $admin = $this->admin('Palawan');
        $driver = $this->driver('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $started = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-702', 'confirmed_driver_id' => $driver->id,
            'travel_date' => now()->subDays(5)->toDateString(), 'travel_date_end' => now()->subDays(5)->toDateString()]);
        $today = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-703', 'confirmed_driver_id' => $driver->id,
            'travel_date' => now()->toDateString(), 'travel_date_end' => now()->addDay()->toDateString()]);

        $this->actingAs($admin)->patch("/vehicle-requests/{$started->id}/status", ['status' => 'declined', 'decline_reason' => 'Too late.'])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'can no longer be cancelled'));
        $this->assertSame('approved', $started->fresh()->status);

        $this->actingAs($admin)->patch("/vehicle-requests/{$today->id}/status", ['status' => 'declined', 'decline_reason' => 'Called off this morning.'])
            ->assertSessionHas('success');
        $this->assertSame('declined', $today->fresh()->status);
    }

    public function test_request_whose_travel_date_passed_can_still_be_approved_deliberately(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['assigned_driver_id' => $this->driver('Palawan')->id]);
        $stale = $this->tripRequest($vehicle, ['travel_date' => now()->subDays(2)->toDateString(), 'travel_date_end' => now()->subDays(2)->toDateString()]);

        $this->actingAs($admin)->get('/vehicle-requests')->assertOk()->assertSee('start_has_passed', false);

        $this->actingAs($admin)->patch("/vehicle-requests/{$stale->id}/status", ['status' => 'approved'])
            ->assertSessionHas('success');
        $this->assertSame('approved', $stale->fresh()->status);
    }
}
