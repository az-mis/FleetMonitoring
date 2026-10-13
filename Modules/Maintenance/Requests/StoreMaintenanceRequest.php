<?php

declare(strict_types=1);

namespace Modules\Maintenance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id'          => ['required', 'exists:vehicles,id'],
            'type'                => ['required', 'in:Preventive,Repair,Inspection,Oil Change,Tire,Battery,Other'],
            'title'               => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'status'              => ['required', 'in:scheduled,ongoing,completed,cancelled'],
            'priority'            => ['required', 'in:normal,high,urgent'],
            'scheduled_date'      => ['required', 'date'],
            'scheduled_date_end'  => ['nullable', 'date', 'after_or_equal:scheduled_date'],
            'completed_date'      => ['nullable', 'date', 'required_if:status,completed'],
            'odometer'            => ['nullable', 'integer', 'min:0'],
            'trigger_km'          => ['nullable', 'integer', 'min:0'],
            'cost'                => ['nullable', 'numeric', 'min:0'],
            'vendor'              => ['nullable', 'string', 'max:255'],
            'notes'               => ['nullable', 'string'],
            'source_report_id'    => ['nullable', 'integer', 'exists:vehicle_issue_reports,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'completed_date.required_if' => 'Please enter the completed date before marking this as completed.',
            'scheduled_date_end.after_or_equal' => 'The end date cannot be before the scheduled date.',
        ];
    }
}
