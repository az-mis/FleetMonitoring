<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Admin\Enums\UserRole;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleReport\Models\VehicleIssueReport;
use Modules\VehicleRequest\Models\VehicleRequest;

class DashboardService
{
    /**
     * Build the "Needs attention" alert cards.
     * Each item is clickable and links to its already-filtered page.
     */
    public function alerts(?string $location): array
    {
        $loc = fn ($query) => $query->when($location, fn ($q) => $q->where('location', $location));

        $oldestPending = $loc(VehicleRequest::query())->where('status', 'pending')->oldest()->first();
        $pendingCount = $loc(VehicleRequest::query())->where('status', 'pending')->count();

        $urgentReportsCount = $loc(VehicleIssueReport::query())
            ->whereIn('status', ['submitted', 'reviewed'])
            ->where('priority', 'urgent')
            ->count();
        $newReportsCount = $loc(VehicleIssueReport::query())->where('status', 'submitted')->count();

        $maintenanceThisWeek = $loc(VehicleMaintenance::query())
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->whereBetween('scheduled_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->count();
        $maintenanceOverdue = $loc(VehicleMaintenance::query())
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->whereDate('scheduled_date', '<', now()->toDateString())
            ->count();

        $expiringLicenses = $loc(User::query())
            ->where('role', UserRole::DRIVER)
            ->whereNotNull('license_expiration_date')
            ->whereDate('license_expiration_date', '<=', now()->addDays(30)->toDateString())
            ->count();

        $unconfirmedDriverTrips = $loc(VehicleRequest::query())
            ->where('status', 'approved')
            ->whereNull('confirmed_driver_id')
            ->whereDate('travel_date', '>=', now()->toDateString())
            ->count();

        $vehiclesWithoutDriver = $loc(Vehicle::query())->whereNull('assigned_driver_id')->count();

        $locationParam = $location ? ['location' => $location] : [];

        return array_values(array_filter([
            $pendingCount > 0 ? [
                'key' => 'pending_requests',
                'label' => 'Pending Requests',
                'value' => $pendingCount,
                'detail' => $oldestPending
                    ? 'Oldest waiting '.$oldestPending->created_at->diffForHumans(null, true)
                    : null,
                'severity' => $pendingCount > 5 ? 'high' : 'medium',
                'url' => route('vehicle-requests.index', $locationParam + ['status' => 'pending']),
            ] : null,
            ($urgentReportsCount + $newReportsCount) > 0 ? [
                'key' => 'vehicle_reports',
                'label' => 'Vehicle Reports',
                'value' => $urgentReportsCount + $newReportsCount,
                'detail' => $urgentReportsCount > 0 ? "{$urgentReportsCount} urgent" : 'New reports to review',
                'severity' => $urgentReportsCount > 0 ? 'high' : 'medium',
                'url' => route('vehicle-reports.index', $locationParam),
            ] : null,
            ($maintenanceThisWeek + $maintenanceOverdue) > 0 ? [
                'key' => 'maintenance',
                'label' => 'Maintenance Due',
                'value' => $maintenanceThisWeek + $maintenanceOverdue,
                'detail' => $maintenanceOverdue > 0 ? "{$maintenanceOverdue} overdue" : 'Starting this week',
                'severity' => $maintenanceOverdue > 0 ? 'high' : 'low',
                'url' => route('maintenance.index', $locationParam + ['status' => 'active']),
            ] : null,
            $expiringLicenses > 0 ? [
                'key' => 'expiring_licenses',
                'label' => 'Licenses Expiring',
                'value' => $expiringLicenses,
                'detail' => 'Within 30 days',
                'severity' => 'medium',
                'url' => route('driver.index', $locationParam),
            ] : null,
            $unconfirmedDriverTrips > 0 ? [
                'key' => 'unconfirmed_driver',
                'label' => 'Trips Missing a Driver',
                'value' => $unconfirmedDriverTrips,
                'detail' => 'Approved, no driver confirmed',
                'severity' => 'medium',
                'url' => route('vehicle-requests.index', $locationParam + ['status' => 'approved']),
            ] : null,
            $vehiclesWithoutDriver > 0 ? [
                'key' => 'unassigned_vehicles',
                'label' => 'Vehicles Without a Driver',
                'value' => $vehiclesWithoutDriver,
                'detail' => null,
                'severity' => 'low',
                'url' => route('vehicle-assigning.index', $locationParam),
            ] : null,
        ]));
    }

    /**
     * KPI cards with month-over-month comparison.
     */
    public function kpis(?string $location, bool $isSuperAdmin): array
    {
        $loc = fn ($query) => $query->when($location, fn ($q) => $q->where('location', $location));

        $startOfThisMonth = now()->startOfMonth();
        $startOfLastMonth = now()->subMonthNoOverflow()->startOfMonth();
        $endOfLastMonth = now()->subMonthNoOverflow()->endOfMonth();

        $pending = $loc(VehicleRequest::query())->where('status', 'pending')->count();
        $approvedTotal = $loc(VehicleRequest::query())->where('status', 'approved')->count();
        $approvedThisMonth = $loc(VehicleRequest::query())->where('status', 'approved')->where('created_at', '>=', $startOfThisMonth)->count();
        $approvedLastMonth = $loc(VehicleRequest::query())->where('status', 'approved')->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        $vehicleTotal = $loc(Vehicle::query())->count();
        $vehiclesOnTrip = $this->vehicleIdsOnTripToday($location)->count();
        $vehiclesInMaintenance = $this->vehicleIdsInMaintenanceToday($location)->count();
        $vehiclesAvailable = max(0, $vehicleTotal - $vehiclesOnTrip - $vehiclesInMaintenance);

        $driverTotal = $loc(User::query())->where('role', UserRole::DRIVER)->count();

        $cards = [
            [
                'key' => 'pending',
                'label' => 'Pending Requests',
                'value' => $pending,
                'icon' => 'clock',
                'color' => 'amber',
                'url' => route('vehicle-requests.index', ($location ? ['location' => $location] : []) + ['status' => 'pending']),
            ],
            [
                'key' => 'approved',
                'label' => 'Approved Trips',
                'value' => $approvedTotal,
                'icon' => 'check',
                'color' => 'emerald',
                'trend' => $this->trend($approvedThisMonth, $approvedLastMonth),
                'url' => route('vehicle-requests.index', ($location ? ['location' => $location] : []) + ['status' => 'approved']),
            ],
            [
                'key' => 'vehicles_available',
                'label' => 'Vehicles Available Today',
                'value' => $vehiclesAvailable,
                'sub' => "of {$vehicleTotal} total",
                'icon' => 'truck',
                'color' => 'blue',
                'url' => route('vehicles.index', $location ? ['location' => $location] : []),
            ],
            [
                'key' => 'drivers',
                'label' => 'Total Drivers',
                'value' => $driverTotal,
                'icon' => 'users',
                'color' => 'violet',
                'url' => route('driver.index', $location ? ['location' => $location] : []),
            ],
        ];

        if ($isSuperAdmin) {
            $cards[] = [
                'key' => 'admins',
                'label' => 'Admins',
                'value' => $loc(User::query())->where('role', UserRole::ADMIN)->count(),
                'icon' => 'shield',
                'color' => 'orange',
                'url' => route('admin.index', $location ? ['location' => $location] : []),
            ];
        }

        return $cards;
    }

    /**
     * Fleet status donut: Available / On trip / Under maintenance / Issue reported.
     */
    public function fleetStatus(?string $location): array
    {
        $loc = fn ($query) => $query->when($location, fn ($q) => $q->where('location', $location));

        $total = $loc(Vehicle::query())->count();
        $onTripIds = $this->vehicleIdsOnTripToday($location);
        $maintenanceIds = $this->vehicleIdsInMaintenanceToday($location);
        $issueIds = $loc(VehicleIssueReport::query())
            ->whereIn('status', ['submitted', 'reviewed', 'ongoing'])
            ->pluck('vehicle_id')
            ->unique();

        $onTrip = $onTripIds->count();
        $maintenance = $maintenanceIds->diff($onTripIds)->count();
        $issue = $issueIds->diff($onTripIds)->diff($maintenanceIds)->count();
        $available = max(0, $total - $onTrip - $maintenance - $issue);

        $locationParam = $location ? ['location' => $location] : [];

        return [
            'total' => $total,
            'segments' => [
                ['label' => 'Available', 'value' => $available, 'color' => '#10b981', 'url' => route('vehicles.index', $locationParam)],
                ['label' => 'On Trip', 'value' => $onTrip, 'color' => '#3b82f6', 'url' => route('vehicle-requests.index', $locationParam + ['status' => 'approved'])],
                ['label' => 'Under Maintenance', 'value' => $maintenance, 'color' => '#f59e0b', 'url' => route('maintenance.index', $locationParam + ['status' => 'active'])],
                ['label' => 'Issue Reported', 'value' => $issue, 'color' => '#ef4444', 'url' => route('vehicle-reports.index', $locationParam)],
            ],
        ];
    }

    /**
     * Today's trips departing/out today.
     */
    public function todaysTrips(?string $location): Collection
    {
        return VehicleRequest::query()
            ->with('vehicle')
            ->when($location, fn ($q) => $q->where('location', $location))
            ->where('status', 'approved')
            ->whereDate('travel_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('travel_date_end')->orWhereDate('travel_date_end', '>=', now()->toDateString());
            })
            ->orderBy('travel_date')
            ->limit(8)
            ->get();
    }

    protected function vehicleIdsOnTripToday(?string $location): Collection
    {
        return VehicleRequest::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->where('status', 'approved')
            ->whereDate('travel_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('travel_date_end')->orWhereDate('travel_date_end', '>=', now()->toDateString());
            })
            ->pluck('vehicle_id')
            ->unique();
    }

    protected function vehicleIdsInMaintenanceToday(?string $location): Collection
    {
        return VehicleMaintenance::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->whereDate('scheduled_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('scheduled_date_end')->orWhereDate('scheduled_date_end', '>=', now()->toDateString());
            })
            ->pluck('vehicle_id')
            ->unique();
    }

    protected function trend(int $current, int $previous): ?array
    {
        if ($previous === 0) {
            return $current > 0 ? ['direction' => 'up', 'percent' => 100] : null;
        }

        $percent = (int) round((($current - $previous) / $previous) * 100);

        return [
            'direction' => $percent >= 0 ? 'up' : 'down',
            'percent' => abs($percent),
        ];
    }
}
