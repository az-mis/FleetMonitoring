{{-- Deactivate / Reactivate button for one driver (decision D10). Expects $driver and $buttonClass. --}}
@php $isActive = $driver->isActive(); @endphp
<button
    type="button"
    onclick="openDriverStatusModal(this)"
    data-name="{{ $driver->name }}"
    data-action="{{ $isActive ? 'deactivate' : 'reactivate' }}"
    data-url="{{ route($isActive ? 'driver.deactivate' : 'driver.reactivate', $driver) }}"
    data-vehicles="{{ $driver->assignedVehicles->pluck('plate_number')->implode(', ') }}"
    title="{{ $isActive ? 'Deactivate driver' : 'Reactivate driver' }}"
    aria-label="{{ $isActive ? 'Deactivate' : 'Reactivate' }} {{ $driver->name }}"
    class="{{ $buttonClass }} {{ $isActive ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }}"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        @if ($isActive)
            <path d="m17 8 5 5M22 8l-5 5"/>
        @else
            <path d="m16 11 2 2 4-4"/>
        @endif
    </svg>
</button>
