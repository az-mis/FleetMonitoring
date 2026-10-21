<?php

declare(strict_types=1);

namespace App\Support;

use Modules\Settings\Models\FleetSetting;

/**
 * System Settings values (fleet_settings), read once per request. The logo, module switches and announcements
 * are needed by the layout, the sidebar, middleware and many partials; without this each of them queried the table.
 */
final class FleetSettings
{
    /** @var array<string, mixed> */
    private array $values = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, $this->values)) {
            $this->values[$key] = FleetSetting::query()->where('setting_key', $key)->value('setting_value');
        }

        return $this->values[$key] ?? $default;
    }

    /** Called whenever a setting is saved or deleted, so the rest of the request sees the new value */
    public function forget(): void
    {
        $this->values = [];
    }
}
