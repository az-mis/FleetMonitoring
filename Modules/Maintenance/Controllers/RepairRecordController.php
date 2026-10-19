<?php

declare(strict_types=1);

namespace Modules\Maintenance\Controllers;

use App\Http\Controllers\Concerns\AuthorizesFleetAccess;
use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Maintenance\Models\Procurement;
use Modules\Maintenance\Models\VehicleMaintenance;

/**
 * A maintenance record's repair details and the purchase requests that pay for it: the GSS
 * "Index for Repair of Vehicles", "Pre & Post Repair CM Value", "Vehicle PMS" and "PR" sheets.
 */
class RepairRecordController extends Controller
{
    use AuthorizesFleetAccess;

    public function show(VehicleMaintenance $maintenance): View
    {
        $this->authorizeRecord($maintenance);

        return view('maintenance::repair', [
            'record' => $maintenance->load(['vehicle.assignedDriver', 'vehicle.endUserOffice', 'requisitionerOffice', 'procurements']),
            'offices' => Office::query()->active()->get(['id', 'name']),
            'statuses' => Procurement::STATUSES,
            'categories' => Procurement::CATEGORIES,
        ]);
    }

    public function update(Request $request, VehicleMaintenance $maintenance): RedirectResponse
    {
        $this->authorizeRecord($maintenance);

        $data = $request->validate([
            'requisitioner_office_id' => ['nullable', 'integer', Rule::exists('offices', 'id')],
            'pm_request_no' => ['nullable', 'string', 'max:30'],
            'pre_inspection_date' => ['nullable', 'date'],
            'defect_nature' => ['nullable', 'string', 'max:2000'],
            'parts_replaced' => ['nullable', 'string', 'max:2000'],
            'post_inspection_date' => ['nullable', 'date', 'after_or_equal:pre_inspection_date'],
            'post_inspection_findings' => ['nullable', 'string', 'max:2000'],
            'current_market_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'cmv_issued_at' => ['nullable', 'date'],
            'cmv_issued_by' => ['nullable', 'string', 'max:100'],
        ], [
            'post_inspection_date.after_or_equal' => 'The post-repair inspection cannot be before the pre-repair inspection.',
        ]);

        $maintenance->update($data);

        return redirect()->route('maintenance.repair', $maintenance)->with('success', "Repair details for {$maintenance->plate_number} saved.");
    }

    public function storeProcurement(Request $request, VehicleMaintenance $maintenance): RedirectResponse
    {
        $this->authorizeRecord($maintenance);

        $maintenance->procurements()->create($this->validateProcurement($request) + [
            'vehicle_id' => $maintenance->vehicle_id,
            'location' => $maintenance->location ?? $maintenance->vehicle?->location,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('maintenance.repair', $maintenance)->with('success', 'Purchase request added.');
    }

    public function updateProcurement(Request $request, VehicleMaintenance $maintenance, Procurement $procurement): RedirectResponse
    {
        $this->authorizeRecord($maintenance);
        abort_unless($procurement->vehicle_maintenance_id === $maintenance->id, 404);

        $procurement->update($this->validateProcurement($request));

        return redirect()->route('maintenance.repair', $maintenance)->with('success', 'Purchase request '.($procurement->pr_number ?: $procurement->description).' updated.');
    }

    public function destroyProcurement(VehicleMaintenance $maintenance, Procurement $procurement): RedirectResponse
    {
        $this->authorizeRecord($maintenance);
        abort_unless($procurement->vehicle_maintenance_id === $maintenance->id, 404);

        $procurement->delete();

        return redirect()->route('maintenance.repair', $maintenance)->with('success', 'Purchase request removed.');
    }

    /** @return array<string, mixed> */
    private function validateProcurement(Request $request): array
    {
        return $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(Procurement::CATEGORIES)],
            'pr_number' => ['nullable', 'string', 'max:40'],
            'pr_date' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'status' => ['required', Rule::in(Procurement::STATUSES)],
            'jo_po_number' => ['nullable', 'string', 'max:60'],
            'jo_po_date' => ['nullable', 'date', 'after_or_equal:pr_date'],
            'coa_date' => ['nullable', 'date', 'after_or_equal:pr_date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'jo_po_date.after_or_equal' => 'The JO/PO date cannot be before the PR date.',
            'coa_date.after_or_equal' => 'The COA date cannot be before the PR date.',
        ]);
    }

    private function authorizeRecord(VehicleMaintenance $maintenance): void
    {
        $this->authorizeFleetLocation($maintenance->vehicle?->location ?? $maintenance->location);
    }
}
