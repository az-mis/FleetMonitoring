<?php

declare(strict_types=1);

namespace Modules\VehicleReport\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\VehicleReport\Models\VehicleIssueReport;
use Modules\VehicleReport\Requests\ReviewVehicleIssueReportRequest;
use Modules\VehicleReport\Requests\StoreVehicleIssueReportRequest;
use Modules\VehicleReport\Services\VehicleReportService;

class VehicleReportController extends Controller
{
    public function __construct(
        private readonly VehicleReportService $vehicleReportService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $isAdmin = in_array($user?->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true);
        $isSuperAdmin = $user?->role === UserRole::SUPER_ADMIN;

        $maintenanceByVehicle = collect();

        if (! $isAdmin) {
            $reports = $this->vehicleReportService->listForDriver($user->id);
            $assignedVehicle = $this->vehicleReportService->assignedVehicleFor($user);
        } else {
            $location = $isSuperAdmin ? null : $user?->location;
            $reports = $this->vehicleReportService->listForAdmin($location);
            $assignedVehicle = null;
            $maintenanceByVehicle = VehicleMaintenance::orderBy('scheduled_date', 'desc')
                ->get()
                ->groupBy('vehicle_id');
        }

        return view('vehiclereport::index', [
            'reports'              => $reports,
            'counts'               => $this->vehicleReportService->counts($reports),
            'isAdmin'              => $isAdmin,
            'isSuperAdmin'         => $isSuperAdmin,
            'assignedVehicle'      => $assignedVehicle,
            'maintenanceByVehicle' => $maintenanceByVehicle,
        ]);
    }

    public function store(StoreVehicleIssueReportRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $vehicle = $this->vehicleReportService->assignedVehicleFor($user);

        if (! $vehicle) {
            return back()->with('error', 'You do not have a permanently assigned vehicle yet.');
        }

        $this->vehicleReportService->create($request->validated(), $vehicle, $user);

        return redirect()->route('vehicle-reports.index')
            ->with('success', 'Vehicle issue report submitted for admin review.');
    }

    /**
     * @throws AuthorizationException
     */
    public function review(ReviewVehicleIssueReportRequest $request, VehicleIssueReport $vehicleReport): RedirectResponse
    {
        $user = auth()->user();

        if (! in_array($user?->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $this->vehicleReportService->review($vehicleReport, $request->validated(), $user);

        return redirect()->route('vehicle-reports.index')
            ->with('success', 'Driver report updated.');
    }

    /**
     * @throws AuthorizationException
     */
    public function createMaintenance(VehicleIssueReport $vehicleReport): RedirectResponse
    {
        $user = auth()->user();

        if (! in_array($user?->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        return redirect()->route('maintenance.index', ['reportId' => $vehicleReport->id]);
    }
}
