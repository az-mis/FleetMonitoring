<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Admin\Enums\UserRole;
use Modules\Destination\Models\Destination;
use Modules\Settings\Models\FleetSetting;
use Modules\VehicleReport\Models\VehicleIssueReport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public static function protectedPages(): array
    {
        return [
            ['/dashboard'], ['/vehicles'], ['/vehicle-assigning'], ['/vehicle-requests'], ['/driver'],
            ['/admin'], ['/fleet-cards'], ['/maintenance'], ['/destinations'], ['/approving-officers'],
            ['/content-settings'], ['/my-profile'], ['/reports'],
        ];
    }

    #[DataProvider('protectedPages')]
    public function test_guests_are_redirected_to_login(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }

    public static function adminOnlyPages(): array
    {
        return [
            ['/vehicles'], ['/vehicle-assigning'], ['/vehicle-requests'], ['/driver'], ['/admin'],
            ['/fleet-cards'], ['/maintenance'], ['/destinations'], ['/approving-officers'], ['/reports'],
        ];
    }

    #[DataProvider('adminOnlyPages')]
    public function test_drivers_cannot_open_admin_pages(string $uri): void
    {
        $this->actingAs($this->driver())->get($uri)->assertForbidden();
    }

    public function test_only_super_admin_can_manage_admin_accounts(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertForbidden();
        $this->actingAs($this->superAdmin())->get('/admin')->assertOk();
    }

    public function test_driver_cannot_change_fleet_card_budgets(): void
    {
        $card = $this->fleetCard($this->vehicle());

        $this->actingAs($this->driver())
            ->post("/fleet-cards/{$card->id}/recharge", ['amount' => 999999])
            ->assertForbidden();
        $this->actingAs($this->driver())->delete("/fleet-cards/{$card->id}")->assertForbidden();

        $this->assertEquals(5000, (float) $card->fresh()->current_balance);
    }

    public function test_admin_cannot_manage_another_locations_fleet_card(): void
    {
        $card = $this->fleetCard($this->vehicle('Oriental Mindoro'));
        $palawanAdmin = $this->admin('Palawan');

        $this->actingAs($palawanAdmin)->post("/fleet-cards/{$card->id}/recharge", ['amount' => 1000])->assertForbidden();
        $this->actingAs($palawanAdmin)->delete("/fleet-cards/{$card->id}")->assertForbidden();
        $this->assertNotNull($card->fresh());

        $this->actingAs($this->admin('Oriental Mindoro'))
            ->post("/fleet-cards/{$card->id}/recharge", ['amount' => 1000])
            ->assertRedirect();
        $this->assertEquals(6000, (float) $card->fresh()->current_balance);
    }

    public function test_admin_cannot_register_fleet_card_for_another_location(): void
    {
        $orminVehicle = $this->vehicle('Oriental Mindoro');

        $this->actingAs($this->admin('Palawan'))->post('/fleet-cards', [
            'card_number' => 'FC-X1', 'card_name' => 'Sneaky', 'location' => 'Oriental Mindoro',
            'vehicle_id' => $orminVehicle->id, 'initial_budget' => 100, 'rate_peso' => 100, 'rate_km' => 5,
        ])->assertSessionHasErrors('vehicle_id');

        $this->assertDatabaseMissing('fleet_cards', ['card_number' => 'FC-X1']);
    }

    public function test_driver_routes_cannot_target_admin_accounts(): void
    {
        $palawanAdmin = $this->admin('Palawan');
        $otherAdmin = $this->admin('Palawan');

        $this->actingAs($palawanAdmin)->put("/driver/{$otherAdmin->id}", [
            'name' => 'Hijacked', 'email' => $otherAdmin->email, 'role' => 'driver',
            'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
            'phone_number' => '09171234567', 'license_number' => 'X', 'license_expiration_date' => '2030-01-01',
        ])->assertNotFound();
        $this->actingAs($palawanAdmin)->delete("/driver/{$otherAdmin->id}")->assertNotFound();

        $otherAdmin->refresh();
        $this->assertSame(UserRole::ADMIN, $otherAdmin->role);
        $this->assertNotSame('Hijacked', $otherAdmin->name);
    }

    public function test_admin_cannot_edit_or_delete_another_locations_vehicle(): void
    {
        $orminVehicle = $this->vehicle('Oriental Mindoro');
        $palawanAdmin = $this->admin('Palawan');

        $this->actingAs($palawanAdmin)->delete("/vehicles/{$orminVehicle->id}")->assertForbidden();
        $this->actingAs($palawanAdmin)
            ->patchJson("/vehicles/{$orminVehicle->id}/odometer", ['odometer' => 1])
            ->assertForbidden();

        $this->assertNotNull($orminVehicle->fresh());
        $this->assertSame(10000, (int) $orminVehicle->fresh()->odometer);
    }

    public function test_admin_cannot_update_another_locations_maintenance(): void
    {
        $orminVehicle = $this->vehicle('Oriental Mindoro');
        $record = $this->maintenance($orminVehicle);

        $this->actingAs($this->admin('Palawan'))->put("/maintenance/{$record->id}", [
            'vehicle_id' => $orminVehicle->id, 'type' => 'Repair', 'title' => 'Changed', 'status' => 'cancelled',
            'priority' => 'normal', 'scheduled_date' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame('scheduled', $record->fresh()->status);
    }

    public function test_admin_location_filter_cannot_be_overridden_with_query_string(): void
    {
        $this->tripRequest($this->vehicle('Oriental Mindoro'), ['reference_code' => 'ORMREQ1', 'requester_name' => 'Ormin Requester']);
        $this->tripRequest($this->vehicle('Palawan'), ['reference_code' => 'PALREQ1', 'requester_name' => 'Palawan Requester']);

        $response = $this->actingAs($this->admin('Palawan'))->get('/vehicle-requests?location=Oriental+Mindoro&status=all');

        $response->assertOk()->assertSee('PALREQ1')->assertDontSee('ORMREQ1');
    }

    public function test_admin_without_location_is_denied_instead_of_seeing_everything(): void
    {
        $locationless = User::factory()->create(['role' => UserRole::ADMIN, 'location' => null]);

        $this->actingAs($locationless)->get('/vehicle-requests')->assertForbidden();
        $this->actingAs($locationless)->get('/fleet-cards')->assertForbidden();
    }

    public function test_drivers_cannot_manage_destinations(): void
    {
        $driver = $this->driver();
        $destination = Destination::create(['name' => 'City Hall', 'location' => 'Palawan', 'distance_km' => 5, 'is_active' => true]);

        $this->actingAs($driver)->post('/destinations', ['name' => 'Fake', 'location' => 'Palawan', 'distance_km' => 999])->assertForbidden();
        $this->actingAs($driver)->delete("/destinations/{$destination->id}")->assertForbidden();

        $this->assertDatabaseMissing('destinations', ['name' => 'Fake']);
        $this->assertNotNull($destination->fresh());
    }

    public function test_admin_cannot_review_another_locations_vehicle_report(): void
    {
        $orminVehicle = $this->vehicle('Oriental Mindoro');
        $driver = $this->driver('Oriental Mindoro');
        $report = VehicleIssueReport::create([
            'vehicle_id' => $orminVehicle->id, 'plate_number' => $orminVehicle->plate_number, 'driver_id' => $driver->id,
            'driver_name' => $driver->name, 'issue_type' => 'Engine', 'priority' => 'normal',
            'description' => 'Noise', 'status' => 'submitted', 'location' => 'Oriental Mindoro',
        ]);

        $this->actingAs($this->admin('Palawan'))
            ->put("/vehicle-reports/{$report->id}", ['status' => 'dismissed'])
            ->assertForbidden();

        $this->assertSame('submitted', $report->fresh()->status);
    }

    public function test_trip_ticket_requires_signed_link_or_authorised_user(): void
    {
        $vehicle = $this->vehicle('Palawan');
        $trip = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-001']);

        $this->get("/trip-ticket/{$trip->id}")->assertForbidden();
        $this->get(\Modules\VehicleRequest\Controllers\VehicleRequestController::publicTripTicketUrl($trip))->assertOk()->assertSee('26-10-001');
        // A link signed the old way (no expiry) no longer opens
        $this->get(URL::signedRoute('trip-ticket', $trip))->assertForbidden();
        $this->actingAs($this->admin('Palawan'))->get("/trip-ticket/{$trip->id}")->assertOk();
        $this->actingAs($this->admin('Oriental Mindoro'))->get("/trip-ticket/{$trip->id}")->assertForbidden();
    }

    public function test_check_status_page_links_to_a_signed_trip_ticket(): void
    {
        $trip = $this->tripRequest($this->vehicle(), ['reference_code' => 'SIGNED1', 'status' => 'approved', 'trip_ticket_number' => '26-10-002']);

        $this->get('/check-status?code=SIGNED1')->assertOk()->assertSee('signature=', false);
    }

    public function test_disabled_module_blocks_write_actions_for_admins(): void
    {
        FleetSetting::create(['setting_key' => 'admin_modules', 'setting_value' => ['vehicles' => false]]);
        $vehicle = $this->vehicle('Palawan');
        $admin = $this->admin('Palawan');

        $this->actingAs($admin)->patchJson("/vehicles/{$vehicle->id}/odometer", ['odometer' => 20000])->assertForbidden();
        $this->actingAs($admin)->get('/vehicles')->assertOk()->assertSee('Module Access Restricted');
        $this->assertSame(10000, (int) $vehicle->fresh()->odometer);

        $this->actingAs($this->superAdmin())->patchJson("/vehicles/{$vehicle->id}/odometer", ['odometer' => 20000])->assertOk();
        $this->assertSame(20000, (int) $vehicle->fresh()->odometer);
    }

    public function test_admin_accounts_require_a_location(): void
    {
        $this->actingAs($this->superAdmin())->post('/admin', [
            'name' => 'No Location', 'email' => 'nolocation@example.test', 'role' => 'admin',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('location');

        $this->assertDatabaseMissing('users', ['email' => 'nolocation@example.test']);
    }

    public function test_settings_toggle_rejects_unknown_keys(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/content-settings/toggle', ['group' => 'driver_modules', 'key' => 'anythingAtAll', 'value' => 1])
            ->assertSessionHasErrors('key');
    }

    public function test_serialized_users_hide_two_factor_secrets(): void
    {
        $user = $this->admin();
        $user->forceFill(['two_factor_secret' => 'secret-value', 'two_factor_recovery_codes' => 'codes'])->save();

        $array = $user->fresh()->toArray();

        $this->assertArrayNotHasKey('two_factor_secret', $array);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $array);
        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_public_vehicle_request_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/request-vehicle', [])->assertStatus(302);
        }

        $this->post('/request-vehicle', [])->assertStatus(429);
    }
}
