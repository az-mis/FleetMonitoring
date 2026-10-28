<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class RememberMeTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    private function recaller(): string
    {
        return Auth::guard('web')->getRecallerName();
    }

    public function test_sign_in_page_offers_remember_me_unticked(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('name="remember"', false)
            ->assertSee('Remember me')
            ->assertDontSee('name="remember" value="1" checked', false);
    }

    public function test_remember_me_keeps_the_browser_signed_in_for_thirty_days(): void
    {
        $admin = $this->admin();

        $response = $this->post('/login', ['email' => $admin->email, 'password' => 'password', 'remember' => '1'])
            ->assertRedirect('/dashboard')
            ->assertCookie($this->recaller());

        $expires = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === $this->recaller())->getExpiresTime();
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $expires, 120);
        $this->assertNotNull($admin->fresh()->remember_token);
    }

    public function test_without_remember_me_the_sign_in_ends_with_the_session(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/dashboard')
            ->assertCookieMissing($this->recaller());
    }

    public function test_changing_the_password_ends_remember_me_on_other_devices_but_not_this_one(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['remember_token' => 'old-token'])->save();
        $change = ['current_password' => 'password', 'password' => 'NewSecret123', 'password_confirmation' => 'NewSecret123'];

        // This browser was remembered: it gets a fresh cookie for the new token
        $this->actingAs($admin)
            ->withCookie($this->recaller(), $admin->id.'|old-token|'.$admin->password)
            ->from('/my-profile')->put('/user/password', $change)
            ->assertSessionHasNoErrors()
            ->assertCookie($this->recaller());

        $token = $admin->fresh()->remember_token;
        $this->assertNotSame('old-token', $token);
        $this->assertNotEmpty($token);
    }

    public function test_changing_the_password_without_remember_me_sets_no_cookie(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['remember_token' => 'old-token'])->save();

        $this->actingAs($admin)->from('/my-profile')->put('/user/password', [
            'current_password' => 'password', 'password' => 'NewSecret123', 'password_confirmation' => 'NewSecret123',
        ])->assertSessionHasNoErrors()->assertCookieMissing($this->recaller());

        $this->assertNotSame('old-token', $admin->fresh()->remember_token);
    }
}
