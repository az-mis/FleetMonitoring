<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * GSS workbook plan, phase 2: each vehicle's property record and paperwork, with expiry alerts.
 */
class VehicleRegistryTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_admin_records_the_property_record_and_paperwork(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['plate_number' => 'SNE 6227']);
        $raed = Office::where('code', 'RAED')->value('id');

        $this->actingAs($admin)->get("/vehicles/{$vehicle->id}/registry")->assertOk()->assertSee('SNE 6227');

        $this->actingAs($admin)->put("/vehicles/{$vehicle->id}/registry", [
            'par_number' => 'MV-001',
            'property_number' => '01-00-MV01',
            'conduction_sticker' => 'A8X 654',
            'acquisition_date' => '2018-10-23',
            'acquisition_cost' => '1248800',
            'custodian_name' => 'Benjamin Gardoce',
            'end_user_office_id' => $raed,
            'book_status' => 'BOOK UP',
            'condition' => 'Serviceable',
            'or_cr_status' => 'Original on file',
            'registration_place' => 'Manila',
            'registration_expiry' => now()->addDays(10)->toDateString(),
            'insurance_type' => 'TPL / Comprehensive',
            'insurance_from' => now()->subYear()->toDateString(),
            'insurance_to' => now()->addMonths(6)->toDateString(),
            'tire_type' => '255/65/R17',
            'has_ewd' => '1',
            'fleet_card_allowance' => '15000',
        ])->assertRedirect("/vehicles/{$vehicle->id}/registry")->assertSessionHasNoErrors();

        $vehicle->refresh();
        $this->assertSame('01-00-MV01', $vehicle->property_number);
        $this->assertSame('RAED', $vehicle->endUserOffice->code);
        $this->assertTrue($vehicle->has_ewd);
        $this->assertSame('warning', $vehicle->registrationStatus()['tone']);
        $this->assertSame('success', $vehicle->insuranceStatus()['tone']);
    }

    public function test_paperwork_dates_are_validated(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');

        $this->actingAs($admin)->put("/vehicles/{$vehicle->id}/registry", [
            'insurance_from' => '2026-06-01',
            'insurance_to' => '2026-01-01',
            'acquisition_date' => now()->addDay()->toDateString(),
            'condition' => 'Brand new',
        ])->assertSessionHasErrors(['insurance_to', 'acquisition_date', 'condition']);
    }

    public function test_accessories_can_be_added_and_removed(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');

        $this->actingAs($admin)->post("/vehicles/{$vehicle->id}/accessories", ['description' => 'Smart Top Canopy', 'date_acquired' => '2018-11-26', 'value' => '118000'])
            ->assertSessionHasNoErrors();
        $accessory = $vehicle->accessories()->firstOrFail();
        $this->actingAs($admin)->get("/vehicles/{$vehicle->id}/registry")->assertSee('Smart Top Canopy');

        $this->actingAs($admin)->delete("/vehicles/{$vehicle->id}/accessories/{$accessory->id}")->assertSessionHasNoErrors();
        $this->assertSame(0, $vehicle->accessories()->count());
    }

    public function test_admins_cannot_edit_another_locations_vehicle(): void
    {
        $vehicle = $this->vehicle('Romblon');

        $this->actingAs($this->admin('Palawan'))->get("/vehicles/{$vehicle->id}/registry")->assertForbidden();
        $this->actingAs($this->admin('Palawan'))->put("/vehicles/{$vehicle->id}/registry", ['par_number' => 'X'])->assertForbidden();
        $this->actingAs($this->driver('Romblon'))->get("/vehicles/{$vehicle->id}/registry")->assertForbidden();
    }

    public function test_expired_or_expiring_papers_raise_a_dashboard_alert_and_filter_the_list(): void
    {
        $expired = $this->vehicle('Palawan', ['plate_number' => 'EXP 001', 'registration_expiry' => now()->subDay()->toDateString()]);
        $this->vehicle('Palawan', ['plate_number' => 'DUE 002', 'insurance_to' => now()->addDays(20)->toDateString()]);
        $this->vehicle('Palawan', ['plate_number' => 'OKAY 003', 'registration_expiry' => now()->addYear()->toDateString()]);

        $alert = collect(app(DashboardService::class)->alerts('Palawan'))->firstWhere('key', 'vehicle_papers');
        $this->assertSame(2, $alert['value']);
        $this->assertSame('1 expired', $alert['detail']);
        $this->assertSame('high', $alert['severity']);

        $this->actingAs($this->admin('Palawan'))->get('/vehicles?papers=due')->assertOk()
            ->assertSee('EXP 001')->assertSee('DUE 002')->assertDontSee('OKAY 003');
        $this->assertSame('danger', $expired->registrationStatus()['tone']);
    }

    public function test_a_registered_cards_allowance_is_the_vehicles_allowance(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['plate_number' => 'SAB 4584', 'fleet_card_allowance' => 25000]);

        // No card yet: the amount kept on the vehicle (from the workbook) is used and stays editable
        $this->assertSame(25000.0, $vehicle->effectiveFleetCardAllowance());
        $this->actingAs($admin)->get("/vehicles/{$vehicle->id}/registry")->assertOk()->assertSee('name="fleet_card_allowance"', false);

        // A card without an allowance changes nothing
        $card = $this->fleetCard($vehicle, ['card_number' => 'PFC-0001']);
        $this->assertSame(25000.0, $vehicle->fresh()->effectiveFleetCardAllowance());

        // Once the card sets one, the vehicle shows the card's amount read-only and keeps its own untouched
        $card->update(['monthly_allowance' => 30000]);
        $this->actingAs($admin)->get("/vehicles/{$vehicle->id}/registry")->assertOk()
            ->assertSee('30,000.00')->assertSee('From fleet card PFC-0001.')->assertDontSee('name="fleet_card_allowance"', false);
        $this->actingAs($admin)->put("/vehicles/{$vehicle->id}/registry", ['fleet_card_allowance' => '1', 'tools' => '1 set jack'])
            ->assertSessionHasNoErrors();
        $vehicle->refresh();
        $this->assertSame('1 set jack', $vehicle->tools);
        $this->assertSame('25000.00', $vehicle->fleet_card_allowance);
        $this->assertSame(30000.0, $vehicle->effectiveFleetCardAllowance());
    }
}
