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

        $location = $request->user()?->role === UserRole::ADMIN ? $request->user()->location : null;
        return view('vehicle::assigning', [
            'vehicles' => Vehicle::with('assignedDriver')->when($location, fn ($builder) => $builder->where('location', $location))->orderBy('plate_number')->get(),
            'drivers' => User::where('role', UserRole::DRIVER)->when($location, fn ($builder) => $builder->where('location', $location))->orderBy('name')->get(),
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
