<?php

declare(strict_types=1);

namespace Modules\Settings\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $customLogoUrl = $appLogo['path'] ?? null ? Storage::disk('public')->url($appLogo['path']) : null;
        $isSuperAdmin = $request->user()?->role === UserRole::SUPER_ADMIN;

        return view('settings::content', compact('settings', 'settingModels', 'customLogoUrl', 'isSuperAdmin'));
    }

    // Changing settings is for the Super Admin only: they apply to every area (regional admins see them read-only)
    public function update(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
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
        $this->authorizeSuperAdmin($request);
        $data = $request->validate([
            'group' => ['required', 'string', 'in:admin_modules,driver_modules'],
            // Only known switches may be written into the settings JSON
            'key' => ['required', 'string', $request->input('group') === 'admin_modules'
                ? 'in:vehicles,vehicleAssigning,vehicleRequests,drivers'
                : 'in:showAssignedVehicle'],
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
        $this->authorizeSuperAdmin($request);
        abort_unless(in_array($target, ['driver', 'admin'], true), 404);

        if ($target === 'admin') {
            $this->authorizeSuperAdmin($request);
        }

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $key = $target.'_announcement';
        $existing = FleetSetting::where('setting_key', $key)->value('setting_value') ?? ['enabled' => false, 'message' => '', 'history' => []];
        $history = $existing['history'] ?? [];
        $newEnabled = (bool) $data['enabled'];

        if (! empty($existing['message'])) {
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

        $label = ucfirst($target).' Announcement Broadcast';
        $statusStr = $newEnabled ? 'enabled' : 'disabled';

        return back()->with('success', "{$label} is now {$statusStr}.");
    }

    public function updateAnnouncement(Request $request, string $target): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        abort_unless(in_array($target, ['driver', 'admin'], true), 404);

        if ($target === 'admin') {
            $this->authorizeSuperAdmin($request);
        }

        $key = $target.'_announcement';
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $existing = FleetSetting::where('setting_key', $key)->value('setting_value') ?? ['enabled' => false, 'message' => '', 'history' => []];
        $history = $existing['history'] ?? [];

        $newMessage = trim($data['message'] ?? '');
        $enabled = (bool) ($existing['enabled'] ?? false);

        if (! empty($newMessage) && $newMessage !== ($existing['message'] ?? '')) {
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

        $label = ucfirst($target).' Announcement';

        return back()->with('success', "{$label} message saved successfully.");
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        // authorize() does not exist on this controller (it does not extend the base Controller),
        // which made every logo upload fail with a 500. The logo is global branding: super admin only.
        $this->authorizeSuperAdmin($request);
        $request->validate([
            // SVG is rejected by the image rule anyway (scriptable format), so it is not offered
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);

        $existing = FleetSetting::where('setting_key', 'app_logo')->value('setting_value');

        // Store the new file first so a failed upload never leaves the app without its current logo
        $path = $request->file('logo')->store('branding', 'public');
        FleetSetting::updateOrCreate(
            ['setting_key' => 'app_logo'],
            [
                // The browser-tab and home-screen icons are made from the logo too (see makeBrandIcons)
                'setting_value' => ['path' => $path] + $this->makeBrandIcons($path),
                'updated_by' => $request->user()->id,
            ]
        );

        if (($existing['path'] ?? null) !== $path) {
            $this->deleteLogoFiles($existing);
        }

        return back()->with('success', 'App logo updated successfully.');
    }

    public function removeLogo(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        $this->deleteLogoFiles(FleetSetting::where('setting_key', 'app_logo')->value('setting_value'));

        // A model delete, so the per-request settings cache is cleared too (FleetSetting::booted)
        FleetSetting::where('setting_key', 'app_logo')->first()?->delete();

        return back()->with('success', 'App logo reset to default.');
    }

    /**
     * Square PNG copies of the logo: 192px for the browser tab (transparent edges) and 180px for
     * phone home screens (on white, since iOS turns transparency black). A logo GD cannot read gets
     * none, and the tab then shows the logo file itself.
     *
     * @return array{icon?: string, touch_icon?: string}
     */
    private function makeBrandIcons(string $path): array
    {
        $disk = Storage::disk('public');
        $source = function_exists('imagecreatefromstring') ? @imagecreatefromstring((string) $disk->get($path)) : false;
        if ($source === false) {
            return [];
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $base = 'branding/'.pathinfo($path, PATHINFO_FILENAME);
        $icons = [];

        foreach (['icon' => [192, false], 'touch_icon' => [180, true]] as $key => [$size, $onWhite]) {
            $canvas = imagecreatetruecolor($size, $size);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, $onWhite ? imagecolorallocate($canvas, 255, 255, 255) : imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagealphablending($canvas, true);

            // Fit the whole logo inside the square, centred
            $scale = min($size / $width, $size / $height);
            $fitWidth = max(1, (int) round($width * $scale));
            $fitHeight = max(1, (int) round($height * $scale));
            imagecopyresampled($canvas, $source, intdiv($size - $fitWidth, 2), intdiv($size - $fitHeight, 2), 0, 0, $fitWidth, $fitHeight, $width, $height);

            ob_start();
            imagepng($canvas);
            $disk->put($icons[$key] = "{$base}-{$size}.png", (string) ob_get_clean());
        }

        return $icons;
    }

    /** The logo and the icons made from it */
    private function deleteLogoFiles(?array $setting): void
    {
        $files = array_filter([$setting['path'] ?? null, $setting['icon'] ?? null, $setting['touch_icon'] ?? null]);
        if ($files) {
            Storage::disk('public')->delete($files);
        }
    }
}
