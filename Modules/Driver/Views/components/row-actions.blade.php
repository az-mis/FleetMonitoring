@props(['driver'])

<div class="flex items-center justify-end gap-1">
    <button
        type="button"
        onclick="openViewDriverModal(this)"
        data-initial="{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($driver->name, 0, 1)) }}"
        data-name="{{ $driver->name }}"
        data-email="{{ $driver->email }}"
        data-avatar="{{ $driver->avatar_url }}"
        data-address="{{ $driver->address }}"
        data-birth-date="{{ optional($driver->birth_date)->format('M d, Y') }}"
        data-age="{{ $driver->age }}"
        data-license-front="{{ $driver->license_front_url }}"
        data-license-back="{{ $driver->license_back_url }}"
        data-license-expiration="{{ optional($driver->license_expiration_date)->format('M d, Y') }}"
        data-created="{{ $driver->created_at->format('M d, Y \a\t g:i A') }}"
        data-updated="{{ $driver->updated_at->format('M d, Y \a\t g:i A') }}"
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
        onclick="openEditDriverModal(this)"
        data-name="{{ $driver->name }}"
        data-email="{{ $driver->email }}"
        data-avatar="{{ $driver->avatar_url }}"
        data-initial="{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($driver->name, 0, 1)) }}"
        data-address="{{ $driver->address }}"
        data-birth-date="{{ optional($driver->birth_date)->format('Y-m-d') }}"
        data-license-expiration="{{ optional($driver->license_expiration_date)->format('Y-m-d') }}"
        data-license-front="{{ $driver->license_front_url }}"
        data-license-back="{{ $driver->license_back_url }}"
        data-update-url="{{ route('driver.update', $driver) }}"
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
        onclick="openDeleteDriverModal(this)"
        data-name="{{ $driver->name }}"
        data-delete-url="{{ route('driver.destroy', $driver) }}"
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
