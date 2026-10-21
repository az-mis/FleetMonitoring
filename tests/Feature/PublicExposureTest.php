<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Admin\Enums\UserRole;
use Modules\VehicleRequest\Controllers\VehicleRequestController;
use Modules\VehicleRequest\Models\VehicleRequest;
use Modules\VehicleRequest\Requests\StoreVehicleRequest;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** Batch C: what the public can do and see (request limits, licence photos, trip-ticket links, headers) */
class PublicExposureTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private function publicRequest(int $vehicleId, string $from, string $to): TestResponse
    {
        return $this->post('/request-vehicle', [
            'form_token' => (string) Str::uuid(),
            'requesterName' => 'Ana Reyes',
            'location' => 'Palawan',
            'requesterContact' => '09171234567',
            'vehicleId' => $vehicleId,
            'destination' => 'City Hall',
            'travelDate' => $from,
            'travelDateEnd' => $to,
            'purpose' => 'Field validation',
        ]);
    }

    public function test_public_requests_are_capped_in_length_and_how_far_ahead(): void
    {
        $vehicle = $this->vehicle('Palawan');
        $day = fn (int $n) => now()->addDays($n)->toDateString();

        // Running to 2099 would block the vehicle for everyone
        $this->publicRequest($vehicle->id, $day(1), '2099-12-31')->assertSessionHasErrors('travelDateEnd');
        $this->publicRequest($vehicle->id, $day(1), $day(StoreVehicleRequest::MAX_TRIP_DAYS + 1))->assertSessionHasErrors('travelDateEnd');
        $this->publicRequest($vehicle->id, $day(StoreVehicleRequest::MAX_DAYS_AHEAD + 1), $day(StoreVehicleRequest::MAX_DAYS_AHEAD + 1))->assertSessionHasErrors('travelDate');
        $this->assertSame(0, VehicleRequest::count());

        // A full 30-day trip, starting as late as allowed, is fine
        $start = StoreVehicleRequest::MAX_DAYS_AHEAD;
        $this->publicRequest($vehicle->id, $day($start), $day($start + StoreVehicleRequest::MAX_TRIP_DAYS - 1))->assertSessionHasNoErrors();
        $this->assertSame(1, VehicleRequest::count());
    }

    public function test_licence_photos_are_private_and_shown_only_to_staff_who_manage_the_driver(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $palawanAdmin = $this->admin('Palawan');

        $this->actingAs($palawanAdmin)->post('/driver', [
            'name' => 'Lorna Reyes', 'email' => 'lorna@fleet.test', 'role' => 'driver', 'location' => 'Palawan',
            'password' => 'Fleet2026Palawan', 'password_confirmation' => 'Fleet2026Palawan',
            'phone_number' => '09175550103', 'license_number' => 'X09-99-9999999', 'license_expiration_date' => now()->addYear()->toDateString(),
            'license_front' => UploadedFile::fake()->image('front.jpg'),
        ])->assertSessionHasNoErrors();

        $driver = User::where('email', 'lorna@fleet.test')->firstOrFail();
        Storage::disk('local')->assertExists($driver->license_front);
        Storage::disk('public')->assertMissing($driver->license_front);

        $url = $driver->license_front_url;
        $this->assertStringContainsString('/driver/'.$driver->id.'/license/front', $url);

        $this->actingAs($palawanAdmin)->get($url)->assertOk();
        $this->actingAs($driver)->get($url)->assertOk();
        $this->actingAs($this->superAdmin())->get($url)->assertOk();
        $this->actingAs($this->admin('Romblon'))->get($url)->assertForbidden();
        $this->actingAs($this->driver('Palawan'))->get($url)->assertForbidden();
        auth()->logout();
        $this->get($url)->assertRedirect('/login');
    }

    public function test_old_public_licence_photos_still_show_and_the_command_moves_them(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('licenses/old.jpg', 'old-photo');
        $driver = $this->driver('Palawan', ['license_back' => 'licenses/old.jpg']);

        $this->actingAs($this->admin('Palawan'))->get($driver->license_back_url)->assertOk();

        $this->artisan('fleet:secure-licence-photos', ['--force' => true])->expectsOutputToContain('Moved 1 of 1')->assertSuccessful();
        Storage::disk('public')->assertMissing('licenses/old.jpg');
        $this->assertSame('old-photo', Storage::disk('local')->get('licenses/old.jpg'));
        $this->actingAs($this->admin('Palawan'))->get($driver->license_back_url)->assertOk();

        $this->artisan('fleet:secure-licence-photos', ['--force' => true])->expectsOutputToContain('Nothing to move')->assertSuccessful();
    }

    public function test_trip_ticket_links_from_check_status_expire(): void
    {
        $trip = $this->tripRequest($this->vehicle('Palawan'), ['status' => 'approved', 'trip_ticket_number' => '26-10-001']);

        $this->get(route('check-status', ['code' => $trip->reference_code]))->assertOk()->assertSee('expires=', false);

        $link = URL::temporarySignedRoute('trip-ticket', now()->addDays(VehicleRequestController::TRIP_TICKET_LINK_DAYS), $trip);
        $this->get($link)->assertOk();

        $this->travel(VehicleRequestController::TRIP_TICKET_LINK_DAYS + 1)->days();
        $this->get($link)->assertForbidden()->assertSee('This trip ticket link has expired');
        // Staff can still open it
        $this->actingAs($this->admin('Palawan'))->get("/trip-ticket/{$trip->id}")->assertOk();
    }

    public function test_responses_carry_security_headers(): void
    {
        $this->get('/login')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertSame(UserRole::ADMIN, $this->admin('Palawan')->role);
    }
}
