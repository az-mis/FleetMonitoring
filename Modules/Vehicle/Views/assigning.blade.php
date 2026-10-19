@extends('layouts.app')

@section('title', 'Vehicle Assigning')
@section('page-title', 'Vehicle Assigning')

@section('content')
    @php
        $locationOptions = \App\Support\FleetLocations::ALL;
        $locationButtonLabel = $selectedLocation ?: 'All Locations';
    @endphp

    {{-- Page header --}}
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Vehicle Assigning</h1>
            <p class="text-sm text-slate-500">Assign a primary driver to each vehicle — a driver may be assigned to multiple vehicles</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <form method="GET" action="{{ route('vehicle-assigning.index') }}" id="assign-search-form" class="flex w-full min-w-0 items-center gap-2 sm:w-72 xl:w-96">
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-600/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        id="assign-search-input"
                        value="{{ request('search') }}"
                        autocomplete="off"
                        aria-label="Search vehicles"
                        placeholder="Search plate, brand, driver..."
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-8 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                </div>
                @if(request('location'))
                    <input type="hidden" name="location" value="{{ request('location') }}">
                @endif
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif

                @if(request('search'))
                    <a href="{{ route('vehicle-assigning.index', array_filter(['location' => request('location'), 'per_page' => request('per_page')])) }}" class="shrink-0 text-xs font-semibold text-slate-500 hover:text-slate-700">Clear</a>
                @endif
            </form>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Location filter dropdown --}}
                @if ($isSuperAdmin)
                    <div class="relative" id="assign-location-dropdown">
                        <button
                            type="button"
                            id="assign-location-menu-btn"
                            onclick="toggleAssignLocationMenu()"
                            class="inline-flex h-10 items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 cursor-pointer"
                        >
                            <span class="flex items-center gap-1.5 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span class="max-w-[9rem] truncate">{{ $locationButtonLabel }}</span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div
                            id="assign-location-menu"
                            class="absolute left-0 z-20 mt-2 hidden w-64 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5 sm:left-auto sm:right-0"
                        >
                            <a
                                href="{{ route('vehicle-assigning.index', array_filter(['search' => request('search'), 'per_page' => request('per_page')])) }}"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 {{ empty($selectedLocation) ? 'bg-emerald-50 text-emerald-800' : '' }}"
                            >
                                <span>All Locations</span>
                                @if (empty($selectedLocation))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                @endif
                            </a>
                            @foreach ($locationOptions as $loc)
                                <a
                                    href="{{ route('vehicle-assigning.index', array_filter(['location' => $loc, 'search' => request('search'), 'per_page' => request('per_page')])) }}"
                                    class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 {{ $selectedLocation === $loc ? 'bg-emerald-50 text-emerald-800' : '' }}"
                                >
                                    <span class="truncate">{{ $loc }}</span>
                                    @if ($selectedLocation === $loc)
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
            </div>
        </div>
    </div>

    {{-- Stats Cards Row --}}
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        {{-- Total Vehicles --}}
        <div class="flex items-center gap-3 rounded-2xl border border-gray-200/80 bg-white p-3.5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-700 text-white shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 17h4V5H2v12h3"/>
                    <path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/>
                    <circle cx="7" cy="17" r="2"/>
                    <circle cx="17" cy="17" r="2"/>
                </svg>
            </div>
            <div>
                <p class="text-[0.625rem] font-extrabold uppercase tracking-wider text-slate-400">TOTAL VEHICLES</p>
                <p class="text-lg font-black text-slate-800 leading-none mt-0.5">{{ $totalVehicles }}</p>
            </div>
        </div>

        {{-- Assigned --}}
        <div class="flex items-center gap-3 rounded-2xl border border-gray-200/80 bg-white p-3.5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </div>
            <div>
                <p class="text-[0.625rem] font-extrabold uppercase tracking-wider text-slate-400">ASSIGNED</p>
                <p class="text-lg font-black text-emerald-600 leading-none mt-0.5">{{ $assignedCount }}</p>
            </div>
        </div>

        {{-- Unassigned --}}
        <div class="flex items-center gap-3 rounded-2xl border border-gray-200/80 bg-white p-3.5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-200 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div>
                <p class="text-[0.625rem] font-extrabold uppercase tracking-wider text-slate-400">UNASSIGNED</p>
                <p class="text-lg font-black text-amber-600 leading-none mt-0.5">{{ $unassignedCount }}</p>
            </div>
        </div>
    </div>

    {{-- Blue Tip Banner --}}
    <div class="mb-4 flex items-center gap-2.5 rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-2.5 text-xs text-blue-800">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 16v-4"/>
            <path d="M12 8h.01"/>
        </svg>
        <span>A driver can be assigned as the primary driver for multiple vehicles simultaneously. When a vehicle is dispatched, the assigned driver is the default — this can be overridden per request if needed.</span>
    </div>

    {{-- Cards Grid --}}
    @if ($vehicles->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
            <p class="text-sm font-medium text-gray-900">No vehicles found</p>
            <p class="mt-1 text-sm text-gray-500">
                @if (request('search') || request('location'))
                    No vehicles match your current search/filter.
                @else
                    No vehicles registered yet.
                @endif
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
            @foreach ($vehicles as $vehicle)
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md flex flex-col justify-between">
                    <div>
                        {{-- Vehicle Header Info --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                @if ($vehicle->photo_url)
                                    <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->plate_number }}" class="h-10 w-12 shrink-0 rounded-lg object-cover ring-1 ring-slate-100">
                                @else
                                    <div class="flex h-10 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                        @include('vehicle::partials.vehicle-type-icon', ['type' => $vehicle->vehicle_type, 'class' => 'h-5 w-5', 'stroke' => '1.5'])
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-extrabold text-slate-900">{{ $vehicle->plate_number }}</p>
                                    <p class="truncate text-[0.6875rem] font-semibold text-slate-500">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                                    @if ($vehicle->location)
                                        <p class="truncate text-[0.625rem] font-medium text-emerald-700 flex items-center gap-0.5 mt-0.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                            <span class="truncate">{{ $vehicle->location }}</span>
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Status Pill --}}
                            @if ($vehicle->assignedDriver)
                                <span class="inline-flex shrink-0 whitespace-nowrap items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[0.625rem] font-extrabold uppercase tracking-wide text-emerald-700 ring-1 ring-emerald-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                    ASSIGNED
                                </span>
                            @else
                                <span class="inline-flex shrink-0 whitespace-nowrap items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[0.625rem] font-extrabold uppercase tracking-wide text-slate-600">
                                    UNASSIGNED
                                </span>
                            @endif
                        </div>

                        {{-- Form to update assignment --}}
                        <form method="POST" action="{{ route('vehicle-assigning.update', $vehicle) }}" class="mt-4 space-y-3" id="form-assign-{{ $vehicle->id }}">
                            @csrf
                            @method('PATCH')

                            @php
                                $availableDrivers = $drivers->filter(function ($d) use ($vehicle) {
                                    // Only filter by location; a driver may be assigned to multiple vehicles
                                    return empty($vehicle->location) || empty($d->location) || $d->location === $vehicle->location;
                                });
                            @endphp

                            <div>
                                <label for="assign-driver-{{ $vehicle->id }}" class="block text-[0.625rem] font-extrabold uppercase tracking-wider text-slate-400 mb-1">PRIMARY DRIVER</label>
                                <div class="relative">
                                    <select
                                        name="assigned_driver_id"
                                        id="assign-driver-{{ $vehicle->id }}"
                                        onchange="this.form.submit()"
                                        class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-3 pr-8 text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer"
                                    >
                                        <option value="">Unassigned</option>
                                        @foreach ($availableDrivers as $driver)
                                            @php
                                                $vCount = $driver->assignedVehicles->count();
                                                $countLabel = $vCount > 0 ? " ({$vCount} vehicle" . ($vCount > 1 ? 's' : '') . ")" : '';
                                            @endphp
                                            <option value="{{ $driver->id }}" @selected($vehicle->assigned_driver_id === $driver->id)>
                                                {{ $driver->name }}{{ $countLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6 9 6 6 6-6"/>
                                    </svg>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Driver Card Details Block --}}
                    <div class="mt-3">
                        @if ($vehicle->assignedDriver)
                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                <div class="flex items-start gap-2.5">
                                    <div class="relative flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-800 text-white text-xs font-bold shadow-sm mt-0.5">
                                        @if ($vehicle->assignedDriver->avatar_url)
                                            <img src="{{ $vehicle->assignedDriver->avatar_url }}" alt="{{ $vehicle->assignedDriver->name }}" class="h-full w-full object-cover">
                                        @else
                                            <span>{{ strtoupper(substr($vehicle->assignedDriver->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 leading-tight space-y-0.5">
                                        <p class="truncate text-xs font-bold text-slate-900">{{ $vehicle->assignedDriver->name }}</p>
                                        <p class="truncate text-[0.6875rem] text-slate-500">{{ $vehicle->assignedDriver->email }}</p>
                                        <p class="truncate text-[0.6875rem] text-slate-500">
                                            Exp: {{ optional($vehicle->assignedDriver->license_expiration_date)->format('Y-m-d') ?: '—' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-4 text-center text-xs text-slate-400 font-medium">
                                No driver assigned
                            </div>
                        @endif

                        {{-- Who held this vehicle and when (decision D7) --}}
                        @if ($vehicle->assignmentHistory->isNotEmpty())
                            <details class="group rounded-xl border border-slate-100 bg-white">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2 text-[0.6875rem] font-bold text-slate-600 hover:text-emerald-700">
                                    <span>Assignment history ({{ $vehicle->assignmentHistory->count() }})</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                </summary>
                                <ol class="space-y-2 border-t border-slate-100 px-3 py-2">
                                    @foreach ($vehicle->assignmentHistory as $entry)
                                        <li class="text-[0.6875rem] leading-snug">
                                            <p class="font-semibold text-slate-800">
                                                {{ $entry->driver_name }}
                                                @if (! $entry->unassigned_at)
                                                    <span class="ml-1 rounded-full bg-emerald-100 px-1.5 py-px text-[0.625rem] font-bold text-emerald-700">Current</span>
                                                @endif
                                            </p>
                                            <p class="text-slate-500">
                                                {{ $entry->assigned_at ? $entry->assigned_at->format('M j, Y') : 'Before history began' }}
                                                &ndash; {{ $entry->unassigned_at ? $entry->unassigned_at->format('M j, Y') : 'present' }}
                                                @if ($entry->assignedBy)
                                                    &middot; by {{ $entry->assignedBy->name }}
                                                @endif
                                            </p>
                                        </li>
                                    @endforeach
                                </ol>
                            </details>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- BOTTOM PAGINATION BAR --}}
        <div class="mt-4 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 font-medium">
                <div>
                    Showing <span class="font-bold text-slate-700">{{ $vehicles->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-700">{{ $vehicles->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-700">{{ $vehicles->total() }}</span> vehicles
                </div>

                <div class="flex items-center gap-2">
                    <span>Rows per page:</span>
                    <select id="assign-per-page-select" onchange="changeAssignPerPage(this.value)" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                        @foreach ([10, 15, 20, 25, 50, 75, 100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <a href="{{ $vehicles->url(1) }}" aria-label="First page" @if($vehicles->onFirstPage()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &laquo;
                </a>
                <a href="{{ $vehicles->previousPageUrl() ?: '#' }}" aria-label="Previous page" @if($vehicles->onFirstPage()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &lt;
                </a>

                @foreach ($vehicles->getUrlRange(max(1, $vehicles->currentPage() - 1), min($vehicles->lastPage(), $vehicles->currentPage() + 1)) as $page => $url)
                    <a href="{{ $url }}" @if ($page == $vehicles->currentPage()) aria-current="page" @endif class="inline-flex h-7 w-7 items-center justify-center rounded-lg border text-xs font-bold transition {{ $page == $vehicles->currentPage() ? 'bg-emerald-700 text-white border-emerald-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                        {{ $page }}
                    </a>
                @endforeach

                <a href="{{ $vehicles->nextPageUrl() ?: '#' }}" aria-label="Next page" @if(!$vehicles->hasMorePages()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &gt;
                </a>
                <a href="{{ $vehicles->url($vehicles->lastPage()) }}" aria-label="Last page" @if(!$vehicles->hasMorePages()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &raquo;
                </a>
            </div>
        </div>
    @endif

    <script>
        function toggleAssignLocationMenu() {
            const menu = document.getElementById('assign-location-menu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('assign-location-dropdown');
            const menu = document.getElementById('assign-location-menu');
            if (dropdown && menu && !dropdown.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        function changeAssignPerPage(size) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', size);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }
    </script>
@endsection
