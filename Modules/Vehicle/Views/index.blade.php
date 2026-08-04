@extends('layouts.app')

@section('title', 'Vehicles')
@section('page-title', 'Vehicles')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Manage Vehicles</h2>
            <p class="mt-1 text-sm text-gray-500">View, add, and manage fleet vehicles.</p>
        </div>

        <button
            type="button"
            onclick="openRegisterVehicleModal()"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Register Vehicle
        </button>
    </div>

    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 13h2l2-6h10l2 6h2"/>
                <path d="M5 13v5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-5"/>
                <circle cx="7.5" cy="16.5" r="1.5"/>
                <circle cx="16.5" cy="16.5" r="1.5"/>
            </svg>
        </div>
        <h3 class="mt-4 text-base font-semibold text-gray-900">No vehicles listed yet</h3>
        <p class="mt-1 max-w-sm text-sm text-gray-500">
            Vehicle listing is coming soon. You can already register a vehicle using the button above.
        </p>
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
