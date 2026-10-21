<?php

declare(strict_types=1);

namespace Modules\Vehicle\Seeders;

use Illuminate\Database\Seeder;
use Modules\Vehicle\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        // Sample data only: never on the live server (create the first account with php artisan fleet:create-super-admin)
        if (app()->isProduction()) {
            $this->command?->error('Refusing to seed 50 sample vehicles in production.');

            return;
        }

        Vehicle::factory()
            ->count(50)
            ->create();
    }
}
