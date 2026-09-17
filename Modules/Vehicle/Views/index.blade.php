@extends('layouts.app')

@section('title', 'Vehicles')
@section('page-title', 'Vehicles')

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
                        <path d="M10 17h4V5H2v12h3"/>
                        <path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/>
                        <circle cx="7" cy="17" r="2"/>
                        <circle cx="17" cy="17" r="2"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-extrabold tracking-tight">Vehicles List</h2>
                    <p class="truncate text-xs text-white/80">{{ $vehicles->total() }} registered</p>
                </div>
            </div>

            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-end">
                <form method="GET" action="{{ route('vehicles.index') }}" id="vehicle-search-form" class="flex w-full min-w-0 items-center gap-2 md:w-56 xl:w-80" onsubmit="return false;">
                    <div class="relative w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input
                            type="text"
                            name="search"
                            id="vehicle-search-input"
                            value="{{ request('search') }}"
                            autocomplete="off"
                            placeholder="Search plate, brand, model..."
                            oninput="onVehicleSearchInput()"
                            class="w-full rounded-xl border-0 bg-white py-2 pl-9 pr-8 text-xs text-slate-800 placeholder-slate-400 shadow-inner focus:outline-none focus:ring-2 focus:ring-emerald-300"
                        >
                        <svg id="vehicle-search-spinner" xmlns="http://www.w3.org/2000/svg" class="hidden absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                        </svg>
                    </div>

                    <a href="{{ route('vehicles.index') }}" id="vehicle-search-clear" onclick="return clearVehicleSearch(event)" class="shrink-0 text-xs font-semibold text-white/80 hover:text-white {{ request('search') ? '' : 'hidden' }}">Clear</a>
                </form>

                {{-- View toggle buttons --}}
                <div class="inline-flex shrink-0 items-center gap-0.5 rounded-xl bg-black/20 p-1 backdrop-blur-sm">
                    <button
                        type="button"
                        id="vehicle-view-toggle-list"
                        onclick="setVehicleView('list')"
                        class="vehicle-view-toggle-btn rounded-lg p-1.5 transition text-white/70 hover:text-white"
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
                        id="vehicle-view-toggle-grid"
                        onclick="setVehicleView('grid')"
                        class="vehicle-view-toggle-btn rounded-lg p-1.5 transition text-white/70 hover:text-white"
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

                {{-- Location filter dropdown --}}
                <div class="relative shrink-0">
                    <button
                        type="button"
                        onclick="toggleVehicleLocationMenu()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-2 text-xs font-semibold text-white shadow-sm ring-1 ring-white/20 hover:bg-white/25 transition cursor-pointer"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span>{{ $locationButtonLabel }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition" id="vehicle-location-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div id="vehicle-location-menu" class="absolute right-0 z-20 mt-2 hidden w-64 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5">
                        <a href="{{ route('vehicles.index', array_filter(['search' => request('search'), 'per_page' => request('per_page')])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-gray-50 {{ !$selectedLocation ? 'bg-emerald-50 text-emerald-800 font-semibold' : '' }}">
                            <span>All Locations</span>
                            @if(!$selectedLocation)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </a>
                        @foreach($locationOptions as $opt)
                            <a href="{{ route('vehicles.index', array_filter(['search' => request('search'), 'per_page' => request('per_page'), 'location' => $opt])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-gray-50 {{ $selectedLocation === $opt ? 'bg-emerald-50 text-emerald-800 font-semibold' : '' }}">
                                <span>{{ $opt }}</span>
                                @if($selectedLocation === $opt)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                <button
                    type="button"
                    onclick="openRegisterVehicleModal()"
                    class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-white px-3.5 py-2 text-sm font-bold text-emerald-800 shadow-sm hover:bg-emerald-50 transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>Register Vehicle</span>
                </button>
            </div>
        </div>
    </div>

    <div id="vehicle-results">
        @include('vehicle::partials.vehicle-results', ['vehicles' => $vehicles])
    </div>

    {{-- Register Vehicle modal --}}
    <div id="register-vehicle-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl my-auto">

            {{-- Step 1: form --}}
            <div id="register-vehicle-step-form">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Register New Vehicle</h2>
                    <button type="button" onclick="closeRegisterVehicleModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-sm text-gray-500">Fill out the details below to add a new vehicle record.</p>

                <form method="POST" action="{{ route('vehicles.store') }}" id="register-vehicle-form" class="mt-5 space-y-4" enctype="multipart/form-data" onsubmit="return handleRegisterVehicleSubmit(event)">
                    @csrf
                    <input type="hidden" name="_vehicle_form" value="create">

                    <div>
                        <label for="rv-plate_number" class="block text-sm font-medium text-gray-700">Plate Number</label>
                        <input
                            type="text"
                            name="plate_number"
                            id="rv-plate_number"
                            value="{{ old('_vehicle_form') === 'create' ? old('plate_number') : '' }}"
                            placeholder="E.G. ABC 1234"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('plate_number') border-red-300 @enderror"
                            required
                        >
                        <p class="mt-1 text-xs text-gray-400">Letters, numbers, dashes, and spaces only.</p>
                        @error('plate_number')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Vehicle Photo</label>
                        <div class="mt-1.5 flex items-center gap-4">
                            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 text-gray-400">
                                <img id="rv-photo-preview-img" src="" alt="" class="hidden h-full w-full object-cover">
                                <svg id="rv-photo-preview-icon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                    <circle cx="12" cy="13" r="4"/>
                                </svg>
                            </div>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <label for="rv-photo" class="inline-flex w-fit cursor-pointer items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition">
                                        Choose File
                                    </label>
                                    <span id="rv-photo-filename" class="text-sm text-gray-500">No file chosen</span>
                                </div>
                                <input type="file" name="photo" id="rv-photo" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="handleVehiclePhotoChange(this)">
                                <span class="text-xs text-gray-400">Optional. JPG, PNG, or WEBP. Max 5MB.</span>
                            </div>
                        </div>
                        @error('photo')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="rv-chassis_number" class="block text-sm font-medium text-gray-700">Chassis/VIN Number</label>
                            <input
                                type="text"
                                name="chassis_number"
                                id="rv-chassis_number"
                                value="{{ old('_vehicle_form') === 'create' ? old('chassis_number') : '' }}"
                                placeholder="e.g. JT2BF22K1W0123456"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('chassis_number') border-red-300 @enderror"
                                required
                            >
                            @error('chassis_number')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="rv-engine_number" class="block text-sm font-medium text-gray-700">Engine Number</label>
                            <input
                                type="text"
                                name="engine_number"
                                id="rv-engine_number"
                                value="{{ old('_vehicle_form') === 'create' ? old('engine_number') : '' }}"
                                placeholder="e.g. 2KD-0123456"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('engine_number') border-red-300 @enderror"
                                required
                            >
                            @error('engine_number')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="rv-location" class="block text-sm font-medium text-gray-700">Office / Location</label>
                        <input type="text" name="location" id="rv-location" value="{{ old('_vehicle_form') === 'create' ? old('location') : '' }}" placeholder="e.g. Oriental Mindoro" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="rv-brand" class="block text-sm font-medium text-gray-700">Brand</label>
                            <input
                                type="text"
                                name="brand"
                                id="rv-brand"
                                value="{{ old('_vehicle_form') === 'create' ? old('brand') : '' }}"
                                placeholder="e.g. Toyota"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('brand') border-red-300 @enderror"
                                required
                            >
                            @error('brand')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="rv-model" class="block text-sm font-medium text-gray-700">Model</label>
                            <input
                                type="text"
                                name="model"
                                id="rv-model"
                                value="{{ old('_vehicle_form') === 'create' ? old('model') : '' }}"
                                placeholder="e.g. Hilux"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('model') border-red-300 @enderror"
                                required
                            >
                            @error('model')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="ev-location" class="block text-sm font-medium text-gray-700">Office / Location</label>
                        <input type="text" name="location" id="ev-location" value="{{ old('_vehicle_form') === 'edit' ? old('location') : '' }}" placeholder="e.g. Oriental Mindoro" class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-emerald-500">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="rv-year" class="block text-sm font-medium text-gray-700">Year</label>
                            <input
                                type="number"
                                name="year"
                                id="rv-year"
                                value="{{ old('_vehicle_form') === 'create' ? old('year') : '' }}"
                                placeholder="e.g. 2023"
                                min="1900"
                                max="{{ date('Y') + 1 }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('year') border-red-300 @enderror"
                                required
                            >
                            @error('year')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="rv-color" class="block text-sm font-medium text-gray-700">Color</label>
                            <input
                                type="text"
                                name="color"
                                id="rv-color"
                                value="{{ old('_vehicle_form') === 'create' ? old('color') : '' }}"
                                placeholder="e.g. White"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('color') border-red-300 @enderror"
                                required
                            >
                            @error('color')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="rv-odometer" class="block text-sm font-medium text-gray-700">Current Odometer (km)</label>
                        <input
                            type="number"
                            name="odometer"
                            id="rv-odometer"
                            value="{{ old('_vehicle_form') === 'create' ? old('odometer', 0) : 0 }}"
                            min="0"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('odometer') border-red-300 @enderror"
                            required
                        >
                        <p class="mt-1 text-xs text-gray-400">The vehicle's current odometer reading at registration.</p>
                        @error('odometer')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="rv-vehicle_type" class="block text-sm font-medium text-gray-700">Vehicle Type</label>
                            <select
                                name="vehicle_type"
                                id="rv-vehicle_type"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('vehicle_type') border-red-300 @enderror"
                            >
                                <option value="">-- Select Type --</option>
                                <option value="sedan">Sedan</option>
                                <option value="suv">SUV</option>
                                <option value="pickup">Pickup Truck</option>
                                <option value="van">Van</option>
                                <option value="truck">Truck</option>
                                <option value="motorcycle">Motorcycle</option>
                                <option value="other">Other</option>
                            </select>
                            @error('vehicle_type')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="rv-fuel_type" class="block text-sm font-medium text-gray-700">Fuel Type</label>
                            <select
                                name="fuel_type"
                                id="rv-fuel_type"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('fuel_type') border-red-300 @enderror"
                            >
                                <option value="">-- Select Fuel Type --</option>
                                <option value="gasoline">Gasoline</option>
                                <option value="diesel">Diesel</option>
                                <option value="electric">Electric</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="other">Other</option>
                            </select>
                            @error('fuel_type')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" onclick="closeRegisterVehicleModal()" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition"
                        >
                            Save Vehicle
                        </button>
                    </div>
                </form>
            </div>

            {{-- Step 2: confirmation --}}
            <div id="register-vehicle-step-confirm" class="hidden">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 13h2l2-6h10l2 6h2"/>
                        <path d="M5 13v5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-5"/>
                        <circle cx="7.5" cy="16.5" r="1.5"/>
                        <circle cx="16.5" cy="16.5" r="1.5"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-gray-900">Register this vehicle?</h2>
                <p class="mt-1 text-sm text-gray-500">Please confirm the details below before saving the record.</p>

                <dl class="mt-4 max-h-72 space-y-2 overflow-y-auto rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Plate Number</dt>
                        <dd id="rv-confirm-plate_number" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Photo</dt>
                        <dd id="rv-confirm-photo" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Chassis/VIN Number</dt>
                        <dd id="rv-confirm-chassis_number" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Engine Number</dt>
                        <dd id="rv-confirm-engine_number" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Brand</dt>
                        <dd id="rv-confirm-brand" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Model</dt>
                        <dd id="rv-confirm-model" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Year</dt>
                        <dd id="rv-confirm-year" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Color</dt>
                        <dd id="rv-confirm-color" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Odometer</dt>
                        <dd id="rv-confirm-odometer" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Vehicle Type</dt>
                        <dd id="rv-confirm-vehicle_type" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Fuel Type</dt>
                        <dd id="rv-confirm-fuel_type" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                </dl>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="backToRegisterVehicleForm()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Back
                    </button>
                    <button type="button" onclick="confirmRegisterVehicle()" class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition">
                        Yes, register vehicle
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any() && old('_vehicle_form') === 'create')
        <script>document.addEventListener('DOMContentLoaded', () => openRegisterVehicleModal());</script>
    @endif

    {{-- Edit Vehicle modal --}}
    <div id="edit-vehicle-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl my-auto">

            {{-- Step 1: form --}}
            <div id="edit-vehicle-step-form">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Edit Vehicle</h2>
                    <button type="button" onclick="closeEditVehicleModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <p id="edit-vehicle-subtitle" class="mt-1 text-sm text-gray-500">Update this vehicle's details below.</p>

                <form method="POST" action="{{ old('_ev_action', route('vehicles.index')) }}" id="edit-vehicle-form" class="mt-5 space-y-4" enctype="multipart/form-data" onsubmit="return handleEditVehicleSubmit(event)">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_vehicle_form" value="edit">
                    <input type="hidden" name="_ev_action" id="edit-vehicle-action-field" value="{{ old('_ev_action') }}">
                    <input type="hidden" name="remove_photo" id="ev-remove-photo" value="0">

                    <div>
                        <label for="ev-plate_number" class="block text-sm font-medium text-gray-700">Plate Number</label>
                        <input
                            type="text"
                            name="plate_number"
                            id="ev-plate_number"
                            value="{{ old('_vehicle_form') === 'edit' ? old('plate_number') : '' }}"
                            placeholder="E.G. ABC 1234"
                            @if (!$isSuperAdmin) readonly @endif
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('plate_number') border-red-300 @enderror {{ !$isSuperAdmin ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' }}"
                            required
                        >
                        <p class="mt-1 text-xs text-gray-400">
                            @if ($isSuperAdmin)
                                Letters, numbers, dashes, and spaces only.
                            @else
                                Only a Super Admin can change the plate number.
                            @endif
                        </p>
                        @error('plate_number')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Vehicle Photo</label>
                        <div class="mt-1.5 flex items-center gap-4">
                            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 text-gray-400">
                                <img id="ev-photo-preview-img" src="" alt="" class="hidden h-full w-full object-cover">
                                <svg id="ev-photo-preview-icon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                    <circle cx="12" cy="13" r="4"/>
                                </svg>
                            </div>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <label for="ev-photo" class="inline-flex w-fit cursor-pointer items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition">
                                        Choose File
                                    </label>
                                    <span id="ev-photo-filename" class="text-sm text-gray-500">No file chosen</span>
                                    <button type="button" id="ev-photo-remove-btn" onclick="removeEditVehiclePhoto()" class="hidden text-sm font-medium text-red-600 hover:text-red-700">
                                        Remove
                                    </button>
                                </div>
                                <input type="file" name="photo" id="ev-photo" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewEditVehiclePhoto(this)">
                                <span class="text-xs text-gray-400">Optional. JPG, PNG, or WEBP. Max 5MB.</span>
                            </div>
                        </div>
                        @error('photo')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="ev-chassis_number" class="block text-sm font-medium text-gray-700">Chassis/VIN Number</label>
                            <input
                                type="text"
                                name="chassis_number"
                                id="ev-chassis_number"
                                value="{{ old('_vehicle_form') === 'edit' ? old('chassis_number') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('chassis_number') border-red-300 @enderror"
                                required
                            >
                            @error('chassis_number')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="ev-engine_number" class="block text-sm font-medium text-gray-700">Engine Number</label>
                            <input
                                type="text"
                                name="engine_number"
                                id="ev-engine_number"
                                value="{{ old('_vehicle_form') === 'edit' ? old('engine_number') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('engine_number') border-red-300 @enderror"
                                required
                            >
                            @error('engine_number')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="ev-brand" class="block text-sm font-medium text-gray-700">Brand</label>
                            <input
                                type="text"
                                name="brand"
                                id="ev-brand"
                                value="{{ old('_vehicle_form') === 'edit' ? old('brand') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('brand') border-red-300 @enderror"
                                required
                            >
                            @error('brand')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="ev-model" class="block text-sm font-medium text-gray-700">Model</label>
                            <input
                                type="text"
                                name="model"
                                id="ev-model"
                                value="{{ old('_vehicle_form') === 'edit' ? old('model') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('model') border-red-300 @enderror"
                                required
                            >
                            @error('model')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="ev-year" class="block text-sm font-medium text-gray-700">Year</label>
                            <input
                                type="number"
                                name="year"
                                id="ev-year"
                                value="{{ old('_vehicle_form') === 'edit' ? old('year') : '' }}"
                                min="1900"
                                max="{{ date('Y') + 1 }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('year') border-red-300 @enderror"
                                required
                            >
                            @error('year')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="ev-color" class="block text-sm font-medium text-gray-700">Color</label>
                            <input
                                type="text"
                                name="color"
                                id="ev-color"
                                value="{{ old('_vehicle_form') === 'edit' ? old('color') : '' }}"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('color') border-red-300 @enderror"
                                required
                            >
                            @error('color')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="ev-odometer" class="block text-sm font-medium text-gray-700">Current Odometer (km)</label>
                        <input
                            type="number"
                            name="odometer"
                            id="ev-odometer"
                            value="{{ old('_vehicle_form') === 'edit' ? old('odometer', 0) : 0 }}"
                            min="0"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('odometer') border-red-300 @enderror"
                            required
                        >
                        @error('odometer')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="ev-vehicle_type" class="block text-sm font-medium text-gray-700">Vehicle Type</label>
                            <select
                                name="vehicle_type"
                                id="ev-vehicle_type"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('vehicle_type') border-red-300 @enderror"
                            >
                                <option value="">-- Select Type --</option>
                                <option value="sedan">Sedan</option>
                                <option value="suv">SUV</option>
                                <option value="pickup">Pickup Truck</option>
                                <option value="van">Van</option>
                                <option value="truck">Truck</option>
                                <option value="motorcycle">Motorcycle</option>
                                <option value="other">Other</option>
                            </select>
                            @error('vehicle_type')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="ev-fuel_type" class="block text-sm font-medium text-gray-700">Fuel Type</label>
                            <select
                                name="fuel_type"
                                id="ev-fuel_type"
                                class="mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('fuel_type') border-red-300 @enderror"
                            >
                                <option value="">-- Select Fuel Type --</option>
                                <option value="gasoline">Gasoline</option>
                                <option value="diesel">Diesel</option>
                                <option value="electric">Electric</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="other">Other</option>
                            </select>
                            @error('fuel_type')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" onclick="closeEditVehicleModal()" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
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

            {{-- Step 2: confirmation --}}
            <div id="edit-vehicle-step-confirm" class="hidden">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-gray-900">Save these changes?</h2>
                <p class="mt-1 text-sm text-gray-500">Please confirm the updated details below.</p>

                <dl class="mt-4 max-h-72 space-y-2 overflow-y-auto rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Plate Number</dt>
                        <dd id="ev-confirm-plate_number" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Photo</dt>
                        <dd id="ev-confirm-photo" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Chassis/VIN Number</dt>
                        <dd id="ev-confirm-chassis_number" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Engine Number</dt>
                        <dd id="ev-confirm-engine_number" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Brand</dt>
                        <dd id="ev-confirm-brand" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Model</dt>
                        <dd id="ev-confirm-model" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Year</dt>
                        <dd id="ev-confirm-year" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Color</dt>
                        <dd id="ev-confirm-color" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Odometer</dt>
                        <dd id="ev-confirm-odometer" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Vehicle Type</dt>
                        <dd id="ev-confirm-vehicle_type" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Fuel Type</dt>
                        <dd id="ev-confirm-fuel_type" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                </dl>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="backToEditVehicleForm()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Back
                    </button>
                    <button type="button" onclick="confirmEditVehicle()" class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition">
                        Yes, save changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any() && old('_vehicle_form') === 'edit')
        <script>document.addEventListener('DOMContentLoaded', () => openEditVehicleModal(null));</script>
    @endif

    {{-- View details modal --}}
    <div id="view-vehicle-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl my-auto">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Vehicle details</h2>
                <button type="button" onclick="closeViewVehicleModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-5 flex items-center gap-3">
                <div class="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-emerald-50 text-emerald-600">
                    <img id="view-vehicle-photo-img" src="" alt="" class="hidden h-full w-full object-cover">
                    <svg id="view-vehicle-photo-icon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 13h2l2-6h10l2 6h2"/>
                        <path d="M5 13v5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-5"/>
                        <circle cx="7.5" cy="16.5" r="1.5"/>
                        <circle cx="16.5" cy="16.5" r="1.5"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p id="view-vehicle-title" class="truncate text-base font-semibold text-gray-900"></p>
                    <p id="view-vehicle-plate" class="truncate text-sm text-gray-500"></p>
                </div>
            </div>

            <dl class="mt-5 space-y-3 border-t border-gray-100 pt-4">
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Chassis/VIN Number</dt>
                    <dd id="view-vehicle-chassis" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Engine Number</dt>
                    <dd id="view-vehicle-engine" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Color</dt>
                    <dd id="view-vehicle-color" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Odometer</dt>
                    <dd id="view-vehicle-odometer" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Vehicle Type</dt>
                    <dd id="view-vehicle-type" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Fuel Type</dt>
                    <dd id="view-vehicle-fuel" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Registered</dt>
                    <dd id="view-vehicle-created" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Last updated</dt>
                    <dd id="view-vehicle-updated" class="text-sm font-medium text-gray-900"></dd>
                </div>
            </dl>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    onclick="closeViewVehicleModal()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal --}}
    <div id="delete-vehicle-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-red-50 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4M12 17h.01"/>
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-semibold text-gray-900">Delete vehicle?</h2>
            <p id="delete-vehicle-warning" class="mt-1 text-sm text-gray-500">
                This will permanently remove <span id="delete-vehicle-name" class="font-medium text-gray-700"></span> from the fleet. This action cannot be undone.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    onclick="closeDeleteVehicleModal()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                >
                    Cancel
                </button>

                <form id="delete-vehicle-form" method="POST" action="">
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
        .vehicle-view-toggle-btn { color: #6b7280; }
        .vehicle-view-toggle-btn.active { background: linear-gradient(to right, #059669, #16a34a); color: #fff; }
    </style>

    <script>
        function applyVehicleView(mode) {
            const list = document.getElementById('vehicle-view-list');
            const grid = document.getElementById('vehicle-view-grid');
            const btnList = document.getElementById('vehicle-view-toggle-list');
            const btnGrid = document.getElementById('vehicle-view-toggle-grid');

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

        function currentVehicleViewMode() {
            return localStorage.getItem('vehicle_view_mode') || 'list';
        }

        function setVehicleView(mode) {
            localStorage.setItem('vehicle_view_mode', mode);
            applyVehicleView(mode);
        }

        document.addEventListener('DOMContentLoaded', function () {
            applyVehicleView(currentVehicleViewMode());
        });

        /* ---------------- View modal ---------------- */

        function openViewVehicleModal(btn) {
            const img = document.getElementById('view-vehicle-photo-img');
            const icon = document.getElementById('view-vehicle-photo-icon');
            if (btn.dataset.photo) {
                img.src = btn.dataset.photo;
                img.classList.remove('hidden');
                icon.classList.add('hidden');
            } else {
                img.classList.add('hidden');
                icon.classList.remove('hidden');
            }

            document.getElementById('view-vehicle-title').textContent = btn.dataset.brand + ' ' + btn.dataset.model + ' (' + btn.dataset.year + ')';
            document.getElementById('view-vehicle-plate').textContent = btn.dataset.plate_number;
            document.getElementById('view-vehicle-chassis').textContent = btn.dataset.chassis_number;
            document.getElementById('view-vehicle-engine').textContent = btn.dataset.engine_number;
            document.getElementById('view-vehicle-color').textContent = btn.dataset.color;
            document.getElementById('view-vehicle-odometer').textContent = btn.dataset.odometer + ' km';
            document.getElementById('view-vehicle-type').textContent = btn.dataset.vehicle_type;
            document.getElementById('view-vehicle-fuel').textContent = btn.dataset.fuel_type;
            document.getElementById('view-vehicle-created').textContent = btn.dataset.created;
            document.getElementById('view-vehicle-updated').textContent = btn.dataset.updated;

            const modal = document.getElementById('view-vehicle-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeViewVehicleModal() {
            const modal = document.getElementById('view-vehicle-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        /* ---------------- Delete modal ---------------- */

        function openDeleteVehicleModal(btn) {
            document.getElementById('delete-vehicle-name').textContent = btn.dataset.plate_number;
            document.getElementById('delete-vehicle-form').action = btn.dataset.deleteUrl;

            const modal = document.getElementById('delete-vehicle-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteVehicleModal() {
            const modal = document.getElementById('delete-vehicle-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        /* ---------------- Edit Vehicle modal ---------------- */

        function editVehicleTypeLabel(value) {
            const select = document.getElementById('ev-vehicle_type');
            const option = select.querySelector(`option[value="${value}"]`);
            return option ? option.textContent : 'None';
        }

        function editFuelTypeLabel(value) {
            const select = document.getElementById('ev-fuel_type');
            const option = select.querySelector(`option[value="${value}"]`);
            return option ? option.textContent : 'None';
        }

        function openEditVehicleModal(btn) {
            const modal = document.getElementById('edit-vehicle-modal');
            const form = document.getElementById('edit-vehicle-form');

            document.getElementById('ev-photo').value = '';
            document.getElementById('ev-remove-photo').value = '0';

            if (btn) {
                form.action = btn.dataset.updateUrl;
                document.getElementById('edit-vehicle-action-field').value = btn.dataset.updateUrl;
                document.getElementById('edit-vehicle-subtitle').textContent = "Update " + btn.dataset.plate_number + "'s details below.";

                document.getElementById('ev-plate_number').value = btn.dataset.plate_number;
                document.getElementById('ev-chassis_number').value = btn.dataset.chassis_number;
                document.getElementById('ev-engine_number').value = btn.dataset.engine_number;
                document.getElementById('ev-brand').value = btn.dataset.brand;
                document.getElementById('ev-model').value = btn.dataset.model;
                document.getElementById('ev-year').value = btn.dataset.year;
                document.getElementById('ev-color').value = btn.dataset.color;
                document.getElementById('ev-odometer').value = btn.dataset.odometer;
                document.getElementById('ev-vehicle_type').value = btn.dataset.vehicle_type || '';
                document.getElementById('ev-fuel_type').value = btn.dataset.fuel_type || '';
                document.getElementById('ev-location').value = btn.dataset.location || '';

                const img = document.getElementById('ev-photo-preview-img');
                const icon = document.getElementById('ev-photo-preview-icon');
                const filenameEl = document.getElementById('ev-photo-filename');
                const removeBtn = document.getElementById('ev-photo-remove-btn');
                if (btn.dataset.photo) {
                    img.src = btn.dataset.photo;
                    img.classList.remove('hidden');
                    icon.classList.add('hidden');
                    filenameEl.textContent = 'Current photo';
                    removeBtn.classList.remove('hidden');
                } else {
                    img.src = '';
                    img.classList.add('hidden');
                    icon.classList.remove('hidden');
                    filenameEl.textContent = 'No file chosen';
                    removeBtn.classList.add('hidden');
                }
            }
            // When re-opened automatically after a validation error, the form
            // already has the posted values (action + old input) rendered server-side.

            document.getElementById('edit-vehicle-step-confirm').classList.add('hidden');
            document.getElementById('edit-vehicle-step-form').classList.remove('hidden');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function previewEditVehiclePhoto(input) {
            const file = input.files && input.files[0];
            if (!file) return;

            document.getElementById('ev-remove-photo').value = '0';

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('ev-photo-preview-img');
                img.src = e.target.result;
                img.classList.remove('hidden');
                document.getElementById('ev-photo-preview-icon').classList.add('hidden');
                document.getElementById('ev-photo-filename').textContent = file.name;
                document.getElementById('ev-photo-remove-btn').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        function removeEditVehiclePhoto() {
            document.getElementById('ev-photo').value = '';
            document.getElementById('ev-remove-photo').value = '1';

            const img = document.getElementById('ev-photo-preview-img');
            img.src = '';
            img.classList.add('hidden');
            document.getElementById('ev-photo-preview-icon').classList.remove('hidden');
            document.getElementById('ev-photo-filename').textContent = 'No file chosen';
            document.getElementById('ev-photo-remove-btn').classList.add('hidden');
        }

        function closeEditVehicleModal() {
            const modal = document.getElementById('edit-vehicle-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function handleEditVehicleSubmit(event) {
            event.preventDefault();
            const form = document.getElementById('edit-vehicle-form');

            if (!form.reportValidity()) {
                return false;
            }

            document.getElementById('ev-confirm-plate_number').textContent = document.getElementById('ev-plate_number').value;

            const photoFile = document.getElementById('ev-photo').files[0];
            const removingPhoto = document.getElementById('ev-remove-photo').value === '1';
            document.getElementById('ev-confirm-photo').textContent = photoFile
                ? photoFile.name
                : (removingPhoto ? 'Will be removed' : 'Unchanged');

            document.getElementById('ev-confirm-chassis_number').textContent = document.getElementById('ev-chassis_number').value;
            document.getElementById('ev-confirm-engine_number').textContent = document.getElementById('ev-engine_number').value;
            document.getElementById('ev-confirm-brand').textContent = document.getElementById('ev-brand').value;
            document.getElementById('ev-confirm-model').textContent = document.getElementById('ev-model').value;
            document.getElementById('ev-confirm-year').textContent = document.getElementById('ev-year').value;
            document.getElementById('ev-confirm-color').textContent = document.getElementById('ev-color').value;
            document.getElementById('ev-confirm-odometer').textContent = document.getElementById('ev-odometer').value + ' km';

            const vehicleTypeValue = document.getElementById('ev-vehicle_type').value;
            document.getElementById('ev-confirm-vehicle_type').textContent = vehicleTypeValue ? editVehicleTypeLabel(vehicleTypeValue) : 'None';

            const fuelTypeValue = document.getElementById('ev-fuel_type').value;
            document.getElementById('ev-confirm-fuel_type').textContent = fuelTypeValue ? editFuelTypeLabel(fuelTypeValue) : 'None';

            document.getElementById('edit-vehicle-step-form').classList.add('hidden');
            document.getElementById('edit-vehicle-step-confirm').classList.remove('hidden');
            return false;
        }

        function backToEditVehicleForm() {
            document.getElementById('edit-vehicle-step-confirm').classList.add('hidden');
            document.getElementById('edit-vehicle-step-form').classList.remove('hidden');
        }

        function confirmEditVehicle() {
            document.getElementById('edit-vehicle-form').submit();
        }

        /* ---------------- Live search ---------------- */

        let vehicleSearchTimer = null;
        let vehicleSearchController = null;

        function onVehicleSearchInput() {
            const value = document.getElementById('vehicle-search-input').value;
            document.getElementById('vehicle-search-clear').classList.toggle('hidden', value.length === 0);

            clearTimeout(vehicleSearchTimer);
            vehicleSearchTimer = setTimeout(() => runVehicleSearch(value), 300);
        }

        function toggleVehicleLocationMenu() {
            const menu = document.getElementById('vehicle-location-menu');
            const chevron = document.getElementById('vehicle-location-chevron');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                chevron?.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                chevron?.classList.remove('rotate-180');
            }
        }

        document.addEventListener('click', function (e) {
            const menu = document.getElementById('vehicle-location-menu');
            const btn = e.target.closest('button[onclick="toggleVehicleLocationMenu()"]');
            if (menu && !menu.classList.contains('hidden') && !btn && !menu.contains(e.target)) {
                menu.classList.add('hidden');
                document.getElementById('vehicle-location-chevron')?.classList.remove('rotate-180');
            }
        });

        function clearVehicleSearch(event) {
            event.preventDefault();
            document.getElementById('vehicle-search-input').value = '';
            document.getElementById('vehicle-search-clear').classList.add('hidden');
            runVehicleSearch('');
            return false;
        }

        function runVehicleSearch(term) {
            const url = new URL(window.location.href);
            if (term) {
                url.searchParams.set('search', term);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.delete('page');

            const spinner = document.getElementById('vehicle-search-spinner');
            spinner.classList.remove('hidden');

            if (vehicleSearchController) {
                vehicleSearchController.abort();
            }
            vehicleSearchController = new AbortController();

            fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: vehicleSearchController.signal,
            })
                .then((response) => response.text())
                .then((html) => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const fresh = doc.getElementById('vehicle-results');
                    if (fresh) {
                        document.getElementById('vehicle-results').innerHTML = fresh.innerHTML;
                        applyVehicleView(currentVehicleViewMode());
                    }
                    window.history.replaceState({}, '', url.toString());
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        console.error('Vehicle search failed', error);
                    }
                })
                .finally(() => {
                    spinner.classList.add('hidden');
                });
        }

        function goToVehiclePage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            fetchAndSwapVehicleResults(url.toString());
        }

        function changeVehiclePerPage(size) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', size);
            url.searchParams.set('page', '1');
            fetchAndSwapVehicleResults(url.toString());
        }

        function fetchAndSwapVehicleResults(urlStr) {
            const spinner = document.getElementById('vehicle-search-spinner');
            spinner?.classList.remove('hidden');

            fetch(urlStr, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const fresh = doc.getElementById('vehicle-results');
                    if (fresh) {
                        document.getElementById('vehicle-results').innerHTML = fresh.innerHTML;
                        applyVehicleView(currentVehicleViewMode());
                    }
                    window.history.pushState({}, '', urlStr);
                })
                .catch(err => console.error('Vehicle fetch error:', err))
                .finally(() => spinner?.classList.add('hidden'));
        }
    </script>

    <script>
        function openRegisterVehicleModal() {
            const modal = document.getElementById('register-vehicle-modal');
            document.getElementById('register-vehicle-step-confirm').classList.add('hidden');
            document.getElementById('register-vehicle-step-form').classList.remove('hidden');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeRegisterVehicleModal() {
            const modal = document.getElementById('register-vehicle-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function handleVehiclePhotoChange(input) {
            const file = input.files && input.files[0];
            const filenameEl = document.getElementById('rv-photo-filename');
            const img = document.getElementById('rv-photo-preview-img');
            const icon = document.getElementById('rv-photo-preview-icon');

            if (!file) {
                filenameEl.textContent = 'No file chosen';
                img.src = '';
                img.classList.add('hidden');
                icon.classList.remove('hidden');
                return;
            }

            filenameEl.textContent = file.name;

            const reader = new FileReader();
            reader.onload = (e) => {
                img.src = e.target.result;
                img.classList.remove('hidden');
                icon.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        function vehicleTypeLabel(value) {
            const select = document.getElementById('rv-vehicle_type');
            const option = select.querySelector(`option[value="${value}"]`);
            return option ? option.textContent : 'None';
        }

        function fuelTypeLabel(value) {
            const select = document.getElementById('rv-fuel_type');
            const option = select.querySelector(`option[value="${value}"]`);
            return option ? option.textContent : 'None';
        }

        function handleRegisterVehicleSubmit(event) {
            event.preventDefault();
            const form = document.getElementById('register-vehicle-form');

            if (!form.reportValidity()) {
                return false;
            }

            document.getElementById('rv-confirm-plate_number').textContent = document.getElementById('rv-plate_number').value;

            const photoFile = document.getElementById('rv-photo').files[0];
            document.getElementById('rv-confirm-photo').textContent = photoFile ? photoFile.name : 'None';

            document.getElementById('rv-confirm-chassis_number').textContent = document.getElementById('rv-chassis_number').value;
            document.getElementById('rv-confirm-engine_number').textContent = document.getElementById('rv-engine_number').value;
            document.getElementById('rv-confirm-brand').textContent = document.getElementById('rv-brand').value;
            document.getElementById('rv-confirm-model').textContent = document.getElementById('rv-model').value;
            document.getElementById('rv-confirm-year').textContent = document.getElementById('rv-year').value;
            document.getElementById('rv-confirm-color').textContent = document.getElementById('rv-color').value;
            document.getElementById('rv-confirm-odometer').textContent = document.getElementById('rv-odometer').value + ' km';

            const vehicleTypeValue = document.getElementById('rv-vehicle_type').value;
            document.getElementById('rv-confirm-vehicle_type').textContent = vehicleTypeValue ? vehicleTypeLabel(vehicleTypeValue) : 'None';

            const fuelTypeValue = document.getElementById('rv-fuel_type').value;
            document.getElementById('rv-confirm-fuel_type').textContent = fuelTypeValue ? fuelTypeLabel(fuelTypeValue) : 'None';

            document.getElementById('register-vehicle-step-form').classList.add('hidden');
            document.getElementById('register-vehicle-step-confirm').classList.remove('hidden');
            return false;
        }

        function backToRegisterVehicleForm() {
            document.getElementById('register-vehicle-step-confirm').classList.add('hidden');
            document.getElementById('register-vehicle-step-form').classList.remove('hidden');
        }

        function confirmRegisterVehicle() {
            document.getElementById('register-vehicle-form').submit();
        }
    </script>
@endsection
