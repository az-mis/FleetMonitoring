<div class="flex items-center justify-end gap-1">
    <button
        type="button"
        onclick="openVehiclePmsModal({{ $vehicle->id }}, '{{ $vehicle->plate_number }}', '{{ trim($vehicle->brand . ' ' . $vehicle->model) }}', {{ $vehicle->odometer }})"
        title="PMS Schedule & Checklist"
        class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition shadow-xs cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
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
        data-change_oil_interval_km="{{ $vehicle->change_oil_interval_km ?: 5000 }}"
        data-vehicle_type="{{ $vehicle->vehicle_type }}"
        data-fuel_type="{{ $vehicle->fuel_type }}"
        data-location="{{ $vehicle->location }}"
        data-created="{{ $vehicle->created_at?->format('M j, Y g:i A') }}"
        data-updated="{{ $vehicle->updated_at?->format('M j, Y g:i A') }}"
        data-created-by="{{ $vehicle->creator ? $vehicle->creator->name . ($vehicle->creator->role ? ' (' . $vehicle->creator->role->label() . ')' : '') : '' }}"
        data-updated-by="{{ $vehicle->updater ? $vehicle->updater->name . ($vehicle->updater->role ? ' (' . $vehicle->updater->role->label() . ')' : '') : '' }}"
        title="Edit"
        class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition shadow-xs cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
        class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition shadow-xs cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18"/>
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
            <path d="M10 11v6M14 11v6"/>
        </svg>
    </button>
</div>
