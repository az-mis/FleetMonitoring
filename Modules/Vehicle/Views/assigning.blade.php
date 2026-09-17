@extends('layouts.app')

@section('title', 'Vehicle Assigning')
@section('page-title', 'Vehicle Assigning')

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
        $locationButtonLabel = $selectedLocation ?: 'All Locations';
    @endphp

    {{-- Top Gradient Banner --}}
    <div class="mb-4 rounded-2xl bg-gradient-to-br from-[#155f35] via-[#1d8148] to-[#269d59] p-4 text-white shadow-lg shadow-emerald-900/15">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-extrabold tracking-tight">Vehicle Assigning</h2>
                    <p class="truncate text-xs text-white/80">Assign each vehicle to one driver permanently.</p>
                </div>
            </div>

            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-end">
                <form method="GET" action="{{ route('vehicle-assigning.index') }}" id="assign-search-form" class="flex w-full min-w-0 items-center gap-2 md:w-56 xl:w-80">
                    <div class="relative w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input
                            type="text"
                            name="search"
                            id="assign-search-input"
                            value="{{ request('search') }}"
                            placeholder="Search plate, brand, driver..."
                            class="w-full rounded-xl border-0 bg-white py-2 pl-9 pr-4 text-xs text-slate-800 placeholder-slate-400 shadow-inner focus:outline-none focus:ring-2 focus:ring-emerald-300"
                        >
                    </div>
                    @if(request('location'))
                        <input type="hidden" name="location" value="{{ request('location') }}">
                    @endif
                </form>

                {{-- Location filter dropdown --}}
                <div class="relative shrink-0" id="assign-location-dropdown">
                    <button
                        type="button"
                        id="assign-location-menu-btn"
                        onclick="toggleAssignLocationMenu()"
                        class="inline-flex w-full items-center justify-between gap-2 rounded-xl bg-black/20 px-3 py-2 text-xs font-semibold text-white backdrop-blur-sm transition hover:bg-black/30 md:w-auto cursor-pointer"
                    >
                        <span class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span class="truncate">{{ $locationButtonLabel }}</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div
                        id="assign-location-menu"
                        class="absolute right-0 top-full z-30 mt-1.5 hidden w-56 overflow-hidden rounded-xl border border-gray-100 bg-white p-1.5 shadow-xl ring-1 ring-black/5"
                    >
                        <a
                            href="{{ route('vehicle-assigning.index', array_filter(['search' => request('search'), 'per_page' => request('per_page')])) }}"
                            class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-semibold {{ empty($selectedLocation) ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-slate-50' }}"
                        >
                            <span>All Locations</span>
                            @if (empty($selectedLocation))
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </a>
                        <div class="my-1 border-t border-gray-100"></div>
                        @foreach ($locationOptions as $loc)
                            <a
                                href="{{ route('vehicle-assigning.index', array_filter(['location' => $loc, 'search' => request('search'), 'per_page' => request('per_page')])) }}"
                                class="flex items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-semibold {{ $selectedLocation === $loc ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-slate-50' }}"
                            >
                                <span class="truncate">{{ $loc }}</span>
                                @if ($selectedLocation === $loc)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
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
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">TOTAL VEHICLES</p>
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
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">ASSIGNED</p>
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
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">UNASSIGNED</p>
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
        <span>Each driver here becomes the permanent, default driver for that vehicle unless they're absent. Per-request driver overrides (e.g. when the assigned driver is unavailable) are planned for a future update.</span>
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
                            <div class="flex items-center gap-3">
                                @if ($vehicle->photo_url)
                                    <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->plate_number }}" class="h-10 w-14 shrink-0 rounded-lg object-cover ring-1 ring-slate-100">
                                @else
                                    <div class="flex h-10 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10 17h4V5H2v12h3"/>
                                            <path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/>
                                            <circle cx="7" cy="17" r="2"/>
                                            <circle cx="17" cy="17" r="2"/>
                                        </svg>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-extrabold text-slate-900">{{ $vehicle->plate_number }}</p>
                                    <p class="truncate text-[11px] font-semibold text-slate-500">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                                </div>
                            </div>

                            {{-- Status Pill --}}
                            @if ($vehicle->assignedDriver)
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-emerald-700 ring-1 ring-emerald-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                    ASSIGNED
                                </span>
                            @else
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-slate-600">
                                    UNASSIGNED
                                </span>
                            @endif
                        </div>

                        {{-- Form to update assignment --}}
                        <form method="POST" action="{{ route('vehicle-assigning.update', $vehicle) }}" class="mt-4 space-y-3" id="form-assign-{{ $vehicle->id }}">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">PERMANENT DRIVER</label>
                                <div class="relative">
                                    <select
                                        name="assigned_driver_id"
                                        onchange="this.form.submit()"
                                        class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-3 pr-8 text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer"
                                    >
                                        <option value="">Unassigned</option>
                                        @foreach ($drivers as $driver)
                                            <option value="{{ $driver->id }}" @selected($vehicle->assigned_driver_id === $driver->id)>
                                                {{ $driver->name }}
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
                                        <p class="truncate text-[11px] text-slate-500">{{ $vehicle->assignedDriver->email }}</p>
                                        <p class="truncate text-[11px] text-slate-500">
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
                <a href="{{ $vehicles->url(1) }}" @if($vehicles->onFirstPage()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &laquo;
                </a>
                <a href="{{ $vehicles->previousPageUrl() ?: '#' }}" @if($vehicles->onFirstPage()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &lt;
                </a>

                @foreach ($vehicles->getUrlRange(max(1, $vehicles->currentPage() - 1), min($vehicles->lastPage(), $vehicles->currentPage() + 1)) as $page => $url)
                    <a href="{{ $url }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border text-xs font-bold transition {{ $page == $vehicles->currentPage() ? 'bg-emerald-700 text-white border-emerald-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                        {{ $page }}
                    </a>
                @endforeach

                <a href="{{ $vehicles->nextPageUrl() ?: '#' }}" @if(!$vehicles->hasMorePages()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &gt;
                </a>
                <a href="{{ $vehicles->url($vehicles->lastPage()) }}" @if(!$vehicles->hasMorePages()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
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
