@props(['vehicle'])

<div class="flex items-center justify-end gap-1">
    {{-- Inspect Vehicle Button --}}
    <button
        type="button"
        onclick="openInspectionModal(this)"
        data-vehicle-id="{{ $vehicle->id }}"
        data-plate_number="{{ $vehicle->plate_number }}"
        data-vehicle-name="{{ $vehicle->brand }} {{ $vehicle->model }}"
        data-odometer="{{ $vehicle->odometer }}"
        data-last-change-oil="{{ $vehicle->last_change_oil_odometer }}"
        data-store-url="{{ route('vehicles.inspections.store', $vehicle) }}"
        title="Inspect Vehicle & Checklist"
        class="inline-flex h-7 px-2 items-center justify-center gap-1 rounded-md bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition shadow-xs text-[11px] font-bold cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
        </svg>
        <span>Inspect</span>
    </button>

    {{-- Inspection History Button --}}
    <button
        type="button"
        onclick="openInspectionHistoryModal(this)"
        data-history-url="{{ route('vehicles.inspections.index', $vehicle) }}"
        data-plate_number="{{ $vehicle->plate_number }}"
        title="View Inspection History"
        class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-600 hover:bg-slate-700 hover:text-white transition shadow-xs cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
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
        data-created="{{ $vehicle->created_at?->format('M j, Y g:i A') }}"
        data-updated="{{ $vehicle->updated_at?->format('M j, Y g:i A') }}"
        data-created-by="{{ $vehicle->creator ? $vehicle->creator->name . ($vehicle->creator->role ? ' (' . $vehicle->creator->role->label() . ')' : '') : '' }}"
        data-updated-by="{{ $vehicle->updater ? $vehicle->updater->name . ($vehicle->updater->role ? ' (' . $vehicle->updater->role->label() . ')' : '') : '' }}"
        title="Edit"
        class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition shadow-xs"
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
        class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition shadow-xs"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18"/>
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
            <path d="M10 11v6M14 11v6"/>
        </svg>
    </button>
</div>
