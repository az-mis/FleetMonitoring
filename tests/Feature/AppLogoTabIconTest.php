<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Settings\Models\FleetSetting;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

class AppLogoTabIconTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_without_a_custom_logo_the_tab_shows_the_mascot(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('favicon.ico?v=', false)
            ->assertSee('apple-touch-icon.png?v=', false);
    }

    public function test_uploading_a_logo_makes_square_tab_icons_used_on_every_page(): void
    {
        Storage::fake('public');
        $superAdmin = $this->superAdmin();

        // A wide logo still becomes a square icon with the whole logo inside
        $this->actingAs($superAdmin)
            ->post('/content-settings/logo', ['logo' => UploadedFile::fake()->image('logo.png', 400, 200)])
            ->assertRedirect()->assertSessionHas('success');

        $logo = FleetSetting::where('setting_key', 'app_logo')->value('setting_value');
        foreach (['icon' => 192, 'touch_icon' => 180] as $key => $size) {
            Storage::disk('public')->assertExists($logo[$key]);
            [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($logo[$key]));
            $this->assertSame([$size, $size], [$width, $height], $key);
        }

        $iconUrl = Storage::disk('public')->url($logo['icon']);
        $touchUrl = Storage::disk('public')->url($logo['touch_icon']);

        // Signed-in pages and the public pages (sign-in) both use it, and the mascot icon is gone
        $this->get('/my-profile')->assertOk()
            ->assertSee('<link rel="icon" type="image/png" href="'.$iconUrl.'"', false)
            ->assertSee('<link rel="apple-touch-icon" href="'.$touchUrl.'"', false)
            ->assertDontSee('favicon.ico?v=', false);
        $this->post('/logout');
        $this->get('/login')->assertOk()->assertSee($iconUrl, false)->assertDontSee('favicon.ico?v=', false);
    }

    public function test_replacing_and_removing_the_logo_cleans_up_its_icons(): void
    {
        Storage::fake('public');
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post('/content-settings/logo', ['logo' => UploadedFile::fake()->image('first.png', 300, 300)]);
        $first = FleetSetting::where('setting_key', 'app_logo')->value('setting_value');

        $this->post('/content-settings/logo', ['logo' => UploadedFile::fake()->image('second.jpg', 300, 300)]);
        $second = FleetSetting::where('setting_key', 'app_logo')->value('setting_value');
        foreach (['path', 'icon', 'touch_icon'] as $key) {
            Storage::disk('public')->assertMissing($first[$key]);
            Storage::disk('public')->assertExists($second[$key]);
        }

        $this->delete('/content-settings/logo')->assertRedirect();
        foreach (['path', 'icon', 'touch_icon'] as $key) {
            Storage::disk('public')->assertMissing($second[$key]);
        }
        $this->get('/my-profile')->assertOk()->assertSee('favicon.ico?v=', false);
    }

    public function test_a_logo_uploaded_before_icons_existed_is_used_as_is(): void
    {
        Storage::fake('public');
        FleetSetting::create(['setting_key' => 'app_logo', 'setting_value' => ['path' => 'branding/old-logo.png']]);
        $logoUrl = Storage::disk('public')->url('branding/old-logo.png');

        $this->get('/login')->assertOk()
            ->assertSee('<link rel="icon" href="'.$logoUrl.'"', false)
            ->assertSee('<link rel="apple-touch-icon" href="'.$logoUrl.'"', false)
            ->assertDontSee('favicon.ico?v=', false);
    }
}
