<?php

declare(strict_types=1);

namespace Modules\Driver\Requests;

use App\Rules\UniqueIdentifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-drivers');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:driver'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'location' => ['nullable', 'string', 'max:120'],
            'license_number' => ['required', 'string', 'max:100', new UniqueIdentifier('users', 'license_number', "driver's license number")],
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
