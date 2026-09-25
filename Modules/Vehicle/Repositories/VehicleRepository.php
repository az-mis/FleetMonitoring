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
            'location' => $data['location'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $vehicle->fill([
            'plate_number' => $data['plate_number'],
            'chassis_number' => $data['chassis_number'],
            'engine_number' => $data['engine_number'],
            'brand' => $data['brand'],
            'model' => $data['model'],
            'year' => $data['year'],
            'color' => $data['color'],
            'odometer' => $data['odometer'],
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'fuel_type' => $data['fuel_type'] ?? null,
            'location' => $data['location'] ?? null,
            'updated_by' => auth()->id(),
        ]);

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
        $this->deletePhotoFile($vehicle->photo);

        return (bool) $vehicle->delete();
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
