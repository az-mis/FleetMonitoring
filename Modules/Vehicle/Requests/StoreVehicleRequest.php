<?php

declare(strict_types=1);

namespace Modules\Vehicle\Requests;

use App\Rules\UniqueIdentifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Admin\Enums\UserRole;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('plate_number'))) {
            $this->merge(['plate_number' => strtoupper(preg_replace('/\s+/', ' ', trim($this->input('plate_number'))))]);
        }

        if ($this->user()?->role !== UserRole::SUPER_ADMIN) {
            $this->merge([
                'location' => $this->user()?->location,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\- ]+$/', new UniqueIdentifier('vehicles', 'plate_number', 'plate number')],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'chassis_number' => ['nullable', 'string', 'max:50'],
            'engine_number' => ['nullable', 'string', 'max:50'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'color' => ['required', 'string', 'max:50'],
            'odometer' => ['required', 'integer', 'min:0'],
            'change_oil_interval_km' => ['nullable', 'integer', 'min:500'],
            'vehicle_type' => ['required', 'string', 'max:50', Rule::exists('vehicle_types', 'name')],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'location.required' => 'Choose the area this vehicle belongs to.',
            'plate_number.regex' => 'Plate number may only contain letters, numbers, dashes, and spaces.',
        ];
    }
}
