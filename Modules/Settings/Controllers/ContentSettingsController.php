<?php

declare(strict_types=1);

namespace Modules\Settings\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\Settings\Models\FleetSetting;

class ContentSettingsController
{
    private function authorize(Request $request): void
    {
        abort_unless($request->user()?->role === UserRole::SUPER_ADMIN, 403);
    }

    public function edit(Request $request): View
    {
        $this->authorize($request);
        $defaults = [
            'admin_modules' => ['vehicles' => true, 'vehicleAssigning' => true, 'vehicleRequests' => true, 'drivers' => true],
            'driver_modules' => ['showAssignedVehicle' => true],
            'admin_announcement' => ['enabled' => false, 'message' => ''],
            'driver_announcement' => ['enabled' => false, 'message' => ''],
        ];
        $settings = [];
        foreach ($defaults as $key => $default) {
            $settings[$key] = array_replace($default, FleetSetting::where('setting_key', $key)->value('setting_value') ?? []);
        }
        return view('settings::content', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize($request);
        $data = $request->validate([
            'admin_modules' => ['array'],
            'admin_modules.*' => ['boolean'],
            'driver_modules' => ['array'],
            'driver_modules.*' => ['boolean'],
            'admin_announcement' => ['array'],
            'admin_announcement.enabled' => ['boolean'],
            'admin_announcement.message' => ['nullable', 'string', 'max:500'],
            'driver_announcement' => ['array'],
            'driver_announcement.enabled' => ['boolean'],
            'driver_announcement.message' => ['nullable', 'string', 'max:500'],
        ]);
        $defaults = ['vehicles' => false, 'vehicleAssigning' => false, 'vehicleRequests' => false, 'drivers' => false];
        $data['admin_modules'] = array_replace($defaults, array_map('boolval', $data['admin_modules'] ?? []));
        $data['driver_modules'] = ['showAssignedVehicle' => (bool) ($data['driver_modules']['showAssignedVehicle'] ?? false)];
        foreach ($data as $key => $value) {
            FleetSetting::updateOrCreate(['setting_key' => $key], ['setting_value' => $value, 'updated_by' => $request->user()->id]);
        }
        return back()->with('success', 'Content settings updated.');
    }
}
