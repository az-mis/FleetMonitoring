<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Settings\Models\FleetSetting;
use Modules\VehicleRequest\Models\VehicleRequest;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class AuthProfileSettingsTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_login_and_logout(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);

        $this->post('/logout')->assertRedirect();
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $admin = $this->admin();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $admin->email, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => $admin->email, 'password' => 'wrong-password'])->assertStatus(429);
    }

    public function test_profile_update_with_avatar(): void
    {
        Storage::fake('public');
        $driver = $this->driver();

        $this->actingAs($driver)->put('/my-profile', [
            'name' => 'Updated Name', 'phone_number' => '09181112222', 'address' => 'Puerto Princesa',
            'avatar' => UploadedFile::fake()->image('me.png', 100, 100),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $driver->refresh();
        $this->assertSame('Updated Name', $driver->name);
        Storage::disk('public')->assertExists($driver->avatar);
    }

    public function test_profile_rejects_non_image_upload(): void
    {
        Storage::fake('public');

        $this->actingAs($this->driver())->put('/my-profile', [
            'name' => 'Name', 'phone_number' => '09181112222',
            'avatar' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('avatar');
    }

    public function test_super_admin_can_upload_and_remove_logo(): void
    {
        Storage::fake('public');
        $superAdmin = $this->superAdmin();

        // Previously both actions crashed with a 500 (undefined authorize() method)
        $this->actingAs($superAdmin)
            ->post('/content-settings/logo', ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)])
            ->assertRedirect()->assertSessionHas('success');
        $path = FleetSetting::where('setting_key', 'app_logo')->value('setting_value')['path'];
        Storage::disk('public')->assertExists($path);

        $this->actingAs($superAdmin)->delete('/content-settings/logo')->assertRedirect();
        Storage::disk('public')->assertMissing($path);

        $this->actingAs($this->admin())
            ->post('/content-settings/logo', ['logo' => UploadedFile::fake()->image('logo.png')])
            ->assertForbidden();
    }

    public function test_driver_travel_history_only_lists_own_trips(): void
    {
        $driver = $this->driver();
        $other = $this->driver();
        $vehicle = $this->vehicle();
        $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => 'MINE-1', 'confirmed_driver_id' => $driver->id, 'destination' => 'Roxas Agriculture Office']);
        $this->tripRequest($vehicle, ['status' => 'approved', 'trip_ticket_number' => 'THEIRS-1', 'confirmed_driver_id' => $other->id, 'destination' => 'Coron Port Authority']);

        $this->actingAs($driver)->get('/travel-history')->assertOk()
            ->assertSee('Roxas Agriculture Office')
            ->assertDontSee('Coron Port Authority');
        $this->actingAs($this->admin())->get('/travel-history')->assertForbidden();
    }

    public function test_vehicle_with_trip_history_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $vehicle = $this->vehicle();
        $this->tripRequest($vehicle);

        $this->actingAs($admin)->delete("/vehicles/{$vehicle->id}")->assertRedirect()->assertSessionHas('error');
        $this->assertNotNull($vehicle->fresh());
        $this->assertSame(1, VehicleRequest::count());

        $unused = $this->vehicle();
        $this->actingAs($admin)->delete("/vehicles/{$unused->id}")->assertSessionHas('success');
        $this->assertNull($unused->fresh());
    }

    public function test_unknown_record_ids_return_404(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put('/maintenance/999999', [])->assertNotFound();
        $this->actingAs($admin)->delete('/fleet-cards/999999')->assertNotFound();
        $this->actingAs($admin)->patch('/vehicle-requests/999999/status', ['status' => 'approved'])->assertNotFound();
    }
}
