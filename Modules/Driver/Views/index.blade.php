@extends('layouts.app')

@section('title', 'Drivers')
@section('page-title', 'Drivers')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Manage Drivers</h2>
            <p class="mt-1 text-sm text-gray-500">View, register, update, and remove driver accounts.</p>
        </div>

        <button
            type="button"
            onclick="openAddDriverModal()"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Register Driver
        </button>
    </div>

    {{-- Toolbar: search + view toggle --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('driver.index') }}" id="driver-search-form" class="flex w-full max-w-sm items-center gap-2" onsubmit="return false;">
            <div class="relative w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    id="driver-search-input"
                    value="{{ request('search') }}"
                    autocomplete="off"
                    placeholder="Search by name, email, or address…"
                    oninput="onDriverSearchInput()"
                    class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-8 text-sm placeholder-gray-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                >
                <svg id="driver-search-spinner" xmlns="http://www.w3.org/2000/svg" class="hidden absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
            </div>

            <a href="{{ route('driver.index') }}" id="driver-search-clear" onclick="return clearDriverSearch(event)" class="shrink-0 text-sm font-medium text-gray-500 hover:text-gray-700 {{ request('search') ? '' : 'hidden' }}">Clear</a>
        </form>

        {{-- View toggle --}}
        <div class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white p-1 self-start">
            <button
                type="button"
                id="driver-view-toggle-list"
                onclick="setDriverView('list')"
                class="driver-view-toggle-btn flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                List
            </button>
            <button
                type="button"
                id="driver-view-toggle-grid"
                onclick="setDriverView('grid')"
                class="driver-view-toggle-btn flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                Grid
            </button>
        </div>
    </div>

    <div id="driver-results">
        @include('driver::partials.driver-results', ['drivers' => $drivers])
    </div>

    {{-- Register Driver modal --}}
    <div id="add-driver-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl my-auto">

            {{-- Step 1: form --}}
            <div id="add-driver-step-form">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Create Account</h2>
                    <button type="button" onclick="closeAddDriverModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-sm text-gray-500">Add a new driver account to the system.</p>

                <form method="POST" action="{{ route('driver.store') }}" id="add-driver-form" class="mt-5 space-y-4" enctype="multipart/form-data" onsubmit="return handleAddDriverSubmit(event)">
                    @csrf
                    <input type="hidden" name="role" value="{{ \Modules\Admin\Enums\UserRole::DRIVER->value }}">
                    <input type="hidden" name="_driver_form" value="create">

                    <div>
                        <label for="add-driver-name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input
                            type="text"
                            name="name"
                            id="add-driver-name"
                            placeholder="e.g. Juan Dela Cruz"
                            value="{{ old('_driver_form') === 'create' ? old('name') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-red-300 @enderror"
                            required
                        >
                        @error('name')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Photo</label>
                        <div class="mt-1.5 flex items-center gap-4">
                            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-gray-400">
                                <img id="add-driver-avatar-preview-img" src="" alt="" class="hidden h-full w-full object-cover">
                                <svg id="add-driver-avatar-preview-icon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>
                                </svg>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="add-driver-avatar" class="inline-flex w-fit cursor-pointer items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition">
                                    Choose File
                                </label>
                                <input type="file" name="avatar" id="add-driver-avatar" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewDriverFile(this, 'add-driver-avatar-preview-img', 'add-driver-avatar-preview-icon')">
                                <span class="text-xs text-gray-400">JPG, PNG, or WEBP. Max 5MB.</span>
                            </div>
                        </div>
                        @error('avatar')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="add-driver-birth-date" class="block text-sm font-medium text-gray-700">Birth Date</label>
                            <input
                                type="date"
                                name="birth_date"
                                id="add-driver-birth-date"
                                onchange="updateDriverAge('add-driver-birth-date', 'add-driver-age')"
                                value="{{ old('_driver_form') === 'create' ? old('birth_date') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('birth_date') border-red-300 @enderror"
                            >
                            @error('birth_date')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="add-driver-age" class="block text-sm font-medium text-gray-700">Age</label>
                            <input type="text" id="add-driver-age" placeholder="Auto-calculated" disabled
                                class="mt-1.5 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500">
                        </div>
                    </div>

                    <div>
                        <label for="add-driver-address" class="block text-sm font-medium text-gray-700">Address</label>
                        <input
                            type="text"
                            name="address"
                            id="add-driver-address"
                            placeholder="e.g. 123 Rizal St., Calapan City, Oriental Mindoro"
                            value="{{ old('_driver_form') === 'create' ? old('address') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('address') border-red-300 @enderror"
                        >
                        @error('address')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="add-driver-email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            type="email"
                            name="email"
                            id="add-driver-email"
                            placeholder="e.g. juan@agency.gov.ph"
                            value="{{ old('_driver_form') === 'create' ? old('email') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('email') border-red-300 @enderror"
                            required
                        >
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="add-driver-password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input
                                type="password"
                                name="password"
                                id="add-driver-password"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('password') border-red-300 @enderror"
                                required
                            >
                            @error('password')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="add-driver-password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input
                                type="password"
                                name="password_confirmation"
                                id="add-driver-password_confirmation"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                required
                            >
                        </div>
                    </div>

                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                        <p class="text-sm font-semibold text-emerald-900">Driver's License Information</p>

                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">License Front Photo</label>
                                <div class="mt-1.5 flex items-center gap-3">
                                    <div class="flex h-16 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white text-gray-300">
                                        <img id="add-driver-license-front-preview" src="" alt="" class="hidden h-full w-full object-cover">
                                        <svg id="add-driver-license-front-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/>
                                        </svg>
                                    </div>
                                    <label for="add-driver-license-front" class="inline-flex cursor-pointer items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition">
                                        Choose File
                                    </label>
                                    <input type="file" name="license_front" id="add-driver-license-front" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewDriverFile(this, 'add-driver-license-front-preview', 'add-driver-license-front-icon')">
                                </div>
                                @error('license_front')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">License Back Photo</label>
                                <div class="mt-1.5 flex items-center gap-3">
                                    <div class="flex h-16 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white text-gray-300">
                                        <img id="add-driver-license-back-preview" src="" alt="" class="hidden h-full w-full object-cover">
                                        <svg id="add-driver-license-back-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/>
                                        </svg>
                                    </div>
                                    <label for="add-driver-license-back" class="inline-flex cursor-pointer items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition">
                                        Choose File
                                    </label>
                                    <input type="file" name="license_back" id="add-driver-license-back" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewDriverFile(this, 'add-driver-license-back-preview', 'add-driver-license-back-icon')">
                                </div>
                                @error('license_back')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="add-driver-license-expiration" class="block text-sm font-medium text-gray-700">License Expiration Date</label>
                            <input
                                type="date"
                                name="license_expiration_date"
                                id="add-driver-license-expiration"
                                value="{{ old('_driver_form') === 'create' ? old('license_expiration_date') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('license_expiration_date') border-red-300 @enderror"
                            >
                            @error('license_expiration_date')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" onclick="closeAddDriverModal()" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition"
                        >
                            Create Account
                        </button>
                    </div>
                </form>
            </div>

            {{-- Step 2: confirmation --}}
            <div id="add-driver-step-confirm" class="hidden">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-gray-900">Register this driver?</h2>
                <p class="mt-1 text-sm text-gray-500">Please confirm the details below before creating the account.</p>

                <dl class="mt-4 space-y-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Name</dt>
                        <dd id="add-driver-confirm-name" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Email</dt>
                        <dd id="add-driver-confirm-email" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Role</dt>
                        <dd class="text-sm font-medium text-gray-900">Driver</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">License expiry</dt>
                        <dd id="add-driver-confirm-license" class="text-sm font-medium text-gray-900"></dd>
                    </div>
                </dl>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="backToAddDriverForm()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Back
                    </button>
                    <button type="button" onclick="confirmAddDriver()" class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition">
                        Yes, create account
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any() && old('_driver_form') === 'create')
        <script>document.addEventListener('DOMContentLoaded', () => openAddDriverModal());</script>
    @endif

    {{-- Edit Driver modal --}}
    <div id="edit-driver-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl my-auto">

            <div id="edit-driver-step-form">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Edit driver account</h2>
                    <button type="button" onclick="closeEditDriverModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <p id="edit-driver-subtitle" class="mt-1 text-sm text-gray-500">Leave password blank to keep it unchanged.</p>

                <form method="POST" action="{{ old('_driver_action', route('driver.index')) }}" id="edit-driver-form" class="mt-5 space-y-4" enctype="multipart/form-data" onsubmit="return handleEditDriverSubmit(event)">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="role" value="{{ \Modules\Admin\Enums\UserRole::DRIVER->value }}">
                    <input type="hidden" name="_driver_form" value="edit">
                    <input type="hidden" name="_driver_action" id="edit-driver-action-field" value="{{ old('_driver_action') }}">
                    <input type="hidden" name="remove_avatar" id="edit-driver-remove-avatar" value="0">

                    <div>
                        <label for="edit-driver-name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input
                            type="text"
                            name="name"
                            id="edit-driver-name"
                            value="{{ old('_driver_form') === 'edit' ? old('name') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-red-300 @enderror"
                            required
                        >
                        @error('name')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Photo</label>
                        <div class="mt-1.5 flex items-center gap-4">
                            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-50 text-lg font-semibold text-emerald-700">
                                <img id="edit-driver-avatar-preview-img" src="" alt="" class="hidden h-full w-full object-cover">
                                <span id="edit-driver-avatar-preview-initial"></span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-3">
                                    <label for="edit-driver-avatar" class="inline-flex cursor-pointer items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition">
                                        Choose File
                                    </label>
                                    <button type="button" id="edit-driver-avatar-remove-btn" onclick="removeEditDriverAvatar()" class="hidden text-sm font-medium text-red-600 hover:text-red-700">
                                        Remove
                                    </button>
                                </div>
                                <input type="file" name="avatar" id="edit-driver-avatar" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewEditDriverAvatarFile(this)">
                                <span class="text-xs text-gray-400">JPG, PNG or WEBP, up to 5MB.</span>
                            </div>
                        </div>
                        @error('avatar')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="edit-driver-birth-date" class="block text-sm font-medium text-gray-700">Birth Date</label>
                            <input
                                type="date"
                                name="birth_date"
                                id="edit-driver-birth-date"
                                onchange="updateDriverAge('edit-driver-birth-date', 'edit-driver-age')"
                                value="{{ old('_driver_form') === 'edit' ? old('birth_date') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                        </div>
                        <div>
                            <label for="edit-driver-age" class="block text-sm font-medium text-gray-700">Age</label>
                            <input type="text" id="edit-driver-age" placeholder="Auto-calculated" disabled
                                class="mt-1.5 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500">
                        </div>
                    </div>

                    <div>
                        <label for="edit-driver-address" class="block text-sm font-medium text-gray-700">Address</label>
                        <input
                            type="text"
                            name="address"
                            id="edit-driver-address"
                            value="{{ old('_driver_form') === 'edit' ? old('address') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>

                    <div>
                        <label for="edit-driver-email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input
                            type="email"
                            name="email"
                            id="edit-driver-email"
                            value="{{ old('_driver_form') === 'edit' ? old('email') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('email') border-red-300 @enderror"
                            required
                        >
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                        <p class="text-sm font-semibold text-emerald-900">Driver's License Information</p>

                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">License Front Photo</label>
                                <div class="mt-1.5 flex items-center gap-3">
                                    <div class="flex h-16 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white text-gray-300">
                                        <img id="edit-driver-license-front-preview" src="" alt="" class="hidden h-full w-full object-cover">
                                        <svg id="edit-driver-license-front-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/>
                                        </svg>
                                    </div>
                                    <label for="edit-driver-license-front" class="inline-flex cursor-pointer items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition">
                                        Choose File
                                    </label>
                                    <input type="file" name="license_front" id="edit-driver-license-front" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewDriverFile(this, 'edit-driver-license-front-preview', 'edit-driver-license-front-icon')">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">License Back Photo</label>
                                <div class="mt-1.5 flex items-center gap-3">
                                    <div class="flex h-16 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white text-gray-300">
                                        <img id="edit-driver-license-back-preview" src="" alt="" class="hidden h-full w-full object-cover">
                                        <svg id="edit-driver-license-back-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/>
                                        </svg>
                                    </div>
                                    <label for="edit-driver-license-back" class="inline-flex cursor-pointer items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition">
                                        Choose File
                                    </label>
                                    <input type="file" name="license_back" id="edit-driver-license-back" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewDriverFile(this, 'edit-driver-license-back-preview', 'edit-driver-license-back-icon')">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="edit-driver-license-expiration" class="block text-sm font-medium text-gray-700">License Expiration Date</label>
                            <input
                                type="date"
                                name="license_expiration_date"
                                id="edit-driver-license-expiration"
                                value="{{ old('_driver_form') === 'edit' ? old('license_expiration_date') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <label for="edit-driver-password" class="block text-sm font-medium text-gray-700">New password <span class="font-normal text-gray-400">(optional)</span></label>
                        <input
                            type="password"
                            name="password"
                            id="edit-driver-password"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('password') border-red-300 @enderror"
                        >
                        @error('password')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-driver-password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="edit-driver-password_confirmation"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" onclick="closeEditDriverModal()" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition"
                        >
                            Continue
                        </button>
                    </div>
                </form>
            </div>

            <div id="edit-driver-step-confirm" class="hidden">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-gray-900">Save these changes?</h2>
                <p class="mt-1 text-sm text-gray-500">Please confirm the updated details below.</p>

                <dl class="mt-4 space-y-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Name</dt>
                        <dd id="edit-driver-confirm-name" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Email</dt>
                        <dd id="edit-driver-confirm-email" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Password</dt>
                        <dd id="edit-driver-confirm-password" class="text-sm font-medium text-gray-900"></dd>
                    </div>
                </dl>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="backToEditDriverForm()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Back
                    </button>
                    <button type="button" onclick="confirmEditDriver()" class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition">
                        Yes, save changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any() && old('_driver_form') === 'edit')
        <script>document.addEventListener('DOMContentLoaded', () => openEditDriverModal(null));</script>
    @endif

    {{-- View details modal --}}
    <div id="view-driver-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl my-auto">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Driver details</h2>
                <button type="button" onclick="closeViewDriverModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-5 flex items-center gap-3">
                <div class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-50 text-base font-semibold text-emerald-700">
                    <img id="view-driver-avatar-img" src="" alt="" class="hidden h-full w-full object-cover">
                    <span id="view-driver-avatar"></span>
                </div>
                <div class="min-w-0">
                    <p id="view-driver-name" class="truncate text-base font-semibold text-gray-900"></p>
                    <p id="view-driver-email" class="truncate text-sm text-gray-500"></p>
                </div>
            </div>

            <dl class="mt-5 space-y-3 border-t border-gray-100 pt-4">
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Address</dt>
                    <dd id="view-driver-address" class="max-w-[60%] truncate text-right text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Birth date</dt>
                    <dd id="view-driver-birth-date" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Age</dt>
                    <dd id="view-driver-age" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">License expiry</dt>
                    <dd id="view-driver-license-expiration" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Joined</dt>
                    <dd id="view-driver-created" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Last updated</dt>
                    <dd id="view-driver-updated" class="text-sm font-medium text-gray-900"></dd>
                </div>
            </dl>

            <div id="view-driver-license-photos" class="mt-4 hidden grid grid-cols-2 gap-3 border-t border-gray-100 pt-4">
                <div>
                    <p class="mb-1 text-xs font-medium text-gray-500">License front</p>
                    <img id="view-driver-license-front" src="" alt="License front" class="h-20 w-full rounded-lg border border-gray-200 object-cover">
                </div>
                <div>
                    <p class="mb-1 text-xs font-medium text-gray-500">License back</p>
                    <img id="view-driver-license-back" src="" alt="License back" class="h-20 w-full rounded-lg border border-gray-200 object-cover">
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    onclick="closeViewDriverModal()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal --}}
    <div id="delete-driver-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-red-50 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4M12 17h.01"/>
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-semibold text-gray-900">Delete driver?</h2>
            <p class="mt-1 text-sm text-gray-500">
                This will permanently remove <span id="delete-driver-name" class="font-medium text-gray-700"></span>'s account. This action cannot be undone.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    onclick="closeDeleteDriverModal()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                >
                    Cancel
                </button>

                <form id="delete-driver-form" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition"
                    >
                        Yes, delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .driver-view-toggle-btn { color: #6b7280; }
        .driver-view-toggle-btn.active { background: linear-gradient(to right, #059669, #16a34a); color: #fff; }
    </style>

    <script>
        function applyDriverView(mode) {
            const list = document.getElementById('driver-view-list');
            const grid = document.getElementById('driver-view-grid');
            const btnList = document.getElementById('driver-view-toggle-list');
            const btnGrid = document.getElementById('driver-view-toggle-grid');

            if (!list || !grid) return;

            if (mode === 'grid') {
                list.classList.add('hidden');
                grid.classList.remove('hidden');
                btnGrid?.classList.add('active');
                btnList?.classList.remove('active');
            } else {
                grid.classList.add('hidden');
                list.classList.remove('hidden');
                btnList?.classList.add('active');
                btnGrid?.classList.remove('active');
            }
        }

        function currentDriverViewMode() {
            return localStorage.getItem('driver_view_mode') || 'list';
        }

        function setDriverView(mode) {
            localStorage.setItem('driver_view_mode', mode);
            applyDriverView(mode);
        }

        document.addEventListener('DOMContentLoaded', function () {
            applyDriverView(currentDriverViewMode());
        });

        function updateDriverAge(dateFieldId, ageFieldId) {
            const value = document.getElementById(dateFieldId).value;
            const ageField = document.getElementById(ageFieldId);
            if (!value) {
                ageField.value = '';
                return;
            }
            const birth = new Date(value);
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            ageField.value = age >= 0 ? age + ' years old' : '';
        }

        function previewDriverFile(input, imgId, iconId) {
            const file = input.files && input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById(imgId);
                img.src = e.target.result;
                img.classList.remove('hidden');
                document.getElementById(iconId).classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        /* ---------------- View modal ---------------- */

        function openViewDriverModal(btn) {
            const img = document.getElementById('view-driver-avatar-img');
            const initial = document.getElementById('view-driver-avatar');
            if (btn.dataset.avatar) {
                img.src = btn.dataset.avatar;
                img.classList.remove('hidden');
                initial.classList.add('hidden');
            } else {
                img.classList.add('hidden');
                initial.classList.remove('hidden');
                initial.textContent = btn.dataset.initial;
            }
            document.getElementById('view-driver-name').textContent = btn.dataset.name;
            document.getElementById('view-driver-email').textContent = btn.dataset.email;
            document.getElementById('view-driver-address').textContent = btn.dataset.address || '—';
            document.getElementById('view-driver-birth-date').textContent = btn.dataset.birthDate || '—';
            document.getElementById('view-driver-age').textContent = btn.dataset.age ? btn.dataset.age + ' years old' : '—';
            document.getElementById('view-driver-license-expiration').textContent = btn.dataset.licenseExpiration || '—';
            document.getElementById('view-driver-created').textContent = btn.dataset.created;
            document.getElementById('view-driver-updated').textContent = btn.dataset.updated;

            const photos = document.getElementById('view-driver-license-photos');
            if (btn.dataset.licenseFront || btn.dataset.licenseBack) {
                photos.classList.remove('hidden');
                document.getElementById('view-driver-license-front').src = btn.dataset.licenseFront || '';
                document.getElementById('view-driver-license-back').src = btn.dataset.licenseBack || '';
            } else {
                photos.classList.add('hidden');
            }

            const modal = document.getElementById('view-driver-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeViewDriverModal() {
            const modal = document.getElementById('view-driver-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        /* ---------------- Delete modal ---------------- */

        function openDeleteDriverModal(btn) {
            document.getElementById('delete-driver-name').textContent = btn.dataset.name;
            document.getElementById('delete-driver-form').action = btn.dataset.deleteUrl;

            const modal = document.getElementById('delete-driver-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteDriverModal() {
            const modal = document.getElementById('delete-driver-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        /* ---------------- Add Driver modal ---------------- */

        function openAddDriverModal() {
            const modal = document.getElementById('add-driver-modal');
            document.getElementById('add-driver-step-confirm').classList.add('hidden');
            document.getElementById('add-driver-step-form').classList.remove('hidden');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAddDriverModal() {
            const modal = document.getElementById('add-driver-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function handleAddDriverSubmit(event) {
            event.preventDefault();
            const form = document.getElementById('add-driver-form');

            if (!form.reportValidity()) {
                return false;
            }

            const password = document.getElementById('add-driver-password').value;
            const confirmation = document.getElementById('add-driver-password_confirmation').value;
            if (password !== confirmation) {
                alert('Password and confirmation do not match.');
                return false;
            }

            document.getElementById('add-driver-confirm-name').textContent = document.getElementById('add-driver-name').value;
            document.getElementById('add-driver-confirm-email').textContent = document.getElementById('add-driver-email').value;
            document.getElementById('add-driver-confirm-license').textContent = document.getElementById('add-driver-license-expiration').value || 'Not set';

            document.getElementById('add-driver-step-form').classList.add('hidden');
            document.getElementById('add-driver-step-confirm').classList.remove('hidden');
            return false;
        }

        function backToAddDriverForm() {
            document.getElementById('add-driver-step-confirm').classList.add('hidden');
            document.getElementById('add-driver-step-form').classList.remove('hidden');
        }

        function confirmAddDriver() {
            document.getElementById('add-driver-form').submit();
        }

        /* ---------------- Edit Driver modal ---------------- */

        function openEditDriverModal(btn) {
            const modal = document.getElementById('edit-driver-modal');
            const form = document.getElementById('edit-driver-form');

            document.getElementById('edit-driver-avatar').value = '';
            document.getElementById('edit-driver-remove-avatar').value = '0';

            if (btn) {
                form.action = btn.dataset.updateUrl;
                document.getElementById('edit-driver-action-field').value = btn.dataset.updateUrl;
                document.getElementById('edit-driver-name').value = btn.dataset.name;
                document.getElementById('edit-driver-email').value = btn.dataset.email;
                document.getElementById('edit-driver-address').value = btn.dataset.address || '';
                document.getElementById('edit-driver-birth-date').value = btn.dataset.birthDate || '';
                document.getElementById('edit-driver-license-expiration').value = btn.dataset.licenseExpiration || '';
                document.getElementById('edit-driver-subtitle').textContent = "Update " + btn.dataset.name + "'s details. Leave password blank to keep it unchanged.";
                document.getElementById('edit-driver-password').value = '';
                document.getElementById('edit-driver-password_confirmation').value = '';
                updateDriverAge('edit-driver-birth-date', 'edit-driver-age');

                const img = document.getElementById('edit-driver-avatar-preview-img');
                const initial = document.getElementById('edit-driver-avatar-preview-initial');
                const removeBtn = document.getElementById('edit-driver-avatar-remove-btn');
                if (btn.dataset.avatar) {
                    img.src = btn.dataset.avatar;
                    img.classList.remove('hidden');
                    initial.classList.add('hidden');
                    removeBtn.classList.remove('hidden');
                } else {
                    img.src = '';
                    img.classList.add('hidden');
                    initial.classList.remove('hidden');
                    initial.textContent = btn.dataset.initial || '';
                    removeBtn.classList.add('hidden');
                }

                const lf = document.getElementById('edit-driver-license-front-preview');
                const lfIcon = document.getElementById('edit-driver-license-front-icon');
                if (btn.dataset.licenseFront) {
                    lf.src = btn.dataset.licenseFront;
                    lf.classList.remove('hidden');
                    lfIcon.classList.add('hidden');
                } else {
                    lf.classList.add('hidden');
                    lfIcon.classList.remove('hidden');
                }

                const lb = document.getElementById('edit-driver-license-back-preview');
                const lbIcon = document.getElementById('edit-driver-license-back-icon');
                if (btn.dataset.licenseBack) {
                    lb.src = btn.dataset.licenseBack;
                    lb.classList.remove('hidden');
                    lbIcon.classList.add('hidden');
                } else {
                    lb.classList.add('hidden');
                    lbIcon.classList.remove('hidden');
                }
            }

            document.getElementById('edit-driver-step-confirm').classList.add('hidden');
            document.getElementById('edit-driver-step-form').classList.remove('hidden');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function previewEditDriverAvatarFile(input) {
            const file = input.files && input.files[0];
            if (!file) return;

            document.getElementById('edit-driver-remove-avatar').value = '0';

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('edit-driver-avatar-preview-img');
                img.src = e.target.result;
                img.classList.remove('hidden');
                document.getElementById('edit-driver-avatar-preview-initial').classList.add('hidden');
                document.getElementById('edit-driver-avatar-remove-btn').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        function removeEditDriverAvatar() {
            document.getElementById('edit-driver-avatar').value = '';
            document.getElementById('edit-driver-remove-avatar').value = '1';

            const img = document.getElementById('edit-driver-avatar-preview-img');
            img.src = '';
            img.classList.add('hidden');
            document.getElementById('edit-driver-avatar-preview-initial').classList.remove('hidden');
            document.getElementById('edit-driver-avatar-remove-btn').classList.add('hidden');
        }

        function closeEditDriverModal() {
            const modal = document.getElementById('edit-driver-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function handleEditDriverSubmit(event) {
            event.preventDefault();
            const form = document.getElementById('edit-driver-form');

            if (!form.reportValidity()) {
                return false;
            }

            const password = document.getElementById('edit-driver-password').value;
            const confirmation = document.getElementById('edit-driver-password_confirmation').value;
            if (password !== confirmation) {
                alert('Password and confirmation do not match.');
                return false;
            }

            document.getElementById('edit-driver-confirm-name').textContent = document.getElementById('edit-driver-name').value;
            document.getElementById('edit-driver-confirm-email').textContent = document.getElementById('edit-driver-email').value;
            document.getElementById('edit-driver-confirm-password').textContent = password ? 'Will be changed' : 'Unchanged';

            document.getElementById('edit-driver-step-form').classList.add('hidden');
            document.getElementById('edit-driver-step-confirm').classList.remove('hidden');
            return false;
        }

        function backToEditDriverForm() {
            document.getElementById('edit-driver-step-confirm').classList.add('hidden');
            document.getElementById('edit-driver-step-form').classList.remove('hidden');
        }

        function confirmEditDriver() {
            document.getElementById('edit-driver-form').submit();
        }

        /* ---------------- Live search ---------------- */

        let driverSearchTimer = null;
        let driverSearchController = null;

        function onDriverSearchInput() {
            const value = document.getElementById('driver-search-input').value;
            document.getElementById('driver-search-clear').classList.toggle('hidden', value.length === 0);

            clearTimeout(driverSearchTimer);
            driverSearchTimer = setTimeout(() => runDriverSearch(value), 300);
        }

        function clearDriverSearch(event) {
            event.preventDefault();
            document.getElementById('driver-search-input').value = '';
            document.getElementById('driver-search-clear').classList.add('hidden');
            runDriverSearch('');
            return false;
        }

        function runDriverSearch(term) {
            const url = new URL(window.location.href);
            if (term) {
                url.searchParams.set('search', term);
            } else {
                url.searchParams.delete('search');
            }

            const spinner = document.getElementById('driver-search-spinner');
            spinner.classList.remove('hidden');

            if (driverSearchController) {
                driverSearchController.abort();
            }
            driverSearchController = new AbortController();

            fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: driverSearchController.signal,
            })
                .then((response) => response.text())
                .then((html) => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const fresh = doc.getElementById('driver-results');
                    if (fresh) {
                        document.getElementById('driver-results').innerHTML = fresh.innerHTML;
                        applyDriverView(currentDriverViewMode());
                    }
                    window.history.replaceState({}, '', url.toString());
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        console.error('Driver search failed', error);
                    }
                })
                .finally(() => {
                    spinner.classList.add('hidden');
                });
        }
    </script>
@endsection
