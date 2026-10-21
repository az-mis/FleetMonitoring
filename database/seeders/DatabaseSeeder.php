<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Vehicle\Seeders\VehicleSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Sample data only: never on the live server (create the first account with php artisan fleet:create-super-admin)
        if (app()->isProduction()) {
            $this->command?->error('Refusing to seed the test user and sample vehicles in production.');

            return;
        }

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(VehicleSeeder::class);
    }
}
