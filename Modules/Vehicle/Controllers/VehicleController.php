<?php

declare(strict_types=1);

namespace Modules\Vehicle\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Requests\StoreVehicleRequest;
use Modules\Vehicle\Requests\UpdateVehicleRequest;
use Modules\Vehicle\Services\VehicleService;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleService $vehicleService,
    ) {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorizeSuperAdmin();

        $user = auth()->user();
        $isSuperAdmin = $user?->role === UserRole::SUPER_ADMIN;
        $location = $isSuperAdmin ? request('location') : $user?->location;

        $perPage = (int) request('per_page', 10);
        $vehicles = $this->vehicleService->list(
            search: request('search'),
            location: $location,
            perPage: $perPage
        );

        return view('vehicle::index', [
            'vehicles' => $vehicles,
            'isSuperAdmin' => $isSuperAdmin,
            'selectedLocation' => $location,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $this->vehicleService->createVehicle($request->validated());

        return redirect()->route('vehicles.index')->with('success', 'Vehicle registered successfully.');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $this->vehicleService->updateVehicle($vehicle, $request->validated());

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $this->vehicleService->deleteVehicle($vehicle);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted.');
    }

    /**
     * @throws AuthorizationException
     */
    private function authorizeSuperAdmin(): void
    {
        if (!in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }
}
