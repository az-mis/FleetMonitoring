<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Enums\UserRole;
use Tests\TestCase;

/** php artisan fleet:create-super-admin: the first Super Admin on a new server */
class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_super_admin(): void
    {
        $this->artisan('fleet:create-super-admin', ['--name' => 'GSS Head', '--email' => 'GSS.Head@da.gov.ph'])
            ->expectsQuestion('Password', 'Str0ng-pass-2026')
            ->expectsQuestion('Type the password again', 'Str0ng-pass-2026')
            ->expectsConfirmation('The password you typed has 16 characters. Is that right?', 'yes')
            ->assertSuccessful();

        $user = User::where('email', 'gss.head@da.gov.ph')->firstOrFail();
        $this->assertSame(UserRole::SUPER_ADMIN, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Str0ng-pass-2026', $user->password));
    }

    public function test_a_wrong_character_count_creates_nothing(): void
    {
        // e.g. stray keystrokes kept by the hidden prompt: "Test1Test1234" when "Test1234" was meant
        $this->artisan('fleet:create-super-admin', ['--name' => 'GSS Head', '--email' => 'gss.head@da.gov.ph'])
            ->expectsQuestion('Password', 'Test1Test1234')
            ->expectsQuestion('Type the password again', 'Test1Test1234')
            ->expectsConfirmation('The password you typed has 13 characters. Is that right?', 'no')
            ->expectsOutputToContain('Nothing was created')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'gss.head@da.gov.ph']);
    }

    public function test_generate_makes_a_password_and_shows_it_once(): void
    {
        $this->artisan('fleet:create-super-admin', ['--name' => 'GSS Head', '--email' => 'gss.head@da.gov.ph', '--generate' => true])
            ->expectsOutputToContain('shown only this once')
            ->assertSuccessful();

        $user = User::where('email', 'gss.head@da.gov.ph')->firstOrFail();
        $this->assertSame(UserRole::SUPER_ADMIN, $user->role);
        $this->assertStringStartsWith('$2y$', $user->password);
    }

    public function test_it_refuses_a_taken_email_or_mismatched_password(): void
    {
        $existing = User::forceCreate(['name' => 'Someone', 'email' => 'taken@da.gov.ph', 'password' => Hash::make('original-pass'), 'role' => UserRole::ADMIN]);

        $this->artisan('fleet:create-super-admin', ['--name' => 'X', '--email' => 'taken@da.gov.ph'])
            ->expectsQuestion('Password', 'Str0ng-pass-2026')
            ->expectsQuestion('Type the password again', 'Str0ng-pass-2026')
            ->expectsConfirmation('The password you typed has 16 characters. Is that right?', 'yes')
            ->expectsOutputToContain('already exists')
            ->assertFailed();
        $this->assertSame(UserRole::ADMIN, $existing->fresh()->role);

        $this->artisan('fleet:create-super-admin', ['--name' => 'Y', '--email' => 'new@da.gov.ph'])
            ->expectsQuestion('Password', 'Str0ng-pass-2026')
            ->expectsQuestion('Type the password again', 'different-2026')
            ->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'new@da.gov.ph']);
    }
}
