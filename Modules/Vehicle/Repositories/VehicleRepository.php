<?php

declare(strict_types=1);

namespace Modules\Vehicle\Repositories;

use Illuminate\Support\Facades\Storage;
use Modules\Vehicle\Models\Vehicle;

class VehicleRepository implements VehicleRepositoryInterface
{
    public function create(array $data): Vehicle
    {
        return Vehicle::create([
            'plate_number' => $data['plate_number'],
            'photo' => $this->storePhoto($data['photo'] ?? null),
            'chassis_number' => $data['chassis_number'],
            'engine_number' => $data['engine_number'],
            'brand' => $data['brand'],
            'model' => $data['model'],
            'year' => $data['year'],
            'color' => $data['color'],
            'odometer' => $data['odometer'],
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'fuel_type' => $data['fuel_type'] ?? null,
        ]);
    }

    private function storePhoto(mixed $file): ?string
    {
        if (!$file) {
            return null;
        }

        return $file->store('vehicles', 'public');
    }
}
