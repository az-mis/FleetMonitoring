<?php

declare(strict_types=1);

namespace Modules\VehicleReport\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewVehicleIssueReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [\Modules\Admin\Enums\UserRole::SUPER_ADMIN, \Modules\Admin\Enums\UserRole::ADMIN], true);
    }

    public function rules(): array
    {
        return [
            'status'                  => ['required', 'in:submitted,reviewed,maintenance_scheduled,ongoing,resolved,dismissed'],
            'admin_notes'             => ['nullable', 'string'],
            'related_maintenance_id'  => [
                'nullable',
                \Illuminate\Validation\Rule::exists('vehicle_maintenances', 'id')
                    ->where('vehicle_id', $this->route('vehicleReport')?->vehicle_id),
            ],
        ];
    }
}
