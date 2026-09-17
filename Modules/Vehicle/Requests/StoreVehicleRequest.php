<?php

declare(strict_types=1);

namespace Modules\Vehicle\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Admin\Enums\UserRole;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true);
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->role !== UserRole::SUPER_ADMIN) {
            $this->merge([
                'location' => $this->user()?->location,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\- ]+$/', 'unique:vehicles,plate_number'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'chassis_number' => ['required', 'string', 'max:50'],
            'engine_number' => ['required', 'string', 'max:50'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color' => ['required', 'string', 'max:50'],
            'odometer' => ['required', 'integer', 'min:0'],
            'vehicle_type' => ['nullable', 'string', 'max:50'],
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
