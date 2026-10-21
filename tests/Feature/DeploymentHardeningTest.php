<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Admin\Enums\UserRole;
use Modules\Admin\Seeders\SuperAdminSeeder;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** Pre-deployment checks: sign-in features, password rule, seeders, rate limits */
class DeploymentHardeningTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_users_cannot_change_their_own_sign_in_email_or_reach_unused_two_factor_routes(): void
    {
        $driver = $this->driver('Palawan', ['email' => 'driver@fleet.test']);

        $this->actingAs($driver)->put('/user/profile-information', ['name' => 'X', 'email' => 'new@evil.test'])->assertNotFound();
        $this->assertSame('driver@fleet.test', $driver->fresh()->email);
        $this->actingAs($driver)->post('/user/two-factor-authentication')->assertNotFound();
        $this->get('/two-factor-challenge')->assertNotFound();
        // Changing one's own password stays available (My Profile)
        $this->actingAs($driver)->put('/user/password', [])->assertSessionHasErrorsIn('updatePassword', 'current_password');
    }

    public function test_passwords_need_twelve_characters_with_letters_and_numbers(): void
    {
        $super = $this->superAdmin();
        $account = fn (string $password, string $email) => [
            'name' => 'New Admin', 'email' => $email, 'role' => UserRole::ADMIN->value, 'location' => 'Palawan',
            'password' => $password, 'password_confirmation' => $password,
        ];

        foreach (['short1', 'onlylettersherenow', '123456789012'] as $i => $weak) {
            $this->actingAs($super)->post(route('admin.store'), $account($weak, "weak{$i}@fleet.test"))->assertSessionHasErrors('password');
        }
        $this->actingAs($super)->post(route('admin.store'), $account('Fleet2026Palawan', 'ok@fleet.test'))->assertSessionHasNoErrors();
        $this->assertTrue(User::where('email', 'ok@fleet.test')->exists());
    }

    public function test_sample_seeders_refuse_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--class' => SuperAdminSeeder::class, '--force' => true])->assertSuccessful();
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'superadmin@fleetmonitoring.test']);
        $this->assertSame(0, User::count());
    }

    public function test_the_forgot_password_form_is_rate_limited_per_address(): void
    {
        Notification::fake();
        config(['mail.default' => 'smtp']);

        for ($i = 1; $i <= 5; $i++) {
            $this->from('/forgot-password')->post('/forgot-password', ['email' => "nobody{$i}@fleet.test"])->assertRedirect('/forgot-password');
        }
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'nobody6@fleet.test'])->assertStatus(429);
    }

    public function test_the_public_destinations_lookup_is_rate_limited(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            $this->getJson('/api/destinations-by-location?location=Palawan')->assertOk();
        }
        $this->getJson('/api/destinations-by-location?location=Palawan')->assertStatus(429);
    }
}
