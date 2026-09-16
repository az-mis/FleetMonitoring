@props(['vehicle'])

<div class="flex items-center justify-end gap-1">
    <button
        type="button"
        onclick="openViewVehicleModal(this)"
        data-plate_number="{{ $vehicle->plate_number }}"
        data-photo="{{ $vehicle->photo_url }}"
        data-chassis_number="{{ $vehicle->chassis_number }}"
        data-engine_number="{{ $vehicle->engine_number }}"
        data-brand="{{ $vehicle->brand }}"
        data-model="{{ $vehicle->model }}"
        data-year="{{ $vehicle->year }}"
        data-color="{{ $vehicle->color }}"
        data-odometer="{{ number_format($vehicle->odometer) }}"
        data-vehicle_type="{{ $vehicle->vehicle_type ? ucfirst($vehicle->vehicle_type) : 'None' }}"
        data-fuel_type="{{ $vehicle->fuel_type ? ucfirst($vehicle->fuel_type) : 'None' }}"
        data-location="{{ $vehicle->location }}"
        data-created="{{ $vehicle->created_at->format('M d, Y \a\t g:i A') }}"
        data-updated="{{ $vehicle->updated_at->format('M d, Y \a\t g:i A') }}"
        title="View"
        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    </button>

    <button
        type="button"
        onclick="openEditVehicleModal(this)"
        data-update-url="{{ route('vehicles.update', $vehicle) }}"
        data-plate_number="{{ $vehicle->plate_number }}"
        data-photo="{{ $vehicle->photo_url }}"
        data-chassis_number="{{ $vehicle->chassis_number }}"
        data-engine_number="{{ $vehicle->engine_number }}"
        data-brand="{{ $vehicle->brand }}"
        data-model="{{ $vehicle->model }}"
        data-year="{{ $vehicle->year }}"
        data-color="{{ $vehicle->color }}"
        data-odometer="{{ $vehicle->odometer }}"
        data-vehicle_type="{{ $vehicle->vehicle_type }}"
        data-fuel_type="{{ $vehicle->fuel_type }}"
        data-location="{{ $vehicle->location }}"
        title="Edit"
        class="rounded-lg p-2 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 transition"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"/>
        </svg>
    </button>

    <button
        type="button"
        onclick="openDeleteVehicleModal(this)"
        data-plate_number="{{ $vehicle->plate_number }}"
        data-delete-url="{{ route('vehicles.destroy', $vehicle) }}"
        title="Delete"
        class="rounded-lg p-2 text-gray-500 hover:bg-red-50 hover:text-red-600 transition"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18"/>
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
            <path d="M10 11v6M14 11v6"/>
        </svg>
    </button>
</div>
