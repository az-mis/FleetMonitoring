<?php

declare(strict_types=1);

namespace Modules\Driver\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Enums\UserRole;
use Symfony\Component\HttpFoundation\Response;

/**
 * A driver's licence photo (a government ID), shown only to signed-in staff who manage that driver, and to the
 * driver. Photos are kept on the private disk; ones uploaded before that still sit on the public disk until
 * `php artisan fleet:secure-licence-photos` moves them.
 */
class DriverLicenseController extends Controller
{
    /** Where licence photos are kept */
    public const DISK = 'local';

    public function show(User $driver, string $side): Response
    {
        $user = auth()->user();
        if ($user?->id !== $driver->id) {
            Gate::authorize('manage-drivers');
            abort_unless($driver->role === UserRole::DRIVER, 404);
            abort_if($user->role === UserRole::ADMIN && (blank($user->location) || $driver->location !== $user->location), 403);
        }

        $path = $side === 'front' ? $driver->license_front : $driver->license_back;
        abort_unless(filled($path), 404);

        foreach ([self::DISK, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path, null, [
                    'Cache-Control' => 'private, max-age=600',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404);
    }
}
