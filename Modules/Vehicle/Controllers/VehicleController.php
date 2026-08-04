<?php

declare(strict_types=1);

namespace Modules\Vehicle\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\Vehicle\Requests\StoreVehicleRequest;
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

        return view('vehicle::index');
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $this->vehicleService->createVehicle($request->validated());

        return redirect()->route('vehicles.index')->with('success', 'Vehicle registered successfully.');
    }

    /**
     * @throws AuthorizationException
     */
    private function authorizeSuperAdmin(): void
    {
        if (auth()->user()?->role !== UserRole::SUPER_ADMIN) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }
}
