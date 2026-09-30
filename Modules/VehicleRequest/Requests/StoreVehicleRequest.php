<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Maintenance\Models\VehicleMaintenance;
use Modules\VehicleRequest\Support\PersonName;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Names are stored in Title Case so they print cleanly on the Trip Ticket.
        $this->merge([
            'requesterName' => PersonName::titleCase((string) $this->input('requesterName')),
            'passengers' => PersonName::titleCaseAll((array) $this->input('passengers', [])),
        ]);
    }

    public function messages(): array
    {
        return [
            'travelDateEnd.required' => 'Please select the Travel Date (To). For a one-day trip, choose the same date as Travel Date (From).',
            'travelDateEnd.after_or_equal' => 'Travel Date (To) cannot be earlier than Travel Date (From).',
            'requesterContact.regex' => 'Contact number must start with 09 and be exactly 11 digits (e.g. 09171234567).',
        ];
    }

    public function rules(): array
    {
        return [
            'requesterName' => ['required', 'string', 'max:120'],
            'location' => ['required', 'string', 'max:120'],
            'requesterOffice' => ['nullable', 'string', 'max:150'],
            'requesterContact' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'requesterIsPassenger' => ['nullable', 'boolean'],
            'vehicleId' => ['required', 'integer', 'exists:vehicles,id'],
            'destination' => ['required', 'string', 'max:250'],
            'travelDate' => ['required', 'date'],
            'travelDateEnd' => ['required', 'date', 'after_or_equal:travelDate'],
            'purpose' => ['required', 'string', 'max:500'],
            'passengers' => ['nullable', 'array', 'max:20'],
            'passengers.*' => ['string', 'max:120'],
            'previousTripTicketDate' => ['nullable', 'date'],
        ];
    }

    /**
     * Server-side mirror of React's selectedMaintenanceConflict check, so a
     * conflicting request can't be submitted even if the client-side
     * calendar/JS never ran (JS disabled, stale page, bypassed request).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $vehicleId = $this->input('vehicleId');
            $start = $this->input('travelDate');
            if (! $vehicleId || ! $start) {
                return;
            }
            $end = $this->input('travelDateEnd') ?: $start;

            $conflict = VehicleMaintenance::query()
                ->where('vehicle_id', $vehicleId)
                ->whereIn('status', ['scheduled', 'ongoing'])
                ->whereDate('scheduled_date', '<=', $end)
                ->where(function ($query) use ($start): void {
                    $query->whereDate('scheduled_date_end', '>=', $start)
                        ->orWhere(function ($query) use ($start): void {
                            $query->whereNull('scheduled_date_end')
                                ->whereDate('scheduled_date', '>=', $start);
                        });
                })
                ->first();

            if ($conflict) {
                $validator->errors()->add(
                    'vehicleId',
                    "This vehicle is unavailable due to {$conflict->title} on the selected date(s). Please choose another vehicle or travel date."
                );
            }
        });
    }
}
