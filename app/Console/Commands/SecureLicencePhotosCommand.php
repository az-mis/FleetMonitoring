<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Driver\Controllers\DriverLicenseController;

use function Laravel\Prompts\confirm;

/**
 * Moves driver licence photos uploaded before they were kept privately from the public disk (reachable by URL)
 * to the private disk. Paths stay the same, so nothing in the database changes. Safe to run more than once.
 */
class SecureLicencePhotosCommand extends Command
{
    protected $signature = 'fleet:secure-licence-photos {--force : Move without asking}';

    protected $description = 'Move driver licence photos from public storage to private storage';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk(DriverLicenseController::DISK);

        $paths = User::query()
            ->where(fn ($q) => $q->whereNotNull('license_front')->orWhereNotNull('license_back'))
            ->get(['license_front', 'license_back'])
            ->flatMap(fn (User $u) => [$u->license_front, $u->license_back])
            ->filter(fn (?string $path) => filled($path) && $public->exists($path))
            ->unique()
            ->values();

        if ($paths->isEmpty()) {
            $this->info('No licence photos left in public storage. Nothing to move.');

            return self::SUCCESS;
        }

        $this->line("{$paths->count()} licence photo(s) are in public storage (anyone with the link can open them).");
        if (! $this->option('force') && ! confirm('Move them to private storage now?', default: false)) {
            $this->warn('Nothing was moved.');

            return self::FAILURE;
        }

        $moved = 0;
        foreach ($paths as $path) {
            // Copy, check, then delete: a photo is never lost if a step fails
            if (! $private->exists($path)) {
                $private->put($path, $public->get($path));
            }
            if ($private->exists($path) && $private->size($path) === $public->size($path)) {
                $public->delete($path);
                $moved++;
            } else {
                $this->error("Could not move {$path}; it was left where it is.");
            }
        }

        $this->info("Moved {$moved} of {$paths->count()} licence photo(s) to private storage.");

        return $moved === $paths->count() ? self::SUCCESS : self::FAILURE;
    }
}
