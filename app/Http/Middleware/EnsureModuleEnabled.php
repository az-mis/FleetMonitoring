<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Admin\Enums\UserRole;
use Modules\Settings\Models\FleetSetting;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the Super Admin's module switches (Content Settings > admin_modules) on the server.
 *
 * Page visits are let through so the layout can show its "Module Access Restricted" screen;
 * every state-changing or JSON request to a disabled module is refused.
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if ($user && $user->role !== UserRole::SUPER_ADMIN && ! $this->isEnabled($module)) {
            if (! $request->isMethodSafe() || $request->expectsJson()) {
                abort(403, 'This module has been disabled by the Super Admin.');
            }
        }

        return $next($request);
    }

    private function isEnabled(string $module): bool
    {
        try {
            $flags = FleetSetting::where('setting_key', 'admin_modules')->value('setting_value') ?? [];
        } catch (\Throwable) {
            return true; // Settings table not migrated yet: keep the default (enabled)
        }

        return (bool) ($flags[$module] ?? true);
    }
}
