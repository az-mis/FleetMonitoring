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
    private function authorizeAdmin(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true), 403);
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === UserRole::SUPER_ADMIN, 403);
    }

    public function edit(Request $request): View
    {
        $this->authorizeAdmin($request);
        $defaults = [
            'admin_modules' => ['vehicles' => true, 'vehicleAssigning' => true, 'vehicleRequests' => true, 'drivers' => true],
            'driver_modules' => ['showAssignedVehicle' => true],
            'admin_announcement' => ['enabled' => false, 'message' => '', 'history' => []],
            'driver_announcement' => ['enabled' => false, 'message' => '', 'history' => []],
        ];
        $settings = [];
        $settingModels = [];
        foreach ($defaults as $key => $default) {
            $model = FleetSetting::with('updatedBy')->where('setting_key', $key)->first();
            $settingModels[$key] = $model;
            $settings[$key] = array_replace($default, $model?->setting_value ?? []);
        }
        $appLogo = FleetSetting::where('setting_key', 'app_logo')->value('setting_value');
        $customLogoUrl = $appLogo['path'] ?? null ? \Illuminate\Support\Facades\Storage::disk('public')->url($appLogo['path']) : null;
        $isSuperAdmin = $request->user()?->role === UserRole::SUPER_ADMIN;
        return view('settings::content', compact('settings', 'settingModels', 'customLogoUrl', 'isSuperAdmin'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'admin_modules' => ['array'],
            'admin_modules.*' => ['boolean'],
            'driver_modules' => ['array'],
            'driver_modules.*' => ['boolean'],
        ]);
        if ($request->user()?->role !== UserRole::SUPER_ADMIN) {
            unset($data['admin_modules']);
        } else {
            $defaults = ['vehicles' => false, 'vehicleAssigning' => false, 'vehicleRequests' => false, 'drivers' => false];
            $data['admin_modules'] = array_replace($defaults, array_map('boolval', $data['admin_modules'] ?? []));
        }
        $data['driver_modules'] = ['showAssignedVehicle' => (bool) ($data['driver_modules']['showAssignedVehicle'] ?? false)];
        foreach ($data as $key => $value) {
            FleetSetting::updateOrCreate(['setting_key' => $key], ['setting_value' => $value, 'updated_by' => $request->user()->id]);
        }
        return back()->with('success', 'Module settings updated.');
    }

    public function updateToggle(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'group' => ['required', 'string', 'in:admin_modules,driver_modules'],
            'key' => ['required', 'string'],
            'value' => ['required', 'boolean'],
        ]);

        if ($data['group'] === 'admin_modules') {
            $this->authorizeSuperAdmin($request);
        }

        $groupKey = $data['group'];
        $itemKey = $data['key'];
        $val = (bool) $data['value'];

        $existing = FleetSetting::where('setting_key', $groupKey)->value('setting_value') ?? [];
        $existing[$itemKey] = $val;

        FleetSetting::updateOrCreate(
            ['setting_key' => $groupKey],
            [
                'setting_value' => $existing,
                'updated_by' => $request->user()->id,
            ]
        );

        $label = ucfirst(str_replace('_', ' ', $itemKey));
        $statusStr = $val ? 'enabled' : 'disabled';
        return back()->with('success', "Setting '{$label}' updated to {$statusStr}.");
    }

    public function toggleAnnouncement(Request $request, string $target): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless(in_array($target, ['driver', 'admin'], true), 404);

        if ($target === 'admin') {
            $this->authorizeSuperAdmin($request);
        }

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $key = $target . '_announcement';
        $existing = FleetSetting::where('setting_key', $key)->value('setting_value') ?? ['enabled' => false, 'message' => '', 'history' => []];
        $history = $existing['history'] ?? [];
        $newEnabled = (bool) $data['enabled'];

        if (!empty($existing['message'])) {
            array_unshift($history, [
                'message' => $existing['message'],
                'enabled' => $newEnabled,
                'updated_at' => now()->toIso8601String(),
                'updated_by_name' => $request->user()->name ?? 'Admin',
            ]);
            $history = array_slice($history, 0, 10);
        }

        $existing['enabled'] = $newEnabled;
        $existing['history'] = $history;

        FleetSetting::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $existing,
                'updated_by' => $request->user()->id,
            ]
        );

        $label = ucfirst($target) . ' Announcement Broadcast';
        $statusStr = $newEnabled ? 'enabled' : 'disabled';
        return back()->with('success', "{$label} is now {$statusStr}.");
    }

    public function updateAnnouncement(Request $request, string $target): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless(in_array($target, ['driver', 'admin'], true), 404);

        if ($target === 'admin') {
            $this->authorizeSuperAdmin($request);
        }

        $key = $target . '_announcement';
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $existing = FleetSetting::where('setting_key', $key)->value('setting_value') ?? ['enabled' => false, 'message' => '', 'history' => []];
        $history = $existing['history'] ?? [];

        $newMessage = trim($data['message'] ?? '');
        $enabled = (bool) ($existing['enabled'] ?? false);

        if (!empty($newMessage) && $newMessage !== ($existing['message'] ?? '')) {
            array_unshift($history, [
                'message' => $newMessage,
                'enabled' => $enabled,
                'updated_at' => now()->toIso8601String(),
                'updated_by_name' => $request->user()->name ?? 'Admin',
            ]);
            $history = array_slice($history, 0, 10);
        }

        $existing['message'] = $newMessage;
        $existing['history'] = $history;

        FleetSetting::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $existing,
                'updated_by' => $request->user()->id,
            ]
        );

        $label = ucfirst($target) . ' Announcement';
        return back()->with('success', "{$label} message saved successfully.");
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $this->authorize($request);
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
        ]);

        $existing = FleetSetting::where('setting_key', 'app_logo')->value('setting_value');
        if (!empty($existing['path']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($existing['path'])) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($existing['path']);
        }

        $path = $request->file('logo')->store('branding', 'public');
        FleetSetting::updateOrCreate(
            ['setting_key' => 'app_logo'],
            [
                'setting_value' => ['path' => $path],
                'updated_by' => $request->user()->id,
            ]
        );

        return back()->with('success', 'App logo updated successfully.');
    }

    public function removeLogo(Request $request): RedirectResponse
    {
        $this->authorize($request);
        $existing = FleetSetting::where('setting_key', 'app_logo')->value('setting_value');
        if (!empty($existing['path']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($existing['path'])) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($existing['path']);
        }

        FleetSetting::where('setting_key', 'app_logo')->delete();

        return back()->with('success', 'App logo reset to default.');
    }
}
