<?php

declare(strict_types=1);

namespace Modules\Admin\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\Admin\Enums\UserRole;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'phone_number' => ['nullable', 'string', 'max:40'],
            // An admin without a location would bypass every per-location restriction
            'location' => ['nullable', 'required_if:role,admin', 'string', 'max:120'],
            'signee1_name' => ['nullable', 'string', 'max:255'],
            'signee1_title' => ['nullable', 'string', 'max:255'],
            'signee2_name' => ['nullable', 'string', 'max:255'],
            'signee2_title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
