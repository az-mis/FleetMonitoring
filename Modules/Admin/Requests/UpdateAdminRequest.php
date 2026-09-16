<?php

declare(strict_types=1);

namespace Modules\Admin\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Admin\Enums\UserRole;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('admin'));
    }

    public function rules(): array
    {
        /** @var User $admin */
        $admin = $this->route('admin');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'phone_number' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:120'],
            'signee1_name' => ['nullable', 'string', 'max:255'],
            'signee1_title' => ['nullable', 'string', 'max:255'],
            'signee2_name' => ['nullable', 'string', 'max:255'],
            'signee2_title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
