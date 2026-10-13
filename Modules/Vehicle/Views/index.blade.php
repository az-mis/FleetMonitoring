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
        // The controller already resolves the effective location: the ?location= filter for
        // super admins, the admin's own assigned location for everyone else. Only fall back
        // to the query string if it wasn't passed in.
        $selectedLocation = $selectedLocation ?? request('location');
        $locationButtonLabel = $selectedLocation ?: 'All Locations';
    @endphp

    {{-- Page header --}}
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Vehicles</h1>
            <p class="text-sm text-slate-500">Manage your fleet vehicles</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <form method="GET" action="{{ route('vehicles.index') }}" id="vehicle-search-form" class="flex w-full min-w-0 items-center gap-2 sm:w-72 xl:w-96" onsubmit="return false;">
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-600/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        id="vehicle-search-input"
                        value="{{ request('search') }}"
                        autocomplete="off"
                        placeholder="Search by plate number, brand, model, or VIN..."
                        oninput="onVehicleSearchInput()"
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-8 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                    <svg id="vehicle-search-spinner" xmlns="http://www.w3.org/2000/svg" class="hidden absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                </div>

                <a href="{{ route('vehicles.index') }}" id="vehicle-search-clear" onclick="return clearVehicleSearch(event)" class="shrink-0 text-xs font-semibold text-slate-500 hover:text-slate-700 {{ request('search') ? '' : 'hidden' }}">Clear</a>
            </form>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Location filter (moved up from the chips row) --}}
                @if ($isSuperAdmin)
                    <div class="relative">
                        <button
                            type="button"
                            onclick="toggleVehicleLocationMenu()"
                            class="inline-flex h-10 items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 cursor-pointer"
                        >
                            <span class="flex items-center gap-1.5 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span class="max-w-[9rem] truncate">{{ $locationButtonLabel }}</span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400 shrink-0 transition" id="vehicle-location-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div id="vehicle-location-menu" class="absolute left-0 z-20 mt-2 hidden w-64 sm:left-auto sm:right-0 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5">
                            <a href="{{ route('vehicles.index', array_filter(['search' => request('search'), 'per_page' => request('per_page')])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 {{ !$selectedLocation ? 'bg-emerald-50 text-emerald-800' : '' }}">
                                <span>All Locations</span>
                                @if(!$selectedLocation)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                @endif
                            </a>
                            @foreach($locationOptions as $opt)
                                <a href="{{ route('vehicles.index', array_filter(['search' => request('search'), 'per_page' => request('per_page'), 'location' => $opt])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 {{ $selectedLocation === $opt ? 'bg-emerald-50 text-emerald-800' : '' }}">
                                    <span>{{ $opt }}</span>
                                    @if($selectedLocation === $opt)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span class="max-w-[9rem] truncate">{{ $selectedLocation ?: 'All Locations' }}</span>
                    </div>
                @endif

                {{-- View toggle buttons (segmented switch) --}}
                <div class="flex h-10 shrink-0 items-center rounded-xl border border-slate-200 bg-white p-1">
                    <button
                        type="button"
                        id="vehicle-view-toggle-list"
                        onclick="setVehicleView('list')"
                        class="vehicle-view-toggle-btn flex h-full w-8 items-center justify-center rounded-lg transition text-slate-400 hover:text-slate-600"
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
                        class="vehicle-view-toggle-btn flex h-full w-8 items-center justify-center rounded-lg transition text-slate-400 hover:text-slate-600"
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
                    onclick="openRegisterVehicleModal()"
                    class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-[#005a36] px-4 text-xs font-extrabold text-white shadow-sm hover:bg-[#00432a] transition cursor-pointer"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>

                    </svg>
                    <span>Register Vehicle</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Category filter chips (one per row in the vehicle_types table) --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <button type="button" onclick="setVehicleCategoryFilter('all')" data-category-filter="all" class="vehicle-category-chip active">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>
            </svg>
            <span>All Vehicles ({{ $totalVehicleCount }})</span>
        </button>

        @foreach ($vehicleTypes as $vehicleType)
            <button type="button" onclick="setVehicleCategoryFilter('{{ $vehicleType->slug }}')" data-category-filter="{{ $vehicleType->slug }}" class="vehicle-category-chip">
                @include('vehicle::partials.vehicle-type-icon', ['iconOverride' => $vehicleType->icon, 'class' => 'h-5 w-5 shrink-0', 'stroke' => '1.75'])
                <span>{{ $vehicleType->chipLabel() }} ({{ $typeCounts[$vehicleType->slug] ?? 0 }})</span>
            </button>
        @endforeach

        <button type="button" onclick="openAddVehicleTypeModal()" class="vehicle-category-chip vehicle-category-chip-add">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            <span>Add Vehicle Type</span>
        </button>

        <button type="button" onclick="openPmsRulesModal()" class="vehicle-category-chip bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100 hover:border-amber-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
            <span>PMS Rules & Templates</span>
        </button>
    </div>

    <div id="vehicle-results">
        @include('vehicle::partials.vehicle-results', ['vehicles' => $vehicles])
    </div>

    {{-- Register Vehicle modal --}}
    @php
        $rvOld = fn (string $key, $default = '') => old('_vehicle_form') === 'create' ? old($key, $default) : $default;

        $rvBrands = ['Toyota', 'Mitsubishi', 'Nissan', 'Isuzu', 'Ford', 'Honda', 'Hyundai', 'Suzuki', 'Kia', 'Mazda', 'Chevrolet', 'Subaru', 'BMW', 'Mercedes-Benz', 'Foton', 'Yamaha', 'Kawasaki'];
        $rvTypes  = $vehicleTypes->pluck('name')->all();
        $rvFuels  = ['Gasoline', 'Diesel', 'Hybrid', 'Electric', 'LPG'];

        $rvBrandValue   = (string) $rvOld('brand');
        $rvBrandIsOther = $rvBrandValue !== '' && ! in_array($rvBrandValue, $rvBrands, true);
        $rvTypeValue    = (string) $rvOld('vehicle_type');
        $rvFuelValue    = (string) $rvOld('fuel_type');

        $rvLabel     = 'mb-1.5 block text-[13px] font-semibold text-slate-700';
        $rvInputBase = 'h-11 w-full rounded-lg border px-3 text-sm placeholder-slate-400 transition focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600';
        $rvInput     = $rvInputBase . ' bg-white text-slate-800';
        $rvSelect    = $rvInput . ' cursor-pointer appearance-none pr-9 invalid:text-slate-400';
        $rvNoSpin    = '[appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';
        $rvBorder    = fn (string $field) => $errors->has($field) ? 'border-red-400' : 'border-slate-200';
    @endphp

    <style>
        #register-vehicle-modal option, #edit-vehicle-modal option { color: #1e293b; }
    </style>

    <div id="register-vehicle-modal" class="fixed inset-0 z-50 hidden items-stretch justify-center bg-black/50 backdrop-blur-sm sm:items-center sm:p-6">
        <div class="flex h-full w-full flex-col overflow-hidden bg-white shadow-2xl sm:h-auto sm:max-h-[92vh] sm:max-w-3xl sm:rounded-2xl">

            {{-- Header --}}
            <div class="flex shrink-0 items-center justify-between bg-[#005a36] px-5 py-4 text-white sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-[#005a36]">
                        @include('vehicle::partials.vehicle-type-icon', ['type' => 'car', 'class' => 'h-5 w-5', 'stroke' => '2'])
                    </span>
                    <h2 class="truncate text-lg font-bold tracking-tight sm:text-xl">Register New Vehicle</h2>
                </div>
                <button type="button" onclick="closeRegisterVehicleModal()" aria-label="Close" class="shrink-0 rounded-lg p-1.5 text-white/90 transition hover:bg-white/10 hover:text-white cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Step 1: form --}}
            <div id="register-vehicle-step-form" class="min-h-0 flex-1 flex-col" style="display:flex">
                <form method="POST" action="{{ route('vehicles.store') }}" id="register-vehicle-form" class="flex min-h-0 flex-1 flex-col" enctype="multipart/form-data" onsubmit="return handleRegisterVehicleSubmit(event)">
                    @csrf
                    <input type="hidden" name="_vehicle_form" value="create">

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
                        <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-12">

                            {{-- Vehicle Photo --}}
                            <div class="sm:col-span-6">
                                <p class="mb-2 text-sm font-bold text-slate-900">Vehicle Photo</p>
                                <div class="flex flex-col gap-3 sm:flex-row">
                                    <label
                                        for="rv-photo"
                                        id="rv-photo-dropzone"
                                        ondragover="handleRegisterPhotoDrag(event, true)"
                                        ondragleave="handleRegisterPhotoDrag(event, false)"
                                        ondrop="handleRegisterPhotoDrop(event)"
                                        class="flex h-36 min-w-0 flex-1 cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-3 text-center transition hover:border-emerald-500 hover:bg-emerald-50/40 sm:h-[204px]"
                                    >
                                        <span class="text-slate-500">
                                            @include('vehicle::partials.vehicle-type-icon', ['type' => 'car', 'class' => 'h-8 w-8', 'stroke' => '1.5'])
                                        </span>
                                        <span class="text-xs text-slate-500"><span class="font-semibold text-slate-800">Click to upload</span> or drag and drop</span>
                                        <span class="text-[11px] text-slate-400">JPG, PNG, WEBP (Max 5MB)</span>
                                    </label>
                                    <input type="file" name="photo" id="rv-photo" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="handleVehiclePhotoChange(this)">

                                    <div id="rv-photo-preview" class="hidden shrink-0 sm:w-40">
                                        <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                            <img id="rv-photo-preview-img" src="" alt="Selected vehicle photo" class="h-36 w-full object-cover sm:h-[168px]">
                                            <button type="button" onclick="clearRegisterPhoto()" aria-label="Remove photo" class="absolute right-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-slate-900/80 text-white transition hover:bg-slate-900 cursor-pointer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <p id="rv-photo-filename" class="mt-1 truncate text-center text-[11px] font-medium text-slate-500"></p>
                                    </div>
                                </div>
                                <p id="rv-photo-error" class="mt-1.5 hidden text-xs text-red-600"></p>
                                @error('photo')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Plate / Chassis / Engine --}}
                            <div class="space-y-4 sm:col-span-6">
                                <div>
                                    <label for="rv-plate_number" class="{{ $rvLabel }}">Plate Number <span class="text-red-500">*</span></label>
                                    <input type="text" name="plate_number" id="rv-plate_number" value="{{ $rvOld('plate_number') }}" maxlength="20" placeholder="e.g. ABC 1234" class="{{ $rvInput }} {{ $rvBorder('plate_number') }}" required>
                                    @error('plate_number')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="rv-chassis_number" class="{{ $rvLabel }}">Chassis / VIN Number <span class="text-slate-400 font-normal">(Optional)</span></label>
                                    <input type="text" name="chassis_number" id="rv-chassis_number" value="{{ $rvOld('chassis_number') }}" maxlength="50" placeholder="e.g. 1HGBH41JXMN109186" class="{{ $rvInput }} {{ $rvBorder('chassis_number') }}">
                                    @error('chassis_number')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="rv-engine_number" class="{{ $rvLabel }}">Engine Number <span class="text-slate-400 font-normal">(Optional)</span></label>
                                    <input type="text" name="engine_number" id="rv-engine_number" value="{{ $rvOld('engine_number') }}" maxlength="50" placeholder="e.g. 2AR1234567" class="{{ $rvInput }} {{ $rvBorder('engine_number') }}">
                                    @error('engine_number')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Brand --}}
                            <div class="sm:col-span-4">
                                <label for="rv-brand" class="{{ $rvLabel }}">Brand <span class="text-red-500">*</span></label>
                                <input type="text" name="brand" id="rv-brand" value="{{ $rvOld('brand') }}" maxlength="100" placeholder="e.g. Toyota, Honda" class="{{ $rvInput }} {{ $rvBorder('brand') }}" required>
                                @error('brand')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Model --}}
                            <div class="sm:col-span-4">
                                <label for="rv-model" class="{{ $rvLabel }}">Model <span class="text-red-500">*</span></label>
                                <input type="text" name="model" id="rv-model" value="{{ $rvOld('model') }}" maxlength="100" placeholder="e.g. Civic" class="{{ $rvInput }} {{ $rvBorder('model') }}" required>
                                @error('model')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Year --}}
                            <div class="sm:col-span-4">
                                <label for="rv-year" class="{{ $rvLabel }}">Year <span class="text-xs font-normal text-slate-400">(Optional)</span></label>
                                <div class="relative">
                                    <input type="number" name="year" id="rv-year" value="{{ $rvOld('year') }}" min="1900" max="{{ date('Y') + 1 }}" placeholder="e.g. 2020" class="{{ $rvInput }} {{ $rvBorder('year') }} pr-10 {{ $rvNoSpin }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                                </div>
                                @error('year')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Color --}}
                            <div class="sm:col-span-4">
                                <label for="rv-color" class="{{ $rvLabel }}">Color <span class="text-red-500">*</span></label>
                                <input type="text" name="color" id="rv-color" value="{{ $rvOld('color') }}" maxlength="50" placeholder="e.g. White" class="{{ $rvInput }} {{ $rvBorder('color') }}" required>
                                @error('color')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Current Odometer --}}
                            <div class="sm:col-span-4">
                                <label for="rv-odometer" class="{{ $rvLabel }}">Current Odometer <span class="text-red-500">*</span></label>
                                <div class="flex h-11 overflow-hidden rounded-lg border bg-white transition focus-within:border-emerald-600 focus-within:ring-1 focus-within:ring-emerald-600 {{ $rvBorder('odometer') }}">
                                    <div class="relative min-w-0 flex-1">
                                        <input type="number" name="odometer" id="rv-odometer" value="{{ $rvOld('odometer') }}" min="0" placeholder="e.g. 25000" class="h-full w-full border-0 bg-transparent px-3 pr-9 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0 {{ $rvNoSpin }}" required>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>
                                    </div>
                                    <span class="flex shrink-0 items-center border-l border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-600">km</span>
                                </div>
                                @error('odometer')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Change Oil Interval --}}
                            <div class="sm:col-span-4">
                                <label for="rv-change_oil_interval_km" class="{{ $rvLabel }}">Change Oil Interval</label>
                                <div class="flex h-11 overflow-hidden rounded-lg border bg-white transition focus-within:border-emerald-600 focus-within:ring-1 focus-within:ring-emerald-600 {{ $rvBorder('change_oil_interval_km') }}">
                                    <div class="relative min-w-0 flex-1">
                                        <input type="number" name="change_oil_interval_km" id="rv-change_oil_interval_km" value="{{ $rvOld('change_oil_interval_km', '5000') }}" min="500" placeholder="e.g. 5000" class="h-full w-full border-0 bg-transparent px-3 pr-9 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0 {{ $rvNoSpin }}">
                                    </div>
                                    <span class="flex shrink-0 items-center border-l border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-600">km</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">Threshold (default 5,000 km)</p>
                            </div>

                            {{-- Vehicle Type --}}
                            <div class="sm:col-span-4">
                                <label for="rv-vehicle_type" class="{{ $rvLabel }}">Vehicle Type <span class="text-red-500">*</span></label>

                                {{-- Dropdown Mode --}}
                                <div id="rv-type-select-wrap" class="relative">
                                    <select name="vehicle_type" id="rv-vehicle_type" onchange="handleVehicleTypeSelectChange(this, 'register')" class="{{ $rvSelect }} {{ $rvBorder('vehicle_type') }}" required>
                                        <option value="">Select type</option>
                                        @if ($rvTypeValue !== '' && ! in_array($rvTypeValue, $rvTypes, true))
                                            <option value="{{ $rvTypeValue }}" selected>{{ $rvTypeValue }}</option>
                                        @endif
                                        @foreach ($rvTypes as $typeOption)
                                            <option value="{{ $typeOption }}" @selected($rvTypeValue === $typeOption)>{{ $typeOption }}</option>
                                        @endforeach
                                        <option value="__add_new" class="font-bold text-emerald-700 bg-emerald-50/90">+ Add New Vehicle Type...</option>
                                    </select>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </div>

                                {{-- Inline Text Input Mode with Save Checkmark (shown when "+ Add New Vehicle Type..." is clicked) --}}
                                <div id="rv-inline-add-type" class="hidden">
                                    <div class="relative flex items-center">
                                        <input
                                            type="text"
                                            id="rv-inline-type-name"
                                            maxlength="30"
                                            placeholder="Type new vehicle type..."
                                            onkeydown="if(event.key === 'Enter'){ event.preventDefault(); saveInlineVehicleType('register'); } else if(event.key === 'Escape'){ toggleInlineAddVehicleType('register', false); }"
                                            class="{{ $rvInput }} pr-16 border-emerald-500 ring-1 ring-emerald-500 focus:border-emerald-600 focus:ring-emerald-600"
                                        >
                                        <div class="absolute right-1.5 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                            {{-- Save Checkmark Button --}}
                                            <button
                                                type="button"
                                                id="rv-inline-type-save-btn"
                                                onclick="saveInlineVehicleType('register')"
                                                title="Save type"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#005a36] text-white hover:bg-[#00432a] transition shadow-xs cursor-pointer"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"/>
                                                </svg>
                                            </button>
                                            {{-- Cancel Button --}}
                                            <button
                                                type="button"
                                                onclick="toggleInlineAddVehicleType('register', false)"
                                                title="Cancel"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <p id="rv-inline-type-error" class="hidden mt-1 text-[11px] font-semibold text-red-600"></p>
                                </div>

                                @error('vehicle_type')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Fuel Type --}}
                            <div class="sm:col-span-4">
                                <label for="rv-fuel_type" class="{{ $rvLabel }}">Fuel Type <span class="text-red-500">*</span></label>
                                <input type="text" name="fuel_type" id="rv-fuel_type" value="{{ $rvOld('fuel_type') }}" maxlength="50" placeholder="e.g. Gasoline, Diesel" class="{{ $rvInput }} {{ $rvBorder('fuel_type') }}" required>
                                @error('fuel_type')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Assigned Location (Super Admin editable, others read-only) --}}
                            <div class="sm:col-span-7">
                                <label for="rv-location" class="{{ $rvLabel }}">Assigned Location / Office / Province @if($isSuperAdmin)<span class="text-red-500">*</span>@endif</label>
                                <div class="relative">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <select
                                        name="location"
                                        id="rv-location"
                                        @if($isSuperAdmin) required @else disabled @endif
                                        class="{{ $rvSelect }} {{ $rvBorder('location') }} pl-10 {{ $isSuperAdmin ? '' : '!cursor-not-allowed !bg-slate-50 !text-slate-500' }}"
                                    >
                                        <option value="">Select location</option>
                                        @foreach($locationOptions as $loc)
                                            <option value="{{ $loc }}" @selected(old('location', auth()->user()?->location) === $loc)>{{ $loc }}</option>
                                        @endforeach
                                    </select>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                                @if(!$isSuperAdmin)
                                    <input type="hidden" name="location" value="{{ auth()->user()?->location }}">
                                @endif
                                <p class="mt-1 text-xs text-slate-400">
                                    @if($isSuperAdmin)
                                        Requesters will only see this vehicle when selecting this location.
                                    @else
                                        Only a Super Admin can change the assigned location.
                                    @endif
                                </p>
                                @error('location')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex shrink-0 items-center gap-3 border-t border-slate-100 bg-white px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:justify-end sm:px-6 sm:pb-4">
                        <button type="button" onclick="closeRegisterVehicleModal()" class="inline-flex h-11 flex-1 items-center justify-center rounded-lg border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 cursor-pointer sm:flex-none">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-[#005a36] px-5 text-sm font-bold text-white shadow-sm transition hover:bg-[#00432a] cursor-pointer sm:flex-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                            <span>Save Vehicle</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Step 2: confirmation --}}
            <div id="register-vehicle-step-confirm" class="min-h-0 flex-1 flex-col" style="display:none">
                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
                    <h3 class="text-lg font-bold text-slate-900">Register this vehicle?</h3>
                    <p class="mt-1 text-sm text-slate-500">Please confirm the details below before saving the record.</p>

                    <dl class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-slate-50/60 px-4">
                        @foreach ([
                            'plate_number'   => 'Plate Number',
                            'photo'          => 'Photo',
                            'chassis_number' => 'Chassis / VIN Number',
                            'engine_number'  => 'Engine Number',
                            'brand'          => 'Brand',
                            'model'          => 'Model',
                            'year'           => 'Year',
                            'color'          => 'Color',
                            'odometer'       => 'Odometer',
                            'vehicle_type'   => 'Vehicle Type',
                            'fuel_type'      => 'Fuel Type',
                            'location'       => 'Assigned Location',
                        ] as $confirmKey => $confirmLabel)
                            <div class="flex items-center justify-between gap-4 py-2.5">
                                <dt class="shrink-0 text-sm text-slate-500">{{ $confirmLabel }}</dt>
                                <dd id="rv-confirm-{{ $confirmKey }}" class="min-w-0 truncate text-right text-sm font-semibold text-slate-900"></dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div class="flex shrink-0 items-center gap-3 border-t border-slate-100 bg-white px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:justify-end sm:px-6 sm:pb-4">
                    <button type="button" onclick="backToRegisterVehicleForm()" class="inline-flex h-11 flex-1 items-center justify-center rounded-lg border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 cursor-pointer sm:flex-none">
                        Back
                    </button>
                    <button type="button" onclick="confirmRegisterVehicle()" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-[#005a36] px-5 text-sm font-bold text-white shadow-sm transition hover:bg-[#00432a] cursor-pointer sm:flex-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                        <span>Save Vehicle</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any() && old('_vehicle_form') === 'create')
        <script>
            window.hasRegisterVehicleErrors = true;
            document.addEventListener('DOMContentLoaded', () => openRegisterVehicleModal());
        </script>
    @else
        <script>
            window.hasRegisterVehicleErrors = false;
        </script>
    @endif

    {{-- Add Vehicle Type modal --}}
    @php
        $avtActive = old('_vehicle_form') === 'type';
        $avtIcon = $avtActive ? old('icon', 'car') : 'car';
    @endphp
    <div id="add-vehicle-type-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6 backdrop-blur-sm">
        <div class="flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between bg-[#005a36] px-5 py-4 text-white">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-[#005a36]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    </span>
                    <h2 class="truncate text-lg font-bold tracking-tight">Add Vehicle Type</h2>
                </div>
                <button type="button" onclick="closeAddVehicleTypeModal()" aria-label="Close" class="shrink-0 rounded-lg p-1.5 text-white/90 transition hover:bg-white/10 hover:text-white cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('vehicles.types.store') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="_vehicle_form" value="type">

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5">
                    <div>
                        <label for="avt-name" class="mb-1.5 block text-[13px] font-semibold text-slate-700">Type Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="name"
                            id="avt-name"
                            value="{{ $avtActive ? old('name') : '' }}"
                            maxlength="30"
                            placeholder="e.g. Bus, Tricycle, Pickup Truck"
                            class="h-11 w-full rounded-lg border px-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600 {{ $avtActive && $errors->has('name') ? 'border-red-400' : 'border-slate-200' }}"
                            required
                        >
                        @if ($avtActive)
                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                        <p class="mt-1 text-xs text-slate-400">Shows up as a filter on this page and as an option in the Vehicle Type dropdown.</p>
                    </div>

                    <div>
                        <span class="mb-1.5 block text-[13px] font-semibold text-slate-700">Icon <span class="text-red-500">*</span></span>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (\Modules\Vehicle\Models\VehicleType::ICONS as $avtIconKey => $avtIconLabel)
                                <label class="cursor-pointer">
                                    <input type="radio" name="icon" value="{{ $avtIconKey }}" class="peer sr-only" @checked($avtIcon === $avtIconKey) required>
                                    <span class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-3 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50 peer-checked:border-[#005a36] peer-checked:bg-emerald-50 peer-checked:text-[#005a36] peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500">
                                        @include('vehicle::partials.vehicle-type-icon', ['iconOverride' => $avtIconKey, 'class' => 'h-6 w-6', 'stroke' => '1.75'])
                                        <span>{{ $avtIconLabel }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if ($avtActive)
                            @error('icon')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3 border-t border-slate-100 bg-white px-5 py-4 sm:justify-end">
                    <button type="button" onclick="closeAddVehicleTypeModal()" class="inline-flex h-11 flex-1 items-center justify-center rounded-lg border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 cursor-pointer sm:flex-none">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-[#005a36] px-5 text-sm font-bold text-white shadow-sm transition hover:bg-[#00432a] cursor-pointer sm:flex-none">
                        <span>Add Type</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any() && old('_vehicle_form') === 'type')
        <script>document.addEventListener('DOMContentLoaded', () => openAddVehicleTypeModal());</script>
    @endif

    {{-- Update (edit) Vehicle modal --}}
    @php
        $evIsOld      = old('_vehicle_form') === 'edit';
        $evOld        = fn (string $key, $default = '') => $evIsOld ? old($key, $default) : $default;
        $evBorder     = fn (string $field) => ($evIsOld && $errors->has($field)) ? 'border-red-400' : 'border-slate-200';

        $evBrandValue   = (string) $evOld('brand');
        $evBrandIsOther = $evBrandValue !== '' && ! in_array($evBrandValue, $rvBrands, true);
        $evTypeValue    = (string) $evOld('vehicle_type');
        $evFuelValue    = (string) $evOld('fuel_type');
    @endphp

    <div id="edit-vehicle-modal" class="fixed inset-0 z-50 hidden items-stretch justify-center bg-black/50 backdrop-blur-sm sm:items-center sm:p-6">
        <div class="flex h-full w-full flex-col overflow-hidden bg-white shadow-2xl sm:h-auto sm:max-h-[92vh] sm:max-w-3xl sm:rounded-2xl">

            {{-- Header --}}
            <div class="flex shrink-0 items-center justify-between bg-[#005a36] px-5 py-4 text-white sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-[#005a36]">
                        @include('vehicle::partials.vehicle-type-icon', ['type' => 'car', 'class' => 'h-5 w-5', 'stroke' => '2'])
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-bold leading-tight tracking-tight sm:text-xl">Update Vehicle Information</h2>
                        <p id="edit-vehicle-subtitle" class="truncate text-xs text-white/85 sm:text-[13px]">Edit the vehicle details and click Save to apply changes.</p>
                    </div>
                </div>
                <button type="button" onclick="closeEditVehicleModal()" aria-label="Close" class="shrink-0 rounded-lg p-1.5 text-white/90 transition hover:bg-white/10 hover:text-white cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ old('_ev_action', route('vehicles.index')) }}" id="edit-vehicle-form" class="flex min-h-0 flex-1 flex-col" enctype="multipart/form-data" onsubmit="return handleEditVehicleSubmit(event)">
                @csrf
                @method('PUT')
                <input type="hidden" name="_vehicle_form" value="edit">
                <input type="hidden" name="_ev_action" id="edit-vehicle-action-field" value="{{ old('_ev_action') }}">
                <input type="hidden" name="remove_photo" id="ev-remove-photo" value="0">

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
                    <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-12">

                        {{-- Vehicle Photo --}}
                        <div class="sm:col-span-5">
                            <p class="mb-2 text-sm font-bold text-slate-900">Vehicle Photo</p>

                            <div class="relative flex h-44 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-100 text-slate-400">
                                <img id="ev-photo-preview-img" src="" alt="Vehicle photo" class="absolute inset-0 hidden h-full w-full object-cover">
                                <span id="ev-photo-preview-icon">
                                    @include('vehicle::partials.vehicle-type-icon', ['type' => 'car', 'class' => 'h-12 w-12', 'stroke' => '1.25'])
                                </span>
                                <button type="button" id="ev-photo-remove-btn" onclick="removeEditVehiclePhoto()" title="Remove photo" aria-label="Remove photo" class="absolute right-2 top-2 hidden h-8 w-8 items-center justify-center rounded-full bg-white text-red-600 shadow-md ring-1 ring-black/5 transition hover:bg-red-50 cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </div>

                            <label
                                for="ev-photo"
                                id="ev-photo-dropzone"
                                ondragover="handleEditPhotoDrag(event, true)"
                                ondragleave="handleEditPhotoDrag(event, false)"
                                ondrop="handleEditPhotoDrop(event)"
                                class="mt-3 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-3 py-3.5 text-center transition hover:border-emerald-500 hover:bg-emerald-50/40"
                            >
                                <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                                    <span id="ev-photo-cta">Change Photo</span>
                                </span>
                                <span class="text-xs text-slate-500">Click to upload or drag and drop</span>
                                <span class="text-[11px] text-slate-400">JPG, PNG, WEBP (Max 5MB)</span>
                            </label>
                            <input type="file" name="photo" id="ev-photo" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="handleEditVehiclePhotoChange(this)">
                            <p id="ev-photo-error" class="mt-1.5 hidden text-xs text-red-600"></p>
                            @if ($evIsOld)
                                @error('photo')
                                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        {{-- Plate / Chassis / Engine / Brand / Model / Year / Color --}}
                        <div class="grid grid-cols-1 content-start gap-x-4 gap-y-4 sm:col-span-7 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="ev-plate_number" class="{{ $rvLabel }}">Plate Number <span class="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    name="plate_number"
                                    id="ev-plate_number"
                                    value="{{ $evOld('plate_number') }}"
                                    maxlength="20"
                                    placeholder="e.g. ABC 1234"
                                    @if (! $isSuperAdmin) readonly @endif
                                    class="{{ $rvInput }} {{ $evBorder('plate_number') }} {{ ! $isSuperAdmin ? '!cursor-not-allowed !bg-slate-50 !text-slate-500' : '' }}"
                                    required
                                >
                                @unless ($isSuperAdmin)
                                    <p class="mt-1 text-xs text-slate-400">Only a Super Admin can change the plate number.</p>
                                @endunless
                                @if ($evIsOld)
                                    @error('plate_number')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label for="ev-chassis_number" class="{{ $rvLabel }}">Chassis / VIN Number <span class="text-slate-400 font-normal">(Optional)</span></label>
                                <input type="text" name="chassis_number" id="ev-chassis_number" value="{{ $evOld('chassis_number') }}" maxlength="50" placeholder="e.g. 1HGBH41JXMN109186" class="{{ $rvInput }} {{ $evBorder('chassis_number') }}">
                                @if ($evIsOld)
                                    @error('chassis_number')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label for="ev-engine_number" class="{{ $rvLabel }}">Engine Number <span class="text-slate-400 font-normal">(Optional)</span></label>
                                <input type="text" name="engine_number" id="ev-engine_number" value="{{ $evOld('engine_number') }}" maxlength="50" placeholder="e.g. 2AR1234567" class="{{ $rvInput }} {{ $evBorder('engine_number') }}">
                                @if ($evIsOld)
                                    @error('engine_number')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            {{-- Brand --}}
                            <div>
                                <label for="ev-brand" class="{{ $rvLabel }}">Brand <span class="text-red-500">*</span></label>
                                <input type="text" name="brand" id="ev-brand" value="{{ $evOld('brand') }}" maxlength="100" placeholder="e.g. Toyota, Fiat" class="{{ $rvInput }} {{ $evBorder('brand') }}" required>
                                @if ($evIsOld)
                                    @error('brand')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label for="ev-model" class="{{ $rvLabel }}">Model <span class="text-red-500">*</span></label>
                                <input type="text" name="model" id="ev-model" value="{{ $evOld('model') }}" maxlength="100" placeholder="e.g. Civic" class="{{ $rvInput }} {{ $evBorder('model') }}" required>
                                @if ($evIsOld)
                                    @error('model')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label for="ev-year" class="{{ $rvLabel }}">Year <span class="text-xs font-normal text-slate-400">(Optional)</span></label>
                                <div class="relative">
                                    <input type="number" name="year" id="ev-year" value="{{ $evOld('year') }}" min="1900" max="{{ date('Y') + 1 }}" placeholder="e.g. 2020" class="{{ $rvInput }} {{ $evBorder('year') }} pr-10 {{ $rvNoSpin }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                                </div>
                                @if ($evIsOld)
                                    @error('year')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            <div>
                                <label for="ev-color" class="{{ $rvLabel }}">Color <span class="text-red-500">*</span></label>
                                <input type="text" name="color" id="ev-color" value="{{ $evOld('color') }}" maxlength="50" placeholder="e.g. White" class="{{ $rvInput }} {{ $evBorder('color') }}" required>
                                @if ($evIsOld)
                                    @error('color')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        {{-- Current Odometer --}}
                        <div class="sm:col-span-3">
                            <label for="ev-odometer" class="{{ $rvLabel }}">Current Odometer <span class="text-red-500">*</span></label>
                            <div class="flex h-11 overflow-hidden rounded-lg border bg-white transition focus-within:border-emerald-600 focus-within:ring-1 focus-within:ring-emerald-600 {{ $evBorder('odometer') }}">
                                <div class="relative min-w-0 flex-1">
                                    <input type="number" name="odometer" id="ev-odometer" value="{{ $evOld('odometer') }}" min="0" placeholder="e.g. 25000" class="h-full w-full border-0 bg-transparent px-3 pr-9 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0 {{ $rvNoSpin }}" required>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>
                                </div>
                                <span class="flex shrink-0 items-center border-l border-slate-200 bg-slate-50 px-2.5 text-xs font-semibold text-slate-600">km</span>
                            </div>
                            @if ($evIsOld)
                                @error('odometer')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        {{-- Change Oil Interval --}}
                        <div class="sm:col-span-3">
                            <label for="ev-change_oil_interval_km" class="{{ $rvLabel }}">Change Oil Interval</label>
                            <div class="flex h-11 overflow-hidden rounded-lg border bg-white transition focus-within:border-emerald-600 focus-within:ring-1 focus-within:ring-emerald-600 {{ $evBorder('change_oil_interval_km') }}">
                                <div class="relative min-w-0 flex-1">
                                    <input type="number" name="change_oil_interval_km" id="ev-change_oil_interval_km" value="{{ $evOld('change_oil_interval_km', '5000') }}" min="500" placeholder="e.g. 5000" class="h-full w-full border-0 bg-transparent px-3 pr-9 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0 {{ $rvNoSpin }}">
                                </div>
                                <span class="flex shrink-0 items-center border-l border-slate-200 bg-slate-50 px-2.5 text-xs font-semibold text-slate-600">km</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Threshold (5,000 km)</p>
                        </div>

                        {{-- Vehicle Type --}}
                        <div class="sm:col-span-3">
                            <label for="ev-vehicle_type" class="{{ $rvLabel }}">Vehicle Type <span class="text-red-500">*</span></label>

                            {{-- Dropdown Mode --}}
                            <div id="ev-type-select-wrap" class="relative">
                                <select name="vehicle_type" id="ev-vehicle_type" onchange="handleVehicleTypeSelectChange(this, 'edit')" class="{{ $rvSelect }} {{ $evBorder('vehicle_type') }}" required>
                                    <option value="">Select type</option>
                                    @if ($evTypeValue !== '' && ! in_array($evTypeValue, $rvTypes, true))
                                        <option value="{{ $evTypeValue }}" selected>{{ $evTypeValue }}</option>
                                    @endif
                                    @foreach ($rvTypes as $typeOption)
                                        <option value="{{ $typeOption }}" @selected($evTypeValue === $typeOption)>{{ $typeOption }}</option>
                                    @endforeach
                                    <option value="__add_new" class="font-bold text-emerald-700 bg-emerald-50/90">+ Add New Vehicle Type...</option>
                                </select>
                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </div>

                            {{-- Inline Text Input Mode with Save Checkmark (shown when "+ Add New Vehicle Type..." is clicked) --}}
                            <div id="ev-inline-add-type" class="hidden">
                                <div class="relative flex items-center">
                                    <input
                                        type="text"
                                        id="ev-inline-type-name"
                                        maxlength="30"
                                        placeholder="Type new vehicle type..."
                                        onkeydown="if(event.key === 'Enter'){ event.preventDefault(); saveInlineVehicleType('edit'); } else if(event.key === 'Escape'){ toggleInlineAddVehicleType('edit', false); }"
                                        class="{{ $rvInput }} pr-16 border-emerald-500 ring-1 ring-emerald-500 focus:border-emerald-600 focus:ring-emerald-600"
                                    >
                                    <div class="absolute right-1.5 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                        {{-- Save Checkmark Button --}}
                                        <button
                                            type="button"
                                            id="ev-inline-type-save-btn"
                                            onclick="saveInlineVehicleType('edit')"
                                            title="Save type"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#005a36] text-white hover:bg-[#00432a] transition shadow-xs cursor-pointer"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"/>
                                            </svg>
                                        </button>
                                        {{-- Cancel Button --}}
                                        <button
                                            type="button"
                                            onclick="toggleInlineAddVehicleType('edit', false)"
                                            title="Cancel"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <p id="ev-inline-type-error" class="hidden mt-1 text-[11px] font-semibold text-red-600"></p>
                            </div>

                            @if ($evIsOld)
                                @error('vehicle_type')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        {{-- Fuel Type --}}
                        <div class="sm:col-span-3">
                            <label for="ev-fuel_type" class="{{ $rvLabel }}">Fuel Type <span class="text-red-500">*</span></label>
                            <input type="text" name="fuel_type" id="ev-fuel_type" value="{{ $evOld('fuel_type') }}" maxlength="50" placeholder="e.g. Gasoline, Diesel" class="{{ $rvInput }} {{ $evBorder('fuel_type') }}" required>
                            @if ($evIsOld)
                                @error('fuel_type')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        {{-- Assigned Location (Super Admin editable, others read-only) --}}
                        <div class="sm:col-span-6">
                            <label for="ev-location" class="{{ $rvLabel }}">Assigned Location / Province @if($isSuperAdmin)<span class="text-red-500">*</span>@endif</label>
                            <div class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <select
                                    name="location"
                                    id="ev-location"
                                    @if($isSuperAdmin) required @else disabled @endif
                                    class="{{ $rvSelect }} {{ $evBorder('location') }} pl-10 {{ $isSuperAdmin ? '' : '!cursor-not-allowed !bg-slate-50 !text-slate-500' }}"
                                >
                                    <option value="">Select location</option>
                                    @foreach($locationOptions as $loc)
                                        <option value="{{ $loc }}" @selected($evOld('location') === $loc)>{{ $loc }}</option>
                                    @endforeach
                                </select>
                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            @if(!$isSuperAdmin)
                                <input type="hidden" name="location" id="ev-location-hidden">
                            @endif
                            <p class="mt-1 text-xs text-slate-400">
                                @if($isSuperAdmin)
                                    Requesters will only see this vehicle when selecting this location.
                                @else
                                    Only a Super Admin can change the assigned location.
                                @endif
                            </p>
                            @if ($evIsOld)
                                @error('location')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        {{-- Record info (read-only) --}}
                        <div class="sm:col-span-12">
                            <dl class="grid grid-cols-1 gap-4 rounded-xl bg-slate-50 px-4 py-4 sm:grid-cols-2 sm:gap-0 sm:divide-x sm:divide-slate-200">
                                <div class="space-y-3 sm:pr-4">
                                    <div class="flex items-start gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                                        <div class="min-w-0">
                                            <dt class="text-[11px] text-slate-500">Date Added</dt>
                                            <dd id="ev-info-created" class="text-[13px] font-medium text-slate-800">—</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <div class="min-w-0">
                                            <dt class="text-[11px] text-slate-500">Added By</dt>
                                            <dd id="ev-info-created-by" class="text-[13px] font-medium text-slate-800">—</dd>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3 sm:pl-4">
                                    <div class="flex items-start gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                        <div class="min-w-0">
                                            <dt class="text-[11px] text-slate-500">Last Updated</dt>
                                            <dd id="ev-info-updated" class="text-[13px] font-medium text-slate-800">—</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <div class="min-w-0">
                                            <dt class="text-[11px] text-slate-500">Updated By</dt>
                                            <dd id="ev-info-updated-by" class="text-[13px] font-medium text-slate-800">—</dd>
                                        </div>
                                    </div>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex shrink-0 items-center gap-3 border-t border-slate-100 bg-white px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:justify-end sm:px-6 sm:pb-4">
                    <button type="button" onclick="closeEditVehicleModal()" class="inline-flex h-11 flex-1 items-center justify-center rounded-lg border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 cursor-pointer sm:flex-none">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-[#005a36] px-5 text-sm font-bold text-white shadow-sm transition hover:bg-[#00432a] cursor-pointer sm:flex-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                        <span>Update Vehicle</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any() && old('_vehicle_form') === 'edit')
        <script>
            window.hasEditVehicleErrors = true;
            document.addEventListener('DOMContentLoaded', () => openEditVehicleModal(null));
        </script>
    @else
        <script>
            window.hasEditVehicleErrors = false;
        </script>
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
        .vehicle-view-toggle-btn { color: #94a3b8; }
        .vehicle-view-toggle-btn.active { background: #005a36; border-color: #005a36; color: #fff; }

        .vehicle-category-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            height: 2.75rem;
            padding: 0 1rem;
            border-width: 1px;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            cursor: pointer;
            transition: background-color .15s, border-color .15s, color .15s;
            border-color: #e2e8f0;
            background: #fff;
            color: #475569;
        }
        .vehicle-category-chip-add {
            border-style: dashed;
            border-color: #94a3b8;
            background: transparent;
            color: #005a36;
        }
        .vehicle-category-chip.vehicle-category-chip-add:hover { border-color: #005a36; background: #f0fdf4; }
        .vehicle-category-chip:hover { background: #f8fafc; }
        .vehicle-category-chip.active {
            background: #005a36;
            border-color: #005a36;
            color: #fff;
        }
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
            applyVehicleCategoryFilter(currentVehicleCategoryFilter());
        });

        /* ---------------- Category chip filter ---------------- */

        function currentVehicleCategoryFilter() {
            return sessionStorage.getItem('vehicle_category_filter') || 'all';
        }

        function setVehicleCategoryFilter(category) {
            sessionStorage.setItem('vehicle_category_filter', category);
            applyVehicleCategoryFilter(category);
        }

        function applyVehicleCategoryFilter(category) {
            document.querySelectorAll('.vehicle-category-chip').forEach((chip) => {
                chip.classList.toggle('active', chip.dataset.categoryFilter === category);
            });

            const cards = document.querySelectorAll('#vehicle-view-grid .vehicle-card');
            const rows = document.querySelectorAll('#vehicle-view-list .vehicle-row');
            let visibleCount = 0;

            const applyTo = (el) => {
                const matches = category === 'all' || el.dataset.category === category;
                el.classList.toggle('hidden', !matches);
                if (matches) visibleCount++;
            };

            cards.forEach(applyTo);
            rows.forEach(applyTo);

            const emptyState = document.getElementById('vehicle-empty-filter-state');
            if (emptyState) {
                emptyState.classList.toggle('hidden', visibleCount !== 0 || (cards.length === 0 && rows.length === 0));
            }
        }

        /* ---------------- Card kebab actions menu ---------------- */

        function closeAllVehicleActionsMenus() {
            document.querySelectorAll('.vehicle-actions-menu').forEach((menu) => menu.classList.add('hidden'));
        }

        function toggleVehicleActionsMenu(menuId) {
            const menu = document.getElementById(menuId);
            if (!menu) return;
            const isOpen = !menu.classList.contains('hidden');
            closeAllVehicleActionsMenus();
            if (!isOpen) menu.classList.remove('hidden');
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.vehicle-actions-menu') && !e.target.closest('[id^="va-menu-btn-"]')) {
                closeAllVehicleActionsMenus();
            }
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

            const yearLabel = btn.dataset.year ? ' (' + btn.dataset.year + ')' : '';
            document.getElementById('view-vehicle-title').textContent = (btn.dataset.brand || '') + ' ' + (btn.dataset.model || '') + yearLabel;
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

        /* ---------------- Edit (Update) Vehicle modal ---------------- */

        // Select a value case-insensitively. Older records may hold a value that isn't in the
        // list (e.g. "Sedan", "diesel"), so keep it selectable instead of silently wiping it.
        function setEditSelectValue(id, value) {
            const select = document.getElementById(id);
            select.querySelectorAll('option[data-legacy]').forEach((o) => o.remove());

            if (value) {
                const match = Array.from(select.options).find((o) => o.value.toLowerCase() === value.toLowerCase());
                if (match) {
                    select.value = match.value;
                    return;
                }
                const legacy = new Option(value, value, true, true);
                legacy.dataset.legacy = '1';
                select.add(legacy);
            }
            select.value = value;
        }

        // Brand: dropdown with an "Other…" free-text fallback; hidden #ev-brand carries the value.
        function syncEditBrand() {
            const select = document.getElementById('ev-brand-select');
            const other = document.getElementById('ev-brand-other');
            const wrap = document.getElementById('ev-brand-other-wrap');
            const hidden = document.getElementById('ev-brand');

            const isOther = select.value === '__other';
            wrap.classList.toggle('hidden', !isOther);
            other.required = isOther;
            hidden.value = isOther ? other.value.trim() : select.value;
        }

        function setEditBrandValue(value) {
            const select = document.getElementById('ev-brand-select');
            const other = document.getElementById('ev-brand-other');
            const match = Array.from(select.options).find((o) => o.value && o.value !== '__other' && o.value.toLowerCase() === value.toLowerCase());

            if (match) {
                select.value = match.value;
                other.value = '';
            } else if (value) {
                select.value = '__other';
                other.value = value;
            } else {
                select.value = '';
                other.value = '';
            }
            syncEditBrand();
        }

        function setEditPhotoState(src) {
            const img = document.getElementById('ev-photo-preview-img');
            const icon = document.getElementById('ev-photo-preview-icon');
            const removeBtn = document.getElementById('ev-photo-remove-btn');
            const cta = document.getElementById('ev-photo-cta');

            if (src) {
                img.src = src;
                img.classList.remove('hidden');
                icon.classList.add('hidden');
                removeBtn.classList.remove('hidden');
                removeBtn.classList.add('flex');
                cta.textContent = 'Change Photo';
            } else {
                img.src = '';
                img.classList.add('hidden');
                icon.classList.remove('hidden');
                removeBtn.classList.add('hidden');
                removeBtn.classList.remove('flex');
                cta.textContent = 'Upload Photo';
            }
        }

        function setEditPhotoError(message) {
            const el = document.getElementById('ev-photo-error');
            el.textContent = message || '';
            el.classList.toggle('hidden', !message);
        }

        function openEditVehicleModal(btn) {
            const modal = document.getElementById('edit-vehicle-modal');
            const form = document.getElementById('edit-vehicle-form');

            document.getElementById('ev-photo').value = '';
            document.getElementById('ev-remove-photo').value = '0';
            setEditPhotoError('');

            if (btn) {
                form.action = btn.dataset.updateUrl;
                document.getElementById('edit-vehicle-action-field').value = btn.dataset.updateUrl;
                document.getElementById('edit-vehicle-subtitle').textContent = 'Edit the details of ' + btn.dataset.plate_number + ' and click Save to apply changes.';

                document.getElementById('ev-plate_number').value = btn.dataset.plate_number;
                document.getElementById('ev-chassis_number').value = btn.dataset.chassis_number;
                document.getElementById('ev-engine_number').value = btn.dataset.engine_number;
                document.getElementById('ev-brand').value = btn.dataset.brand || '';
                document.getElementById('ev-model').value = btn.dataset.model;
                document.getElementById('ev-year').value = btn.dataset.year;
                document.getElementById('ev-color').value = btn.dataset.color;
                document.getElementById('ev-odometer').value = btn.dataset.odometer;
                document.getElementById('ev-change_oil_interval_km').value = btn.dataset.change_oil_interval_km || 5000;
                setEditSelectValue('ev-vehicle_type', btn.dataset.vehicle_type || '');
                document.getElementById('ev-fuel_type').value = btn.dataset.fuel_type || '';

                const locVal = btn.dataset.location || '';
                document.getElementById('ev-location').value = locVal;
                const evLocHidden = document.getElementById('ev-location-hidden');
                if (evLocHidden) {
                    evLocHidden.value = locVal;
                }

                setEditPhotoState(btn.dataset.photo || '');

                const info = (id, value) => { document.getElementById(id).textContent = value || '—'; };
                info('ev-info-created', btn.dataset.created);
                info('ev-info-created-by', btn.dataset.createdBy);
                info('ev-info-updated', btn.dataset.updated);
                info('ev-info-updated-by', btn.dataset.updatedBy);
            } else {
                // Re-opened automatically after a validation error: values were rendered server-side.
                syncEditBrand();
                setEditPhotoState('');
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeEditVehicleModal() {
            if (window.hasEditVehicleErrors) {
                window.location.href = window.location.pathname + window.location.search;
                return;
            }

            const modal = document.getElementById('edit-vehicle-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');

            const form = document.getElementById('edit-vehicle-form');
            if (form) {
                form.reset();
            }
            modal.querySelectorAll('.text-red-600').forEach(el => el.remove());
            modal.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                el.classList.add('border-slate-200');
            });
        }

        function handleEditVehiclePhotoChange(input) {
            const file = input.files && input.files[0];
            setEditPhotoError('');
            if (!file) return;

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                input.value = '';
                setEditPhotoError('Please choose a JPG, PNG or WEBP image.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                input.value = '';
                setEditPhotoError('That image is larger than 5MB. Please choose a smaller one.');
                return;
            }

            document.getElementById('ev-remove-photo').value = '0';

            const reader = new FileReader();
            reader.onload = (e) => setEditPhotoState(e.target.result);
            reader.readAsDataURL(file);
        }

        function removeEditVehiclePhoto() {
            document.getElementById('ev-photo').value = '';
            document.getElementById('ev-remove-photo').value = '1';
            setEditPhotoError('');
            setEditPhotoState('');
        }

        function handleEditPhotoDrag(event, active) {
            event.preventDefault();
            const zone = document.getElementById('ev-photo-dropzone');
            zone.classList.toggle('border-emerald-500', active);
            zone.classList.toggle('bg-emerald-50/40', active);
        }

        function handleEditPhotoDrop(event) {
            event.preventDefault();
            handleEditPhotoDrag(event, false);

            const file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
            if (!file) return;

            const input = document.getElementById('ev-photo');
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            handleEditVehiclePhotoChange(input);
        }

        function handleEditVehicleSubmit(event) {
            syncEditBrand();
            return event.target.reportValidity();
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
                        applyVehicleCategoryFilter(currentVehicleCategoryFilter());
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
                        applyVehicleCategoryFilter(currentVehicleCategoryFilter());
                    }
                    window.history.pushState({}, '', urlStr);
                })
                .catch(err => console.error('Vehicle fetch error:', err))
                .finally(() => spinner?.classList.add('hidden'));
        }
    </script>

    <script>
        const RV_MAX_PHOTO_BYTES = 5 * 1024 * 1024;
        const RV_PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

        function showRegisterStep(step) {
            document.getElementById('register-vehicle-step-form').style.display = step === 'form' ? 'flex' : 'none';
            document.getElementById('register-vehicle-step-confirm').style.display = step === 'confirm' ? 'flex' : 'none';
        }

        function openRegisterVehicleModal() {
            const modal = document.getElementById('register-vehicle-modal');
            showRegisterStep('form');
            syncRegisterBrand();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeRegisterVehicleModal() {
            if (window.hasRegisterVehicleErrors) {
                window.location.href = window.location.pathname + window.location.search;
                return;
            }

            const modal = document.getElementById('register-vehicle-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');

            const form = document.getElementById('register-vehicle-form');
            if (form) {
                form.reset();
            }
            if (typeof clearRegisterPhoto === 'function') {
                clearRegisterPhoto();
            }
            if (typeof syncRegisterBrand === 'function') {
                syncRegisterBrand();
            }
            if (typeof showRegisterStep === 'function') {
                showRegisterStep('form');
            }

            modal.querySelectorAll('.text-red-600').forEach(el => el.remove());
            modal.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                el.classList.add('border-slate-200');
            });
        }

        // Brand: dropdown with an "Other…" free-text fallback. The hidden #rv-brand input carries the final value.
        function syncRegisterBrand() {
            const select = document.getElementById('rv-brand-select');
            const other = document.getElementById('rv-brand-other');
            const wrap = document.getElementById('rv-brand-other-wrap');
            const hidden = document.getElementById('rv-brand');
            if (!select || !other || !wrap || !hidden) return;

            const isOther = select.value === '__other';
            wrap.classList.toggle('hidden', !isOther);
            other.required = isOther;
            hidden.value = isOther ? other.value.trim() : select.value;
        }

        function setRegisterPhotoError(message) {
            const el = document.getElementById('rv-photo-error');
            el.textContent = message || '';
            el.classList.toggle('hidden', !message);
        }

        function resetRegisterPhotoPreview() {
            document.getElementById('rv-photo-preview-img').src = '';
            document.getElementById('rv-photo-filename').textContent = '';
            document.getElementById('rv-photo-preview').classList.add('hidden');
        }

        function handleVehiclePhotoChange(input) {
            const file = input.files && input.files[0];
            setRegisterPhotoError('');

            if (!file) {
                resetRegisterPhotoPreview();
                return;
            }

            if (!RV_PHOTO_TYPES.includes(file.type)) {
                input.value = '';
                resetRegisterPhotoPreview();
                setRegisterPhotoError('Please choose a JPG, PNG or WEBP image.');
                return;
            }

            if (file.size > RV_MAX_PHOTO_BYTES) {
                input.value = '';
                resetRegisterPhotoPreview();
                setRegisterPhotoError('That image is larger than 5MB. Please choose a smaller one.');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('rv-photo-preview-img').src = e.target.result;
                document.getElementById('rv-photo-filename').textContent = file.name;
                document.getElementById('rv-photo-preview').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        function clearRegisterPhoto() {
            document.getElementById('rv-photo').value = '';
            setRegisterPhotoError('');
            resetRegisterPhotoPreview();
        }

        function handleRegisterPhotoDrag(event, active) {
            event.preventDefault();
            const zone = document.getElementById('rv-photo-dropzone');
            zone.classList.toggle('border-emerald-500', active);
            zone.classList.toggle('bg-emerald-50/40', active);
        }

        function handleRegisterPhotoDrop(event) {
            event.preventDefault();
            handleRegisterPhotoDrag(event, false);

            const file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
            if (!file) return;

            const input = document.getElementById('rv-photo');
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            handleVehiclePhotoChange(input);
        }

        function handleRegisterVehicleSubmit(event) {
            event.preventDefault();
            const form = document.getElementById('register-vehicle-form');

            if (!form.reportValidity()) {
                return false;
            }

            const value = (id) => document.getElementById(id).value;
            const setConfirm = (key, text) => {
                document.getElementById('rv-confirm-' + key).textContent = text || 'None';
            };

            const photoFile = document.getElementById('rv-photo').files[0];

            setConfirm('plate_number', value('rv-plate_number'));
            setConfirm('photo', photoFile ? photoFile.name : '');
            setConfirm('chassis_number', value('rv-chassis_number'));
            setConfirm('engine_number', value('rv-engine_number'));
            setConfirm('brand', value('rv-brand'));
            setConfirm('model', value('rv-model'));
            setConfirm('year', value('rv-year'));
            setConfirm('color', value('rv-color'));
            setConfirm('odometer', value('rv-odometer') + ' km');
            setConfirm('vehicle_type', value('rv-vehicle_type'));
            setConfirm('fuel_type', value('rv-fuel_type'));
            setConfirm('location', value('rv-location'));

            showRegisterStep('confirm');
            return false;
        }

        function backToRegisterVehicleForm() {
            showRegisterStep('form');
        }

        function confirmRegisterVehicle() {
            document.getElementById('register-vehicle-form').submit();
        }

        document.addEventListener('DOMContentLoaded', syncRegisterBrand);
    </script>
    {{-- VEHICLE INSPECTION CHECKLIST MODAL --}}
    <div id="vehicle-inspection-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-2xl border border-slate-200 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700 border border-blue-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-black text-slate-900" id="insp-modal-title">Vehicle Inspection Checklist</h3>
                        <p class="text-xs text-slate-500" id="insp-modal-subtitle">Plate: —</p>
                    </div>
                </div>
                <button type="button" onclick="closeInspectionModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <form id="vehicle-inspection-form" onsubmit="submitInspectionForm(event)" class="flex flex-col min-h-0 flex-1">
                @csrf
                <div class="overflow-y-auto py-4 space-y-5 flex-1 pr-1 scrollbar-thin">
                    {{-- Odometer & Change Oil Section --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-3">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Current Odometer Reading (KM) <span class="text-red-500">*</span></label>
                                <input type="number" name="odometer_km" id="insp-odometer" min="0" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <p class="text-[11px] text-slate-400 mt-1" id="insp-odometer-hint">Last recorded: — km</p>
                            </div>

                            <div class="flex items-center pt-2">
                                <label class="flex items-center gap-2.5 cursor-pointer bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 w-full hover:border-emerald-300 transition">
                                    <input type="checkbox" name="change_oil_done" id="insp-change-oil" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">🛢️ Mark Change Oil as Done</span>
                                        <span class="text-[10px] text-slate-400 block">Resets 5,000 km change oil counter to current odometer</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Checklist Items Grouped by Category --}}
                    @php
                        $checklistCategories = [
                            'Oils & Fluids' => [
                                'Engine Oil Level & Condition',
                                'Brake Fluid Level',
                                'Transmission Fluid Level',
                                'Radiator Coolant Level',
                                'Windshield Washer Fluid',
                            ],
                            'Brakes & Steering' => [
                                'Service Brake Operation',
                                'Parking / Hand Brake',
                                'Power Steering Fluid / Steering Play',
                            ],
                            'Tires & Wheels' => [
                                'Front & Rear Tire Pressure',
                                'Tire Tread Condition',
                                'Spare Tire Condition & Air',
                                'Jack & Lug Wrench Tools',
                            ],
                            'Electrical & Lights' => [
                                'Headlights (Low & High Beam)',
                                'Turn Signal Lights & Hazard Warning',
                                'Brake Lights & Reverse Lights',
                                'Horn Operation',
                                'Battery Terminals & Charge Status',
                            ],
                            'Body & Cabin' => [
                                'Windshield Wipers & Washers',
                                'Side & Rearview Mirrors',
                                'Seatbelts Functionality',
                                'Air Conditioning Performance',
                                'Cleanliness & Body Condition',
                            ],
                        ];
                        $itemIndex = 0;
                    @endphp

                    @foreach ($checklistCategories as $catName => $catItems)
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 pb-2 border-b border-slate-100 mb-3">{{ $catName }}</h4>
                            <div class="space-y-3">
                                @foreach ($catItems as $itemName)
                                    @php $idx = $itemIndex++; @endphp
                                    <input type="hidden" name="items[{{ $idx }}][category]" value="{{ $catName }}">
                                    <input type="hidden" name="items[{{ $idx }}][name]" value="{{ $itemName }}">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                        <span class="font-semibold text-slate-800">{{ $itemName }}</span>

                                        <div class="flex items-center gap-3 shrink-0">
                                            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg">
                                                <label class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[11px] font-bold cursor-pointer transition has-[:checked]:bg-emerald-600 has-[:checked]:text-white text-slate-600">
                                                    <input type="radio" name="items[{{ $idx }}][status]" value="pass" checked class="hidden">
                                                    <span>✓ Pass</span>
                                                </label>
                                                <label class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[11px] font-bold cursor-pointer transition has-[:checked]:bg-amber-500 has-[:checked]:text-white text-slate-600">
                                                    <input type="radio" name="items[{{ $idx }}][status]" value="attention" class="hidden">
                                                    <span>⚠️ Attention</span>
                                                </label>
                                                <label class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[11px] font-bold cursor-pointer transition has-[:checked]:bg-red-600 has-[:checked]:text-white text-slate-600">
                                                    <input type="radio" name="items[{{ $idx }}][status]" value="fail" class="hidden">
                                                    <span>✕ Fail</span>
                                                </label>
                                            </div>

                                            <input type="text" name="items[{{ $idx }}][remarks]" placeholder="Remarks (opt)" class="w-36 rounded-md border border-slate-200 px-2 py-1 text-[11px] text-slate-700 focus:border-blue-500 focus:outline-none">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">General Notes / Remarks</label>
                        <textarea name="notes" rows="2" placeholder="Optional inspector notes..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-blue-500 focus:outline-none"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 shrink-0">
                    <button type="button" onclick="closeInspectionModal()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="insp-submit-btn" class="rounded-xl bg-blue-700 px-5 py-2 text-xs font-bold text-white hover:bg-blue-800 transition cursor-pointer">
                        Save Inspection Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- INSPECTION HISTORY MODAL --}}
    <div id="vehicle-inspection-history-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl border border-slate-200 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700 border border-slate-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-black text-slate-900" id="hist-modal-title">Inspection Logs</h3>
                        <p class="text-xs text-slate-500" id="hist-modal-subtitle">Plate: —</p>
                    </div>
                </div>
                <button type="button" onclick="closeInspectionHistoryModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div id="hist-modal-content" class="overflow-y-auto py-4 space-y-3 flex-1 pr-1 scrollbar-thin">
                <div class="text-center py-8 text-slate-400 text-xs">Loading inspection logs...</div>
            </div>
        </div>
    </div>

    <script>
        function openAddVehicleTypeModal() {
            const modal = document.getElementById('add-vehicle-type-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            document.getElementById('avt-name')?.focus();
        }

        function closeAddVehicleTypeModal() {
            const modal = document.getElementById('add-vehicle-type-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
        }

        function handleVehicleTypeSelectChange(select, context) {
            if (select.value === '__add_new') {
                toggleInlineAddVehicleType(context, true);
            }
        }

        function toggleInlineAddVehicleType(context, forceOpen) {
            const isRegister = context === 'register';
            const wrap = document.getElementById(isRegister ? 'rv-type-select-wrap' : 'ev-type-select-wrap');
            const panel = document.getElementById(isRegister ? 'rv-inline-add-type' : 'ev-inline-add-type');
            const select = document.getElementById(isRegister ? 'rv-vehicle_type' : 'ev-vehicle_type');
            if (!wrap || !panel) return;

            const shouldOpen = forceOpen !== undefined ? forceOpen : panel.classList.contains('hidden');
            if (shouldOpen) {
                wrap.classList.add('hidden');
                panel.classList.remove('hidden');
                const nameInput = document.getElementById(isRegister ? 'rv-inline-type-name' : 'ev-inline-type-name');
                setTimeout(() => nameInput?.focus(), 50);
            } else {
                panel.classList.add('hidden');
                wrap.classList.remove('hidden');
                if (select && select.value === '__add_new') {
                    select.value = '';
                }
                const errorEl = document.getElementById(isRegister ? 'rv-inline-type-error' : 'ev-inline-type-error');
                if (errorEl) errorEl.classList.add('hidden');
                const nameInput = document.getElementById(isRegister ? 'rv-inline-type-name' : 'ev-inline-type-name');
                if (nameInput) nameInput.value = '';
            }
        }

        async function saveInlineVehicleType(context) {
            const isRegister = context === 'register';
            const nameInput = document.getElementById(isRegister ? 'rv-inline-type-name' : 'ev-inline-type-name');
            const errorEl = document.getElementById(isRegister ? 'rv-inline-type-error' : 'ev-inline-type-error');
            const saveBtn = document.getElementById(isRegister ? 'rv-inline-type-save-btn' : 'ev-inline-type-save-btn');

            const name = nameInput?.value.trim();

            if (!name) {
                if (errorEl) {
                    errorEl.textContent = 'Please enter a vehicle type name.';
                    errorEl.classList.remove('hidden');
                }
                nameInput?.focus();
                return;
            }

            if (errorEl) errorEl.classList.add('hidden');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.dataset.origHtml = saveBtn.innerHTML;
                saveBtn.innerHTML = '<svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>';
            }

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || document.querySelector('input[name="_token"]')?.value;

                const response = await fetch("{{ route('vehicles.types.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ name, icon: 'car' })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Failed to add vehicle type.');
                    if (errorEl) {
                        errorEl.textContent = msg;
                        errorEl.classList.remove('hidden');
                    }
                    return;
                }

                const newTypeName = data.type.name;

                // Sync new type into both dropdowns (Register & Edit)
                ['rv-vehicle_type', 'ev-vehicle_type'].forEach(selectId => {
                    const select = document.getElementById(selectId);
                    if (!select) return;
                    let optionExists = false;
                    for (let i = 0; i < select.options.length; i++) {
                        if (select.options[i].value === newTypeName) {
                            optionExists = true;
                            break;
                        }
                    }
                    if (!optionExists) {
                        const opt = document.createElement('option');
                        opt.value = newTypeName;
                        opt.textContent = newTypeName;
                        const addNewOpt = select.querySelector('option[value="__add_new"]');
                        if (addNewOpt) {
                            select.insertBefore(opt, addNewOpt);
                        } else {
                            select.appendChild(opt);
                        }
                    }
                });

                // Select the new type in the active dropdown
                const activeSelect = document.getElementById(isRegister ? 'rv-vehicle_type' : 'ev-vehicle_type');
                if (activeSelect) {
                    activeSelect.value = newTypeName;
                    activeSelect.dispatchEvent(new Event('change'));
                }

                // Switch back to dropdown mode with the new type selected
                toggleInlineAddVehicleType(context, false);

            } catch (err) {
                console.error('Error adding vehicle type:', err);
                if (errorEl) {
                    errorEl.textContent = 'A network error occurred. Please try again.';
                    errorEl.classList.remove('hidden');
                }
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    if (saveBtn.dataset.origHtml) {
                        saveBtn.innerHTML = saveBtn.dataset.origHtml;
                    }
                }
            }
        }

        let currentInspectionStoreUrl = '';

        function openInspectionModal(btn) {
            currentInspectionStoreUrl = btn.dataset.storeUrl;
            const plate = btn.dataset.plate_number;
            const name = btn.dataset.vehicleName;
            const odometer = btn.dataset.odometer || 0;

            document.getElementById('insp-modal-title').textContent = `Inspect Vehicle: ${plate}`;
            document.getElementById('insp-modal-subtitle').textContent = `${name}`;
            document.getElementById('insp-odometer').value = odometer;
            document.getElementById('insp-odometer-hint').textContent = `Last recorded: ${Number(odometer).toLocaleString()} km`;
            document.getElementById('insp-change-oil').checked = false;

            const modal = document.getElementById('vehicle-inspection-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeInspectionModal() {
            const modal = document.getElementById('vehicle-inspection-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function submitInspectionForm(e) {
            e.preventDefault();
            if (!currentInspectionStoreUrl) return;

            const form = document.getElementById('vehicle-inspection-form');
            const submitBtn = document.getElementById('insp-submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving Report...';

            const formData = new FormData(form);

            fetch(currentInspectionStoreUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Inspection Report';
                if (data.success) {
                    closeInspectionModal();
                    window.location.reload();
                } else {
                    alert(data.message || 'Error saving inspection.');
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Inspection Report';
                console.error(err);
                alert('An error occurred while saving the inspection.');
            });
        }

        function openInspectionHistoryModal(btn) {
            const historyUrl = btn.dataset.historyUrl;
            const plate = btn.dataset.plate_number;

            document.getElementById('hist-modal-title').textContent = `Inspection Logs (${plate})`;
            document.getElementById('hist-modal-content').innerHTML = '<div class="text-center py-8 text-slate-400 text-xs">Loading inspection logs...</div>';

            const modal = document.getElementById('vehicle-inspection-history-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            fetch(historyUrl, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                const list = data.inspections || [];
                const container = document.getElementById('hist-modal-content');

                if (list.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-10 text-slate-400">
                            <p class="text-xs font-semibold">No inspection records found</p>
                            <p class="text-[11px] text-slate-400">Click "Inspect" on the vehicle row to conduct the first inspection.</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                list.forEach(item => {
                    const statusBadge = item.overall_status === 'failed'
                        ? '<span class="rounded-full bg-red-50 text-red-700 border border-red-200 px-2 py-0.5 text-[10px] font-bold">Failed</span>'
                        : (item.overall_status === 'needs_attention'
                            ? '<span class="rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 text-[10px] font-bold">Needs Attention</span>'
                            : '<span class="rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 text-[10px] font-bold">Passed</span>');

                    const dateStr = new Date(item.inspected_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                    const inspectorName = item.inspector?.name || 'Admin';

                    let itemsHtml = '';
                    (item.items || []).forEach(it => {
                        const icon = it.status === 'pass' ? '✓' : (it.status === 'attention' ? '⚠️' : '✕');
                        const color = it.status === 'pass' ? 'text-emerald-600' : (it.status === 'attention' ? 'text-amber-600' : 'text-red-600 font-bold');
                        itemsHtml += `
                            <div class="flex items-center justify-between py-1 border-b border-slate-50 text-[11px]">
                                <span class="text-slate-700">${it.item_name} <span class="text-[10px] text-slate-400">(${it.category})</span></span>
                                <span class="${color}">${icon} ${it.status.toUpperCase()} ${it.remarks ? '— ' + it.remarks : ''}</span>
                            </div>
                        `;
                    });

                    html += `
                        <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-2 text-xs">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <div>
                                    <span class="font-bold text-slate-900">${dateStr}</span>
                                    <span class="text-[11px] text-slate-400 ml-2">by ${inspectorName}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    ${item.change_oil_done ? '<span class="rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 text-[10px] font-bold">🛢️ Oil Changed</span>' : ''}
                                    ${statusBadge}
                                </div>
                            </div>
                            <div class="text-[11px] text-slate-600">
                                <strong>Odometer:</strong> ${Number(item.odometer_km).toLocaleString()} km
                                ${item.notes ? `<div class="mt-1 text-slate-500 italic">"${item.notes}"</div>` : ''}
                            </div>
                            <div class="pt-2">
                                <details class="cursor-pointer">
                                    <summary class="text-[11px] font-bold text-blue-600 hover:underline">View Checklist Breakdown (${(item.items || []).length} items)</summary>
                                    <div class="mt-2 space-y-0.5 max-h-48 overflow-y-auto pr-1">
                                        ${itemsHtml}
                                    </div>
                                </details>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
            })
            .catch(err => {
                console.error(err);
                document.getElementById('hist-modal-content').innerHTML = '<div class="text-center py-8 text-red-500 text-xs">Failed to load inspection history.</div>';
            });
        }

        function closeInspectionHistoryModal() {
            const modal = document.getElementById('vehicle-inspection-history-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // ══════════════════════════════════════════════════════════════════════
        // PMS CHECKLIST & SCHEDULE MODAL (CARMIND-STYLE)
        // ══════════════════════════════════════════════════════════════════════
        let currentPmsVehicleId = null;
        let currentPmsVehicleOdo = 0;

        function openVehiclePmsModal(vehicleId, plate, label, odo) {
            currentPmsVehicleId = vehicleId;
            currentPmsVehicleOdo = odo || 0;

            document.getElementById('pms-modal-plate').textContent = plate;
            document.getElementById('pms-modal-label').textContent = label;
            document.getElementById('pms-modal-odo').textContent = Number(odo).toLocaleString() + ' km';
            document.getElementById('pms-new-item-form').classList.add('hidden');

            const modal = document.getElementById('vehicle-pms-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            loadVehiclePmsItems(vehicleId);
        }

        function closeVehiclePmsModal() {
            const modal = document.getElementById('vehicle-pms-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            currentPmsVehicleId = null;
        }

        function togglePmsNewItemForm() {
            const f = document.getElementById('pms-new-item-form');
            f.classList.toggle('hidden');
            if (!f.classList.contains('hidden')) {
                document.getElementById('pms-item-name').focus();
            }
        }

        function loadVehiclePmsItems(vehicleId) {
            const listEl = document.getElementById('pms-items-list');
            listEl.innerHTML = '<div class="text-center py-8 text-xs text-slate-400">Loading PMS items...</div>';

            fetch(`/vehicles/${vehicleId}/pms-items`, { credentials: 'same-origin' })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) throw new Error('Failed to load items');

                    // Update Health Score summary badge inside modal
                    const hs = data.vehicle.health_score || { score: 100, label: 'Good', color: 'emerald' };
                    const hsBadge = document.getElementById('pms-modal-health');
                    hsBadge.textContent = `${hs.score}% ${hs.label}`;
                    hsBadge.className = `rounded-full px-2.5 py-0.5 text-xs font-bold ${
                        hs.color === 'emerald' ? 'bg-emerald-100 text-emerald-800' :
                        hs.color === 'amber' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'
                    }`;

                    const items = data.items || [];
                    if (items.length === 0) {
                        listEl.innerHTML = `
                            <div class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                <p class="text-xs font-semibold text-slate-600">No PMS items configured yet.</p>
                                <p class="text-[11px] text-slate-400 mt-1">Add items like "Engine Oil & Filter", "Air Filter", "Brake Fluid", or "Coolant".</p>
                                <button type="button" onclick="togglePmsNewItemForm()" class="mt-3 text-xs font-bold text-emerald-700 hover:underline cursor-pointer">+ Add First Item</button>
                            </div>
                        `;
                        return;
                    }

                    // Group items by category (replace vs inspect vs lubricate vs other)
                    const groups = {
                        replace: { title: 'Replace', items: [] },
                        inspect: { title: 'Inspect & Check', items: [] },
                        lubricate: { title: 'Lubricate & Service', items: [] },
                        other: { title: 'Other Items', items: [] },
                    };

                    items.forEach(it => {
                        const cat = groups[it.category] ? it.category : 'other';
                        groups[cat].items.push(it);
                    });

                    let html = '';
                    Object.values(groups).forEach(g => {
                        if (g.items.length === 0) return;
                        html += `
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs font-extrabold uppercase tracking-wider text-slate-500 pt-2 border-b border-slate-100 pb-1">
                                    <span>${g.title} (${g.items.length})</span>
                                </div>
                                <div class="space-y-2">
                        `;

                        g.items.forEach(it => {
                            const statusColor = it.status === 'overdue' ? 'bg-red-50 border-red-200 text-red-700' :
                                                it.status === 'due_soon' ? 'bg-amber-50 border-amber-200 text-amber-800' :
                                                it.status === 'ok' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' :
                                                'bg-slate-50 border-slate-200 text-slate-600';

                            const statusDot = it.status === 'overdue' ? 'bg-red-500 animate-pulse' :
                                              it.status === 'due_soon' ? 'bg-amber-500' :
                                              it.status === 'ok' ? 'bg-emerald-500' : 'bg-slate-400';

                            // Interval rule text
                            const intervalParts = [];
                            if (it.interval_km) intervalParts.push(`every ${Number(it.interval_km).toLocaleString()} km`);
                            if (it.interval_months) intervalParts.push(`every ${it.interval_months} mos`);
                            const ruleText = intervalParts.join(' or ') || 'No interval set';

                            // Next due text
                            let nextDueText = '';
                            if (it.next_due_km) nextDueText += `Due at ${Number(it.next_due_km).toLocaleString()} km`;
                            if (it.next_due_date) {
                                const d = new Date(it.next_due_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
                                nextDueText += (nextDueText ? ' or ' : 'Due ') + d;
                            }

                            html += `
                                <div class="rounded-xl border border-slate-200 bg-white p-3 hover:border-slate-300 transition">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-extrabold border ${statusColor}">
                                                    <span class="h-1.5 w-1.5 rounded-full ${statusDot}"></span>
                                                    ${it.status_label}
                                                </span>
                                                <h4 class="text-xs font-bold text-slate-900 truncate">${it.item_name}</h4>
                                            </div>
                                            <p class="text-[11px] text-slate-500 mt-1">
                                                Rule: <strong class="text-slate-700">${ruleText}</strong>
                                                ${nextDueText ? ` • <span class="text-slate-700 font-semibold">${nextDueText}</span>` : ''}
                                            </p>
                                            ${it.last_done_km || it.last_done_date ? `
                                                <p class="text-[10px] text-slate-400 mt-0.5">
                                                    Last done: ${it.last_done_km ? Number(it.last_done_km).toLocaleString() + ' km' : ''} ${it.last_done_date ? 'on ' + it.last_done_date : ''}
                                                </p>
                                            ` : ''}
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <button
                                                type="button"
                                                onclick="logPmsDone(${it.id}, '${it.item_name.replace(/'/g, "\\'")}')"
                                                class="rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-emerald-700 transition cursor-pointer"
                                                title="Log this service as done now"
                                            >
                                                ✓ Done
                                            </button>
                                            <button
                                                type="button"
                                                onclick="deletePmsItem(${it.id})"
                                                class="rounded-lg p-1 text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer"
                                                title="Delete this item"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        html += `</div></div>`;
                    });

                    listEl.innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    listEl.innerHTML = '<div class="text-center py-8 text-xs text-red-500">Failed to load PMS items.</div>';
                });
        }

        function handleCreatePmsItem(event) {
            event.preventDefault();
            if (!currentPmsVehicleId) return;

            const name = document.getElementById('pms-item-name').value.trim();
            const category = document.getElementById('pms-item-category').value;
            const km = document.getElementById('pms-item-interval-km').value;
            const months = document.getElementById('pms-item-interval-months').value;

            if (!name) return;

            const payload = {
                item_name: name,
                category: category,
                interval_km: km ? parseInt(km) : null,
                interval_months: months ? parseInt(months) : null,
                last_done_km: currentPmsVehicleOdo || null,
                last_done_date: new Date().toISOString().split('T')[0],
            };

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/vehicles/${currentPmsVehicleId}/pms-items`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('pms-item-name').value = '';
                    document.getElementById('pms-item-interval-km').value = '';
                    document.getElementById('pms-item-interval-months').value = '';
                    document.getElementById('pms-new-item-form').classList.add('hidden');
                    loadVehiclePmsItems(currentPmsVehicleId);
                } else {
                    alert('Failed to add item: ' + (data.message || 'Validation error'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred while adding PMS item.');
            });
        }

        function logPmsDone(itemId, itemName) {
            if (!currentPmsVehicleId) return;
            const odoInput = prompt(`Service done for "${itemName}". Enter vehicle odometer reading (km):`, currentPmsVehicleOdo);
            if (odoInput === null) return; // user cancelled

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/vehicles/${currentPmsVehicleId}/pms-items/${itemId}/log-done`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    odometer_km: parseInt(odoInput) || currentPmsVehicleOdo,
                    done_date: new Date().toISOString().split('T')[0],
                }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadVehiclePmsItems(currentPmsVehicleId);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to log service.');
            });
        }

        function deletePmsItem(itemId) {
            if (!currentPmsVehicleId) return;
            if (!confirm('Are you sure you want to remove this PMS item?')) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/vehicles/${currentPmsVehicleId}/pms-items/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadVehiclePmsItems(currentPmsVehicleId);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to delete item.');
            });
        }

        // ══════════════════════════════════════════════════════════════════════
        // GLOBAL PMS RULES & TEMPLATES
        // ══════════════════════════════════════════════════════════════════════
        function openPmsRulesModal() {
            const modal = document.getElementById('pms-rules-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            loadPmsTemplates();
        }

        function closePmsRulesModal() {
            const modal = document.getElementById('pms-rules-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function loadPmsTemplates() {
            const listEl = document.getElementById('pms-templates-list');
            listEl.innerHTML = '<div class="text-center py-6 text-xs text-slate-400">Loading rules...</div>';

            fetch('/vehicles/pms-templates', { credentials: 'same-origin' })
                .then(res => res.json())
                .then(data => {
                    const templates = data.templates || [];
                    if (templates.length === 0) {
                        listEl.innerHTML = `
                            <div class="text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                <p class="text-xs font-semibold text-slate-600">No global PMS rules defined yet.</p>
                                <p class="text-[11px] text-slate-400 mt-1">Create rules like "Engine Oil Change every 5,000 km" above to auto-apply to vehicles.</p>
                            </div>
                        `;
                        return;
                    }

                    let html = '';
                    templates.forEach(tpl => {
                        const intervals = [];
                        if (tpl.interval_km) intervals.push(`every ${Number(tpl.interval_km).toLocaleString()} km`);
                        if (tpl.interval_months) intervals.push(`every ${tpl.interval_months} months`);
                        const intervalStr = intervals.join(' or ') || 'No interval';

                        const typesStr = tpl.applicable_types && tpl.applicable_types.length
                            ? tpl.applicable_types.join(', ')
                            : 'All Vehicle Types';

                        html += `
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3 shadow-2xs hover:border-amber-300 transition">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h5 class="text-xs font-bold text-slate-900">${tpl.item_name}</h5>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 uppercase">${tpl.category}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        <strong>Interval:</strong> <span class="text-amber-800 font-bold">${intervalStr}</span> •
                                        <strong>Applies to:</strong> <span class="text-slate-700">${typesStr}</span>
                                    </p>
                                </div>
                                <button type="button" onclick="deletePmsTemplate(${tpl.id})" class="rounded-lg p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer" title="Delete rule">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                </button>
                            </div>
                        `;
                    });

                    listEl.innerHTML = html;
                })
                .catch(err => {
                    console.error(err);
                    listEl.innerHTML = '<div class="text-center py-6 text-xs text-red-500">Failed to load rules.</div>';
                });
        }

        function handleCreatePmsTemplate(event) {
            event.preventDefault();

            const name = document.getElementById('tpl-item-name').value.trim();
            const category = document.getElementById('tpl-category').value;
            const km = document.getElementById('tpl-interval-km').value;
            const months = document.getElementById('tpl-interval-months').value;

            // Get checked types
            const checkboxes = document.querySelectorAll('input[name="tpl_types"]:checked');
            const types = Array.from(checkboxes).map(cb => cb.value);

            if (!name) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch('/vehicles/pms-templates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    item_name: name,
                    category: category,
                    interval_km: km ? parseInt(km) : null,
                    interval_months: months ? parseInt(months) : null,
                    applicable_types: types.length > 0 ? types : null,
                }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('tpl-item-name').value = '';
                    document.getElementById('tpl-interval-km').value = '';
                    document.getElementById('tpl-interval-months').value = '';
                    document.querySelectorAll('input[name="tpl_types"]').forEach(cb => cb.checked = false);
                    loadPmsTemplates();
                } else {
                    alert('Error: ' + (data.message || 'Validation error'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred.');
            });
        }

        function deletePmsTemplate(id) {
            if (!confirm('Are you sure you want to remove this PMS rule?')) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/vehicles/pms-templates/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadPmsTemplates();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Failed to delete rule.');
            });
        }

        // ══════════════════════════════════════════════════════════════════════
        // QUICK ODOMETER UPDATE MODAL (CARMIND-STYLE)
        // ══════════════════════════════════════════════════════════════════════
        let quickOdoVehicleId = null;

        function openQuickOdometerModal(vehicleId, plate, currentOdo) {
            quickOdoVehicleId = vehicleId;
            document.getElementById('quick-odo-plate').textContent = plate;
            document.getElementById('quick-odo-current').textContent = Number(currentOdo).toLocaleString() + ' km';
            const input = document.getElementById('quick-odo-input');
            input.value = currentOdo;
            input.min = currentOdo;

            const modal = document.getElementById('quick-odometer-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => input.focus(), 100);
        }

        function closeQuickOdometerModal() {
            const modal = document.getElementById('quick-odometer-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            quickOdoVehicleId = null;
        }

        function handleQuickOdometerSubmit(event) {
            event.preventDefault();
            if (!quickOdoVehicleId) return;

            const newOdo = parseInt(document.getElementById('quick-odo-input').value);
            if (isNaN(newOdo) || newOdo < 0) return;

            const btn = document.getElementById('quick-odo-submit-btn');
            const origText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Saving...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/vehicles/${quickOdoVehicleId}/odometer`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ odometer: newOdo }),
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = origText;

                if (data.success) {
                    // Update in-page displays without reloading
                    const listSpan = document.getElementById(`odo-display-${quickOdoVehicleId}`);
                    if (listSpan) listSpan.textContent = Number(newOdo).toLocaleString() + ' km';

                    const cardSpan = document.getElementById(`card-odo-display-${quickOdoVehicleId}`);
                    if (cardSpan) cardSpan.textContent = Number(newOdo).toLocaleString() + ' km';

                    closeQuickOdometerModal();

                    // Optional reload to refresh computed health bars, or notify
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Validation failed'));
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.textContent = origText;
                alert('Failed to update odometer.');
            });
        }
    </script>

    {{-- PMS MODAL (CarMind style checklist) --}}
    <div id="vehicle-pms-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-xs p-4">
        <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-slate-100 flex flex-col max-h-[90vh] overflow-hidden">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-extrabold text-slate-900">PMS Schedule & Checklist</h3>
                        <span id="pms-modal-health" class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">100% Good</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <span id="pms-modal-plate" class="font-bold text-slate-700"></span> •
                        <span id="pms-modal-label"></span> •
                        Odometer: <strong id="pms-modal-odo" class="text-slate-800"></strong>
                    </p>
                </div>
                <button type="button" onclick="closeVehiclePmsModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                {{-- Add new item form (collapsible) --}}
                <div id="pms-new-item-form" class="hidden rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <p class="text-xs font-extrabold text-slate-800">Add Maintenance Item to Schedule</p>
                    <form onsubmit="handleCreatePmsItem(event)" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Item Name</label>
                                <input type="text" id="pms-item-name" placeholder="e.g. Engine Oil & Filter, Brake Pads" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Category</label>
                                <select id="pms-item-category" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    <option value="replace">Replace</option>
                                    <option value="inspect">Inspect & Check</option>
                                    <option value="lubricate">Lubricate & Service</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Interval (km)</label>
                                <input type="number" id="pms-item-interval-km" min="100" placeholder="e.g. 7500" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Interval (months)</label>
                                <input type="number" id="pms-item-interval-months" min="1" placeholder="e.g. 6" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" onclick="togglePmsNewItemForm()" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">Cancel</button>
                            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition">Save Item</button>
                        </div>
                    </form>
                </div>

                {{-- Action Bar --}}
                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-500">Track routine service intervals like CarMind PMS booklets.</p>
                    <button
                        type="button"
                        onclick="togglePmsNewItemForm()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-800 transition cursor-pointer"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                        Add Item
                    </button>
                </div>

                {{-- Items container --}}
                <div id="pms-items-list" class="space-y-4">
                    {{-- Dynamically populated --}}
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-6 py-3">
                <button type="button" onclick="closeVehiclePmsModal()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- GLOBAL PMS RULES & TEMPLATES MODAL --}}
    <div id="pms-rules-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-xs p-4">
        <div class="relative w-full max-w-3xl rounded-2xl bg-white shadow-2xl border border-slate-100 flex flex-col max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Global PMS Rules & Templates</h3>
                        <p class="text-xs text-slate-500">Define recurring service intervals once. They automatically apply to all matching vehicle types.</p>
                    </div>
                </div>
                <button type="button" onclick="closePmsRulesModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                {{-- Add new global rule form --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-3">
                    <p class="text-xs font-black uppercase tracking-wider text-slate-700">+ Create Fleet PMS Rule</p>
                    <form onsubmit="handleCreatePmsTemplate(event)" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Service Item Name <span class="text-red-500">*</span></label>
                                <input type="text" id="tpl-item-name" placeholder="e.g. Engine Oil & Filter Change" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Category <span class="text-red-500">*</span></label>
                                <select id="tpl-category" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-amber-500">
                                    <option value="replace">Replace</option>
                                    <option value="inspect">Inspect & Check</option>
                                    <option value="lubricate">Lubricate & Service</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Interval (km)</label>
                                <input type="number" id="tpl-interval-km" min="100" placeholder="e.g. 5000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Interval (months)</label>
                                <input type="number" id="tpl-interval-months" min="1" placeholder="e.g. 6" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-amber-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1.5">Apply to Vehicle Types (leave unchecked to apply to ALL)</label>
                            <div class="flex flex-wrap gap-2 text-xs">
                                @foreach($vehicleTypes as $vt)
                                    <label class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-slate-700 cursor-pointer hover:bg-slate-100">
                                        <input type="checkbox" name="tpl_types" value="{{ $vt->name }}" class="rounded text-amber-600 focus:ring-amber-500">
                                        <span>{{ $vt->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-end pt-1">
                            <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700 transition cursor-pointer">
                                Save Rule & Apply Across Fleet
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Existing Rules List --}}
                <div>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-2">Active Fleet Rules</h4>
                    <div id="pms-templates-list" class="space-y-2">
                        {{-- Dynamically loaded --}}
                    </div>
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-6 py-3">
                <button type="button" onclick="closePmsRulesModal()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- QUICK ODOMETER UPDATE MODAL (CarMind style: fast 1-field popup) --}}
    <div id="quick-odometer-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-xs p-4">
        <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl border border-slate-100 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="m16.2 7.8-4.2 4.2"/><path d="m12 12v-6"/></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Update Odometer</h3>
                        <p class="text-[11px] text-slate-400" id="quick-odo-plate">Vehicle</p>
                    </div>
                </div>
                <button type="button" onclick="closeQuickOdometerModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer">
                    ✕
                </button>
            </div>

            <form onsubmit="handleQuickOdometerSubmit(event)" class="space-y-4">
                <div>
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <span class="font-bold text-slate-600">Current Reading:</span>
                        <span class="font-black text-slate-800" id="quick-odo-current">0 km</span>
                    </div>

                    <label class="block text-xs font-bold text-slate-700 mb-1">New Odometer Reading (km)</label>
                    <div class="relative flex items-center rounded-xl border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-emerald-500">
                        <input
                            type="number"
                            id="quick-odo-input"
                            min="0"
                            required
                            class="w-full rounded-l-xl border-0 bg-transparent px-3 py-2 text-sm font-extrabold text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-0"
                            placeholder="e.g. 62800"
                        >
                        <span class="border-l border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-500 rounded-r-xl">
                            km
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Updates oil change countdown, PMS intervals &amp; health score in real time.</p>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeQuickOdometerModal()" class="rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="quick-odo-submit-btn" class="rounded-xl bg-[#005a36] px-4 py-2 text-xs font-extrabold text-white hover:bg-[#00432a] transition cursor-pointer shadow-sm">
                        Save Reading
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
