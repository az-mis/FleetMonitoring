<?php

declare(strict_types=1);

namespace Modules\VehicleReport\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleIssueReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id'  => ['nullable', 'integer', 'exists:vehicles,id'],
            'issue_type'  => ['required', 'in:Tire,Engine,Brake,Battery,Electrical,Body / Exterior,Other'],
            'priority'    => ['required', 'in:normal,high,urgent'],
            'description' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Please describe the vehicle issue.',
        ];
    }
}
