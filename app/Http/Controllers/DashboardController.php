<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected ReportService $reportService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $roleValue = $user?->role?->value ?? $user?->role;
        $isAdmin = in_array($roleValue, ['admin', 'super_admin'], true);
        $isSuperAdmin = $roleValue === 'super_admin';

        $locationOptions = [
            'Oriental Mindoro',
            'Occidental Mindoro',
            'Marinduque',
            'Palawan',
            'Romblon',
            'Quezon City Satellite Office',
        ];

        $dashboardLocation = match ($roleValue) {
            'admin' => $user->location,
            'super_admin' => in_array($request->input('location'), $locationOptions, true) ? $request->input('location') : null,
            default => null,
        };

        $data = [];

        if ($isAdmin) {
            $data = [
                'alerts' => $this->dashboardService->alerts($dashboardLocation),
                'kpis' => $this->dashboardService->kpis($dashboardLocation, $isSuperAdmin),
                'fleetStatus' => $this->dashboardService->fleetStatus($dashboardLocation),
                'fleetHealth' => $this->dashboardService->fleetHealth($dashboardLocation),
                'fleetBudget' => $this->dashboardService->fleetBudget($dashboardLocation),
                'todaysTrips' => $this->dashboardService->todaysTrips($dashboardLocation),
                'upcomingTrips' => $this->dashboardService->upcomingTrips($dashboardLocation),
                'calendarEvents' => $this->dashboardService->calendarEvents($dashboardLocation),
                'requestsPerMonth' => $this->reportService->requestsPerMonth($dashboardLocation),
                'topDestinations' => $this->reportService->topDestinations($dashboardLocation),
                'maintenanceCostPerMonth' => $this->reportService->maintenanceCostPerMonth($dashboardLocation),
            ];

            if ($isSuperAdmin && ! $dashboardLocation) {
                $data['locationComparison'] = $this->reportService->locationComparison($locationOptions);
            }
        }

        return view('dashboard', array_merge($data, [
            'isAdmin' => $isAdmin,
            'isSuperAdmin' => $isSuperAdmin,
            'dashboardLocation' => $dashboardLocation,
            'locationOptions' => $locationOptions,
        ]));
    }

    /**
     * JSON refresh endpoint for the alert strip + KPI cards (polled every minute client-side).
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $roleValue = $user?->role?->value ?? $user?->role;
        abort_unless(in_array($roleValue, ['admin', 'super_admin'], true), 403);

        $locationOptions = [
            'Oriental Mindoro', 'Occidental Mindoro', 'Marinduque',
            'Palawan', 'Romblon', 'Quezon City Satellite Office',
        ];
        $isSuperAdmin = $roleValue === 'super_admin';
        $location = $isSuperAdmin
            ? (in_array($request->input('location'), $locationOptions, true) ? $request->input('location') : null)
            : $user->location;

        return response()->json([
            'alerts' => $this->dashboardService->alerts($location),
            'kpis' => $this->dashboardService->kpis($location, $isSuperAdmin),
            'fleetStatus' => $this->dashboardService->fleetStatus($location),
            'fleetBudget' => $this->dashboardService->fleetBudget($location),
            'updatedAt' => now()->toIso8601String(),
        ]);
    }
}
