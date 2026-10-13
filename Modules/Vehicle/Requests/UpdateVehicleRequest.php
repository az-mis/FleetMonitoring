<?php

declare(strict_types=1);

namespace Modules\Vehicle\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Admin\Enums\UserRole;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleType;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true);
    }

    protected function prepareForValidation(): void
    {
        // Only a Super Admin is allowed to change the plate number. Regardless
        // of what was submitted, pin it back to the current value for anyone
        // else so a tampered/enabled field can never slip through.
        if ($this->user()?->role !== UserRole::SUPER_ADMIN) {
            /** @var Vehicle $vehicle */
            $vehicle = $this->route('vehicle');

            $this->merge([
                'plate_number' => $vehicle?->plate_number,
                'location' => $vehicle?->location,
            ]);
        }
    }

    public function rules(): array
    {
        /** @var Vehicle $vehicle */
        $vehicle = $this->route('vehicle');

        return [
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\- ]+$/', Rule::unique('vehicles', 'plate_number')->ignore($vehicle->id)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
            'chassis_number' => ['nullable', 'string', 'max:50'],
            'engine_number' => ['nullable', 'string', 'max:50'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color' => ['required', 'string', 'max:50'],
            'odometer' => ['required', 'integer', 'min:0'],
            'change_oil_interval_km' => ['nullable', 'integer', 'min:500'],
            // Any registered type, or the value the vehicle already has (older records
            // may hold values like "Sedan" that were never registered as a type).
            'vehicle_type' => ['nullable', 'string', 'max:50', Rule::in(array_filter([
                ...VehicleType::registry()->pluck('name')->all(),
                $vehicle->vehicle_type,
            ]))],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'plate_number.regex' => 'Plate number may only contain letters, numbers, dashes, and spaces.',
        ];
    }
}
