<?php

declare(strict_types=1);

namespace Modules\Vehicle\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Modules\Vehicle\Models\Vehicle;

class VehicleRepository implements VehicleRepositoryInterface
{
    public function paginate(int $perPage = 10, ?string $search = null, ?string $location = null): LengthAwarePaginator
    {
        return Vehicle::query()
            ->with(['assignedDriver', 'creator', 'updater'])
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($search, function ($q) use ($search) {
                $terms = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY);

                foreach ($terms as $term) {
                    $q->where(function ($inner) use ($term) {
                        $inner->where('plate_number', 'like', "%{$term}%")
                            ->orWhere('brand', 'like', "%{$term}%")
                            ->orWhere('model', 'like', "%{$term}%")
                            ->orWhere('chassis_number', 'like', "%{$term}%")
                            ->orWhere('engine_number', 'like', "%{$term}%")
                            ->orWhere('color', 'like', "%{$term}%")
                            ->orWhereRaw("CONCAT(brand, ' ', model) like ?", ["%{$term}%"]);
                    });
                }
            })
            ->latest()
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function create(array $data): Vehicle
    {
        $odometer = (int) ($data['odometer'] ?? 0);

        return Vehicle::create([
            'plate_number' => $data['plate_number'],
            'photo' => $this->storePhoto($data['photo'] ?? null),
            'chassis_number' => $data['chassis_number'] ?? null,
            'engine_number' => $data['engine_number'] ?? null,
            'brand' => $data['brand'],
            'model' => $data['model'],
            'year' => $data['year'],
            'color' => $data['color'],
            'odometer' => $odometer,
            'last_change_oil_odometer' => $odometer, // Basis odometer for oil change interval
            'change_oil_interval_km' => !empty($data['change_oil_interval_km']) ? (int) $data['change_oil_interval_km'] : 5000,
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'fuel_type' => $data['fuel_type'] ?? null,
            'location' => $data['location'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $newOdometer = (int) ($data['odometer'] ?? $vehicle->odometer);
        $updateFields = [
            'plate_number' => $data['plate_number'],
            'chassis_number' => $data['chassis_number'] ?? null,
            'engine_number' => $data['engine_number'] ?? null,
            'brand' => $data['brand'],
            'model' => $data['model'],
            'year' => $data['year'],
            'color' => $data['color'],
            'odometer' => $newOdometer,
            'change_oil_interval_km' => !empty($data['change_oil_interval_km']) ? (int) $data['change_oil_interval_km'] : 5000,
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'fuel_type' => $data['fuel_type'] ?? null,
            'location' => $data['location'] ?? null,
            'updated_by' => auth()->id(),
        ];

        // If the vehicle never had a baseline change-oil odometer recorded, initialize it to the current odometer
        if (empty($vehicle->last_change_oil_odometer) && $newOdometer > 0) {
            $updateFields['last_change_oil_odometer'] = $newOdometer;
        }

        $vehicle->fill($updateFields);

        if (!empty($data['photo'])) {
            $this->deletePhotoFile($vehicle->photo);
            $vehicle->photo = $this->storePhoto($data['photo']);
        } elseif (!empty($data['remove_photo'])) {
            $this->deletePhotoFile($vehicle->photo);
            $vehicle->photo = null;
        }

        $vehicle->save();

        return $vehicle;
    }

    public function delete(Vehicle $vehicle): bool
    {
        $photo = $vehicle->photo;
        $deleted = (bool) $vehicle->delete();

        // Remove the file only once the row is gone, so a failed delete keeps the photo
        if ($deleted) {
            $this->deletePhotoFile($photo);
        }

        return $deleted;
    }

    private function storePhoto(mixed $file): ?string
    {
        if (!$file) {
            return null;
        }

        return $file->store('vehicles', 'public');
    }

    private function deletePhotoFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
