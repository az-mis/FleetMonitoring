<?php

namespace Tests\Feature;

use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\FleetCard\Models\FleetCardLog;
use Modules\FleetCard\Services\FleetCardService;
use Modules\VehicleRequest\Models\TripTicketCompletion;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** Batch D: fleet card charges are priced by litres, and the budget figures follow the ledger */
class FleetCardMoneyTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_a_distance_charge_costs_km_over_km_per_litre_times_the_litre_price(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['fuel_economy_km_per_liter' => 10]);
        $card = $this->fleetCard($vehicle, ['rate_peso' => 68.50, 'rate_km' => 1, 'initial_budget' => 10000, 'current_balance' => 10000]);

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['distance_source' => 'manual', 'distance_km' => 100])
            ->assertSessionHasNoErrors();

        // 100 km ÷ 10 km/L = 10 L × ₱68.50 = ₱685 (it used to charge ₱68.50 per km: ₱6,850)
        $log = FleetCardLog::firstOrFail();
        $this->assertEquals(685.00, (float) $log->consumed_amount);
        $this->assertEquals(10.00, (float) $log->liters_used);
        $this->assertEquals(6.85, (float) $log->rate_peso_per_km);
        $this->assertEquals(9315.00, (float) $card->fresh()->current_balance);
    }

    public function test_a_distance_charge_needs_the_vehicles_fuel_economy(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['plate_number' => 'SAB 4584', 'fuel_economy_km_per_liter' => null]);
        $card = $this->fleetCard($vehicle);

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['distance_source' => 'manual', 'distance_km' => 40])
            ->assertSessionHasErrors(['distance_km' => 'Set the fuel economy (km per litre) of SAB 4584 on its Property & Registration page first, or charge the trip from its Part B litres.']);

        $this->assertSame(0, FleetCardLog::count());
        $this->assertEquals(5000, (float) $card->fresh()->current_balance);
    }

    public function test_part_b_litres_are_charged_as_logged_whatever_the_fuel_economy(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['fuel_economy_km_per_liter' => null]);
        $card = $this->fleetCard($vehicle, ['rate_peso' => 60]);
        $trip = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-001', 'distance_km' => 120]);
        TripTicketCompletion::forceCreate(['vehicle_request_id' => $trip->id, 'vehicle_id' => $vehicle->id, 'trip_ticket_number' => '26-10-001', 'distance_travelled' => 130, 'fuel_used_liters' => 14, 'odo_beginning' => 1000, 'odo_ending' => 1130]);

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['vehicle_request_id' => $trip->id, 'distance_source' => 'destination_master'])
            ->assertSessionHasNoErrors();

        $this->assertEquals(840.00, (float) FleetCardLog::firstOrFail()->consumed_amount); // 14 L × ₱60
    }

    public function test_deleting_a_top_up_leaves_the_starting_budget_alone(): void
    {
        $admin = $this->admin('Palawan');
        $card = $this->fleetCard($this->vehicle('Palawan'), ['initial_budget' => 10000, 'current_balance' => 10000]);

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['distance_source' => 'manual', 'distance_km' => 400]); // ₱20/km: -8000
        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/recharge", ['amount' => 3000]);
        $card->refresh();
        $this->assertEquals(5000, (float) $card->current_balance);
        $this->assertEquals(10000, (float) $card->initial_budget);
        $this->assertEquals(13000, $card->total_allocated);

        $topUp = FleetCardLog::where('distance_source', 'recharge')->firstOrFail();
        $this->actingAs($admin)->deleteJson("/fleet-cards/{$card->id}/recharge/{$topUp->id}")->assertOk();
        // A second click on the gone top-up changes nothing
        $this->actingAs($admin)->deleteJson("/fleet-cards/{$card->id}/recharge/{$topUp->id}")->assertNotFound();

        $card->refresh();
        $this->assertEquals(2000, (float) $card->current_balance);
        $this->assertEquals(10000, (float) $card->initial_budget); // it used to drop to 7000
    }

    public function test_consumed_is_what_was_spent_even_after_top_ups(): void
    {
        $admin = $this->admin('Palawan');
        $card = $this->fleetCard($this->vehicle('Palawan'), ['initial_budget' => 10000, 'current_balance' => 10000]);

        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['distance_source' => 'manual', 'distance_km' => 450]); // -9000
        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/recharge", ['amount' => 5000]);

        $card->refresh();
        $this->assertEquals(9000, $card->total_spent);
        $this->assertEquals(15000, $card->total_allocated);
        $this->assertEquals(40.0, $card->balance_percentage); // 6000 of 15000 left

        $budget = app(DashboardService::class)->fleetBudget('Palawan');
        $this->assertEquals(15000, $budget['total_allocated']);
        $this->assertEquals(9000, $budget['total_consumed']); // it used to say 4000
        $this->assertEquals(6000, $budget['total_remaining']);

        $page = $this->actingAs($admin)->get('/fleet-cards')->assertOk();
        $this->assertEquals(9000, $page->viewData('totalConsumed'));
        $this->assertEquals(15000, $page->viewData('totalInitialBudget'));
    }

    public function test_a_trip_cannot_be_charged_twice_even_past_the_form_check(): void
    {
        $this->actingAs($this->admin('Palawan'));
        $vehicle = $this->vehicle('Palawan');
        $card = $this->fleetCard($vehicle);
        $trip = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-002', 'distance_km' => 10]);
        $service = app(FleetCardService::class);

        // Two submits that both passed validation before either was saved
        $service->recordExpense($card, ['vehicle_request_id' => $trip->id]);
        try {
            $service->recordExpense($card, ['vehicle_request_id' => $trip->id]);
            $this->fail('The second charge should be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('vehicle_request_id', $e->errors());
        }

        $this->assertSame(1, FleetCardLog::count());
        $this->assertEquals(4800, (float) $card->fresh()->current_balance); // 10 km × ₱20, once
    }

    public function test_a_deleted_expense_is_refunded_once(): void
    {
        $admin = $this->admin('Palawan');
        $card = $this->fleetCard($this->vehicle('Palawan'));
        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", ['distance_source' => 'manual', 'distance_km' => 50]); // -1000
        $log = FleetCardLog::firstOrFail();

        $this->actingAs($admin)->deleteJson("/fleet-cards/{$card->id}/log/{$log->id}")->assertOk();
        $this->actingAs($admin)->deleteJson("/fleet-cards/{$card->id}/log/{$log->id}")->assertNotFound();

        $this->assertEquals(5000, (float) $card->fresh()->current_balance);
    }
}
