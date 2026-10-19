<?php

namespace Tests\Feature;

use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\VehicleRequest\Models\TripTicketCompletion;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * Agency decision D6 from the Phase 4 UAT report: cost per km uses the actual logged distance of
 * trips that have already happened, and card spending is credited to the trip's own vehicle.
 */
class ReportCostBasisTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_cost_report_uses_logged_distance_of_past_trips_and_the_trips_vehicle(): void
    {
        $admin = $this->admin('Palawan');
        $cardVehicle = $this->vehicle('Palawan', ['plate_number' => 'CARD-111']);
        $tripVehicle = $this->vehicle('Palawan', ['plate_number' => 'TRIP-222']);
        $card = $this->fleetCard($cardVehicle);
        $past = now()->subDays(2)->toDateString();

        // Past trip on another vehicle: estimated 10 km, Part B logged 40 km, charged to the card
        $loggedTrip = $this->tripRequest($tripVehicle, [
            'status' => 'approved', 'trip_ticket_number' => '26-10-701',
            'travel_date' => $past, 'travel_date_end' => $past, 'distance_km' => 10,
        ]);
        TripTicketCompletion::create([
            'vehicle_request_id' => $loggedTrip->id, 'trip_ticket_number' => '26-10-701',
            'vehicle_id' => $tripVehicle->id, 'distance_travelled' => 40,
        ]);
        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/log-expense", [
            'distance_source' => 'manual', 'distance_km' => 50, 'vehicle_request_id' => $loggedTrip->id,
        ])->assertSessionHasNoErrors(); // ₱800, priced on the trip's 40 km

        // Past trip without Part B: the 20 km estimate counts
        $this->tripRequest($cardVehicle, [
            'status' => 'approved', 'trip_ticket_number' => '26-10-702',
            'travel_date' => $past, 'travel_date_end' => $past, 'distance_km' => 20,
        ]);

        // Upcoming trip has not happened yet, so its 500 km must not count
        $this->tripRequest($cardVehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-703', 'distance_km' => 500]);

        $costs = app(ReportService::class)->costPerKmAnalysis('Palawan', now()->subDays(10), now()->addDays(10));

        $this->assertEquals(60, $costs['total_km']);
        $this->assertEquals(800, $costs['fuel_spent']);

        $byPlate = collect($costs['vehicles'])->keyBy('plate');
        $this->assertEquals(800, $byPlate['TRIP-222']['total_spent']);
        $this->assertEquals(40, $byPlate['TRIP-222']['total_km']);
        $this->assertEquals(0, $byPlate['CARD-111']['total_spent']);
        $this->assertEquals(20, $byPlate['CARD-111']['total_km']);
        $this->assertSame('TRIP-222', $costs['vehicles'][0]['plate']);
    }
}
