<?php

declare(strict_types=1);

namespace Modules\Admin\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Enums\UserRole;

class AdminRepository implements AdminRepositoryInterface
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return User::query()
            ->where('role', UserRole::ADMIN->value)
            ->when($search, fn($q) => $q->where(fn($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
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
            'avatar' => $this->storeAvatar($data['avatar'] ?? null),
        ]);
    }

    public function update(User $admin, array $data): User
    {
        $admin->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
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