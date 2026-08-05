<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'role', 'avatar', 'birth_date', 'address', 'license_front', 'license_back', 'license_expiration_date'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $appends = ['avatar_url', 'license_front_url', 'license_back_url', 'age'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => \Modules\Admin\Enums\UserRole::class,
            'birth_date' => 'date',
            'license_expiration_date' => 'date',
        ];
    }

    protected function avatarUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
        );
    }

    protected function licenseFrontUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->license_front ? Storage::disk('public')->url($this->license_front) : null,
        );
    }

    protected function licenseBackUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->license_back ? Storage::disk('public')->url($this->license_back) : null,
        );
    }

    protected function age(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->birth_date ? $this->birth_date->age : null,
        );
    }
}
