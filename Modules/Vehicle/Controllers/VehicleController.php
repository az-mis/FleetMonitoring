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
use Modules\Vehicle\Requests\StoreVehicleTypeRequest;
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

        $typeCounts = $this->vehicleService->typeCounts($location);

        return view('vehicle::index', [
            'vehicles' => $vehicles,
            'isSuperAdmin' => $isSuperAdmin,
            'selectedLocation' => $location,
            'vehicleTypes' => $this->vehicleService->vehicleTypes(),
            'typeCounts' => $typeCounts['counts'],
            'totalVehicleCount' => $typeCounts['total'],
        ]);
    }

    /**
     * Dedicated Vehicle Inspections Management Page
     * @throws AuthorizationException
     */
    public function inspectionsPage(): View
    {
        $this->authorizeSuperAdmin();

        $user = auth()->user();
        $isSuperAdmin = $user?->role === UserRole::SUPER_ADMIN;
        $location = $isSuperAdmin ? request('location') : $user?->location;

        $query = \Modules\Vehicle\Models\VehicleInspection::query()
            ->with(['vehicle', 'inspector:id,name'])
            ->latest('inspected_at');

        if ($location) {
            $query->whereHas('vehicle', fn ($q) => $q->where('location', $location));
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('vehicle', function ($vq) use ($search) {
                    $vq->where('plate_number', 'like', "%{$search}%")
                       ->orWhere('brand', 'like', "%{$search}%")
                       ->orWhere('model', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $inspections = $query->paginate((int) request('per_page', 10))->withQueryString();

        $allVehicles = Vehicle::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->orderBy('plate_number')
            ->get();

        return view('vehicle::inspections', [
            'inspections' => $inspections,
            'allVehicles' => $allVehicles,
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
    public function storeType(StoreVehicleTypeRequest $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $type = $this->vehicleService->createVehicleType($request->validated());

        return redirect()->route('vehicles.index', request()->only(['search', 'per_page', 'location']))
            ->with('success', "Vehicle type \"{$type->name}\" added.");
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
     * Store new vehicle inspection checklist record
     */
    public function storeInspection(\Illuminate\Http\Request $request, Vehicle $vehicle): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'odometer_km'     => 'required|integer|min:0',
            'change_oil_done' => 'nullable|boolean',
            'notes'           => 'nullable|string|max:1000',
            'items'           => 'required|array',
            'items.*.category' => 'required|string',
            'items.*.name'     => 'required|string',
            'items.*.status'   => 'required|in:pass,attention,fail',
            'items.*.remarks'  => 'nullable|string|max:255',
        ]);

        $changeOilDone = !empty($validated['change_oil_done']);
        $newOdometer = (int) $validated['odometer_km'];

        // Compute overall status (if any item failed -> failed, if attention -> needs_attention, else passed)
        $hasFail = false;
        $hasAttention = false;
        foreach ($validated['items'] as $item) {
            if ($item['status'] === 'fail') $hasFail = true;
            if ($item['status'] === 'attention') $hasAttention = true;
        }
        $overallStatus = $hasFail ? 'failed' : ($hasAttention ? 'needs_attention' : 'passed');

        // Create Inspection Header
        $inspection = \Modules\Vehicle\Models\VehicleInspection::create([
            'vehicle_id'      => $vehicle->id,
            'inspector_id'    => auth()->id(),
            'odometer_km'     => $newOdometer,
            'overall_status'  => $overallStatus,
            'change_oil_done' => $changeOilDone,
            'notes'           => $validated['notes'] ?? null,
            'inspected_at'    => now(),
        ]);

        // Save Detail Items
        foreach ($validated['items'] as $itemData) {
            \Modules\Vehicle\Models\VehicleInspectionItem::create([
                'inspection_id' => $inspection->id,
                'category'      => $itemData['category'],
                'item_name'     => $itemData['name'],
                'status'        => $itemData['status'],
                'remarks'       => $itemData['remarks'] ?? null,
            ]);
        }

        // Update Vehicle Odometer and Change Oil Odometer if done
        $vehicleUpdates = [];
        if ($newOdometer > $vehicle->odometer) {
            $vehicleUpdates['odometer'] = $newOdometer;
        }

        if ($changeOilDone) {
            $vehicleUpdates['last_change_oil_odometer'] = $newOdometer;
        }

        if (!empty($vehicleUpdates)) {
            $vehicle->update($vehicleUpdates);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Vehicle inspection recorded successfully.',
                'inspection' => $inspection->load('items', 'inspector'),
            ]);
        }

        return redirect()->route('vehicles.index')->with('success', "Inspection recorded for {$vehicle->plate_number}.");
    }

    /**
     * Get inspection history JSON for a vehicle
     */
    public function indexInspections(Vehicle $vehicle): \Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();

        $inspections = $vehicle->inspections()
            ->with(['items', 'inspector:id,name'])
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'label' => $vehicle->brand . ' ' . $vehicle->model,
                'odometer' => $vehicle->odometer,
                'change_oil_status' => $vehicle->change_oil_status,
            ],
            'inspections' => $inspections,
        ]);
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
