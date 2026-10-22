<div class="flex items-center justify-end gap-1.5">
    <button
        type="button"
        onclick="openVehiclePmsModal({{ $vehicle->id }}, @js($vehicle->plate_number), @js(trim($vehicle->brand . ' ' . $vehicle->model)), {{ $vehicle->odometer }})"
        title="PMS Schedule & Checklist"
        aria-label="PMS Schedule & Checklist"
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50/70 text-blue-700 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition shadow-2xs cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
        </svg>
    </button>

    @php $papers = $vehicle->registrationStatus()['tone'] === 'danger' || $vehicle->insuranceStatus()['tone'] === 'danger' ? 'danger' : (in_array('warning', [$vehicle->registrationStatus()['tone'], $vehicle->insuranceStatus()['tone']], true) ? 'warning' : null); @endphp
    <a
        href="{{ route('vehicles.registry', $vehicle) }}"
        title="Property & Registration{{ $papers === 'danger' ? ' (papers expired)' : ($papers === 'warning' ? ' (papers due soon)' : '') }}"
        aria-label="Property and registration for {{ $vehicle->plate_number }}"
        class="relative inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50/70 text-emerald-700 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition shadow-2xs"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>
        </svg>
        @if ($papers)
            <span class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full ring-2 ring-white {{ $papers === 'danger' ? 'bg-red-500' : 'bg-amber-400' }}" aria-hidden="true"></span>
        @endif
    </a>

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
        data-condition="{{ $vehicle->condition }}"
        data-created="{{ $vehicle->created_at?->format('M j, Y g:i A') }}"
        data-updated="{{ $vehicle->updated_at?->format('M j, Y g:i A') }}"
        data-created-by="{{ $vehicle->creator ? $vehicle->creator->name . ($vehicle->creator->role ? ' (' . $vehicle->creator->role->label() . ')' : '') : '' }}"
        data-updated-by="{{ $vehicle->updater ? $vehicle->updater->name . ($vehicle->updater->role ? ' (' . $vehicle->updater->role->label() . ')' : '') : '' }}"
        title="Edit Vehicle"
        aria-label="Edit Vehicle"
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50/70 text-emerald-700 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition shadow-2xs cursor-pointer"
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
        title="Delete Vehicle"
        aria-label="Delete Vehicle"
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50/70 text-rose-700 hover:bg-rose-600 hover:text-white hover:border-rose-600 transition shadow-2xs cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18"/>
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
            <path d="M10 11v6M14 11v6"/>
        </svg>
    </button>
</div>
