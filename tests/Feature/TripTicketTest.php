<?php

namespace Tests\Feature;

use App\Services\TripTicketNumberer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\VehicleRequest\Models\TripTicketCompletion;
use Modules\VehicleRequest\Models\VehicleRequest;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * GSS workbook plan, phase 3: each area's own trip ticket numbers, and the driver's Part B on the printed ticket.
 */
class TripTicketTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function approve(string $location): VehicleRequest
    {
        $trip = $this->tripRequest($this->vehicle($location, ['assigned_driver_id' => $this->driver($location)->id]));
        $this->actingAs($this->admin($location))->patch("/vehicle-requests/{$trip->id}/status", ['status' => 'approved'])->assertSessionHas('success');

        return $trip->fresh();
    }

    public function test_each_area_numbers_tickets_in_its_own_format(): void
    {
        Carbon::setTestNow('2026-03-10 09:00:00');

        $this->assertSame('001-26', $this->approve('Quezon City Satellite Office')->trip_ticket_number);
        $this->assertSame('002-26', $this->approve('Quezon City Satellite Office')->trip_ticket_number);
        $this->assertSame('26-03-001', $this->approve('Palawan')->trip_ticket_number);
        $this->assertSame('0001-03-2026', $this->approve('Oriental Mindoro')->trip_ticket_number);
        $this->assertSame('0001-03-2026', $this->approve('Romblon')->trip_ticket_number);
        $this->assertSame('0001-03-2026', $this->approve('Marinduque')->trip_ticket_number);
        $this->assertSame('0001-03-2026', $this->approve('Occidental Mindoro')->trip_ticket_number);
    }

    public function test_the_sequence_runs_through_the_year_and_restarts_in_january(): void
    {
        Carbon::setTestNow('2026-11-30 09:00:00');
        $this->assertSame('0001-11-2026', $this->approve('Oriental Mindoro')->trip_ticket_number);

        Carbon::setTestNow('2026-12-01 09:00:00');
        $this->assertSame('0002-12-2026', $this->approve('Oriental Mindoro')->trip_ticket_number);
        $this->assertSame('26-12-001', $this->approve('Palawan')->trip_ticket_number);

        Carbon::setTestNow('2027-01-04 09:00:00');
        $this->assertSame('0001-01-2027', $this->approve('Oriental Mindoro')->trip_ticket_number);
        $this->assertSame('27-01-001', $this->approve('Palawan')->trip_ticket_number);
        $this->assertSame('001-27', $this->approve('Quezon City Satellite Office')->trip_ticket_number);
    }

    public function test_numbers_continue_from_the_highest_stored_and_old_formats_are_kept(): void
    {
        Carbon::setTestNow('2026-10-15 09:00:00');
        $vehicle = $this->vehicle('Oriental Mindoro');
        $old = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => 'FMS-OR-MIN-2026-10-0007', 'travel_date' => '2026-10-01', 'travel_date_end' => '2026-10-01']);
        // Numbers carried over from the paper series (e.g. by the workbook import) are continued
        $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '0339-03-2026', 'travel_date' => '2026-03-01', 'travel_date_end' => '2026-03-01']);
        $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '0950-12-2025', 'travel_date' => '2025-12-01', 'travel_date_end' => '2025-12-01']);

        $this->assertSame('0340-10-2026', $this->approve('Oriental Mindoro')->trip_ticket_number);
        $this->assertSame('FMS-OR-MIN-2026-10-0007', $old->fresh()->trip_ticket_number);
    }

    public function test_a_number_is_unique_within_an_area_only(): void
    {
        $this->tripRequest($this->vehicle('Romblon'), ['status' => 'approved', 'trip_ticket_number' => '0001-10-2026']);
        $this->tripRequest($this->vehicle('Marinduque'), ['status' => 'approved', 'trip_ticket_number' => '0001-10-2026']);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->tripRequest($this->vehicle('Romblon'), ['status' => 'approved', 'trip_ticket_number' => '0001-10-2026']);
    }

    public function test_the_numberer_formats_and_reads_back_each_pattern(): void
    {
        $numberer = app(TripTicketNumberer::class);
        $at = Carbon::parse('2026-07-01');

        $this->assertSame('1000-07-2026', $numberer->format('{seq4}-{mm}-{yyyy}', $at, 1000));
        $this->tripRequest($this->vehicle('Quezon City Satellite Office'), ['status' => 'approved', 'trip_ticket_number' => '1000-26']);
        Carbon::setTestNow($at);
        $this->assertSame('1001-26', $numberer->next('Quezon City Satellite Office'));
    }

    public function test_lookup_asks_for_more_when_a_number_exists_in_two_areas(): void
    {
        $this->tripRequest($this->vehicle('Romblon'), ['status' => 'approved', 'trip_ticket_number' => '0001-10-2026']);
        $marinduque = $this->tripRequest($this->vehicle('Marinduque'), ['status' => 'approved', 'trip_ticket_number' => '0001-10-2026']);

        $this->actingAs($this->superAdmin())->getJson('/vehicle-requests/lookup-trip?ticket_number=0001-10-2026')->assertStatus(409);
        $this->actingAs($this->admin('Marinduque'))->getJson('/vehicle-requests/lookup-trip?ticket_number=0001-10-2026')
            ->assertOk()->assertJsonPath('trip.id', $marinduque->id);
    }

    public function test_printed_ticket_shows_part_b_without_fund_cluster_or_gate_log(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $trip = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-045']);

        $this->actingAs($admin)->get("/trip-ticket/{$trip->id}")->assertOk()
            ->assertDontSee('Fund Cluster', false)
            ->assertDontSee('guard on duty', false)
            ->assertDontSee('15,250');

        TripTicketCompletion::create([
            'vehicle_request_id' => $trip->id, 'trip_ticket_number' => '26-10-045', 'vehicle_id' => $vehicle->id,
            'departure_garage_time' => '7:30 AM', 'arrival_garage_time' => '5:15 PM',
            'odo_beginning' => 15000, 'odo_ending' => 15250, 'distance_travelled' => 250,
            'fuel_balance_in_tank' => 20, 'fuel_issued_office' => 0, 'fuel_purchased_trip' => 15.5,
            'fuel_total_liters' => 35.5, 'fuel_used_liters' => 25, 'fuel_ending_balance' => 10.5,
            'lub_oil_issued' => 1, 'remarks' => 'Smooth trip',
        ]);

        $this->actingAs($admin)->get("/trip-ticket/{$trip->id}")->assertOk()
            ->assertSee('7:30 AM')->assertSee('5:15 PM')
            ->assertSee('15,000')->assertSee('15,250')->assertSee('250')
            ->assertSee('35.5')->assertSee('10.5')->assertSee('Smooth trip');
    }
}
