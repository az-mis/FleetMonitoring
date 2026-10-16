<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Enums\UserRole;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleReport\Models\VehicleIssueReport;
use Modules\VehicleRequest\Models\VehicleRequest;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/**
 * Regression tests for the defects found during Phase 4 user acceptance testing (UAT).
 */
class UatFindingsTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_super_admin_cannot_delete_or_demote_their_own_account(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['name' => 'Main Super Admin', 'location' => null]);
        $other = User::factory()->create(['name' => 'Romblon Admin', 'role' => UserRole::ADMIN, 'location' => 'Romblon']);

        $this->actingAs($superAdmin)->get('/admin')->assertOk()
            ->assertDontSee('Delete Main Super Admin')
            ->assertSee('Delete Romblon Admin');

        $this->actingAs($superAdmin)->delete("/admin/{$superAdmin->id}")
            ->assertRedirect('/admin')
            ->assertSessionHas('error', 'You cannot delete your own account. Ask another Super Admin to remove it.');
        $this->assertModelExists($superAdmin);

        $this->actingAs($superAdmin)->put("/admin/{$superAdmin->id}", [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'role' => 'admin',
            'location' => 'Palawan',
        ])->assertSessionHas('error');
        $this->assertSame(UserRole::SUPER_ADMIN, $superAdmin->fresh()->role);

        // Removing someone else still works
        $this->actingAs($superAdmin)->delete("/admin/{$other->id}")->assertSessionHas('success');
        $this->assertModelMissing($other);
    }

    public function test_admin_form_explains_that_an_admin_needs_a_location(): void
    {
        $superAdmin = $this->superAdmin();
        $payload = [
            '_admin_form' => 'create',
            'name' => 'No Location',
            'email' => 'no.location@fleet.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
            'location' => '',
            'phone_number' => '09170000000',
        ];

        $this->actingAs($superAdmin)->from('/admin')->post('/admin', $payload)
            ->assertRedirect('/admin')
            ->assertSessionHasErrors(['location' => 'Choose the province this Admin will manage.']);

        // The reopened Add Admin dialog shows the message next to the location field
        $this->actingAs($superAdmin)->from('/admin')->followingRedirects()->post('/admin', $payload)
            ->assertOk()
            ->assertSee('Choose the province this Admin will manage.');
        $this->assertDatabaseMissing('users', ['email' => 'no.location@fleet.test']);
    }

    public function test_driver_with_trip_or_report_history_cannot_be_deleted(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan');

        $withTrip = $this->driver('Palawan');
        $trip = $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => '26-10-501', 'confirmed_driver_id' => $withTrip->id]);

        $withReport = $this->driver('Palawan');
        VehicleIssueReport::create([
            'vehicle_id' => $vehicle->id,
            'plate_number' => $vehicle->plate_number,
            'driver_id' => $withReport->id,
            'driver_name' => $withReport->name,
            'issue_type' => 'Brake',
            'description' => 'Brake pedal feels soft.',
            'location' => 'Palawan',
        ]);

        $noHistory = $this->driver('Palawan');

        $this->actingAs($admin)->delete("/driver/{$withTrip->id}")->assertRedirect('/driver')->assertSessionHas('error');
        $this->actingAs($admin)->delete("/driver/{$withReport->id}")->assertSessionHas('error');
        $this->assertModelExists($withTrip);
        $this->assertModelExists($withReport);
        $this->assertSame($withTrip->id, $trip->fresh()->confirmed_driver_id);
        $this->assertSame(1, VehicleIssueReport::count());

        $this->actingAs($admin)->delete("/driver/{$noHistory->id}")->assertSessionHas('success');
        $this->assertModelMissing($noHistory);
    }

    public function test_plate_numbers_are_unique_regardless_of_spacing_dashes_or_case(): void
    {
        $admin = $this->admin('Palawan');
        $this->vehicle('Palawan', ['plate_number' => 'QAA 1002']);

        foreach (['QAA1002', 'qaa-1002', ' qaa  1002 '] as $plate) {
            $this->actingAs($admin)->post('/vehicles', $this->vehiclePayload($plate))
                ->assertSessionHasErrors(['plate_number' => 'This plate number is already registered as QAA 1002.']);
        }
        $this->assertSame(1, Vehicle::count());

        $this->actingAs($admin)->post('/vehicles', $this->vehiclePayload('nbc  4521'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vehicles', ['plate_number' => 'NBC 4521']);
    }

    public function test_license_numbers_are_unique_without_blocking_edits_to_existing_duplicates(): void
    {
        $admin = $this->admin('Palawan');
        $juan = $this->driver('Palawan', ['license_number' => 'D01-11-1111111']);

        $this->actingAs($admin)->post('/driver', $this->driverPayload([
            'email' => 'lorna@fleet.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'license_number' => 'd0111 1111111',
        ]))->assertSessionHasErrors('license_number');
        $this->assertDatabaseMissing('users', ['email' => 'lorna@fleet.test']);

        // A duplicate that already existed (live data has one) can still have its other details edited
        $legacy = $this->driver('Palawan', ['license_number' => $juan->license_number]);
        $this->actingAs($admin)->put("/driver/{$legacy->id}", $this->driverPayload([
            'name' => $legacy->name,
            'email' => $legacy->email,
            'license_number' => 'D01-11-1111111',
            'phone_number' => '09170000001',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('09170000001', $legacy->fresh()->phone_number);

        // ...but cannot be changed to another driver's number
        $other = $this->driver('Palawan', ['license_number' => 'N02-22-2222222']);
        $this->actingAs($admin)->put("/driver/{$legacy->id}", $this->driverPayload([
            'name' => $legacy->name,
            'email' => $legacy->email,
            'license_number' => 'N02 22 2222222',
        ]))->assertSessionHasErrors('license_number');
        $this->assertSame('N02-22-2222222', $other->fresh()->license_number);
    }

    public function test_quick_odometer_update_cannot_lower_the_reading(): void
    {
        $admin = $this->admin('Palawan');
        $vehicle = $this->vehicle('Palawan', ['odometer' => 1200]);

        $this->actingAs($admin)->patchJson("/vehicles/{$vehicle->id}/odometer", ['odometer' => 900])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['odometer' => 'The new reading cannot be lower than the current odometer (1,200 km).']);
        $this->assertEquals(1200, $vehicle->fresh()->odometer);

        $this->actingAs($admin)->patchJson("/vehicles/{$vehicle->id}/odometer", ['odometer' => 1250])->assertOk();
        $this->assertEquals(1250, $vehicle->fresh()->odometer);
    }

    public function test_profile_validates_driver_phone_and_birth_date(): void
    {
        $driver = $this->driver('Palawan');

        $this->actingAs($driver)->put('/my-profile', ['name' => $driver->name, 'phone_number' => '12345'])
            ->assertSessionHasErrors('phone_number');
        $this->actingAs($driver)->put('/my-profile', ['name' => $driver->name, 'phone_number' => '09171234567', 'birth_date' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors('birth_date');
        $this->actingAs($driver)->put('/my-profile', ['name' => $driver->name, 'phone_number' => '09179998888'])
            ->assertSessionHasNoErrors();
        $this->assertSame('09179998888', $driver->fresh()->phone_number);

        // Admins may use an office landline
        $admin = $this->admin('Palawan');
        $this->actingAs($admin)->put('/my-profile', ['name' => $admin->name, 'phone_number' => '(048) 433-1234'])
            ->assertSessionHasNoErrors();
    }

    public function test_account_menu_shows_settings_only_to_admins_and_log_out_to_everyone(): void
    {
        $this->actingAs($this->driver())->get('/my-profile')->assertOk()
            ->assertDontSee(route('content-settings'), false)
            ->assertSee('Log out');

        $this->actingAs($this->admin())->get('/my-profile')->assertOk()
            ->assertSee(route('content-settings'), false);
    }

    public function test_error_pages_explain_the_problem_in_plain_language(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Go to Dashboard');

        $this->actingAs($this->driver())->get('/admin')->assertForbidden()
            ->assertSee('Access not allowed')
            ->assertDontSee('This action is unauthorized');
    }

    public function test_trip_log_odometer_error_uses_the_form_wording(): void
    {
        $admin = $this->admin('Palawan');
        $trip = $this->tripRequest($this->vehicle('Palawan'), [
            'status' => 'approved',
            'trip_ticket_number' => '26-10-601',
            'travel_date' => now()->subDay()->toDateString(),
            'travel_date_end' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($admin)->postJson("/vehicle-requests/{$trip->id}/complete-trip", ['odo_beginning' => 15000, 'odo_ending' => 14900])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['odo_ending' => 'The odometer at the end of the trip cannot be lower than at the beginning (15000 km).']);
        $this->assertSame('approved', VehicleRequest::find($trip->id)->status);
    }

    private function vehiclePayload(string $plate): array
    {
        return [
            'plate_number' => $plate,
            'brand' => 'Toyota',
            'model' => 'Hiace',
            'color' => 'Silver',
            'odometer' => 1200,
            'vehicle_type' => 'Van',
            'fuel_type' => 'Diesel',
        ];
    }

    private function driverPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lorna Reyes',
            'email' => 'driver@fleet.test',
            'role' => 'driver',
            'phone_number' => '09175550103',
            'license_number' => 'X09-99-9999999',
            'license_expiration_date' => now()->addYear()->toDateString(),
        ], $overrides);
    }
}
