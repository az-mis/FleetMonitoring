<?php

declare(strict_types=1);

namespace Modules\VehicleRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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

    /** Public requests: how far ahead a trip may start, and how long it may last (longer trips go through the office) */
    public const MAX_DAYS_AHEAD = 180;

    public const MAX_TRIP_DAYS = 30;

    public function messages(): array
    {
        return [
            'travelDate.before_or_equal' => 'Trips can be requested up to '.self::MAX_DAYS_AHEAD.' days (about 6 months) ahead.',
            'travelDateEnd.required' => 'Please select the Travel Date (To). For a one-day trip, choose the same date as Travel Date (From).',
            'travelDateEnd.after_or_equal' => 'Travel Date (To) cannot be earlier than Travel Date (From).',
            'requesterContact.regex' => 'Contact number must start with 09 and be exactly 11 digits (e.g. 09171234567).',
            'vehicleId.exists' => 'The selected vehicle is not available at the selected station. Please choose a vehicle from the list.',
            'travelDate.after_or_equal' => 'Travel Date (From) cannot be in the past.',
            'previousTripTicketDate.before_or_equal' => 'Previous Trip Ticket Date cannot be in the future.',
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
            // The vehicle must be stationed at the selected location, so the request reaches that office's admins
            'vehicleId' => ['required', 'integer', Rule::exists('vehicles', 'id')->where('location', (string) $this->input('location'))],
            'destination' => ['required', 'string', 'max:250'],
            'distance_km' => ['nullable', 'integer', 'min:0'],
            'travelDate' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(self::MAX_DAYS_AHEAD)->toDateString()],
            'travelDateEnd' => ['required', 'date', 'after_or_equal:travelDate'],
            'purpose' => ['required', 'string', 'max:500'],
            'passengers' => ['nullable', 'array', 'max:20'],
            'passengers.*' => ['string', 'max:120'],
            'previousTripTicketDate' => ['nullable', 'date', 'before_or_equal:today'],
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

            // One request covers at most MAX_TRIP_DAYS days (a request to 2099 would block the vehicle for everyone)
            try {
                $days = \Illuminate\Support\Carbon::parse($start)->diffInDays(\Illuminate\Support\Carbon::parse($end)) + 1;
            } catch (\Throwable) {
                $days = 0; // unreadable dates are reported by the date rules
            }
            if ($days > self::MAX_TRIP_DAYS) {
                $validator->errors()->add('travelDateEnd', 'A trip can last at most '.self::MAX_TRIP_DAYS.' days. For longer travel, please contact the fleet office.');
            }

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
