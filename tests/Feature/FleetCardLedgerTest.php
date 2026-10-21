<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FleetCard\Models\FleetCardLog;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class FleetCardLedgerTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_deleting_an_older_expense_refunds_only_that_expense(): void
    {
        $admin = $this->admin('Palawan');
        $card = $this->fleetCard($this->vehicle('Palawan'));   // 5000 budget, ₱100 per 5 km = ₱20/km

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['distance_source' => 'manual', 'distance_km' => 50]);   // -1000
        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['distance_source' => 'manual', 'distance_km' => 25]);   // -500
        $this->assertEquals(3500, (float) $card->fresh()->current_balance);

        $firstExpense = FleetCardLog::where('fleet_card_id', $card->id)->orderBy('id')->first();
        $this->actingAs($admin)->deleteJson("/fleet-cards/{$card->id}/log/{$firstExpense->id}")->assertOk();

        // 5000 - 500 (the remaining expense) = 4500. Previously the balance was reset to the deleted
        // log's balance_before (5000), wiping out the second expense.
        $this->assertEquals(4500, (float) $card->fresh()->current_balance);
    }

    public function test_top_up_and_its_removal_restore_the_balance(): void
    {
        $admin = $this->admin('Palawan');
        $card = $this->fleetCard($this->vehicle('Palawan'));

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/recharge", ['amount' => 2000]);
        $this->assertEquals(7000, (float) $card->fresh()->current_balance);

        $topUp = FleetCardLog::where('fleet_card_id', $card->id)->where('distance_source', 'recharge')->first();
        $this->actingAs($admin)->deleteJson("/fleet-cards/{$card->id}/recharge/{$topUp->id}")->assertOk();

        $this->assertEquals(5000, (float) $card->fresh()->current_balance);
    }

    public function test_a_trip_can_only_be_charged_once_and_only_to_a_card_in_its_location(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $card = $this->fleetCard($vehicle);
        $trip = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => 'T-1', 'distance_km' => 10]);
        $otherTrip = $this->tripRequest($this->vehicle('Romblon'), ['status' => 'approved', 'trip_ticket_number' => 'T-2']);

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['vehicle_request_id' => $trip->id])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['vehicle_request_id' => $trip->id])->assertSessionHasErrors('vehicle_request_id');
        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['vehicle_request_id' => $otherTrip->id])->assertSessionHasErrors('vehicle_request_id');

        $this->assertSame(1, FleetCardLog::where('fleet_card_id', $card->id)->count());
    }

    public function test_card_number_must_be_unique(): void
    {
        $admin = $this->admin('Palawan');
        $card = $this->fleetCard($this->vehicle('Palawan'), ['card_number' => 'FC-DUP']);
        $payload = ['card_name' => 'Card', 'location' => 'Palawan', 'provider' => 'Petron', 'initial_budget' => 100, 'rate_peso' => 100, 'rate_km' => 5];

        $this->actingAs($admin)->post('/fleet-cards', $payload + ['card_number' => 'FC-DUP'])->assertSessionHasErrors('card_number');
        $this->actingAs($admin)->put("/fleet-cards/{$card->id}", $payload + ['card_number' => 'FC-DUP'])->assertSessionHasNoErrors();
    }
}
