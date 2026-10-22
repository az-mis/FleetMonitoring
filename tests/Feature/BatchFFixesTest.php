<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Services\DashboardService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\FleetCard\Models\FleetCardLog;
use Modules\FleetCard\Models\FleetCardMonthlySpend;
use Modules\FleetCard\Services\FleetCardSpendService;
use Modules\VehicleRequest\Models\VehicleRequest;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** Batch F: Monthly Spend per area, unusable vehicles, Part B corrections, report definitions */
class BatchFFixesTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private function office(string $code): int
    {
        return (int) Office::where('code', $code)->value('id');
    }

    public function test_two_areas_each_record_their_own_statement_and_all_areas_adds_them_up(): void
    {
        $palawan = $this->admin('Palawan');
        $romblon = $this->admin('Romblon');
        $payload = ['provider' => 'Shell', 'office_id' => $this->office('AMAD'), 'year' => 2026, 'month' => 10];

        $this->actingAs($palawan)->post('/fleet-cards/monthly-spend', $payload + ['amount' => 1000])->assertSessionHasNoErrors();
        $this->actingAs($romblon)->post('/fleet-cards/monthly-spend', $payload + ['amount' => 2500])->assertSessionHasNoErrors();

        $this->assertSame(2, FleetCardMonthlySpend::count());
        $spend = app(FleetCardSpendService::class);
        $cell = fn (?string $area) => $spend->grid(2026, $area)['rows']->firstWhere('provider', 'Shell')['months'][10]['amount'];
        $this->assertEquals(1000, $cell('Palawan'));
        $this->assertEquals(2500, $cell('Romblon'));
        $this->assertEquals(3500, $cell(null));
    }

    public function test_an_area_statement_replaces_only_that_areas_logged_expenses(): void
    {
        $this->actingAs($this->admin('Palawan'));
        $amad = $this->office('AMAD');
        $palawanCard = $this->fleetCard($this->vehicle('Palawan'), ['provider' => 'Shell', 'office_id' => $amad]);
        $romblonCard = $this->fleetCard($this->vehicle('Romblon'), ['provider' => 'Shell', 'office_id' => $amad]);
        foreach ([[$palawanCard, 700], [$romblonCard, 400]] as [$card, $amount]) {
            FleetCardLog::forceCreate(['fleet_card_id' => $card->id, 'vehicle_id' => $card->vehicle_id, 'distance_source' => 'manual', 'distance_km' => 0, 'rate_peso_per_km' => 0,
                'consumed_amount' => $amount, 'balance_before' => 5000, 'balance_after' => 5000 - $amount, 'created_at' => '2026-10-05 09:00:00', 'updated_at' => '2026-10-05 09:00:00']);
        }
        FleetCardMonthlySpend::create(['provider' => 'Shell', 'office_id' => $amad, 'division_label' => 'AMAD', 'location' => 'Palawan', 'year' => 2026, 'month' => 10, 'amount' => 900]);

        $row = app(FleetCardSpendService::class)->grid(2026)['rows']->firstWhere('provider', 'Shell');

        // Palawan's statement (900) replaces Palawan's logs (700); Romblon's logs (400) still count
        $this->assertEquals(1300, $row['months'][10]['amount']);
        $this->assertSame('mixed', $row['months'][10]['source']);
    }

    public function test_unusable_vehicles_cannot_be_requested_or_approved(): void
    {
        $vehicle = $this->vehicle('Palawan', ['plate_number' => 'OLD 0001', 'condition' => 'For disposal']);

        $this->get('/request-vehicle')->assertOk()->assertDontSee('OLD 0001');
        $this->post('/request-vehicle', [
            'form_token' => (string) Str::uuid(), 'requesterName' => 'Ana Reyes', 'location' => 'Palawan', 'requesterContact' => '09171234567',
            'vehicleId' => $vehicle->id, 'destination' => 'City Hall', 'travelDate' => now()->addDays(2)->toDateString(),
            'travelDateEnd' => now()->addDays(2)->toDateString(), 'purpose' => 'Field validation',
        ])->assertSessionHasErrors('vehicleId');

        $pending = $this->tripRequest($vehicle);
        $this->actingAs($this->admin('Palawan'))->patch("/vehicle-requests/{$pending->id}/status", ['status' => 'approved', 'confirmed_driver_id' => $this->driver('Palawan')->id])
            ->assertSessionHas('error', 'OLD 0001 is marked For disposal, so this request cannot be approved. Decline it or choose another vehicle.');
        $this->assertSame('pending', $pending->fresh()->status);
    }

    public function test_a_vehicle_for_repair_stays_bookable_but_is_flagged_on_the_public_form(): void
    {
        $this->vehicle('Palawan', ['plate_number' => 'FIX 0001', 'condition' => 'For repair']);
        $this->vehicle('Palawan', ['plate_number' => 'OK 0001', 'condition' => 'Serviceable']);

        $vehicles = collect($this->getJson('/request-vehicle/station-availability?location=Palawan')->assertOk()->json('vehicles'))->keyBy('plate');

        $this->assertTrue($vehicles['FIX 0001']['for_repair']);
        $this->assertFalse($vehicles['OK 0001']['for_repair']);
    }

    public function test_correcting_a_part_b_typo_brings_the_odometer_back_down(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['odometer' => 10000]);
        $trip = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-010', 'travel_date' => now()->subDay()->toDateString(), 'travel_date_end' => now()->subDay()->toDateString(), 'confirmed_driver_id' => $this->driver('Palawan')->id]);
        $partB = fn (int $end) => ['odo_beginning' => 10000, 'odo_ending' => $end, 'fuel_used_liters' => 5];

        $this->actingAs($admin)->post("/vehicle-requests/{$trip->id}/complete-trip", $partB(101000))->assertSessionHasNoErrors(); // typed one 0 too many
        $this->assertSame(101000, (int) $vehicle->fresh()->odometer);

        $this->actingAs($admin)->post("/vehicle-requests/{$trip->id}/complete-trip", $partB(10100))->assertSessionHasNoErrors();
        $this->assertSame(10100, (int) $vehicle->fresh()->odometer);
    }

    public function test_reports_count_approvals_by_approval_date_and_top_destinations_skip_declined(): void
    {
        $vehicle = $this->vehicle('Palawan');
        // Requested last month, approved this month: counts for this month
        $trip = $this->tripRequest($vehicle, ['status' => 'approved', 'destination' => 'Narra']);
        VehicleRequest::whereKey($trip->id)->update(['created_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(2), 'approved_at' => now()]);
        $this->tripRequest($vehicle, ['status' => 'declined', 'destination' => 'Coron', 'decline_reason' => 'No driver']);

        // 1 approved this month vs 0 last month (by request date it would read 0 vs 1, down 100%)
        $approved = collect(app(DashboardService::class)->kpis('Palawan', false))->firstWhere('key', 'approved');
        $this->assertSame(['direction' => 'up', 'percent' => 100], $approved['trend']);

        $top = app(ReportService::class)->topDestinations('Palawan');
        $this->assertNotContains('Coron', $top['labels']);
    }
}
