<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRequest\Models\VehicleRequest;

class ReportService
{
    public const RANGES = ['week', 'month', 'quarter', 'custom'];

    /**
     * Resolve a range key (+ optional custom from/to) into [start, end] Carbon dates.
     */
    public function resolveRange(string $range, ?string $from = null, ?string $to = null): array
    {
        return match ($range) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
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
        $rows = VehicleRequest::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->when($start && $end, fn ($q) => $q->whereBetween('travel_date', [$start, $end]))
            ->selectRaw('destination, count(*) as total')
            ->groupBy('destination')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'destination');

        return ['labels' => $rows->keys()->all(), 'data' => $rows->values()->all()];
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
}
