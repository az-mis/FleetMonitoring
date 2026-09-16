<?php

declare(strict_types=1);

namespace Modules\Driver\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Enums\UserRole;

class DriverRepository implements DriverRepositoryInterface
{
    public function paginate(int $perPage = 10, ?string $search = null, ?string $location = null): LengthAwarePaginator
    {
        return User::query()
            ->with(['assignedVehicles'])
            ->where('role', UserRole::DRIVER->value)
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($search, fn($q) => $q->where(fn($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'phone_number' => $data['phone_number'] ?? null,
            'location' => $data['location'] ?? null,
            'avatar' => $this->storeFile($data['avatar'] ?? null, 'avatars'),
            'birth_date' => $data['birth_date'] ?? null,
            'address' => $data['address'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'license_front' => $this->storeFile($data['license_front'] ?? null, 'licenses'),
            'license_back' => $this->storeFile($data['license_back'] ?? null, 'licenses'),
            'license_expiration_date' => $data['license_expiration_date'] ?? null,
        ]);
    }

    public function update(User $driver, array $data): User
    {
        $driver->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'phone_number' => $data['phone_number'] ?? null,
            'location' => array_key_exists('location', $data) ? $data['location'] : $driver->location,
            'birth_date' => $data['birth_date'] ?? null,
            'address' => $data['address'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'license_expiration_date' => $data['license_expiration_date'] ?? null,
        ]);

        if (!empty($data['password'])) {
            $driver->password = Hash::make($data['password']);
        }

        if (!empty($data['avatar'])) {
            $this->deleteFile($driver->avatar);
            $driver->avatar = $this->storeFile($data['avatar'], 'avatars');
        } elseif (!empty($data['remove_avatar'])) {
            $this->deleteFile($driver->avatar);
            $driver->avatar = null;
        }

        if (!empty($data['license_front'])) {
            $this->deleteFile($driver->license_front);
            $driver->license_front = $this->storeFile($data['license_front'], 'licenses');
        }

        if (!empty($data['license_back'])) {
            $this->deleteFile($driver->license_back);
            $driver->license_back = $this->storeFile($data['license_back'], 'licenses');
        }

        $driver->save();

        return $driver;
    }

    public function delete(User $driver): bool
    {
        $this->deleteFile($driver->avatar);
        $this->deleteFile($driver->license_front);
        $this->deleteFile($driver->license_back);

        return (bool) $driver->delete();
    }

    private function storeFile(mixed $file, string $folder): ?string
    {
        if (!$file) {
            return null;
        }

        return $file->store($folder, 'public');
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
