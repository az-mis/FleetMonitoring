<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'passengers' => array_values(array_filter($this->input('passengers', []), fn ($name) => filled(trim((string) $name)))),
        ]);
    }

    public function rules(): array
    {
        return [
            'requesterName' => ['required', 'string', 'max:120'],
            'location' => ['required', 'string', 'max:120'],
            'requesterOffice' => ['nullable', 'string', 'max:150'],
            'requesterContact' => ['nullable', 'string', 'max:40'],
            'requesterIsPassenger' => ['nullable', 'boolean'],
            'vehicleId' => ['required', 'integer', 'exists:vehicles,id'],
            'destination' => ['required', 'string', 'max:250'],
            'travelDate' => ['required', 'date'],
            'travelDateEnd' => ['nullable', 'date', 'after_or_equal:travelDate'],
            'purpose' => ['required', 'string', 'max:500'],
            'passengers' => ['nullable', 'array', 'max:20'],
            'passengers.*' => ['string', 'max:120'],
            'previousTripTicketDate' => ['nullable', 'date'],
        ];
    }
}
