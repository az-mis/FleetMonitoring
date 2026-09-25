<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    protected array $locationOptions = [
        'Oriental Mindoro',
        'Occidental Mindoro',
        'Marinduque',
        'Palawan',
        'Romblon',
        'Quezon City Satellite Office',
    ];

    public function __construct(protected ReportService $reportService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $roleValue = $user?->role?->value ?? $user?->role;
        abort_unless(in_array($roleValue, ['admin', 'super_admin'], true), 403);

        $isSuperAdmin = $roleValue === 'super_admin';

        $location = $isSuperAdmin
            ? (in_array($request->input('location'), $this->locationOptions, true) ? $request->input('location') : null)
            : $user->location;

        $range = in_array($request->input('range'), ReportService::RANGES, true) ? $request->input('range') : 'month';
        [$start, $end] = $this->reportService->resolveRange($range, $request->input('from'), $request->input('to'));

        $data = [
            'requestsPerMonth' => $this->reportService->requestsPerMonth($location),
            'topDestinations' => $this->reportService->topDestinations($location, $start, $end, 10),
            'tripsPerVehicle' => $this->reportService->tripsPerVehicle($location, $start, $end, 10),
            'maintenanceCostPerMonth' => $this->reportService->maintenanceCostPerMonth($location),
        ];

        if ($isSuperAdmin && ! $location) {
            $data['locationComparison'] = $this->reportService->locationComparison($this->locationOptions);
        }

        return view('reports.index', array_merge($data, [
            'isSuperAdmin' => $isSuperAdmin,
            'location' => $location,
            'locationOptions' => $this->locationOptions,
            'range' => $range,
            'rangeStart' => $start,
            'rangeEnd' => $end,
        ]));
    }
}
