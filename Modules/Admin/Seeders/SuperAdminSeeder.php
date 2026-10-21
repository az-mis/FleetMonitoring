<?php

declare(strict_types=1);

namespace Modules\Admin\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Enums\UserRole;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Sample data only: never on the live server (create the first account with php artisan fleet:create-super-admin)
        if (app()->isProduction()) {
            $this->command?->error('Refusing to seed the default Super Admin (password "password") in production.');

            return;
        }

        User::firstOrCreate(
            ['email' => 'superadmin@fleetmonitoring.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::SUPER_ADMIN,
                'email_verified_at' => now(),
            ]
        );
    }
}