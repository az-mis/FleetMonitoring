<?php

declare(strict_types=1);

namespace Modules\Vehicle\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\Vehicle\Models\Vehicle;

class VehicleAssigningController
{
    public function index(Request $request): View
    {
        abort_unless(in_array($request->user()?->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true), 403);

        $userLocation = $request->user()?->role === UserRole::ADMIN ? $request->user()->location : null;
        $location = $request->input('location', $userLocation);
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);

        $query = Vehicle::with('assignedDriver')
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
            ->orderBy('name')
            ->get();

        return view('vehicle::assigning', [
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'totalVehicles' => $totalVehicles,
            'assignedCount' => $assignedCount,
            'unassignedCount' => $unassignedCount,
            'selectedLocation' => $location,
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless(in_array($request->user()?->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true), 403);
        abort_unless($request->user()?->role !== UserRole::ADMIN || $vehicle->location === $request->user()->location, 403);
        $data = $request->validate([
            'assigned_driver_id' => ['nullable', 'integer', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);
        if (!empty($data['assigned_driver_id']) && !User::whereKey($data['assigned_driver_id'])->where('role', UserRole::DRIVER)->exists()) {
            return back()->withErrors(['assigned_driver_id' => 'The selected user is not a driver.']);
        }

        $vehicle->update($data);
        return back()->with('success', 'Vehicle assignment updated.');
    }
}
