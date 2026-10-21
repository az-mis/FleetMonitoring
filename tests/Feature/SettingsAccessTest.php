<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\FleetSetting;
use Tests\Feature\Concerns\CreatesFleetData;
use Tests\TestCase;

/** System Settings apply to every area, so only the Super Admin changes them; regional admins see them read-only */
class SettingsAccessTest extends TestCase
{
    use CreatesFleetData;
    use RefreshDatabase;

    public function test_regional_admins_see_driver_settings_but_cannot_change_them(): void
    {
        FleetSetting::create(['setting_key' => 'driver_announcement', 'setting_value' => ['enabled' => true, 'message' => 'Fuel cards renewed', 'history' => []]]);
        $admin = $this->admin('Palawan');

        $this->actingAs($admin)->get('/content-settings')->assertOk()
            ->assertSee('What drivers see')->assertSee('Fuel cards renewed')->assertSee('Set by the Super Admin for every area');

        $this->actingAs($admin)->post('/content-settings/toggle', ['group' => 'driver_modules', 'key' => 'showAssignedVehicle', 'value' => 0])->assertForbidden();
        $this->actingAs($admin)->post('/content-settings/announcement/driver', ['message' => 'Palawan only?'])->assertForbidden();
        $this->actingAs($admin)->post('/content-settings/announcement/driver/toggle', ['enabled' => 0])->assertForbidden();
        $this->actingAs($admin)->put('/content-settings', ['driver_modules' => ['showAssignedVehicle' => 0]])->assertForbidden();

        $this->assertSame('Fuel cards renewed', FleetSetting::where('setting_key', 'driver_announcement')->first()->setting_value['message']);
        $this->assertNull(FleetSetting::where('setting_key', 'driver_modules')->first());
    }

    public function test_the_super_admin_changes_driver_settings(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->post('/content-settings/toggle', ['group' => 'driver_modules', 'key' => 'showAssignedVehicle', 'value' => 0])->assertRedirect();
        $this->actingAs($super)->post('/content-settings/announcement/driver', ['message' => 'Region-wide notice'])->assertRedirect();

        $this->assertFalse(FleetSetting::where('setting_key', 'driver_modules')->first()->setting_value['showAssignedVehicle']);
        $this->assertSame('Region-wide notice', FleetSetting::where('setting_key', 'driver_announcement')->first()->setting_value['message']);
    }
}
