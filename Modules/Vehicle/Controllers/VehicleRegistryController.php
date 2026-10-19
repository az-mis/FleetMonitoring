<?php

declare(strict_types=1);

namespace Modules\Vehicle\Controllers;

use App\Http\Controllers\Concerns\AuthorizesFleetAccess;
use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleAccessory;

/**
 * A vehicle's property record and paperwork: the "Vehicle Inventory" and "List of Vehicle" sheets of the GSS workbook.
 */
class VehicleRegistryController extends Controller
{
    use AuthorizesFleetAccess;

    public const CONDITIONS = ['Serviceable', 'Unserviceable', 'For repair', 'For disposal'];

    public const OR_CR_STATUSES = ['Original on file', 'Copy only', 'No copy'];

    public function show(Vehicle $vehicle): View
    {
        $this->authorizeVehicle($vehicle);

        return view('vehicle::registry', [
            'vehicle' => $vehicle->load(['accessories', 'endUserOffice', 'assignedDriver']),
            'offices' => Office::query()->active()->get(['id', 'name']),
            'conditions' => self::CONDITIONS,
            'orCrStatuses' => self::OR_CR_STATUSES,
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        $data = $request->validate([
            'par_number' => ['nullable', 'string', 'max:40'],
            'property_number' => ['nullable', 'string', 'max:40'],
            'old_plate_number' => ['nullable', 'string', 'max:30'],
            'conduction_sticker' => ['nullable', 'string', 'max:30'],
            'acquisition_date' => ['nullable', 'date', 'before_or_equal:today'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'custodian_name' => ['nullable', 'string', 'max:150'],
            'end_user_office_id' => ['nullable', 'integer', Rule::exists('offices', 'id')],
            'assigned_person' => ['nullable', 'string', 'max:150'],
            'book_status' => ['nullable', 'string', 'max:40'],
            'condition' => ['nullable', Rule::in(self::CONDITIONS)],
            'registry_remarks' => ['nullable', 'string', 'max:2000'],
            'history' => ['nullable', 'string', 'max:2000'],
            'or_cr_status' => ['nullable', Rule::in(self::OR_CR_STATUSES)],
            'registration_place' => ['nullable', 'string', 'max:100'],
            'registration_expiry' => ['nullable', 'date'],
            'renewal_schedule' => ['nullable', 'string', 'max:100'],
            'insurance_type' => ['nullable', 'string', 'max:60'],
            'insurance_from' => ['nullable', 'date'],
            'insurance_to' => ['nullable', 'date', 'after_or_equal:insurance_from'],
            'tire_type' => ['nullable', 'string', 'max:60'],
            'has_ewd' => ['nullable', 'boolean'],
            'tools' => ['nullable', 'string', 'max:255'],
            'fleet_card_allowance' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ], [
            'insurance_to.after_or_equal' => 'The insurance end date must be on or after its start date.',
            'acquisition_date.before_or_equal' => 'The acquisition date cannot be in the future.',
        ]);

        $vehicle->fill($data + ['updated_by' => $request->user()->id])->save();

        return redirect()->route('vehicles.registry', $vehicle)->with('success', "Property and registration details for {$vehicle->plate_number} saved.");
    }

    public function storeAccessory(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        $data = $request->validate([
            'description' => ['required', 'string', 'max:200'],
            'date_acquired' => ['nullable', 'date', 'before_or_equal:today'],
            'value' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);
        $vehicle->accessories()->create($data);

        return redirect()->route('vehicles.registry', $vehicle)->with('success', 'Accessory added.');
    }

    public function destroyAccessory(Vehicle $vehicle, VehicleAccessory $accessory): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        abort_unless($accessory->vehicle_id === $vehicle->id, 404);

        $accessory->delete();

        return redirect()->route('vehicles.registry', $vehicle)->with('success', 'Accessory removed.');
    }

    private function authorizeVehicle(Vehicle $vehicle): void
    {
        if (! in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
        $this->authorizeFleetLocation($vehicle->location);
    }
}
