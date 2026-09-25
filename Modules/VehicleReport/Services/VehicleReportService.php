<?php

declare(strict_types=1);

namespace Modules\VehicleReport\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\VehicleReport\Models\VehicleIssueReport;
use Modules\Vehicle\Models\Vehicle;

class VehicleReportService
{
    /** Where a driver report should be once its linked maintenance record reaches a given status. */
    private const REPORT_STATUS_FOR_MAINTENANCE = [
        'scheduled' => 'maintenance_scheduled',
        'ongoing'   => 'ongoing',
        'completed' => 'resolved',
        'cancelled' => 'reviewed',
    ];

    /** Reports submitted by a single driver, newest first. */
    public function listForDriver(int $driverId): Collection
    {
        return VehicleIssueReport::where('driver_id', $driverId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /** All reports visible to an admin, optionally scoped to a location. */
    public function listForAdmin(?string $location = null): Collection
    {
        return VehicleIssueReport::with('vehicle')
            ->when($location, fn ($q) => $q->where('location', $location))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function counts(Collection $reports): array
    {
        return [
            'submitted' => $reports->where('status', 'submitted')->count(),
            'active'    => $reports->whereIn('status', ['reviewed', 'maintenance_scheduled', 'ongoing'])->count(),
            'resolved'  => $reports->where('status', 'resolved')->count(),
        ];
    }

    /** The vehicle a driver is permanently assigned to, if any. */
    public function assignedVehicleFor(User $driver): ?Vehicle
    {
        return Vehicle::where('assigned_driver_id', $driver->id)->first();
    }

    public function create(array $data, Vehicle $vehicle, User $driver): VehicleIssueReport
    {
        return VehicleIssueReport::create([
            ...$data,
            'vehicle_id'    => $vehicle->id,
            'plate_number'  => $vehicle->plate_number,
            'vehicle_label' => trim("{$vehicle->brand} {$vehicle->model}"),
            'driver_id'     => $driver->id,
            'driver_name'   => $driver->name,
            'status'        => 'submitted',
            'location'      => $vehicle->location,
        ]);
    }

    public function review(VehicleIssueReport $report, array $data, User $reviewer): VehicleIssueReport
    {
        $linked = ! empty($data['related_maintenance_id'])
            ? VehicleMaintenance::find($data['related_maintenance_id'])
            : null;

        $report->update([
            'status'                     => $data['status'],
            'admin_notes'                => $data['admin_notes'] ?: null,
            'related_maintenance_id'     => $linked?->id,
            'related_maintenance_title'  => $linked?->title,
            'reviewed_by'                => $reviewer->id,
            'reviewed_by_name'           => $reviewer->name,
        ]);

        return $report;
    }

    /**
     * Keep driver reports in step with the maintenance record they are linked to.
     *
     * The linked title is always refreshed. The report's progress status and the
     * "update for the driver" note are only changed when the maintenance status
     * actually moved ($statusChanged), so simply editing a record's details never
     * overwrites a note an admin wrote by hand. Dismissed reports are left alone.
     */
    public function syncFromMaintenance(VehicleMaintenance $maintenance, bool $statusChanged, ?User $actor = null): void
    {
        $target = self::REPORT_STATUS_FOR_MAINTENANCE[$maintenance->status] ?? null;

        VehicleIssueReport::where('related_maintenance_id', $maintenance->id)
            ->get()
            ->each(function (VehicleIssueReport $report) use ($maintenance, $statusChanged, $target, $actor): void {
                $changes = ['related_maintenance_title' => $maintenance->title];

                if ($statusChanged && $target && $report->status !== 'dismissed' && $report->status !== $target) {
                    $changes += [
                        'status'           => $target,
                        'admin_notes'      => $this->maintenanceNote($maintenance),
                        'reviewed_by'      => $actor?->id ?? $report->reviewed_by,
                        'reviewed_by_name' => $actor?->name ?? $report->reviewed_by_name,
                    ];
                }

                $report->update($changes);
            });
    }

    /** Called before a maintenance record is deleted so its reports are not left pointing at nothing. */
    public function releaseMaintenance(VehicleMaintenance $maintenance, ?User $actor = null): void
    {
        VehicleIssueReport::where('related_maintenance_id', $maintenance->id)
            ->get()
            ->each(function (VehicleIssueReport $report) use ($actor): void {
                $changes = ['related_maintenance_title' => null];

                if (! in_array($report->status, ['resolved', 'dismissed'], true)) {
                    $changes += [
                        'status'           => 'reviewed',
                        'admin_notes'      => 'The linked maintenance record was removed. Your report is back under review.',
                        'reviewed_by'      => $actor?->id ?? $report->reviewed_by,
                        'reviewed_by_name' => $actor?->name ?? $report->reviewed_by_name,
                    ];
                }

                $report->update($changes);
            });
    }

    private function maintenanceNote(VehicleMaintenance $maintenance): string
    {
        return match ($maintenance->status) {
            'completed' => "Maintenance completed: {$maintenance->title}"
                . ($maintenance->completed_date ? ' (' . $maintenance->completed_date->format('M j, Y') . ')' : ''),
            'ongoing'   => "Maintenance work is now ongoing: {$maintenance->title}",
            'cancelled' => "Maintenance was cancelled: {$maintenance->title}. Your report is back under review.",
            default     => "Maintenance has been scheduled: {$maintenance->title}",
        };
    }

    /** Maintenance records for the report's vehicle, for the "link existing maintenance" dropdown. */
    public function maintenanceOptionsForVehicle(int $vehicleId): Collection
    {
        return VehicleMaintenance::where('vehicle_id', $vehicleId)
            ->orderBy('scheduled_date', 'desc')
            ->get();
    }
}
