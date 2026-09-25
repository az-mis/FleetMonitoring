<?php

declare(strict_types=1);

namespace Modules\Maintenance\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Maintenance\Requests\StoreMaintenanceRequest;
use Modules\Maintenance\Requests\UpdateMaintenanceRequest;
use Modules\Maintenance\Services\MaintenanceService;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleReport\Models\VehicleIssueReport;
use Modules\VehicleReport\Services\VehicleReportService;

class MaintenanceController extends Controller
{
    public function __construct(
        private readonly MaintenanceService $maintenanceService,
        private readonly VehicleReportService $vehicleReportService,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorizeAdminAccess();

        $user        = auth()->user();
        $isSuperAdmin = $user?->role === UserRole::SUPER_ADMIN;
        $location    = $isSuperAdmin ? request('location') : $user?->location;
        $status      = request('status', 'active');
        $perPage     = (int) request('per_page', 10);

        $records = $this->maintenanceService->list(
            search: request('search'),
            location: $location,
            status: $status,
            perPage: $perPage,
        );

        $counts = $this->maintenanceService->counts($location);

        $vehicles = Vehicle::orderBy('plate_number')->get();
        if ($location) {
            $vehicles = $vehicles->where('location', $location)->values();
        }

        // Vehicle Reports opens this page with a reportId to prefill the "Add Maintenance" modal.
        $sourceReport = null;
        if ($reportId = request('reportId')) {
            $sourceReport = VehicleIssueReport::find($reportId);
        }

        return view('maintenance::index', [
            'records'         => $records,
            'counts'          => $counts,
            'vehicles'        => $vehicles,
            'isSuperAdmin'    => $isSuperAdmin,
            'selectedLocation' => $location,
            'statusFilter'    => $status,
            'sourceReport'    => $sourceReport,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreMaintenanceRequest $request): RedirectResponse
    {
        $this->authorizeAdminAccess();

        $vehicle = Vehicle::findOrFail($request->validated('vehicle_id'));
        $maintenance = $this->maintenanceService->create($request->validated(), $vehicle);

        // If this record was created from a driver's Vehicle Report, link the two
        // together and move the report forward, mirroring the React behaviour.
        if ($reportId = $request->validated('source_report_id')) {
            if ($report = VehicleIssueReport::find($reportId)) {
                $report->update([
                    'status'                    => 'maintenance_scheduled',
                    'related_maintenance_id'    => $maintenance->id,
                    'related_maintenance_title' => $maintenance->title,
                    'admin_notes'               => "Maintenance has been created: {$maintenance->title}",
                    'reviewed_by'               => auth()->id(),
                    'reviewed_by_name'          => auth()->user()?->name,
                ]);
            }

            return redirect()->route('maintenance.index', request()->only(['search', 'location', 'status', 'per_page']))
                ->with('success', 'Maintenance created and linked to the driver report.');
        }

        return redirect()->route('maintenance.index', request()->only(['search', 'location', 'status', 'per_page']))
            ->with('success', 'Maintenance record added.');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateMaintenanceRequest $request, VehicleMaintenance $maintenance): RedirectResponse
    {
        $this->authorizeAdminAccess();

        $vehicle = Vehicle::findOrFail($request->validated('vehicle_id'));
        $record = $this->maintenanceService->update($maintenance, $request->validated(), $vehicle);

        // Reflect the change on the driver's report (e.g. completed maintenance -> resolved report).
        $this->vehicleReportService->syncFromMaintenance($record, $record->wasChanged('status'), auth()->user());

        return redirect()->route('maintenance.index', request()->only(['search', 'location', 'status', 'per_page']))
            ->with('success', 'Maintenance record updated.');
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(VehicleMaintenance $maintenance): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $this->vehicleReportService->releaseMaintenance($maintenance, auth()->user());
        $this->maintenanceService->delete($maintenance);

        return redirect()->route('maintenance.index', request()->only(['search', 'location', 'status', 'per_page']))
            ->with('success', 'Maintenance record deleted.');
    }

    // -------------------------------------------------------------------------

    private function authorizeAdminAccess(): void
    {
        if (! in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    private function authorizeSuperAdmin(): void
    {
        if (auth()->user()?->role !== UserRole::SUPER_ADMIN) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }
}
