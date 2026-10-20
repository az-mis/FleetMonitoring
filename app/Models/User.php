<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Enums\UserRole;
use Modules\Authentication\Notifications\ResetPasswordNotification;
use Modules\Vehicle\Models\Vehicle;

#[Fillable(['name', 'email', 'password', 'role', 'avatar', 'phone_number', 'location', 'birth_date', 'address', 'license_number', 'license_front', 'license_back', 'license_expiration_date'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const DEACTIVATED_MESSAGE = 'This account has been deactivated. Please contact your fleet administrator.';

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
            'role' => UserRole::class,
            'birth_date' => 'date',
            'license_expiration_date' => 'date',
            'deactivated_at' => 'datetime',
        ];
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
        );
    }

    protected function licenseFrontUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->license_front ? Storage::disk('public')->url($this->license_front) : null,
        );
    }

    protected function licenseBackUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->license_back ? Storage::disk('public')->url($this->license_back) : null,
        );
    }

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->birth_date ? $this->birth_date->age : null,
        );
    }

    /** Deactivated drivers cannot sign in or be given new trips, but keep their history (decision D10). */
    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    /**
     * "Forgot password?" email. A deactivated account gets none (it could not sign in anyway); the page still
     * shows the same answer, so nobody learns which accounts exist or are deactivated.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        if ($this->isActive()) {
            $this->notify(new ResetPasswordNotification($token));
        }
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('deactivated_at');
    }

    public function assignedVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'assigned_driver_id');
    }

    /**
     * Why this driver may not be given a trip or vehicle through $lastDay: deactivated, or a licence problem.
     */
    public function drivingProblem(\DateTimeInterface|string|null $lastDay = null): ?string
    {
        if (! $this->isActive()) {
            return "{$this->name} is deactivated. Reactivate the driver in Drivers or choose another driver.";
        }

        return $this->licenceProblem($lastDay);
    }

    /**
     * Why this driver may not drive through $lastDay (default: today), or null when the licence is valid
     * through that day. A missing expiry date is not blocked here; the Drivers form requires one.
     */
    public function licenceProblem(\DateTimeInterface|string|null $lastDay = null): ?string
    {
        $expiry = $this->license_expiration_date;
        if (! $expiry) {
            return null;
        }

        $date = $expiry->format('M j, Y');
        if ($expiry->lt(today())) {
            return "{$this->name}'s driver's licence expired on {$date}. Update the licence in Drivers or choose another driver.";
        }
        if ($lastDay && $expiry->lt(Carbon::parse($lastDay)->startOfDay())) {
            return "{$this->name}'s driver's licence expires on {$date}, before this trip ends. Update the licence in Drivers or choose another driver.";
        }

        return null;
    }
}
