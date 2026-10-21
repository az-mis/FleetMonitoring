<?php

namespace Tests\Feature;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\FleetCard\Models\FleetCard;
use Modules\FleetCard\Models\FleetCardMonthlySpend;
use Modules\FleetCard\Services\FleetCardSpendService;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * GSS workbook plan, phase 5: fleet card provider, division and monthly allowance, and the monthly spend
 * per division ("Index for Fleetcard").
 */
class FleetCardSpendTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function office(string $code): int
    {
        return (int) Office::where('code', $code)->value('id');
    }

    /** Logs a ₱1,000 expense (50 km at ₱20/km) on the card today */
    private function spend1000(FleetCard $card): void
    {
        $this->actingAs($this->admin($card->location))
            ->post("/fleet-cards/{$card->id}/log-expense", ['distance_source' => 'manual', 'distance_km' => 50])
            ->assertSessionHasNoErrors();
    }

    public function test_card_records_provider_division_and_allowance(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');
        $payload = [
            'card_number' => 'PET-0001', 'card_name' => 'Petron Palawan #1', 'vehicle_id' => $vehicle->id, 'location' => 'Palawan',
            'initial_budget' => 20000, 'rate_peso' => 68.5, 'rate_km' => 1,
            'provider' => 'Petron', 'office_id' => $this->office('PRES'), 'monthly_allowance' => 15000, 'allowed_products' => 'Turbo Diesel',
        ];

        $this->actingAs($admin)->post('/fleet-cards', $payload)->assertSessionHasNoErrors();
        $card = FleetCard::where('card_number', 'PET-0001')->firstOrFail();
        $this->assertSame('Petron', $card->provider);
        $this->assertSame('PRES', $card->office->code);
        $this->assertEquals(15000, (float) $card->monthly_allowance);

        $this->actingAs($admin)->post('/fleet-cards', ['provider' => 'Caltex', 'monthly_allowance' => -1] + $payload + ['card_number' => 'PET-0002'])
            ->assertSessionHasErrors(['provider', 'monthly_allowance']);
        $this->actingAs($admin)->get('/fleet-cards')->assertOk()->assertSee('Petron · Palawan Research Experiment Station (PRES) · Turbo Diesel');
    }

    public function test_statement_amounts_replace_logged_expenses_month_by_month(): void
    {
        $card = $this->fleetCard($this->vehicle('Palawan'), ['provider' => 'Shell', 'office_id' => $this->office('PRES'), 'monthly_allowance' => 2000]);
        $this->spend1000($card);
        $this->spend1000($card);

        $grid = app(FleetCardSpendService::class)->grid(2026);
        $pres = $grid['rows']->firstWhere('office_id', $this->office('PRES'));
        $this->assertEquals(2000, $pres['months'][3]['amount']);
        $this->assertSame('logs', $pres['months'][3]['source']);
        $this->assertEquals(2000, $pres['allowance']);

        FleetCardMonthlySpend::create(['provider' => 'Shell', 'office_id' => $this->office('PRES'), 'division_label' => 'Palawan Research Experiment Station (PRES)', 'year' => 2026, 'month' => 3, 'amount' => 2150.75, 'source' => 'statement']);
        FleetCardMonthlySpend::create(['provider' => 'Shell', 'office_id' => $this->office('PRES'), 'division_label' => 'Palawan Research Experiment Station (PRES)', 'year' => 2026, 'month' => 1, 'amount' => 119263.04, 'source' => 'import']);
        FleetCardMonthlySpend::create(['provider' => 'Shell', 'division_label' => 'BAR-PRES VEHICLE', 'year' => 2026, 'month' => 1, 'amount' => 14550.29, 'source' => 'import', 'excluded_from_total' => true]);

        $grid = app(FleetCardSpendService::class)->grid(2026);
        $pres = $grid['rows']->firstWhere('office_id', $this->office('PRES'));
        $this->assertEquals(2150.75, $pres['months'][3]['amount']);
        $this->assertSame('statement', $pres['months'][3]['source']);
        $this->assertEquals(121413.79, round($pres['total'], 2));
        // The excluded vehicle is listed but left out of the column totals
        $this->assertTrue($grid['rows']->firstWhere('label', 'BAR-PRES VEHICLE')['excluded']);
        $this->assertEquals(119263.04, $grid['totals'][1]);
        $this->assertEquals(121413.79, round($grid['grand'], 2));
    }

    public function test_top_ups_are_not_counted_as_spend(): void
    {
        $admin = $this->admin('Palawan');
        $card = $this->fleetCard($this->vehicle('Palawan'), ['provider' => 'Shell', 'office_id' => $this->office('RAED'), 'monthly_allowance' => 5000]);
        $this->spend1000($card);
        $this->actingAs($admin)->post("/fleet-cards/{$card->id}/recharge", ['amount' => 3000])->assertSessionHasNoErrors();

        $row = app(FleetCardSpendService::class)->grid(2026)['rows']->firstWhere('office_id', $this->office('RAED'));
        $this->assertEquals(1000, $row['months'][3]['amount']);
    }

    public function test_division_over_its_monthly_allowance_is_flagged(): void
    {
        $admin = $this->admin('Palawan');
        $card = $this->fleetCard($this->vehicle('Palawan'), ['provider' => 'Shell', 'office_id' => $this->office('AMAD'), 'monthly_allowance' => 1500]);
        $this->actingAs($admin)->get('/fleet-cards')->assertOk()->assertDontSee('Over the monthly allowance');

        $this->spend1000($card);
        $this->spend1000($card);

        $this->actingAs($admin)->get('/fleet-cards')->assertOk()
            ->assertSee('Over the monthly allowance in March')
            ->assertSee('₱2,000.00 spent of ₱1,500.00')
            ->assertSee('March: ₱2,000.00 of ₱1,500.00 allowance');
        $this->actingAs($admin)->get('/fleet-cards/monthly-spend')->assertOk()
            ->assertSee('Monthly spend 2026')->assertSee('Agribusiness &amp; Marketing Assistance Division (AMAD)', false)->assertSee('2,000.00');
    }

    public function test_admin_records_statement_amounts_for_their_own_area_only(): void
    {
        $admin = $this->admin('Palawan');
        $payload = ['provider' => 'Shell', 'office_id' => $this->office('PRES'), 'year' => 2026, 'month' => 2, 'amount' => 104240.71, 'location' => 'Romblon'];

        $this->actingAs($admin)->post('/fleet-cards/monthly-spend', $payload)->assertSessionHasNoErrors();
        $spend = FleetCardMonthlySpend::firstOrFail();
        $this->assertSame('Palawan', $spend->location, 'an admin always records for their own area');
        $this->assertSame('statement', $spend->source);

        // Saving the same division and month again updates it
        $this->actingAs($admin)->post('/fleet-cards/monthly-spend', ['amount' => 104000] + $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, FleetCardMonthlySpend::count());
        $this->assertEquals(104000, (float) $spend->fresh()->amount);

        $regionWide = FleetCardMonthlySpend::create(['provider' => 'Shell', 'office_id' => $this->office('ORED'), 'division_label' => 'Office of the Regional Executive Director (ORED)', 'year' => 2026, 'month' => 2, 'amount' => 23488.63]);
        $romblon = FleetCardMonthlySpend::create(['provider' => 'Shell', 'office_id' => $this->office('RAED'), 'division_label' => 'Regional Agricultural Engineering Division (RAED)', 'location' => 'Romblon', 'year' => 2026, 'month' => 2, 'amount' => 999]);

        // Palawan records its own ORED figure; the region-wide one stays as it was
        $this->actingAs($admin)->post('/fleet-cards/monthly-spend', ['office_id' => $this->office('ORED'), 'amount' => 1] + $payload)->assertSessionHasNoErrors();
        $this->assertEquals(23488.63, (float) $regionWide->fresh()->amount);
        $this->assertNull($regionWide->fresh()->location);
        $this->assertSame('Palawan', FleetCardMonthlySpend::where('amount', 1)->value('location'));
        $this->actingAs($admin)->delete("/fleet-cards/monthly-spend/{$regionWide->id}")->assertForbidden();
        $this->actingAs($admin)->delete("/fleet-cards/monthly-spend/{$romblon->id}")->assertForbidden();
        $this->actingAs($admin)->get('/fleet-cards/monthly-spend?year=2026')->assertOk()->assertDontSee('999.00');
        $this->actingAs($this->driver('Palawan'))->get('/fleet-cards/monthly-spend')->assertForbidden();

        $this->actingAs($admin)->delete("/fleet-cards/monthly-spend/{$spend->id}")->assertSessionHas('success');
        $this->actingAs($this->superAdmin())->delete("/fleet-cards/monthly-spend/{$regionWide->id}")->assertSessionHas('success');
        $this->assertSame(2, FleetCardMonthlySpend::count()); // Romblon's and Palawan's ORED
    }
}
