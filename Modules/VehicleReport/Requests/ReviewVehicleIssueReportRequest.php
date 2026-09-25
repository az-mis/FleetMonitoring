<?php

declare(strict_types=1);

namespace Modules\VehicleReport\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewVehicleIssueReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'                  => ['required', 'in:submitted,reviewed,maintenance_scheduled,ongoing,resolved,dismissed'],
            'admin_notes'             => ['nullable', 'string'],
            'related_maintenance_id'  => ['nullable', 'exists:vehicle_maintenances,id'],
        ];
    }
}
