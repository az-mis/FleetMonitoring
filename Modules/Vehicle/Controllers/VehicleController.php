<?php

declare(strict_types=1);

namespace Modules\Vehicle\Controllers;

use App\Http\Controllers\Concerns\AuthorizesFleetAccess;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
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
    use AuthorizesFleetAccess;

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

        $perPage = min(max((int) request('per_page', 10), 1), 100);
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

        $inspections = $query->paginate(min(max((int) request('per_page', 10), 1), 100))->withQueryString();

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
    public function storeType(StoreVehicleTypeRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorizeSuperAdmin();

        $type = $this->vehicleService->createVehicleType($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'type' => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'slug' => $type->slug,
                    'icon' => $type->icon,
                ],
                'message' => "Vehicle type \"{$type->name}\" added.",
            ]);
        }

        return redirect()->route('vehicles.index', request()->only(['search', 'per_page', 'location']))
            ->with('success', "Vehicle type \"{$type->name}\" added.");
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $this->authorizeFleetLocation($vehicle->location);

        $this->vehicleService->updateVehicle($vehicle, $request->validated());

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $this->authorizeFleetLocation($vehicle->location);

        if (\Modules\VehicleRequest\Models\VehicleRequest::where('vehicle_id', $vehicle->id)->exists()) {
            return redirect()->route('vehicles.index')
                ->with('error', "{$vehicle->plate_number} has trip requests on record and cannot be deleted.");
        }

        $this->vehicleService->deleteVehicle($vehicle);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted.');
    }

    /**
     * Store new vehicle inspection checklist record
     */
    public function storeInspection(\Illuminate\Http\Request $request, Vehicle $vehicle): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();
        $this->authorizeFleetLocation($vehicle->location);

        $validated = $request->validate([
            'odometer_km'      => 'required|integer|min:0',
            'change_oil_done'  => 'nullable|string',
            'notes'            => 'nullable|string|max:1000',
            'items'            => 'required|array',
            'items.*.category' => 'required|string',
            'items.*.name'     => 'required|string',
            'items.*.status'   => 'required|in:pass,attention,fail',
            'items.*.remarks'  => 'nullable|string|max:255',
        ]);

        // FormData sends checkbox as "on" string; also handle true/1 for API calls
        $changeOilDone = !empty($validated['change_oil_done']) &&
            in_array($validated['change_oil_done'], ['on', 'true', '1', 1, true], true);
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
            // Also sync PMS Oil Change item if present
            $oilPmsItem = $vehicle->pmsItems()
                ->where(fn($q) => $q->where('item_name', 'like', '%oil%')->orWhere('item_name', 'like', '%Oil%'))
                ->first();
            if ($oilPmsItem) {
                $oilPmsItem->update([
                    'last_done_km'   => $newOdometer,
                    'last_done_date' => now()->toDateString(),
                ]);
            }
        }

        if (!empty($vehicleUpdates)) {
            $vehicle->update($vehicleUpdates);
        }

        // Bridge to Maintenance: if inspection overall_status is 'failed', auto-create a draft Maintenance record so admin can act on it
        if ($hasFail) {
            $failedItemNames = collect($validated['items'])
                ->where('status', 'fail')
                ->pluck('name')
                ->implode(', ');

            \Modules\Maintenance\Models\VehicleMaintenance::create([
                'vehicle_id'      => $vehicle->id,
                'plate_number'    => $vehicle->plate_number,
                'vehicle_label'   => trim("{$vehicle->brand} {$vehicle->model}"),
                'type'            => 'Repair',
                'title'           => "Inspection Failed: {$failedItemNames}",
                'description'     => "Flagged during vehicle inspection on " . now()->format('M j, Y') . ". Failed items: {$failedItemNames}." . (!empty($validated['notes']) ? " Notes: {$validated['notes']}" : ''),
                'status'          => 'scheduled',
                'priority'        => 'urgent',
                'scheduled_date'  => now()->toDateString(),
                'odometer'        => $newOdometer,
                'location'        => $vehicle->location,
                'created_by'      => auth()->id(),
                'created_by_name' => auth()->user()?->name,
            ]);
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
        $this->authorizeFleetLocation($vehicle->location);

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
     * Get PMS items for a vehicle (JSON)
     */
    public function indexPmsItems(Vehicle $vehicle): \Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();
        $this->authorizeFleetLocation($vehicle->location);

        // Ensure global templates that apply to this vehicle's type are synced
        $vehicle->syncPmsWithTemplates();

        $items = $vehicle->pmsItems()->latest()->get();

        return response()->json([
            'success' => true,
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'label' => trim("{$vehicle->brand} {$vehicle->model}"),
                'odometer' => $vehicle->odometer,
                'health_score' => $vehicle->health_score,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Store new PMS checklist item for a vehicle
     */
    public function storePmsItem(\Illuminate\Http\Request $request, Vehicle $vehicle): \Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();
        $this->authorizeFleetLocation($vehicle->location);

        $validated = $request->validate([
            'item_name'       => 'required|string|max:255',
            'category'        => 'required|in:replace,inspect,lubricate,other',
            'interval_km'     => 'nullable|integer|min:1',
            'interval_months' => 'nullable|integer|min:1',
            'last_done_km'    => 'nullable|integer|min:0',
            'last_done_date'  => 'nullable|date',
            'notes'           => 'nullable|string|max:500',
        ]);

        $item = $vehicle->pmsItems()->create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PMS item added.',
            'item'    => $item,
            'health_score' => $vehicle->fresh()->health_score,
        ]);
    }

    /**
     * Update an existing PMS checklist item
     */
    public function updatePmsItem(\Illuminate\Http\Request $request, Vehicle $vehicle, \Modules\Vehicle\Models\VehiclePmsItem $item): \Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();
        $this->authorizeFleetLocation($vehicle->location);
        abort_unless((int) $item->vehicle_id === (int) $vehicle->id, 404);

        $validated = $request->validate([
            'item_name'       => 'required|string|max:255',
            'category'        => 'required|in:replace,inspect,lubricate,other',
            'interval_km'     => 'nullable|integer|min:1',
            'interval_months' => 'nullable|integer|min:1',
            'last_done_km'    => 'nullable|integer|min:0',
            'last_done_date'  => 'nullable|date',
            'notes'           => 'nullable|string|max:500',
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'PMS item updated.',
            'item'    => $item->fresh(),
            'health_score' => $vehicle->fresh()->health_score,
        ]);
    }

    /**
     * Quick "Log Done" for a PMS item (updates last done km/date and vehicle odometer if higher)
     */
    public function logPmsItemDone(\Illuminate\Http\Request $request, Vehicle $vehicle, \Modules\Vehicle\Models\VehiclePmsItem $item): \Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();
        $this->authorizeFleetLocation($vehicle->location);
        abort_unless((int) $item->vehicle_id === (int) $vehicle->id, 404);

        $validated = $request->validate([
            'odometer_km' => 'nullable|integer|min:0',
            'done_date'   => 'nullable|date',
        ]);

        $doneKm = $validated['odometer_km'] ?? $vehicle->odometer;
        $doneDate = $validated['done_date'] ?? now()->toDateString();

        $item->update([
            'last_done_km'   => $doneKm,
            'last_done_date' => $doneDate,
        ]);

        if ($doneKm && $doneKm > $vehicle->odometer) {
            $vehicle->update(['odometer' => $doneKm]);
        }

        return response()->json([
            'success' => true,
            'message' => "Service logged for {$item->item_name}.",
            'item'    => $item->fresh(),
            'health_score' => $vehicle->fresh()->health_score,
        ]);
    }

    /**
     * Delete a PMS item
     */
    public function destroyPmsItem(Vehicle $vehicle, \Modules\Vehicle\Models\VehiclePmsItem $item): \Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();
        $this->authorizeFleetLocation($vehicle->location);
        abort_unless((int) $item->vehicle_id === (int) $vehicle->id, 404);

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'PMS item removed.',
            'health_score' => $vehicle->fresh()->health_score,
        ]);
    }

    /**
     * List all global PMS templates
     */
    public function indexPmsTemplates(): \Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();

        $templates = \Modules\Vehicle\Models\VehiclePmsTemplate::latest()->get();

        return response()->json([
            'success'   => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Store new global PMS template and immediately sync to matching vehicles
     */
    public function storePmsTemplate(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'item_name'        => 'required|string|max:255',
            'category'         => 'required|in:replace,inspect,lubricate,other',
            'interval_km'      => 'nullable|integer|min:1',
            'interval_months'  => 'nullable|integer|min:1',
            'applicable_types' => 'nullable|array',
            'notes'            => 'nullable|string|max:500',
        ]);

        $template = \Modules\Vehicle\Models\VehiclePmsTemplate::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        // Auto-sync this new template to all active vehicles matching applicable_types
        $vehicles = Vehicle::all();
        foreach ($vehicles as $vehicle) {
            $vehicle->syncPmsWithTemplates();
        }

        return response()->json([
            'success'  => true,
            'message'  => "PMS Rule \"{$template->item_name}\" saved and applied across fleet.",
            'template' => $template,
        ]);
    }

    /**
     * Delete a global PMS template and remove its generated vehicle items
     */
    public function destroyPmsTemplate(\Modules\Vehicle\Models\VehiclePmsTemplate $template): \Illuminate\Http\JsonResponse
    {
        $this->authorizeSuperAdmin();

        // Remove any vehicle PMS items spawned from this template
        \Modules\Vehicle\Models\VehiclePmsItem::where('template_id', $template->id)->delete();

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'PMS Rule removed and cleaned up across fleet.',
        ]);
    }

    /**
     * Quick Update Odometer (CarMind style: instant mileage sync)
     */
    public function updateOdometer(\Illuminate\Http\Request $request, Vehicle $vehicle): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $this->authorizeFleetLocation($vehicle->location);

        $validated = $request->validate([
            'odometer' => 'required|integer|min:0',
        ]);

        $vehicle->update([
            'odometer' => $validated['odometer'],
            'updated_by' => auth()->id(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Odometer updated to " . number_format($validated['odometer']) . " km.",
                'odometer' => $validated['odometer'],
                'health_score' => $vehicle->fresh()->health_score,
                'change_oil_status' => $vehicle->fresh()->change_oil_status,
            ]);
        }

        return back()->with('success', "Odometer for {$vehicle->plate_number} updated to " . number_format($validated['odometer']) . " km.");
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
