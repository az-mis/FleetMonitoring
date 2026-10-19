<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Gss\GssExportService;
use App\Services\Gss\GssScope;
use App\Services\ReportExportService;
use App\Services\ReportService;
use App\Support\FleetLocations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    protected array $locationOptions = FleetLocations::ALL;

    public function __construct(
        protected ReportService $reportService,
        protected ReportExportService $exportService,
    ) {}

    public function index(Request $request): View
    {
        $ctx = $this->context($request);

        return view('reports.index', $ctx + [
            'tripLogPreview' => $ctx['tripLog']->take(15),
            'locationOptions' => $this->locationOptions,
            'gssSheets' => collect(app(GssExportService::class)->sheets())->map->title(),
        ]);
    }

    /** Print-optimised page with the official letterhead (Print / Save as PDF). */
    public function print(Request $request): View
    {
        return view('reports.print', $this->context($request));
    }

    public function export(Request $request, string $format): BinaryFileResponse|RedirectResponse
    {
        abort_unless(in_array($format, ['xlsx', 'docx'], true), 404);
        $ctx = $this->context($request);

        $missing = $format === 'xlsx'
            ? ! class_exists(Spreadsheet::class)
            : ! class_exists(PhpWord::class);

        if ($missing) {
            return back()->withErrors([
                'export' => $format === 'xlsx'
                    ? 'Excel export needs a package. Run: composer require phpoffice/phpspreadsheet'
                    : 'Word export needs a package. Run: composer require phpoffice/phpword',
            ]);
        }

        $path = $format === 'xlsx'
            ? $this->exportService->xlsx($ctx)
            : $this->exportService->docx($ctx);

        return response()->download($path, $this->exportName($format, $ctx))->deleteFileAfterSend(true);
    }

    /** The vehicle sheets of the GSS workbook for one year (admins get only their own area) */
    public function gssWorkbook(Request $request, GssExportService $gss): BinaryFileResponse
    {
        $user = $request->user();
        $roleValue = $user?->role?->value ?? $user?->role;
        abort_unless(in_array($roleValue, ['admin', 'super_admin'], true), 403);

        $data = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'location' => ['nullable', Rule::in($this->locationOptions)],
            'sheets' => ['nullable', 'array'],
            'sheets.*' => ['string', Rule::in(array_keys($gss->sheets()))],
        ]);

        $location = $roleValue === 'super_admin' ? ($data['location'] ?? null) : $user->location;
        abort_if($roleValue !== 'super_admin' && blank($location), 403, 'Your account has no assigned location.');
        $scope = new GssScope((int) ($data['year'] ?? now()->format('Y')), $location, $user->name);

        $name = sprintf('GSS_%d_%s.xlsx', $scope->year, $location ? Str::studly(Str::slug($location, ' ')) : 'AllAreas');

        return response()->download($gss->export($scope, $data['sheets'] ?? []), $name)->deleteFileAfterSend(true);
    }

    /** Trip_Report_QuezonCity_2026-09-01_to_2026-09-30.xlsx */
    private function exportName(string $ext, array $ctx): string
    {
        $loc = $ctx['location'] ? Str::studly(Str::slug($ctx['location'], ' ')) : 'AllOffices';

        return sprintf('Trip_Report_%s_%s_to_%s.%s', $loc, $ctx['rangeStart']->format('Y-m-d'), $ctx['rangeEnd']->format('Y-m-d'), $ext);
    }

    /** Shared data for the page, the print view and the exports. Admins are always locked to their own location. */
    private function context(Request $request): array
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

        $summary = $this->reportService->summary($location, $start, $end);

        $ctx = [
            'isSuperAdmin' => $isSuperAdmin,
            'location' => $location,
            'range' => $range,
            'rangeStart' => $start,
            'rangeEnd' => $end,
            'summary' => $summary,
            'requestsPerMonth' => $this->reportService->requestsPerMonth($location),
            'topDestinations' => $this->reportService->topDestinations($location, $start, $end, 10),
            'tripsPerVehicle' => $this->reportService->tripsPerVehicle($location, $start, $end, 10),
            'maintenanceCostPerMonth' => $this->reportService->maintenanceCostPerMonth($location),
            'statusBreakdown' => $this->reportService->statusBreakdown($summary),
            'tripLog' => $this->reportService->tripLog($location, $start, $end),
            'maintenanceLog' => $this->reportService->maintenanceLog($location, $start, $end),
            'costAnalysis' => $this->reportService->costPerKmAnalysis($location, $start, $end),
            'generatedBy' => $user->name,
            'generatedAt' => now(),
            'scopeLabel' => $location ?: 'All Offices',
        ];

        // Super Admin, all locations: cross-office comparison (never shown to Admins).
        if ($isSuperAdmin && ! $location) {
            $ctx['locationBreakdown'] = $this->reportService->locationBreakdown($this->locationOptions, $start, $end);
        }

        return $ctx;
    }
}
