<?php

declare(strict_types=1);

namespace Modules\Maintenance\Controllers;

use App\Http\Controllers\Concerns\AuthorizesFleetAccess;
use App\Http\Controllers\Concerns\ReturnsToList;
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
use Modules\VehicleRequest\Models\VehicleRequest;

class MaintenanceController extends Controller
{
    use AuthorizesFleetAccess;
    use ReturnsToList;

    public function __construct(
        private readonly MaintenanceService $maintenanceService,
        private readonly VehicleReportService $vehicleReportService,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function index(): View|RedirectResponse
    {
        $this->authorizeAdminAccess();

        $user = auth()->user();
        $isSuperAdmin = $user?->role === UserRole::SUPER_ADMIN;
        $location = $isSuperAdmin ? request('location') : $user?->location;
        $status = request('status', 'active');
        $perPage = min(max((int) request('per_page', 10), 1), 100);

        // A notification opens the list at the record it is about (?highlight_id=…): the filter and page that hold it
        $highlight = null;
        if ($highlightId = (int) request('highlight_id')) {
            $highlight = VehicleMaintenance::query()
                ->when(! $isSuperAdmin, fn ($q) => $q->where('location', $user?->location))
                ->find($highlightId);
            if ($highlight && ! request()->has('page')) {
                $status = $this->maintenanceService->statusShows($highlight, $status) ? $status : 'all';
                $location = $isSuperAdmin && $location && $location !== $highlight->location ? null : $location;
                $search = request('search');
                $page = $this->maintenanceService->pageOf($highlight->id, $search, $location, $status, $perPage);
                if ($page === null && $search) {
                    $search = null;
                    $page = $this->maintenanceService->pageOf($highlight->id, null, $location, $status, $perPage);
                }

                return redirect()->route('maintenance.index', array_filter([
                    'highlight_id' => $highlight->id,
                    'auto_edit' => request('auto_edit'),
                    'status' => $status,
                    'location' => $isSuperAdmin ? $location : null,
                    'search' => $search,
                    'per_page' => request('per_page'),
                    'page' => $page ?? 1,
                ]));
            }
        }

        $records = $this->maintenanceService->list(
            search: request('search'),
            location: $location,
            status: $status,
            perPage: $perPage,
        );
        if ($lastPage = $this->pageOutOfRange($records)) {
            return $lastPage;
        }

        $counts = $this->maintenanceService->counts($location);

        $vehicles = Vehicle::orderBy('plate_number')->get();
        if ($location) {
            $vehicles = $vehicles->where('location', $location)->values();
        }

        // Vehicle Reports opens this page with a reportId to prefill the "Add Maintenance" modal.
        $sourceReport = null;
        if ($reportId = request('reportId')) {
            $adminLocation = $this->fleetLocationScope();
            $sourceReport = VehicleIssueReport::query()
                ->when($adminLocation, fn ($q) => $q->where('location', $adminLocation))
                ->find($reportId);
        }

        return view('maintenance::index', [
            'records' => $records,
            'counts' => $counts,
            'vehicles' => $vehicles,
            'isSuperAdmin' => $isSuperAdmin,
            'selectedLocation' => $location,
            'statusFilter' => $status,
            'sourceReport' => $sourceReport,
            // The record a notification pointed to (null when it was deleted or is in another area)
            'highlight' => $highlight,
            'highlightMissing' => request('highlight_id') && ! $highlight,
            // Upcoming approved trips, so the schedule calendar can warn about clashes (decision D4)
            'approvedTrips' => VehicleRequest::query()
                ->where('status', 'approved')
                ->whereRaw('COALESCE(travel_date_end, travel_date) >= ?', [today()->toDateString()])
                ->when($location, fn ($q) => $q->where('location', $location))
                ->get(['vehicle_id', 'trip_ticket_number', 'travel_date', 'travel_date_end'])
                ->map(fn ($trip) => [
                    'vehicle_id' => $trip->vehicle_id,
                    'ticket' => $trip->trip_ticket_number,
                    'start' => $trip->travel_date->toDateString(),
                    'end' => ($trip->travel_date_end ?? $trip->travel_date)->toDateString(),
                ])->values(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreMaintenanceRequest $request): RedirectResponse
    {
        $this->authorizeAdminAccess();

        $vehicle = Vehicle::findOrFail($request->validated('vehicle_id'));
        $this->authorizeFleetLocation($vehicle->location);
        $maintenance = $this->maintenanceService->create($request->validated(), $vehicle);

        // If this record was created from a driver's Vehicle Report, link the two
        // together and move the report forward, mirroring the React behaviour.
        if ($reportId = $request->validated('source_report_id')) {
            // Only a report for the same vehicle can be linked
            if ($report = VehicleIssueReport::where('vehicle_id', $vehicle->id)->find($reportId)) {
                $report->update([
                    'status' => 'maintenance_scheduled',
                    'related_maintenance_id' => $maintenance->id,
                    'related_maintenance_title' => $maintenance->title,
                    'admin_notes' => "Maintenance has been created: {$maintenance->title}",
                    'reviewed_by' => auth()->id(),
                    'reviewed_by_name' => auth()->user()?->name,
                ]);
            }

            return $this->backToList('maintenance.index', request()->only(['search', 'location', 'status', 'per_page']))
                ->with('success', 'Maintenance created and linked to the driver report.'.$this->tripClashNote($maintenance));
        }

        return $this->backToList('maintenance.index', request()->only(['search', 'location', 'status', 'per_page']))
            ->with('success', 'Maintenance record added.'.$this->tripClashNote($maintenance));
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateMaintenanceRequest $request, VehicleMaintenance $maintenance): RedirectResponse
    {
        $this->authorizeAdminAccess();
        $this->authorizeFleetLocation($maintenance->vehicle?->location ?? $maintenance->location);

        $vehicle = Vehicle::findOrFail($request->validated('vehicle_id'));
        $this->authorizeFleetLocation($vehicle->location);
        $record = $this->maintenanceService->update($maintenance, $request->validated(), $vehicle);

        // Reflect the change on the driver's report (e.g. completed maintenance -> resolved report).
        $this->vehicleReportService->syncFromMaintenance($record, $record->wasChanged('status'), auth()->user());

        return $this->backToList('maintenance.index', request()->only(['search', 'location', 'status', 'per_page']))
            ->with('success', 'Maintenance record updated.'.$this->tripClashNote($record));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(VehicleMaintenance $maintenance): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $this->vehicleReportService->releaseMaintenance($maintenance, auth()->user());
        $this->maintenanceService->delete($maintenance);

        return $this->backToList('maintenance.index', request()->only(['search', 'location', 'status', 'per_page']))
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

    /**
     * Maintenance may be scheduled over an approved trip (decision D4), but the admin is told which trip
     * clashes so it can be moved or cancelled.
     */
    private function tripClashNote(VehicleMaintenance $maintenance): string
    {
        if (! in_array($maintenance->status, ['scheduled', 'ongoing'], true) || ! $maintenance->scheduled_date) {
            return '';
        }

        $start = $maintenance->scheduled_date->toDateString();
        $end = ($maintenance->scheduled_date_end ?? $maintenance->scheduled_date)->toDateString();
        $tickets = VehicleRequest::query()
            ->where('vehicle_id', $maintenance->vehicle_id)
            ->where('status', 'approved')
            ->whereDate('travel_date', '<=', $end)
            ->whereRaw('COALESCE(travel_date_end, travel_date) >= ?', [$start])
            ->pluck('trip_ticket_number')
            ->filter();

        if ($tickets->isEmpty()) {
            return '';
        }

        return ' Note: this vehicle also has approved trip '.$tickets->implode(', ').' on these dates. Move or cancel the trip if the vehicle cannot go.';
    }
}
