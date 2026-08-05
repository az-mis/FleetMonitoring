<?php

declare(strict_types=1);

namespace Modules\Vehicle\Seeders;

use Illuminate\Database\Seeder;
use Modules\Vehicle\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        Vehicle::factory()
            ->count(50)
            ->create();
    }
}
