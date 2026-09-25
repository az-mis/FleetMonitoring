<?php

declare(strict_types=1);

namespace Modules\Vehicle\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Admin\Enums\UserRole;
use Modules\Vehicle\Models\VehicleType;

class StoreVehicleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN], true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30', 'regex:/^[\pL\pN][\pL\pN \-\/&().]*$/u', Rule::unique('vehicle_types', 'name')],
            'icon' => ['required', Rule::in(array_keys(VehicleType::ICONS))],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'That vehicle type already exists.',
            'name.regex' => 'Vehicle type may only contain letters, numbers, spaces, and - / & ( ) .',
            'icon.required' => 'Please choose an icon.',
        ];
    }
}
