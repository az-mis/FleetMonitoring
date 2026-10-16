<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Enums\UserRole;
use Modules\ApprovingOfficer\Models\ApprovingOfficer;
use Modules\Destination\Models\Destination;
use Modules\FleetCard\Models\FleetCard;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleType;
use Modules\VehicleRequest\Models\VehicleRequest;
use RuntimeException;

/**
 * Seeds an isolated QA database with one account per role and a small fleet.
 * Run with: APP_ENV=qa php artisan db:seed --class=QaSeeder
 * All QA accounts use the password "password" (same convention as SuperAdminSeeder).
 */
class QaSeeder extends Seeder
{
    public function run(): void
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');
        if (! str_ends_with(strtolower($database), '_qa')) {
            throw new RuntimeException("QaSeeder only runs against a *_qa database (current: {$database}).");
        }

        $password = Hash::make('password');
        $user = fn (string $email, string $name, UserRole $role, ?string $location, array $extra = []) => User::forceCreate(array_merge([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'location' => $location,
            'email_verified_at' => now(),
        ], $extra));

        $superAdmin = $user('qa.superadmin@fleet.test', 'QA Super Admin', UserRole::SUPER_ADMIN, null);
        $palawanAdmin = $user('qa.admin.palawan@fleet.test', 'QA Admin Palawan', UserRole::ADMIN, 'Palawan', ['phone_number' => '09170000001']);
        $user('qa.admin.ormin@fleet.test', 'QA Admin Oriental Mindoro', UserRole::ADMIN, 'Oriental Mindoro', ['phone_number' => '09170000002']);

        $driverDefaults = fn (string $license) => [
            'phone_number' => '0918'.substr($license, -7),
            'license_number' => $license,
            'license_expiration_date' => now()->addYear()->toDateString(),
            'birth_date' => '1985-05-10',
            'address' => 'Puerto Princesa City, Palawan',
        ];
        $driverOne = $user('qa.driver.one@fleet.test', 'Juan Dela Cruz', UserRole::DRIVER, 'Palawan', $driverDefaults('D01-11-1111111'));
        $driverTwo = $user('qa.driver.two@fleet.test', 'Maria Clara Santos-Villanueva De Los Reyes', UserRole::DRIVER, 'Palawan', $driverDefaults('D01-22-2222222'));
        $orminDriver = $user('qa.driver.ormin@fleet.test', 'Pedro Penduko', UserRole::DRIVER, 'Oriental Mindoro', $driverDefaults('D01-33-3333333'));

        foreach (['Pick-up' => 'truck', 'SUV' => 'car', 'Van' => 'van'] as $name => $icon) {
            VehicleType::firstOrCreate(['slug' => str($name)->slug()->value()], ['name' => $name, 'icon' => $icon]);
        }

        $vehicle = fn (array $attributes) => Vehicle::create(array_merge([
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2022,
            'color' => 'White',
            'odometer' => 15000,
            'last_change_oil_odometer' => 12000,
            'change_oil_interval_km' => 5000,
            'vehicle_type' => 'Pick-up',
            'fuel_type' => 'diesel',
            'created_by' => $superAdmin->id,
        ], $attributes));

        $hilux = $vehicle(['plate_number' => 'QAA 1001', 'location' => 'Palawan', 'assigned_driver_id' => $driverOne->id]);
        $vehicle(['plate_number' => 'QAA 1002', 'location' => 'Palawan', 'brand' => 'Mitsubishi', 'model' => 'Montero Sport GLS 4x2 AT Premium Edition', 'vehicle_type' => 'SUV']);
        $vehicle(['plate_number' => 'QAA 1003', 'location' => 'Palawan', 'brand' => 'Nissan', 'model' => 'Urvan', 'vehicle_type' => 'Van', 'odometer' => 98000, 'last_change_oil_odometer' => 92000]);
        $orminVehicle = $vehicle(['plate_number' => 'QAB 2001', 'location' => 'Oriental Mindoro', 'assigned_driver_id' => $orminDriver->id]);

        foreach ([['Puerto Princesa City Hall', 5], ['Roxas Municipal Agriculture Office', 130], ['Brooke\'s Point', 190]] as [$name, $km]) {
            Destination::create(['name' => $name, 'location' => 'Palawan', 'distance_km' => $km, 'is_active' => true, 'created_by' => $palawanAdmin->id]);
        }

        foreach (['Palawan', 'Oriental Mindoro'] as $location) {
            ApprovingOfficer::create([
                'location' => $location,
                'signee1_name' => 'Dr. QA Approver',
                'signee1_title' => 'Regional Executive Director',
                'updated_by' => $superAdmin->id,
            ]);
        }

        FleetCard::create([
            'vehicle_id' => $hilux->id,
            'card_number' => 'QA-FC-0001',
            'card_name' => 'Palawan Fuel Card',
            'location' => 'Palawan',
            'initial_budget' => 20000,
            'rate_peso' => 100,
            'rate_km' => 5,
            'current_balance' => 20000,
            'created_by' => $palawanAdmin->id,
        ]);

        // Real requests always have an end date (both request forms require it); default to a one-day trip
        $request = fn (string $code, array $attributes) => VehicleRequest::create(array_merge([
            'travel_date_end' => $attributes['travel_date'] ?? null,
            'reference_code' => $code,
            'requester_name' => 'Ana Reyes',
            'location' => 'Palawan',
            'requester_office' => 'Regulatory Division',
            'requester_contact' => '09171234567',
            'requester_is_passenger' => true,
            'vehicle_id' => $hilux->id,
            'destination' => 'Puerto Princesa City Hall',
            'distance_km' => 5,
            'purpose' => 'Coordination meeting with the city agriculture office.',
            'passengers' => ['Ben Cruz'],
            'status' => 'pending',
        ], $attributes));

        $request('QAPEND1', ['travel_date' => now()->addDays(5)->toDateString()]);
        $request('QAPAST1', [
            'travel_date' => now()->subDays(3)->toDateString(),
            'status' => 'approved',
            'trip_ticket_number' => now()->format('y-m').'-901',
            'confirmed_driver_id' => $driverOne->id,
            'approved_by' => $palawanAdmin->id,
            'approved_at' => now()->subDays(4),
        ]);
        $request('QAORM01', [
            'location' => 'Oriental Mindoro',
            'vehicle_id' => $orminVehicle->id,
            'destination' => 'Calapan City',
            'travel_date' => now()->addDays(7)->toDateString(),
        ]);
    }
}
