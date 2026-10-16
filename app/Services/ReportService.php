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
            'custom' => $this->customRange($from, $to),
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    private function customRange(?string $from, ?string $to): array
    {
        $parse = function (?string $value): ?Carbon {
            if (! $value) {
                return null;
            }
            try {
                return Carbon::createFromFormat('Y-m-d', $value) ?: null;
            } catch (\Throwable) {
                return null;
            }
        };

        $start = ($parse($from) ?? now()->subMonthsNoOverflow(3))->startOfDay();
        $end = ($parse($to) ?? now())->endOfDay();

        return $start->lessThanOrEqualTo($end) ? [$start, $end] : [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
    }

    public function requestsPerMonth(?string $location, int $months = 6): array
    {
        $start = now()->subMonthsNoOverflow($months - 1)->startOfMonth();

        $rows = VehicleRequest::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, count(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonthsNoOverflow($i);
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
        ksort($topTowns);

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
        $start = now()->subMonthsNoOverflow($months - 1)->startOfMonth();

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
            $month = now()->subMonthsNoOverflow($i);
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

    /**
     * Cost per km analysis with period-over-period comparison and smart insights (CarMind style).
     */
    public function costPerKmAnalysis(?string $location, Carbon $start, Carbon $end, ?int $vehicleFilterId = null): array
    {
        $daysInRange = max(1, $start->diffInDays($end) + 1);
        $prevStart = (clone $start)->subDays($daysInRange);
        $prevEnd = (clone $start)->subDay()->endOfDay();

        // 1. Current Period Data
        $currStats = $this->aggregateCostsAndDistance($location, $start, $end, $vehicleFilterId);

        // 2. Previous Period Data
        $prevStats = $this->aggregateCostsAndDistance($location, $prevStart, $prevEnd, $vehicleFilterId);

        // 3. Comparison metrics
        $diffCost = $currStats['total_cost'] - $prevStats['total_cost'];
        $pctChange = $prevStats['total_cost'] > 0
            ? round(($diffCost / $prevStats['total_cost']) * 100)
            : ($currStats['total_cost'] > 0 ? 100 : 0);

        // 4. Per-vehicle breakdown
        $vehicleBreakdown = $this->vehicleCostBreakdown($location, $start, $end);

        // 5. Smart Insights ("Worth a Look")
        $insights = $this->buildCostInsights($currStats, $prevStats, $diffCost, $pctChange, $vehicleBreakdown);

        return [
            'total_spent'      => $currStats['total_cost'],
            'fuel_spent'       => $currStats['fuel_cost'],
            'maint_spent'      => $currStats['maint_cost'],
            'total_km'         => $currStats['total_km'],
            'cost_per_km'      => $currStats['cost_per_km'],
            'entry_count'      => $currStats['entry_count'],
            'prev_spent'       => $prevStats['total_cost'],
            'diff_cost'        => abs($diffCost),
            'diff_direction'   => $diffCost >= 0 ? 'more' : 'less',
            'pct_change'       => abs($pctChange),
            'vehicles'         => $vehicleBreakdown,
            'insights'         => $insights,
        ];
    }

    private function aggregateCostsAndDistance(?string $location, Carbon $start, Carbon $end, ?int $vehicleFilterId = null): array
    {
        // Fuel / Card expenses
        $cardLogsQuery = \Modules\FleetCard\Models\FleetCardLog::query()
            ->when($vehicleFilterId, fn ($q) => $q->where('vehicle_id', $vehicleFilterId))
            ->when($location, fn ($q) => $q->whereHas('vehicle', fn ($vq) => $vq->where('location', $location)))
            ->where('distance_source', '!=', 'recharge')
            ->whereBetween('created_at', [$start, $end]);

        $fuelCost = (float) (clone $cardLogsQuery)->sum('consumed_amount');
        $fuelEntries = (clone $cardLogsQuery)->count();

        // Maintenance expenses
        $maintQuery = VehicleMaintenance::query()
            ->when($vehicleFilterId, fn ($q) => $q->where('vehicle_id', $vehicleFilterId))
            ->when($location, fn ($q) => $q->where('location', $location))
            ->whereNotNull('completed_date')
            ->whereBetween('completed_date', [$start, $end]);

        $maintCost = (float) (clone $maintQuery)->sum('cost');
        $maintEntries = (clone $maintQuery)->count();

        $totalCost = round($fuelCost + $maintCost, 2);

        // Distance: from approved trips in this period
        $tripKm = (float) VehicleRequest::query()
            ->when($vehicleFilterId, fn ($q) => $q->where('vehicle_id', $vehicleFilterId))
            ->when($location, fn ($q) => $q->where('location', $location))
            ->where('status', 'approved')
            ->whereBetween('travel_date', [$start, $end])
            ->sum('distance_km');

        // Fallback distance from fleet card logs if trip distance_km is 0
        if ($tripKm <= 0) {
            $tripKm = (float) (clone $cardLogsQuery)->sum('distance_km');
        }

        $costPerKm = $tripKm > 0 ? round($totalCost / $tripKm, 2) : 0.00;

        return [
            'total_cost'  => $totalCost,
            'fuel_cost'   => round($fuelCost, 2),
            'maint_cost'  => round($maintCost, 2),
            'total_km'    => round($tripKm, 1),
            'cost_per_km' => $costPerKm,
            'entry_count' => $fuelEntries + $maintEntries,
        ];
    }

    private function vehicleCostBreakdown(?string $location, Carbon $start, Carbon $end): array
    {
        $vehicles = Vehicle::query()
            ->when($location, fn ($q) => $q->where('location', $location))
            ->get();

        $breakdown = [];
        foreach ($vehicles as $v) {
            $fuel = (float) \Modules\FleetCard\Models\FleetCardLog::where('vehicle_id', $v->id)
                ->where('distance_source', '!=', 'recharge')
                ->whereBetween('created_at', [$start, $end])
                ->sum('consumed_amount');

            $maint = (float) VehicleMaintenance::where('vehicle_id', $v->id)
                ->whereNotNull('completed_date')
                ->whereBetween('completed_date', [$start, $end])
                ->sum('cost');

            $km = (float) VehicleRequest::where('vehicle_id', $v->id)
                ->where('status', 'approved')
                ->whereBetween('travel_date', [$start, $end])
                ->sum('distance_km');

            if ($km <= 0) {
                $km = (float) \Modules\FleetCard\Models\FleetCardLog::where('vehicle_id', $v->id)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('distance_km');
            }

            $total = $fuel + $maint;
            $cpk = $km > 0 ? round($total / $km, 2) : 0.00;

            if ($total > 0 || $km > 0) {
                $breakdown[] = [
                    'id'          => $v->id,
                    'plate'       => $v->plate_number,
                    'label'       => trim("{$v->brand} {$v->model}"),
                    'total_spent' => round($total, 2),
                    'total_km'    => round($km, 1),
                    'cost_per_km' => $cpk,
                    'fuel_cost'   => round($fuel, 2),
                    'maint_cost'  => round($maint, 2),
                ];
            }
        }

        usort($breakdown, fn ($a, $b) => $b['total_spent'] <=> $a['total_spent']);

        return $breakdown;
    }

    private function buildCostInsights(array $curr, array $prev, float $diff, float $pct, array $vehicles): array
    {
        $insights = [];

        // 1. Spending comparison trend
        if ($prev['total_cost'] > 0) {
            $dirText = $diff >= 0 ? 'more' : 'less';
            $insights[] = [
                'tag'   => 'SPENDING TREND',
                'title' => sprintf('₱%s in the selected period', number_format($curr['total_cost'], 2)),
                'desc'  => sprintf('%d%% %s than the previous period (₱%s).', abs(round($pct)), $dirText, number_format($prev['total_cost'], 2)),
                'tone'  => $diff > 0 ? 'amber' : 'emerald',
            ];
        }

        // 2. Cost efficiency insight
        if ($curr['cost_per_km'] > 0) {
            $insights[] = [
                'tag'   => 'COST EFFICIENCY',
                'title' => sprintf('₱%s per kilometer fleet average', number_format($curr['cost_per_km'], 2)),
                'desc'  => sprintf('Based on %s km logged and ₱%s total fuel & service expenses.', number_format($curr['total_km'], 1), number_format($curr['total_cost'], 2)),
                'tone'  => 'sky',
            ];
        }

        // 3. Highest spending vehicle
        if (!empty($vehicles)) {
            $top = $vehicles[0];
            $insights[] = [
                'tag'   => 'TOP VEHICLE EXPENSE',
                'title' => sprintf('%s (%s) had highest spend', $top['plate'], $top['label']),
                'desc'  => sprintf('Total of ₱%s (₱%s/km over %s km).', number_format($top['total_spent'], 2), number_format($top['cost_per_km'], 2), number_format($top['total_km'], 1)),
                'tone'  => 'violet',
            ];
        }

        return $insights;
    }
}
