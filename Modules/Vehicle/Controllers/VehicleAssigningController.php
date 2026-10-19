<?php

declare(strict_types=1);

namespace Modules\Vehicle\Controllers;

use App\Http\Controllers\Concerns\AuthorizesFleetAccess;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\Vehicle\Models\Vehicle;

class VehicleAssigningController
{
    use AuthorizesFleetAccess;

    public function index(Request $request): View
    {
        $this->authorizeFleetAdmin();

        $isSuperAdmin = $request->user()?->role === UserRole::SUPER_ADMIN;
        $userLocation = $request->user()?->role === UserRole::ADMIN ? $request->user()->location : null;
        $location = $isSuperAdmin ? $request->input('location') : $userLocation;
        $search = $request->input('search');
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $query = Vehicle::with(['assignedDriver', 'assignmentHistory.assignedBy:id,name'])
            ->when($location, fn ($builder) => $builder->where('location', $location))
            ->when($search, function ($builder) use ($search) {
                $builder->where(function ($q) use ($search) {
                    $q->where('plate_number', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('assignedDriver', fn ($dq) => $dq->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('plate_number');

        $totalVehicles = (clone $query)->count();
        $assignedCount = (clone $query)->whereNotNull('assigned_driver_id')->count();
        $unassignedCount = (clone $query)->whereNull('assigned_driver_id')->count();

        $vehicles = $query->paginate($perPage)->withQueryString();

        $drivers = User::where('role', UserRole::DRIVER)
            ->when($userLocation, fn ($builder) => $builder->where('location', $userLocation))
            ->with(['assignedVehicles'])
            ->orderBy('name')
            ->get();

        return view('vehicle::assigning', [
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'totalVehicles' => $totalVehicles,
            'assignedCount' => $assignedCount,
            'unassignedCount' => $unassignedCount,
            'selectedLocation' => $location,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeFleetLocation($vehicle->location);

        $data = $request->validate([
            'assigned_driver_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (! empty($data['assigned_driver_id'])) {
            $driver = User::find($data['assigned_driver_id']);
            // Errors use the flash message: this page has no field-level error display
            if (! $driver || $driver->role !== UserRole::DRIVER) {
                return back()->with('error', 'The selected user is not a driver.');
            }

            if (! empty($vehicle->location) && ! empty($driver->location) && $driver->location !== $vehicle->location) {
                return back()->with('error', 'The selected driver is not assigned to '.$vehicle->location.'.');
            }

            if ($problem = $driver->licenceProblem()) {
                return back()->with('error', $problem);
            }
        }

        $newDriverId = $data['assigned_driver_id'] ?? null;

        DB::transaction(function () use ($vehicle, $newDriverId, $request) {
            // Keep a record of who held the vehicle and when (decision D7)
            if ((int) $vehicle->assigned_driver_id !== (int) $newDriverId) {
                $vehicle->assignmentHistory()->whereNull('unassigned_at')->update(['unassigned_at' => now()]);

                if ($newDriverId) {
                    $vehicle->assignmentHistory()->create([
                        'driver_id' => $newDriverId,
                        'driver_name' => User::whereKey($newDriverId)->value('name'),
                        'assigned_at' => now(),
                        'assigned_by' => $request->user()->id,
                    ]);
                }
            }

            $vehicle->update(['assigned_driver_id' => $newDriverId]);
        });

        return back()->with('success', 'Vehicle assignment updated.');
    }
}
