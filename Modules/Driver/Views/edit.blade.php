@extends('layouts.app')

@section('title', 'Edit Driver')
@section('page-title', 'Edit Driver')

@section('content')
    <div class="mb-6">
        <a href="{{ route('driver.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Back to Drivers
        </a>
    </div>

    <div class="max-w-xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">Edit driver account</h2>
        <p class="mt-1 text-sm text-gray-500">Update {{ $driver->name }}'s details. Leave password blank to keep it unchanged.</p>

        <form method="POST" action="{{ route('driver.update', $driver) }}" class="mt-6 space-y-5" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="role" value="{{ \Modules\Admin\Enums\UserRole::DRIVER->value }}">

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $driver->name) }}"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-red-300 @enderror" required>
                @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="birth_date" class="block text-sm font-medium text-gray-700">Birth Date</label>
                    <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date', optional($driver->birth_date)->format('Y-m-d')) }}"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" name="address" id="address" value="{{ old('address', $driver->address) }}"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                <input type="email" name="email" id="email" value="{{ old('email', $driver->email) }}"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('email') border-red-300 @enderror" required>
                @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Role</label>
                <div class="mt-1.5 flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5">
                    <x-admin::role-badge :role="\Modules\Admin\Enums\UserRole::DRIVER" />
                    <span class="text-sm text-gray-500">This page only manages Driver accounts.</span>
                </div>
            </div>

            <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                <p class="text-sm font-semibold text-emerald-900">Driver's License Information</p>
                <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="license_front" class="block text-sm font-medium text-gray-700">License Front Photo</label>
                        @if ($driver->license_front_url)
                            <img src="{{ $driver->license_front_url }}" class="mt-1.5 mb-2 h-16 w-24 rounded-lg border border-gray-200 object-cover">
                        @endif
                        <input type="file" name="license_front" id="license_front" accept="image/*" class="block w-full text-sm text-gray-600">
                    </div>
                    <div>
                        <label for="license_back" class="block text-sm font-medium text-gray-700">License Back Photo</label>
                        @if ($driver->license_back_url)
                            <img src="{{ $driver->license_back_url }}" class="mt-1.5 mb-2 h-16 w-24 rounded-lg border border-gray-200 object-cover">
                        @endif
                        <input type="file" name="license_back" id="license_back" accept="image/*" class="block w-full text-sm text-gray-600">
                    </div>
                </div>
                <div class="mt-4">
                    <label for="license_expiration_date" class="block text-sm font-medium text-gray-700">License Expiration Date</label>
                    <input type="date" name="license_expiration_date" id="license_expiration_date" value="{{ old('license_expiration_date', optional($driver->license_expiration_date)->format('Y-m-d')) }}"
                        class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <label for="password" class="block text-sm font-medium text-gray-700">New password <span class="font-normal text-gray-400">(optional)</span></label>
                <input type="password" name="password" id="password"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('password') border-red-300 @enderror">
                @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                <a href="{{ route('driver.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection
