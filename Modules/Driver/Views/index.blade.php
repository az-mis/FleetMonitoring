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

    {{-- Page header --}}
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Drivers</h1>
            <p class="text-sm text-slate-500">Manage your fleet drivers</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <form method="GET" action="{{ route('driver.index') }}" id="driver-search-form" class="flex w-full min-w-0 items-center gap-2 sm:w-72 xl:w-96" onsubmit="return false;">
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-600/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-8 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                    <svg id="driver-search-spinner" xmlns="http://www.w3.org/2000/svg" class="hidden absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                </div>

                <a href="{{ route('driver.index') }}" id="driver-search-clear" onclick="return clearDriverSearch(event)" class="shrink-0 text-xs font-semibold text-slate-500 hover:text-slate-700 {{ request('search') ? '' : 'hidden' }}">Clear</a>
            </form>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Active / Inactive filter (decision D10) --}}
                @php
                    $statusOptions = ['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'];
                    $currentStatus = $statusFilter ?? '';
                @endphp
                <div class="flex h-10 shrink-0 items-center rounded-xl border border-slate-200 bg-white p-1" role="group" aria-label="Driver status">
                    @foreach ($statusOptions as $value => $label)
                        <a
                            href="{{ route('driver.index', array_filter(['search' => request('search'), 'per_page' => request('per_page'), 'location' => request('location'), 'status' => $value])) }}"
                            @if ($currentStatus === $value) aria-current="true" @endif
                            class="flex h-full items-center rounded-lg px-2.5 text-xs font-semibold transition {{ $currentStatus === $value ? 'bg-[#005a36] text-white' : 'text-slate-500 hover:text-slate-700' }}"
                        >{{ $label }}</a>
                    @endforeach
                </div>

                {{-- Location filter dropdown --}}
                <div class="relative">
                    <button type="button" onclick="toggleDriverLocationMenu()" class="inline-flex h-10 items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 cursor-pointer">
                        <span class="flex items-center gap-1.5 truncate">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span class="max-w-[9rem] truncate">{{ $locationButtonLabel }}</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400 shrink-0 transition" id="driver-location-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div id="driver-location-menu" class="absolute left-0 z-20 mt-2 hidden w-64 sm:left-auto sm:right-0 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5">
                        <a href="{{ route('driver.index', array_filter(['search' => request('search'), 'per_page' => request('per_page'), 'status' => request('status')])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold transition {{ blank($selectedLocation) ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-gray-50' }}">
                            <span>All Locations</span>
                            @if(blank($selectedLocation))
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </a>
                        @foreach ($locationOptions as $opt)
                            <a href="{{ route('driver.index', array_filter(['search' => request('search'), 'per_page' => request('per_page'), 'location' => $opt, 'status' => request('status')])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 {{ $selectedLocation === $opt ? 'bg-emerald-50 text-emerald-800' : '' }}">
                                <span>{{ $opt }}</span>
                                @if($selectedLocation === $opt)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- View toggle buttons (segmented switch) --}}
                <div class="flex h-10 shrink-0 items-center rounded-xl border border-slate-200 bg-white p-1">
                    <button
                        type="button"
                        id="driver-view-toggle-list"
                        onclick="setDriverView('list')"
                        class="driver-view-toggle-btn flex h-full w-8 items-center justify-center rounded-lg transition text-slate-400 hover:text-slate-600"
                        title="List View"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="8" y1="6" x2="21" y2="6"/>
                            <line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/>
                            <line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        id="driver-view-toggle-grid"
                        onclick="setDriverView('grid')"
                        class="driver-view-toggle-btn flex h-full w-8 items-center justify-center rounded-lg transition text-slate-400 hover:text-slate-600"
                        title="Grid View"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                            <rect x="14" y="14" width="7" height="7" rx="1"/>
                        </svg>
                    </button>
                </div>

                <button
                    type="button"
                    onclick="openAddDriverModal()"
                    class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-[#005a36] px-4 text-xs font-extrabold text-white shadow-sm hover:bg-[#00432a] transition cursor-pointer"
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
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl my-auto" role="dialog" aria-modal="true" aria-labelledby="add-driver-modal-title">

            {{-- Step 1: form --}}
            <div id="add-driver-step-form">
                <div class="flex items-center justify-between">
                    <h2 id="add-driver-modal-title" class="text-lg font-semibold text-gray-900">Add Driver</h2>
                    <button type="button" onclick="closeAddDriverModal()" aria-label="Close" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
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
                        <input type="file" name="avatar" id="add-driver-avatar" accept="image/png,image/jpeg,image/webp" class="peer sr-only" onchange="previewAddDriverAvatar(this)">
                        <label for="add-driver-avatar" class="relative cursor-pointer rounded-full peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-2">
                            <span class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-emerald-600 text-white text-2xl font-bold" id="add-driver-avatar-circle">
                                <img id="add-driver-avatar-preview-img" src="" alt="" class="hidden h-full w-full object-cover rounded-full">
                                <span id="add-driver-avatar-initial">D</span>
                            </span>
                            <span class="absolute bottom-0 right-0 flex h-6 w-6 items-center justify-center rounded-full bg-white border border-gray-200 shadow">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3Z"/><circle cx="12" cy="13" r="3"/>
                                </svg>
                            </span>
                        </label>
                        <p class="text-xs text-gray-400">Photo (optional, max 5MB)</p>
                        @if (old('_driver_form') !== 'edit')
                            @error('avatar')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
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
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 {{ old('_driver_form') !== 'edit' && $errors->has('name') ? 'border-red-300' : '' }}"
                            required
                            oninput="updateAddDriverInitial(this.value)"
                        >
                        @if (old('_driver_form') !== 'edit') @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror @endif
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
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 {{ old('_driver_form') !== 'edit' && $errors->has('email') ? 'border-red-300' : '' }}"
                            required
                        >
                        @if (old('_driver_form') !== 'edit') @error('email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror @endif
                    </div>

                    {{-- Temporary Password --}}
                    <div>
                        <label for="add-driver-password" class="block text-sm font-medium text-gray-700">Temporary Password <span class="text-red-500">*</span></label>
                        <input
                            type="password"
                            name="password"
                            id="add-driver-password"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 {{ old('_driver_form') !== 'edit' && $errors->has('password') ? 'border-red-300' : '' }}"
                            required
                        >
                        <input type="hidden" name="password_confirmation" id="add-driver-password-confirm">
                        @if (old('_driver_form') !== 'edit') @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror @endif
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
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="add-driver-birth-date" class="block text-sm font-medium text-gray-700">Birth Date</label>
                            <input
                                type="date"
                                name="birth_date"
                                id="add-driver-birth-date"
                                value="{{ old('_driver_form') === 'create' ? old('birth_date') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 {{ old('_driver_form') !== 'edit' && $errors->has('birth_date') ? 'border-red-300' : '' }}"
                            >
                            @if (old('_driver_form') !== 'edit') @error('birth_date')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror @endif
                        </div>
                        <div>
                            <label for="add-driver-license-no" class="block text-sm font-medium text-gray-700">License No. <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                name="license_number"
                                id="add-driver-license-no"
                                placeholder="e.g. N01-23-456789"
                                value="{{ old('_driver_form') === 'create' ? old('license_number') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 {{ old('_driver_form') !== 'edit' && $errors->has('license_number') ? 'border-red-300' : '' }}"
                                required
                            >
                            @if (old('_driver_form') !== 'edit') @error('license_number')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror @endif
                        </div>
                    </div>

                    {{-- License Expiration + Phone Number --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="add-driver-license-expiration" class="block text-sm font-medium text-gray-700">License Expiration <span class="text-red-500">*</span></label>
                            <input
                                type="date"
                                name="license_expiration_date"
                                id="add-driver-license-expiration"
                                value="{{ old('_driver_form') === 'create' ? old('license_expiration_date') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 {{ old('_driver_form') !== 'edit' && $errors->has('license_expiration_date') ? 'border-red-300' : '' }}"
                                required
                            >
                            @if (old('_driver_form') !== 'edit') @error('license_expiration_date')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror @endif
                        </div>
                        <div>
                            <label for="add-driver-phone" class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                name="phone_number"
                                id="add-driver-phone"
                                placeholder="e.g. 09171234567"
                                inputmode="numeric"
                                maxlength="11"
                                pattern="09[0-9]{9}"
                                title="Must start with 09 and be exactly 11 digits"
                                value="{{ old('_driver_form') === 'create' ? old('phone_number') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 {{ old('_driver_form') !== 'edit' && $errors->has('phone_number') ? 'border-red-300' : '' }}"
                                required
                            >
                            @if (old('_driver_form') !== 'edit') @error('phone_number')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror @endif
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
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 {{ old('_driver_form') !== 'edit' && $errors->has('address') ? 'border-red-300' : '' }}"
                        >
                        @if (old('_driver_form') !== 'edit') @error('address')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror @endif
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
        <script>
            window.hasAddDriverErrors = true;
            document.addEventListener('DOMContentLoaded', () => openAddDriverModal());
        </script>
    @else
        <script>
            window.hasAddDriverErrors = false;
        </script>
    @endif


    {{-- Edit Driver modal --}}
    <div id="edit-driver-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl my-auto" role="dialog" aria-modal="true" aria-labelledby="edit-driver-modal-title">

            <div id="edit-driver-step-form">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 id="edit-driver-modal-title" class="text-base font-extrabold text-gray-900">Edit Driver</h2>
                    <button type="button" onclick="closeEditDriverModal()" aria-label="Close" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ old('_driver_action', route('driver.index')) }}" id="edit-driver-form" class="mt-3 space-y-2.5" enctype="multipart/form-data" onsubmit="return handleEditDriverSubmit(event)">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="role" value="{{ \Modules\Admin\Enums\UserRole::DRIVER->value }}">
                    <input type="hidden" name="_driver_form" value="edit">
                    <input type="hidden" name="_driver_action" id="edit-driver-action-field" value="{{ old('_driver_action') }}">
                    <input type="hidden" name="remove_avatar" id="edit-driver-remove-avatar" value="0">

                    {{-- Centered Avatar Uploader with Change & Remove options --}}
                    <div class="flex flex-col items-center justify-center">
                        <div class="relative">
                            <div class="relative flex h-14 w-14 items-center justify-center overflow-hidden rounded-full border-2 border-emerald-500 bg-gray-100 text-gray-400 shadow-2xs">
                                <img id="edit-driver-avatar-preview-img" src="" alt="" class="hidden h-full w-full object-cover">
                                <span id="edit-driver-avatar-preview-initial" class="text-lg font-bold text-gray-700"></span>
                            </div>
                        </div>

                        <div class="mt-1.5 flex items-center gap-2">
                            <input type="file" name="avatar" id="edit-driver-avatar" accept="image/png,image/jpeg,image/webp" class="peer sr-only" onchange="previewEditDriverAvatarFile(this)">
                            <label for="edit-driver-avatar" class="inline-flex cursor-pointer items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[0.6875rem] font-semibold text-emerald-700 hover:bg-emerald-100 transition peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                    <circle cx="12" cy="13" r="3"/>
                                </svg>
                                <span>Change Photo</span>
                            </label>
                            <button
                                type="button"
                                id="edit-driver-avatar-remove-btn"
                                onclick="removeEditDriverAvatar()"
                                class="hidden items-center gap-1 rounded-md border border-red-200 bg-red-50 px-2 py-0.5 text-[0.6875rem] font-semibold text-red-600 hover:bg-red-100 transition"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18"/>
                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                </svg>
                                <span>Remove</span>
                            </button>
                        </div>
                        @if (old('_driver_form') === 'edit')
                            @error('avatar')
                                <p class="mt-0.5 text-[0.625rem] text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    {{-- Full Name & Email --}}
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <div>
                            <label for="edit-driver-name" class="block text-[0.6875rem] font-bold text-gray-700">Full Name <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                name="name"
                                id="edit-driver-name"
                                value="{{ old('_driver_form') === 'edit' ? old('name') : '' }}"
                                class="mt-0.5 w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 {{ old('_driver_form') === 'edit' && $errors->has('name') ? 'border-red-300' : '' }}"
                                required
                            >
                            @if (old('_driver_form') === 'edit')
                                @error('name')
                                    <p class="mt-0.5 text-[0.625rem] text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="edit-driver-email" class="block text-[0.6875rem] font-bold text-gray-700">Email <span class="text-red-500">*</span></label>
                            <input
                                type="email"
                                name="email"
                                id="edit-driver-email"
                                value="{{ old('_driver_form') === 'edit' ? old('email') : '' }}"
                                class="mt-0.5 w-full rounded-lg border border-slate-200 bg-slate-100/70 px-2.5 py-1.5 text-xs text-slate-600 focus:outline-none cursor-not-allowed {{ old('_driver_form') === 'edit' && $errors->has('email') ? 'border-red-300' : '' }}"
                                required
                                readonly
                            >
                        </div>
                    </div>

                    {{-- Assigned Location / Office Province --}}
                    <div>
                        <label for="edit-driver-location" class="block text-[0.6875rem] font-bold text-gray-700">Assigned Location / Office Province</label>
                        <select
                            name="location"
                            id="edit-driver-location"
                            class="mt-0.5 w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer"
                        >
                            <option value="">Select Location / Province</option>
                            @foreach ($locationOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                        @if (old('_driver_form') === 'edit')
                            @error('location')
                                <p class="mt-0.5 text-[0.625rem] text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    {{-- Birth Date & License No. --}}
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <div>
                            <label for="edit-driver-birth-date" class="block text-[0.6875rem] font-bold text-gray-700">Birth Date</label>
                            <input
                                type="date"
                                name="birth_date"
                                id="edit-driver-birth-date"
                                value="{{ old('_driver_form') === 'edit' ? old('birth_date') : '' }}"
                                class="mt-0.5 w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                            @if (old('_driver_form') === 'edit')
                                @error('birth_date')
                                    <p class="mt-0.5 text-[0.625rem] text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                        <div>
                            <label for="edit-driver-license-no" class="block text-[0.6875rem] font-bold text-gray-700">License No. <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                name="license_number"
                                id="edit-driver-license-no"
                                placeholder="e.g. N01-23-456789"
                                value="{{ old('_driver_form') === 'edit' ? old('license_number') : '' }}"
                                class="mt-0.5 w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                required
                            >
                            @if (old('_driver_form') === 'edit')
                                @error('license_number')
                                    <p class="mt-0.5 text-[0.625rem] text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    {{-- License Expiration & Phone Number --}}
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        <div>
                            <label for="edit-driver-license-expiration" class="block text-[0.6875rem] font-bold text-gray-700">License Expiration <span class="text-red-500">*</span></label>
                            <input
                                type="date"
                                name="license_expiration_date"
                                id="edit-driver-license-expiration"
                                value="{{ old('_driver_form') === 'edit' ? old('license_expiration_date') : '' }}"
                                class="mt-0.5 w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                required
                            >
                            @if (old('_driver_form') === 'edit')
                                @error('license_expiration_date')
                                    <p class="mt-0.5 text-[0.625rem] text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                        <div>
                            <label for="edit-driver-phone" class="block text-[0.6875rem] font-bold text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                name="phone_number"
                                id="edit-driver-phone"
                                placeholder="09171234567"
                                inputmode="numeric"
                                maxlength="11"
                                pattern="09[0-9]{9}"
                                title="Must start with 09 and be exactly 11 digits"
                                value="{{ old('_driver_form') === 'edit' ? old('phone_number') : '' }}"
                                class="mt-0.5 w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                required
                            >
                            @if (old('_driver_form') === 'edit')
                                @error('phone_number')
                                    <p class="mt-0.5 text-[0.625rem] text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label for="edit-driver-address" class="block text-[0.6875rem] font-bold text-gray-700">Address</label>
                        <input
                            type="text"
                            name="address"
                            id="edit-driver-address"
                            placeholder="Complete address"
                            value="{{ old('_driver_form') === 'edit' ? old('address') : '' }}"
                            class="mt-0.5 w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                        @if (old('_driver_form') === 'edit')
                            @error('address')
                                <p class="mt-0.5 text-[0.625rem] text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    {{-- Change Password (Optional) with Eye Toggle --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-2.5 space-y-2">
                        <div class="flex items-center gap-1.5 text-slate-800 font-bold text-[0.6875rem]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <span>Change Password <span class="font-normal text-slate-400">(Optional)</span></span>
                        </div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div>
                                <label for="edit-driver-password" class="block text-[0.625rem] font-semibold text-gray-600">New Password</label>
                                <div class="relative mt-0.5">
                                    <input
                                        type="password"
                                        name="password"
                                        id="edit-driver-password"
                                        placeholder="••••••••"
                                        class="w-full rounded-lg border border-gray-300 bg-white pl-2.5 pr-8 py-1 text-xs text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 {{ old('_driver_form') === 'edit' && $errors->has('password') ? 'border-red-300' : '' }}"
                                    >
                                    <button
                                        type="button"
                                        onclick="togglePasswordVisibility('edit-driver-password', 'edit-pass-eye-icon')"
                                        aria-label="Show password"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded text-gray-400 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 cursor-pointer"
                                    >
                                        <svg id="edit-pass-eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                                @if (old('_driver_form') === 'edit')
                                    @error('password')
                                        <p class="mt-0.5 text-[0.625rem] text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                            <div>
                                <label for="edit-driver-password-confirm" class="block text-[0.625rem] font-semibold text-gray-600">Confirm New Password</label>
                                <div class="relative mt-0.5">
                                    <input
                                        type="password"
                                        name="password_confirmation"
                                        id="edit-driver-password-confirm"
                                        placeholder="••••••••"
                                        class="w-full rounded-lg border border-gray-300 bg-white pl-2.5 pr-8 py-1 text-xs text-gray-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    >
                                    <button
                                        type="button"
                                        onclick="togglePasswordVisibility('edit-driver-password-confirm', 'edit-confirm-pass-eye-icon')"
                                        aria-label="Show password"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded text-gray-400 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 cursor-pointer"
                                    >
                                        <svg id="edit-confirm-pass-eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full rounded-xl bg-emerald-700 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-800 transition cursor-pointer"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <div id="edit-driver-step-confirm" class="hidden relative">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h2 class="text-base font-extrabold text-gray-900">Confirm Driver Update?</h2>
                    <button type="button" onclick="closeEditDriverModal()" aria-label="Close" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
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
        <script>
            window.hasEditDriverErrors = true;
            document.addEventListener('DOMContentLoaded', () => openEditDriverModal(null));
        </script>
    @else
        <script>
            window.hasEditDriverErrors = false;
        </script>
    @endif

    {{-- View details modal --}}
    <div id="view-driver-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl my-auto" role="dialog" aria-modal="true" aria-labelledby="view-driver-modal-title">
            <div class="flex items-center justify-between">
                <h2 id="view-driver-modal-title" class="text-lg font-semibold text-gray-900">Driver details</h2>
                <button type="button" onclick="closeViewDriverModal()" aria-label="Close" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
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
    {{-- Deactivate / Reactivate confirmation (decision D10) --}}
    <div id="driver-status-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="driver-status-modal-title">
            <h2 id="driver-status-modal-title" class="text-lg font-semibold text-gray-900"></h2>
            <div id="driver-status-modal-body" class="mt-2 space-y-2 text-sm text-gray-600"></div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    onclick="closeDriverStatusModal()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                >
                    Cancel
                </button>

                <form id="driver-status-form" method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <button id="driver-status-submit" type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition"></button>
                </form>
            </div>
        </div>
    </div>

    <div id="delete-driver-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="delete-driver-modal-title">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-red-50 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4M12 17h.01"/>
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                </svg>
            </div>

            <h2 id="delete-driver-modal-title" class="mt-4 text-lg font-semibold text-gray-900">Delete driver?</h2>
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
        .driver-view-toggle-btn { color: #94a3b8; }
        .driver-view-toggle-btn.active { background: #005a36; border-color: #005a36; color: #fff; }
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

        function openDriverStatusModal(btn) {
            const deactivate = btn.dataset.action === 'deactivate';
            const name = btn.dataset.name;
            const title = document.getElementById('driver-status-modal-title');
            const body = document.getElementById('driver-status-modal-body');
            const submit = document.getElementById('driver-status-submit');

            title.textContent = deactivate ? `Deactivate ${name}?` : `Reactivate ${name}?`;
            body.replaceChildren();
            const lines = deactivate
                ? [
                    `${name} will no longer be able to sign in, and will not appear when choosing a driver for a trip or vehicle.`,
                    btn.dataset.vehicles ? `They will be removed as the default driver of ${btn.dataset.vehicles}.` : null,
                    'Their past trips and vehicle reports are kept. You can reactivate them at any time.',
                ]
                : [`${name} will be able to sign in again and can be chosen for trips and vehicles.`];
            lines.filter(Boolean).forEach((text) => {
                const p = document.createElement('p');
                p.textContent = text;
                body.appendChild(p);
            });

            submit.textContent = deactivate ? 'Yes, deactivate' : 'Yes, reactivate';
            submit.className = 'rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition '
                + (deactivate ? 'bg-amber-600 hover:bg-amber-700' : 'bg-[#005a36] hover:bg-[#00432a]');
            document.getElementById('driver-status-form').action = btn.dataset.url;

            const modal = document.getElementById('driver-status-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDriverStatusModal() {
            const modal = document.getElementById('driver-status-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

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
            if (window.hasAddDriverErrors) {
                window.location.href = window.location.pathname + window.location.search;
                return;
            }

            const modal = document.getElementById('add-driver-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            const form = document.getElementById('add-driver-form');
            if (form) {
                form.reset();
            }
            if (typeof clearAddDriverPhoto === 'function') {
                clearAddDriverPhoto();
            }
            modal.querySelectorAll('p.text-red-600').forEach(el => el.remove());
            modal.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                el.classList.add('border-slate-200');
            });
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
                document.getElementById('edit-driver-action-field').value = btn.dataset.updateUrl;
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
                    removeBtn?.classList.add('inline-flex');
                } else {
                    img.src = '';
                    img.classList.add('hidden');
                    initial.classList.remove('hidden');
                    initial.textContent = btn.dataset.initial || '';
                    removeBtn?.classList.add('hidden');
                    removeBtn?.classList.remove('inline-flex');
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
                const removeBtn = document.getElementById('edit-driver-avatar-remove-btn');
                removeBtn?.classList.remove('hidden');
                removeBtn?.classList.add('inline-flex');
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
            const removeBtn = document.getElementById('edit-driver-avatar-remove-btn');
            removeBtn?.classList.add('hidden');
            removeBtn?.classList.remove('inline-flex');
        }

        function closeEditDriverModal() {
            if (window.hasEditDriverErrors) {
                window.location.href = window.location.pathname + window.location.search;
                return;
            }

            const modal = document.getElementById('edit-driver-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            const form = document.getElementById('edit-driver-form');
            if (form) {
                form.reset();
            }
            modal.querySelectorAll('p.text-red-600').forEach(el => el.remove());
            modal.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                el.classList.add('border-slate-200');
            });
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

        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input) return;
            const toggleBtn = icon?.closest('button');

            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/>`;
                toggleBtn?.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                icon.innerHTML = `<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>`;
                toggleBtn?.setAttribute('aria-label', 'Show password');
            }
        }

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            const isOpen = (id) => {
                const el = document.getElementById(id);
                return el && !el.classList.contains('hidden');
            };
            if (isOpen('driver-status-modal')) closeDriverStatusModal();
            else if (isOpen('delete-driver-modal')) closeDeleteDriverModal();
            else if (isOpen('view-driver-modal')) closeViewDriverModal();
            else if (isOpen('edit-driver-modal')) closeEditDriverModal();
            else if (isOpen('add-driver-modal')) closeAddDriverModal();
        });

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
