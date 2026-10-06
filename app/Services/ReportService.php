<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\VehicleRequest;

class ReportService
{
    public const RANGES = ['week', 'month', 'quarter', 'year', 'custom'];

    /**
     * Resolve a range key (+ optional custom from/to) into [start, end] Carbon dates.
     */
    public function resolveRange(string $range, ?string $from = null, ?string $to = null): array
    {
        return match ($range) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : now()->subMonths(3)->startOfDay(),
                $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay(),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    public function requestsPerMonth(?string $location, int $months = 6): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $rows = VehicleRequest::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, count(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $data[] = (int) ($rows[$key] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function topDestinations(?string $location, $start = null, $end = null, int $limit = 6): array
    {
        $requests = VehicleRequest::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($start && $end, fn ($q) => $q->whereBetween('travel_date', [$start, $end]))
            ->pluck('destination');

        $townCounts = [];

        foreach ($requests as $destStr) {
            if (!$destStr) continue;

            $parts = str_contains($destStr, ';;')
                ? explode(';;', $destStr)
                : explode(',', $destStr);

            foreach ($parts as $part) {
                $town = trim($part);
                if ($town === '') continue;
                $townCounts[$town] = ($townCounts[$town] ?? 0) + 1;
            }
        }

        arsort($townCounts);
        $topTowns = array_slice($townCounts, 0, $limit, true);

        return ['labels' => array_keys($topTowns), 'data' => array_values($topTowns)];
    }

    public function tripsPerVehicle(?string $location, $start = null, $end = null, int $limit = 8): array
    {
        $rows = VehicleRequest::query()
            ->join('vehicles', 'vehicles.id', '=', 'vehicle_requests.vehicle_id')
            ->when($location, fn ($q) => $q->where('vehicle_requests.location', $location))
            ->when($start && $end, fn ($q) => $q->whereBetween('vehicle_requests.travel_date', [$start, $end]))
            ->where('vehicle_requests.status', 'approved')
            ->selectRaw('vehicles.plate_number, count(*) as total')
            ->groupBy('vehicles.plate_number')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'plate_number');

        return ['labels' => $rows->keys()->all(), 'data' => $rows->values()->all()];
    }

    public function maintenanceCostPerMonth(?string $location, int $months = 6): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $rows = VehicleMaintenance::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->whereNotNull('completed_date')
            ->where('completed_date', '>=', $start)
            ->selectRaw("DATE_FORMAT(completed_date, '%Y-%m') as ym, SUM(cost) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $data[] = round((float) ($rows[$key] ?? 0), 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * For Super Admin viewing all locations: one row per location comparing vehicles,
     * pending requests, trips (approved) and maintenance (active) counts.
     */
    public function locationComparison(array $locations): array
    {
        return collect($locations)->map(function (string $location) {
            return [
                'location' => $location,
                'vehicles' => Vehicle::where('location', $location)->count(),
                'pending' => VehicleRequest::where('location', $location)->where('status', 'pending')->count(),
                'trips' => VehicleRequest::where('location', $location)->where('status', 'approved')->count(),
                'maintenance' => VehicleMaintenance::where('location', $location)->whereIn('status', ['scheduled', 'ongoing'])->count(),
            ];
        })->all();
    }

    /** Headline numbers for the selected range (requests filtered by travel_date). */
    public function summary(?string $location, $start, $end): array
    {
        $requests = fn () => VehicleRequest::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->whereBetween('travel_date', [$start, $end]);

        $total = $requests()->count();
        $approved = $requests()->where('status', 'approved')->count();
        $declined = $requests()->where('status', 'declined')->count();
        $pending = $requests()->where('status', 'pending')->count();
        $decided = $approved + $declined;

        $maintenance = VehicleMaintenance::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->whereNotNull('completed_date')
            ->whereBetween('completed_date', [$start, $end]);

        return [
            'total' => $total,
            'approved' => $approved,
            'declined' => $declined,
            'pending' => $pending,
            'approval_rate' => $decided > 0 ? round($approved / $decided * 100) : null,
            'fleet' => Vehicle::query()->when($location, fn ($q) => $q->where('location', $location))->count(),
            'active_maintenance' => VehicleMaintenance::query()
                ->when($location, fn ($q) => $q->where('location', $location))
                ->whereIn('status', ['scheduled', 'ongoing'])->count(),
            'maintenance_cost' => round((float) (clone $maintenance)->sum('cost'), 2),
            'maintenance_done' => (clone $maintenance)->count(),
        ];
    }

    /** Detailed trip log for the range. Pass $limit = null for the full list (exports). */
    public function tripLog(?string $location, $start, $end, ?int $limit = null)
    {
        return VehicleRequest::query()
            ->with(['vehicle', 'confirmedDriver'])
            ->when($location, fn ($q) => $q->where('location', $location))
            ->whereBetween('travel_date', [$start, $end])
            ->orderByDesc('travel_date')
            ->orderByDesc('id')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    /** Requests by status for the range (doughnut). */
    public function statusBreakdown(array $summary): array
    {
        return [
            'labels' => ['Approved', 'Pending', 'Declined'],
            'data' => [$summary['approved'], $summary['pending'], $summary['declined']],
        ];
    }

    /** Completed maintenance in the range. */
    public function maintenanceLog(?string $location, $start, $end)
    {
        return VehicleMaintenance::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->whereNotNull('completed_date')
            ->whereBetween('completed_date', [$start, $end])
            ->orderByDesc('completed_date')
            ->get();
    }

    /**
     * Super Admin only: per-location figures for the range, including maintenance spend
     * and approval rate.
     */
    public function locationBreakdown(array $locations, $start, $end): array
    {
        return collect($locations)->map(function (string $location) use ($start, $end) {
            $base = fn () => VehicleRequest::where('location', $location)->whereBetween('travel_date', [$start, $end]);
            $approved = $base()->where('status', 'approved')->count();
            $declined = $base()->where('status', 'declined')->count();
            $decided = $approved + $declined;

            return [
                'location' => $location,
                'vehicles' => Vehicle::where('location', $location)->count(),
                'requests' => $base()->count(),
                'pending' => $base()->where('status', 'pending')->count(),
                'trips' => $approved,
                'declined' => $declined,
                'approval_rate' => $decided > 0 ? round($approved / $decided * 100) : null,
                'maintenance_active' => VehicleMaintenance::where('location', $location)->whereIn('status', ['scheduled', 'ongoing'])->count(),
                'maintenance_cost' => round((float) VehicleMaintenance::where('location', $location)
                    ->whereNotNull('completed_date')->whereBetween('completed_date', [$start, $end])->sum('cost'), 2),
            ];
        })->all();
    }
}
