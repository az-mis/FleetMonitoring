<?php

declare(strict_types=1);

namespace Modules\Admin\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Enums\UserRole;
use Modules\ApprovingOfficer\Models\ApprovingOfficer;

class AdminRepository implements AdminRepositoryInterface
{
    public function paginate(int $perPage = 10, ?string $search = null, ?string $location = null): LengthAwarePaginator
    {
        return User::query()
            ->whereIn('role', [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value])
            ->when($search, fn($q) => $q->where(fn($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($location, fn($q) => $q->where('location', $location))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'avatar' => $this->storeAvatar($data['avatar'] ?? null),
            'phone_number' => $data['phone_number'] ?? null,
            'location' => $data['role'] === UserRole::ADMIN->value ? ($data['location'] ?? null) : null,
        ]);
        if ($data['role'] === UserRole::ADMIN->value && !empty($data['location']) && array_key_exists('signee1_name', $data)) {
            ApprovingOfficer::query()->updateOrCreate(
                ['location' => $data['location']],
                [
                    'signee1_name' => $data['signee1_name'] ?? null,
                    'signee1_title' => $data['signee1_title'] ?? null,
                    'signee2_name' => $data['signee2_name'] ?? null,
                    'signee2_title' => $data['signee2_title'] ?? null,
                    'updated_by' => auth()->id(),
                ],
            );
        }

        return $user;
    }

    public function update(User $admin, array $data): User
    {
        $admin->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'phone_number' => $data['phone_number'] ?? null,
            'location' => $data['location'] ?? null,
        ]);

        if (!empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
        }

        if (!empty($data['avatar'])) {
            $this->deleteAvatarFile($admin->avatar);
            $admin->avatar = $this->storeAvatar($data['avatar']);
        } elseif (!empty($data['remove_avatar'])) {
            $this->deleteAvatarFile($admin->avatar);
            $admin->avatar = null;
        }

        $admin->save();

        if (!empty($data['location']) && array_key_exists('signee1_name', $data)) {
            ApprovingOfficer::query()->updateOrCreate(
                ['location' => $data['location']],
                [
                    'signee1_name' => $data['signee1_name'] ?? null,
                    'signee1_title' => $data['signee1_title'] ?? null,
                    'signee2_name' => $data['signee2_name'] ?? null,
                    'signee2_title' => $data['signee2_title'] ?? null,
                    'updated_by' => auth()->id(),
                ],
            );
        }

        return $admin;
    }

    public function delete(User $admin): bool
    {
        $this->deleteAvatarFile($admin->avatar);

        return (bool) $admin->delete();
    }

    private function storeAvatar(mixed $file): ?string
    {
        if (!$file) {
            return null;
        }

        return $file->store('avatars', 'public');
    }

    private function deleteAvatarFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
