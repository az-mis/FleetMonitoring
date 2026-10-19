<?php

declare(strict_types=1);

namespace Modules\Driver\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Driver\Repositories\DriverRepositoryInterface;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\VehicleRequest;

class DriverService
{
    public function __construct(
        private readonly DriverRepositoryInterface $repository,
    ) {}

    public function list(?string $search = null, ?string $location = null, int $perPage = 10, ?string $status = null): LengthAwarePaginator
    {
        return $this->repository->paginate(perPage: $perPage, search: $search, location: $location, status: $status);
    }

    public function createDriver(array $data): User
    {
        return $this->repository->create($data);
    }

    public function updateDriver(User $driver, array $data): User
    {
        return $this->repository->update($driver, $data);
    }

    /**
     * Ticket numbers of approved trips this driver still has to drive. A driver with upcoming trips
     * cannot be deactivated until those trips get another driver or are cancelled.
     */
    public function upcomingTrips(User $driver): Collection
    {
        return VehicleRequest::query()
            ->where('confirmed_driver_id', $driver->id)
            ->where('status', 'approved')
            ->whereDoesntHave('completion')
            ->whereRaw('COALESCE(travel_date_end, travel_date) >= ?', [today()->toDateString()])
            ->orderBy('travel_date')
            ->pluck('trip_ticket_number');
    }

    /**
     * Deactivate a driver: they can no longer sign in or be picked, and lose their default vehicles
     * (recorded in each vehicle's assignment history). Past trips and reports are kept.
     *
     * @return int The number of vehicles unassigned
     */
    public function deactivate(User $driver): int
    {
        return DB::transaction(function () use ($driver) {
            $vehicles = Vehicle::where('assigned_driver_id', $driver->id)->lockForUpdate()->get();

            foreach ($vehicles as $vehicle) {
                $vehicle->assignmentHistory()->whereNull('unassigned_at')->update(['unassigned_at' => now()]);
                $vehicle->update(['assigned_driver_id' => null]);
            }

            // A new remember token also ends "remember me" sign-ins on other devices
            $driver->forceFill([
                'deactivated_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            return $vehicles->count();
        });
    }

    public function reactivate(User $driver): void
    {
        $driver->forceFill(['deactivated_at' => null])->save();
    }

    public function deleteDriver(User $driver): bool
    {
        return $this->repository->delete($driver);
    }
}
