<?php

namespace Tests\Feature\Concerns;

use App\Models\User;
use Modules\Admin\Enums\UserRole;
use Modules\FleetCard\Models\FleetCard;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\VehicleRequest;

trait CreatesFleetData
{
    protected function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['location' => null]);
    }

    protected function admin(string $location = 'Palawan'): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN, 'location' => $location]);
    }

    protected function driver(string $location = 'Palawan', array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRole::DRIVER,
            'location' => $location,
            'phone_number' => '09171234567',
            'license_number' => 'LIC-'.fake()->unique()->numerify('########'),
            'license_expiration_date' => now()->addYear()->toDateString(),
        ], $attributes));
    }

    protected function vehicle(string $location = 'Palawan', array $attributes = []): Vehicle
    {
        static $sequence = 0;
        $sequence++;

        return Vehicle::create(array_merge([
            'plate_number' => 'TST '.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT).fake()->unique()->numerify('##'),
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2022,
            'color' => 'White',
            'odometer' => 10000,
            'last_change_oil_odometer' => 8000,
            'change_oil_interval_km' => 5000,
            // With the test cards' ₱100 per litre this is ₱20 per km
            'fuel_economy_km_per_liter' => 5,
            'vehicle_type' => 'Pick-up',
            'fuel_type' => 'diesel',
            'location' => $location,
        ], $attributes));
    }

    protected function tripRequest(Vehicle $vehicle, array $attributes = []): VehicleRequest
    {
        return VehicleRequest::create(array_merge([
            'reference_code' => strtoupper(fake()->unique()->bothify('???????')),
            'requester_name' => 'Ana Reyes',
            'location' => $vehicle->location,
            'requester_office' => 'Regulatory Division',
            'requester_contact' => '09171234567',
            'requester_is_passenger' => true,
            'vehicle_id' => $vehicle->id,
            'destination' => 'City Hall',
            'distance_km' => 10,
            'travel_date' => now()->addDays(3)->toDateString(),
            'travel_date_end' => now()->addDays(3)->toDateString(),
            'purpose' => 'Coordination meeting',
            'status' => 'pending',
        ], $attributes));
    }

    protected function fleetCard(Vehicle $vehicle, array $attributes = []): FleetCard
    {
        return FleetCard::create(array_merge([
            'vehicle_id' => $vehicle->id,
            'card_number' => 'FC-'.fake()->unique()->numerify('######'),
            'card_name' => 'Fuel Card',
            'location' => $vehicle->location,
            'initial_budget' => 5000,
            'rate_peso' => 100,
            'rate_km' => 5,
            'current_balance' => 5000,
        ], $attributes));
    }

    protected function maintenance(Vehicle $vehicle, array $attributes = []): VehicleMaintenance
    {
        return VehicleMaintenance::create(array_merge([
            'vehicle_id' => $vehicle->id,
            'plate_number' => $vehicle->plate_number,
            'vehicle_label' => "{$vehicle->brand} {$vehicle->model}",
            'type' => 'Repair',
            'title' => 'Brake repair',
            'status' => 'scheduled',
            'priority' => 'normal',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'location' => $vehicle->location,
        ], $attributes));
    }
}
