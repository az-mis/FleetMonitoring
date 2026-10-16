<?php

declare(strict_types=1);

namespace Modules\Driver\Requests;

use App\Models\User;
use App\Rules\UniqueIdentifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-drivers');
    }

    public function rules(): array
    {
        /** @var User $driver */
        $driver = $this->route('driver');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($driver->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:driver'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_avatar' => ['nullable', 'boolean'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'location' => ['nullable', 'string', 'max:120'],
            // Checked only when the number changes, so an existing duplicate doesn't block unrelated edits
            'license_number' => array_filter(['required', 'string', 'max:100',
                UniqueIdentifier::normalize($this->input('license_number')) !== UniqueIdentifier::normalize($driver->license_number)
                    ? new UniqueIdentifier('users', 'license_number', "driver's license number", $driver->id)
                    : null,
            ]),
            'license_front' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'license_back' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'license_expiration_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Phone Number must start with 09 and be exactly 11 digits (e.g. 09171234567).',
        ];
    }
}
