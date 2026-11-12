<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Drivers use the same mobile number format as the Drivers form; admins may use office landlines
        $phoneRules = $request->user()->role === UserRole::DRIVER
            ? ['required', 'string', 'regex:/^09\d{9}$/']
            : ['required', 'string', 'max:40'];

        // Only a Super Admin changes their own email here; everyone else's is set by whoever manages their account
        $emailRules = $request->user()->role === UserRole::SUPER_ADMIN
            ? ['email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)]]
            : [];

        $data = $request->validate([
            ...$emailRules,
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => $phoneRules,
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_avatar' => ['nullable', 'boolean'],
        ], [
            'phone_number.regex' => 'Phone Number must start with 09 and be exactly 11 digits (e.g. 09171234567).',
        ]);

        $user = $request->user();
        unset($data['avatar'], $data['remove_avatar']);
        if ($request->boolean('remove_avatar') && $user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $data['avatar'] = null;
        } elseif ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->forceFill($data)->save();

        return back()->with('success', 'Profile updated successfully.');
    }
}
