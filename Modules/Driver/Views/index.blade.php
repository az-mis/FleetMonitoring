@extends('layouts.app')

@section('title', 'Drivers')
@section('page-title', 'Drivers')

@section('content')
    @php
        $locationOptions = [
            'Oriental Mindoro',
            'Occidental Mindoro',
            'Marinduque',
            'Palawan',
            'Romblon',
            'Quezon City Satellite Office',
        ];
        $selectedLocation = request('location');
        $locationButtonLabel = $selectedLocation ?: 'All Locations';
    @endphp

    <div class="mb-4 rounded-2xl bg-gradient-to-br from-[#155f35] via-[#1d8148] to-[#269d59] p-4 text-white shadow-lg shadow-emerald-900/15">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-extrabold tracking-tight">Drivers</h2>
                    <p class="truncate text-xs text-white/80">{{ $drivers->total() }} on record</p>
                </div>
            </div>

            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-end">
                <form method="GET" action="{{ route('driver.index') }}" id="driver-search-form" class="flex w-full min-w-0 items-center gap-2 md:w-56 xl:w-80" onsubmit="return false;">
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
                            placeholder="Search by name or email..."
                            oninput="onDriverSearchInput()"
                            class="w-full rounded-xl border-0 bg-white py-2 pl-9 pr-8 text-xs text-slate-800 placeholder-slate-400 shadow-inner focus:outline-none focus:ring-2 focus:ring-emerald-300"
                        >
                        <svg id="driver-search-spinner" xmlns="http://www.w3.org/2000/svg" class="hidden absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                        </svg>
                    </div>

                    <a href="{{ route('driver.index') }}" id="driver-search-clear" onclick="return clearDriverSearch(event)" class="shrink-0 text-xs font-semibold text-white/80 hover:text-white {{ request('search') ? '' : 'hidden' }}">Clear</a>
                </form>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    {{-- View toggle buttons --}}
                    <div class="inline-flex shrink-0 items-center gap-0.5 rounded-xl bg-black/20 p-1 backdrop-blur-sm">
                        <button type="button" id="driver-view-toggle-list" onclick="setDriverView('list')" title="List view" class="driver-view-toggle-btn rounded-lg p-1.5 transition text-white/70 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 6h13M8 12h13M8 18h13"/>
                                <path d="M3 6h.01M3 12h.01M3 18h.01"/>
                            </svg>
                        </button>
                        <button type="button" id="driver-view-toggle-grid" onclick="setDriverView('grid')" title="Grid view" class="driver-view-toggle-btn rounded-lg p-1.5 transition text-white/70 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1"/>
                                <rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/>
                                <rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Location filter dropdown --}}
                    <div class="relative shrink-0 flex-1 md:flex-initial">
                        <button type="button" onclick="toggleDriverLocationMenu()" class="inline-flex w-full items-center justify-between gap-2 rounded-xl bg-black/20 px-3 py-2 text-xs font-semibold text-white backdrop-blur-sm transition hover:bg-black/30 md:w-auto cursor-pointer">
                            <span class="flex items-center gap-1.5 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white/80 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span class="truncate">{{ $locationButtonLabel }}</span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white/70 shrink-0 transition" id="driver-location-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div id="driver-location-menu" class="absolute right-0 z-20 mt-2 hidden w-64 overflow-hidden rounded-xl border border-gray-100 bg-white p-1.5 shadow-xl ring-1 ring-black/5">
                            <a href="{{ route('driver.index', array_filter(['search' => request('search'), 'per_page' => request('per_page')])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold transition {{ blank($selectedLocation) ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-gray-50' }}">
                                <span>All Locations</span>
                                @if(blank($selectedLocation))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                @endif
                            </a>
                            @foreach ($locationOptions as $opt)
                                <a href="{{ route('driver.index', array_filter(['search' => request('search'), 'per_page' => request('per_page'), 'location' => $opt])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 {{ $selectedLocation === $opt ? 'bg-emerald-50 text-emerald-800' : '' }}">
                                    <span>{{ $opt }}</span>
                                    @if($selectedLocation === $opt)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="openAddDriverModal()"
                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-extrabold text-emerald-800 shadow-sm hover:bg-emerald-50 transition md:w-auto cursor-pointer"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>Add Driver</span>
                </button>
            </div>
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
                    <h2 class="text-lg font-semibold text-gray-900">Add Driver</h2>
                    <button type="button" onclick="closeAddDriverModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('driver.store') }}" id="add-driver-form" class="mt-5 space-y-4" enctype="multipart/form-data" onsubmit="return handleAddDriverSubmit(event)">
                    @csrf
                    <input type="hidden" name="role" value="{{ \Modules\Admin\Enums\UserRole::DRIVER->value }}">
                    <input type="hidden" name="_driver_form" value="create">

                    {{-- Centered avatar upload --}}
                    <div class="flex flex-col items-center gap-2">
                        <div class="relative cursor-pointer" onclick="document.getElementById('add-driver-avatar').click()">
                            <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-emerald-600 text-white text-2xl font-bold" id="add-driver-avatar-circle">
                                <img id="add-driver-avatar-preview-img" src="" alt="" class="hidden h-full w-full object-cover rounded-full">
                                <span id="add-driver-avatar-initial">D</span>
                            </div>
                            <span class="absolute bottom-0 right-0 flex h-6 w-6 items-center justify-center rounded-full bg-white border border-gray-200 shadow">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z"/><circle cx="12" cy="13" r="3"/>
                                </svg>
                            </span>
                        </div>
                        <input type="file" name="avatar" id="add-driver-avatar" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewAddDriverAvatar(this)">
                        <p class="text-xs text-gray-400">Photo (optional, max 5MB)</p>
                        @error('avatar')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Full Name --}}
                    <div>
                        <label for="add-driver-name" class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="name"
                            id="add-driver-name"
                            placeholder="Driver's full name"
                            value="{{ old('_driver_form') === 'create' ? old('name') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-red-300 @enderror"
                            required
                            oninput="updateAddDriverInitial(this.value)"
                        >
                        @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="add-driver-email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                        <input
                            type="email"
                            name="email"
                            id="add-driver-email"
                            placeholder="driver@example.com"
                            value="{{ old('_driver_form') === 'create' ? old('email') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('email') border-red-300 @enderror"
                            required
                        >
                        @error('email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Temporary Password --}}
                    <div>
                        <label for="add-driver-password" class="block text-sm font-medium text-gray-700">Temporary Password <span class="text-red-500">*</span></label>
                        <input
                            type="password"
                            name="password"
                            id="add-driver-password"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('password') border-red-300 @enderror"
                            required
                        >
                        <input type="hidden" name="password_confirmation" id="add-driver-password-confirm">
                        @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Assigned Location / Office Province --}}
                    <div>
                        <label for="add-driver-location" class="block text-sm font-medium text-gray-700">Assigned Location / Office Province</label>
                        <select
                            name="location"
                            id="add-driver-location"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                            <option value="">— All Locations (No restriction) —</option>
                            @foreach($locationOptions as $loc)
                                <option value="{{ $loc }}" {{ old('_driver_form') === 'create' && old('location') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">Drivers assigned to a specific province will be prioritized for vehicle assignments in that location.</p>
                    </div>

                    {{-- Birth Date + License No. --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="add-driver-birth-date" class="block text-sm font-medium text-gray-700">Birth Date</label>
                            <input
                                type="date"
                                name="birth_date"
                                id="add-driver-birth-date"
                                value="{{ old('_driver_form') === 'create' ? old('birth_date') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('birth_date') border-red-300 @enderror"
                            >
                            @error('birth_date')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="add-driver-license-no" class="block text-sm font-medium text-gray-700">License No.</label>
                            <input
                                type="text"
                                name="license_number"
                                id="add-driver-license-no"
                                placeholder="e.g. N01-23-456789"
                                value="{{ old('_driver_form') === 'create' ? old('license_number') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('license_number') border-red-300 @enderror"
                            >
                            @error('license_number')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- License Expiration + Phone Number --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="add-driver-license-expiration" class="block text-sm font-medium text-gray-700">License Expiration</label>
                            <input
                                type="date"
                                name="license_expiration_date"
                                id="add-driver-license-expiration"
                                value="{{ old('_driver_form') === 'create' ? old('license_expiration_date') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('license_expiration_date') border-red-300 @enderror"
                            >
                            @error('license_expiration_date')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="add-driver-phone" class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                name="phone_number"
                                id="add-driver-phone"
                                placeholder="e.g. 0917-1234567"
                                value="{{ old('_driver_form') === 'create' ? old('phone_number') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label for="add-driver-address" class="block text-sm font-medium text-gray-700">Address</label>
                        <input
                            type="text"
                            name="address"
                            id="add-driver-address"
                            placeholder="Complete address"
                            value="{{ old('_driver_form') === 'create' ? old('address') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('address') border-red-300 @enderror"
                        >
                        @error('address')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-[#005a36] py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 transition"
                    >
                        Add Driver
                    </button>
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
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-extrabold text-gray-900">Edit Driver</h2>
                    <button type="button" onclick="closeEditDriverModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ old('_driver_action', route('driver.index')) }}" id="edit-driver-form" class="mt-4 space-y-4" enctype="multipart/form-data" onsubmit="return handleEditDriverSubmit(event)">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="role" value="{{ \Modules\Admin\Enums\UserRole::DRIVER->value }}">
                    <input type="hidden" name="_driver_form" value="edit">
                    <input type="hidden" name="_driver_action" id="edit-driver-action-field" value="{{ old('_driver_action') }}">
                    <input type="hidden" name="remove_avatar" id="edit-driver-remove-avatar" value="0">

                    {{-- Centered Avatar Uploader with Change & Remove options --}}
                    <div class="flex flex-col items-center justify-center">
                        <div class="relative">
                            <div class="relative flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-2 border-emerald-500 bg-gray-100 text-gray-400 shadow-sm">
                                <img id="edit-driver-avatar-preview-img" src="" alt="" class="hidden h-full w-full object-cover">
                                <span id="edit-driver-avatar-preview-initial" class="text-2xl font-bold text-gray-700"></span>
                            </div>
                            <input type="file" name="avatar" id="edit-driver-avatar" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewEditDriverAvatarFile(this)">
                        </div>

                        <div class="mt-2.5 flex items-center gap-2">
                            <label for="edit-driver-avatar" class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                    <circle cx="12" cy="13" r="3"/>
                                </svg>
                                <span>Change Photo</span>
                            </label>
                            <button
                                type="button"
                                id="edit-driver-avatar-remove-btn"
                                onclick="removeEditDriverAvatar()"
                                class="hidden inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-100 transition"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18"/>
                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                </svg>
                                <span>Remove</span>
                            </button>
                        </div>
                        <p class="mt-1 text-[11px] font-medium text-slate-400">JPG, PNG, or WEBP. Max 5MB.</p>
                    </div>

                    {{-- Full Name --}}
                    <div>
                        <label for="edit-driver-name" class="block text-xs font-bold text-gray-700">Full Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="name"
                            id="edit-driver-name"
                            value="{{ old('_driver_form') === 'edit' ? old('name') : '' }}"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-red-300 @enderror"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email (Disabled look + helper note) --}}
                    <div>
                        <label for="edit-driver-email" class="block text-xs font-bold text-gray-700">Email <span class="text-red-500">*</span></label>
                        <input
                            type="email"
                            name="email"
                            id="edit-driver-email"
                            value="{{ old('_driver_form') === 'edit' ? old('email') : '' }}"
                            class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-100/70 px-3 py-2 text-sm text-slate-600 focus:outline-none cursor-not-allowed @error('email') border-red-300 @enderror"
                            required
                            readonly
                        >
                        <p class="mt-1 text-[11px] text-slate-400">Email changes require a backend admin action.</p>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Assigned Location / Office Province --}}
                    <div>
                        <label for="edit-driver-location" class="block text-xs font-bold text-gray-700">Assigned Location / Office Province</label>
                        <select
                            name="location"
                            id="edit-driver-location"
                            class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer"
                        >
                            <option value="">Select Location / Province</option>
                            @foreach ($locationOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Drivers assigned to a specific province will be prioritized for vehicle assignments in that location.</p>
                    </div>

                    {{-- Birth Date & License No. --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="edit-driver-birth-date" class="block text-xs font-bold text-gray-700">Birth Date</label>
                            <input
                                type="date"
                                name="birth_date"
                                id="edit-driver-birth-date"
                                value="{{ old('_driver_form') === 'edit' ? old('birth_date') : '' }}"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                        </div>
                        <div>
                            <label for="edit-driver-license-no" class="block text-xs font-bold text-gray-700">License No. <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                name="license_number"
                                id="edit-driver-license-no"
                                placeholder="e.g. N01-23-456789"
                                value="{{ old('_driver_form') === 'edit' ? old('license_number') : '' }}"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                        </div>
                    </div>

                    {{-- License Expiration & Phone Number --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="edit-driver-license-expiration" class="block text-xs font-bold text-gray-700">License Expiration</label>
                            <input
                                type="date"
                                name="license_expiration_date"
                                id="edit-driver-license-expiration"
                                value="{{ old('_driver_form') === 'edit' ? old('license_expiration_date') : '' }}"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                        </div>
                        <div>
                            <label for="edit-driver-phone" class="block text-xs font-bold text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                name="phone_number"
                                id="edit-driver-phone"
                                placeholder="09123456789"
                                value="{{ old('_driver_form') === 'edit' ? old('phone_number') : '' }}"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label for="edit-driver-address" class="block text-xs font-bold text-gray-700">Address</label>
                        <input
                            type="text"
                            name="address"
                            id="edit-driver-address"
                            placeholder="Complete address"
                            value="{{ old('_driver_form') === 'edit' ? old('address') : '' }}"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>

                    <div class="pt-3">
                        <button
                            type="submit"
                            class="w-full rounded-xl bg-emerald-700 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-800 transition"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <div id="edit-driver-step-confirm" class="hidden relative">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-extrabold text-gray-900">Confirm Driver Update?</h2>
                    <button type="button" onclick="closeEditDriverModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="mt-5 flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div class="text-sm">
                        <p class="text-gray-700">Are you sure you want to update <span id="edit-driver-confirm-name" class="font-bold text-gray-900">Juan Dela Cruz</span>?</p>
                        <p id="edit-driver-confirm-location-wrapper" class="mt-1 text-xs text-gray-500">• Location: <span id="edit-driver-confirm-location" class="font-medium text-gray-700">Quezon City Satellite Office</span></p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="backToEditDriverForm()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmEditDriver()" class="rounded-lg bg-[#005a36] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 transition">
                        Yes, Save Changes
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

        function previewAddDriverAvatar(input) {
            const file = input.files && input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('add-driver-avatar-preview-img');
                const initial = document.getElementById('add-driver-avatar-initial');
                img.src = e.target.result;
                img.classList.remove('hidden');
                initial.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        function updateAddDriverInitial(name) {
            const img = document.getElementById('add-driver-avatar-preview-img');
            if (!img.classList.contains('hidden')) return;
            const initial = document.getElementById('add-driver-avatar-initial');
            initial.textContent = name ? name.trim().charAt(0).toUpperCase() : 'D';
        }

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

            const password = document.getElementById('add-driver-password').value;
            const confirmInput = document.getElementById('add-driver-password-confirm');
            if (confirmInput) confirmInput.value = password;

            if (!form.reportValidity()) {
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
                document.getElementById('edit-driver-name').value = btn.dataset.name;
                document.getElementById('edit-driver-email').value = btn.dataset.email;
                document.getElementById('edit-driver-location').value = btn.dataset.location || '';
                document.getElementById('edit-driver-phone').value = btn.dataset.phoneNumber || '';
                document.getElementById('edit-driver-address').value = btn.dataset.address || '';
                document.getElementById('edit-driver-birth-date').value = btn.dataset.birthDate || '';
                document.getElementById('edit-driver-license-no').value = btn.dataset.licenseNumber || '';
                document.getElementById('edit-driver-license-expiration').value = btn.dataset.licenseExpiration || '';

                const img = document.getElementById('edit-driver-avatar-preview-img');
                const initial = document.getElementById('edit-driver-avatar-preview-initial');
                const removeBtn = document.getElementById('edit-driver-avatar-remove-btn');
                if (btn.dataset.avatar) {
                    img.src = btn.dataset.avatar;
                    img.classList.remove('hidden');
                    initial.classList.add('hidden');
                    removeBtn?.classList.remove('hidden');
                } else {
                    img.src = '';
                    img.classList.add('hidden');
                    initial.classList.remove('hidden');
                    initial.textContent = btn.dataset.initial || '';
                    removeBtn?.classList.add('hidden');
                }
            }

            document.getElementById('edit-driver-step-confirm')?.classList.add('hidden');
            document.getElementById('edit-driver-step-form')?.classList.remove('hidden');
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
                document.getElementById('edit-driver-avatar-remove-btn')?.classList.remove('hidden');
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
            document.getElementById('edit-driver-avatar-remove-btn')?.classList.add('hidden');
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

            const driverName = document.getElementById('edit-driver-name').value;
            const driverLocation = document.getElementById('edit-driver-location').value;

            document.getElementById('edit-driver-confirm-name').textContent = driverName;
            
            const locSpan = document.getElementById('edit-driver-confirm-location');
            const locWrapper = document.getElementById('edit-driver-confirm-location-wrapper');
            if (driverLocation) {
                locSpan.textContent = driverLocation;
                locWrapper.classList.remove('hidden');
            } else {
                locWrapper.classList.add('hidden');
            }

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

        let driverSearchTimer = null;
        let driverSearchController = null;

        function onDriverSearchInput() {
            const value = document.getElementById('driver-search-input').value;
            document.getElementById('driver-search-clear').classList.toggle('hidden', value.length === 0);

            clearTimeout(driverSearchTimer);
            driverSearchTimer = setTimeout(() => {
                const url = new URL(window.location.href);
                if (value) {
                    url.searchParams.set('search', value);
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.set('page', '1');
                fetchAndSwapDriverResults(url.toString());
            }, 300);
        }

        function clearDriverSearch(event) {
            event.preventDefault();
            document.getElementById('driver-search-input').value = '';
            document.getElementById('driver-search-clear').classList.add('hidden');
            const url = new URL(window.location.href);
            url.searchParams.delete('search');
            url.searchParams.set('page', '1');
            fetchAndSwapDriverResults(url.toString());
            return false;
        }

        function toggleDriverLocationMenu() {
            const menu = document.getElementById('driver-location-menu');
            const chevron = document.getElementById('driver-location-chevron');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                chevron?.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                chevron?.classList.remove('rotate-180');
            }
        }

        document.addEventListener('click', function (e) {
            const menu = document.getElementById('driver-location-menu');
            const btn = e.target.closest('button[onclick="toggleDriverLocationMenu()"]');
            if (menu && !menu.classList.contains('hidden') && !btn && !menu.contains(e.target)) {
                menu.classList.add('hidden');
                document.getElementById('driver-location-chevron')?.classList.remove('rotate-180');
            }
        });

        function changeDriverPerPage(size) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', size);
            url.searchParams.set('page', '1');
            fetchAndSwapDriverResults(url.toString());
        }

        function goToDriverPage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            fetchAndSwapDriverResults(url.toString());
        }

        function fetchAndSwapDriverResults(urlStr) {
            const spinner = document.getElementById('driver-search-spinner');
            spinner?.classList.remove('hidden');

            fetch(urlStr, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('driver-results');
                if (fresh) {
                    document.getElementById('driver-results').innerHTML = fresh.innerHTML;
                    applyDriverView(currentDriverViewMode());
                }
                window.history.pushState({}, '', urlStr);
            })
            .catch(err => console.error('Driver fetch error:', err))
            .finally(() => spinner?.classList.add('hidden'));
        }
    </script>
@endsection
