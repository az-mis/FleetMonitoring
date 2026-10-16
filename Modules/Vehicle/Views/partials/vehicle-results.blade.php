@php
    // data-category carries the vehicle type slug so the chips can filter rows/cards.
    $vehicleCategory = static fn (?string $type): string => \Modules\Vehicle\Models\VehicleType::resolve($type)?->slug ?? 'car';
@endphp

@if ($vehicles->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
        <p class="text-sm font-medium text-gray-900">No vehicles found</p>
        <p class="mt-1 text-sm text-gray-500">
            @if (request('search'))
                No results match "{{ request('search') }}".
            @else
                Get started by registering your first vehicle.
            @endif
        </p>
    </div>
@else
    {{-- LIST VIEW --}}
    <div id="vehicle-view-list" class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                <thead class="bg-slate-50/80 text-[0.6875rem] font-black uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-bold">Plate</th>
                        <th class="px-4 py-3.5 font-bold">Vehicle</th>
                        <th class="px-4 py-3.5 font-bold">Assigned Driver</th>
                        <th class="px-3 py-3.5 font-bold">Year</th>
                        <th class="px-3 py-3.5 font-bold">Color</th>
                        <th class="px-4 py-3.5 font-bold">Odometer &amp; Service</th>
                        <th class="px-4 py-3.5 font-bold">Health Score</th>
                        <th class="px-3 py-3.5 font-bold">Type</th>
                        <th class="px-3 py-3.5 font-bold">Fuel</th>
                        <th class="px-4 py-3.5 font-bold">Location</th>
                        <th class="sticky right-0 bg-slate-50 px-5 py-3.5 text-right font-bold shadow-[-6px_0_8px_-6px_rgba(15,23,42,0.15)]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-slate-700">
                    @foreach ($vehicles as $vehicle)
                        <tr class="vehicle-row group hover:bg-slate-50/80 transition duration-150" data-category="{{ $vehicleCategory($vehicle->vehicle_type) }}">
                            {{-- Plate Number with vehicle thumbnail --}}
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($vehicle->photo_url)
                                        <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->plate_number }}" class="h-10 w-14 shrink-0 rounded-xl object-cover border border-slate-200/70 shadow-2xs">
                                    @else
                                        <div class="flex h-10 w-14 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400 border border-slate-200/60 shadow-2xs">
                                            @include('vehicle::partials.vehicle-type-icon', ['type' => $vehicle->vehicle_type, 'class' => 'h-5 w-5', 'stroke' => '1.75'])
                                        </div>
                                    @endif
                                    <div>
                                        <span class="font-extrabold text-slate-900 text-sm tracking-tight block">{{ $vehicle->plate_number }}</span>
                                        <span class="text-[0.625rem] font-semibold text-slate-400 uppercase tracking-wider">{{ $vehicle->vehicle_type ?: 'Vehicle' }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- Brand / Model --}}
                            <td class="whitespace-nowrap px-4 py-3.5 font-bold text-slate-800">
                                <p class="text-xs">{{ $vehicle->brand }}</p>
                                <p class="text-[0.6875rem] font-medium text-slate-500">{{ $vehicle->model }}</p>
                            </td>

                            {{-- Assigned Driver --}}
                            <td class="whitespace-nowrap px-4 py-3.5">
                                @if ($vehicle->assignedDriver)
                                    <div class="flex items-center gap-2.5">
                                        <div class="relative flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#005a36] text-white text-[0.6875rem] font-extrabold shadow-2xs">
                                            @if ($vehicle->assignedDriver->avatar_url)
                                                <img src="{{ $vehicle->assignedDriver->avatar_url }}" alt="{{ $vehicle->assignedDriver->name }}" class="h-full w-full object-cover">
                                            @else
                                                <span>{{ strtoupper(substr($vehicle->assignedDriver->name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0 leading-tight">
                                            <p class="truncate text-xs font-bold text-slate-900">{{ $vehicle->assignedDriver->name }}</p>
                                            <p class="truncate text-[0.625rem] text-slate-400">{{ $vehicle->assignedDriver->phone_number ?: '—' }}</p>
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[0.6875rem] font-medium text-slate-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span> Unassigned
                                    </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5 text-slate-600 font-semibold">{{ $vehicle->year ?: '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-slate-600 font-medium">{{ $vehicle->color }}</td>

                            {{-- Odometer & Oil status badge --}}
                            <td class="whitespace-nowrap px-4 py-3.5">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-900 font-extrabold text-xs tracking-tight" id="odo-display-{{ $vehicle->id }}">{{ number_format($vehicle->odometer) }} km</span>
                                        <button
                                            type="button"
                                            onclick="openQuickOdometerModal({{ $vehicle->id }}, @js($vehicle->plate_number), {{ $vehicle->odometer }})"
                                            class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[0.625rem] font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 hover:text-emerald-900 transition border border-emerald-200/60 cursor-pointer shadow-2xs"
                                            title="Quick update odometer reading"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                            Update
                                        </button>
                                    </div>
                                    @php $oil = $vehicle->change_oil_status; @endphp
                                    @if ($oil['status'] === 'overdue')
                                        <div class="inline-flex items-center gap-1 rounded-full bg-red-50 border border-red-200 px-2 py-0.5 text-[0.625rem] font-extrabold text-red-600 shadow-2xs" title="{{ $oil['label'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-600 animate-pulse"></span>
                                            <span>Oil Overdue</span>
                                        </div>
                                    @elseif ($oil['status'] === 'due_soon')
                                        <div class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2 py-0.5 text-[0.625rem] font-extrabold text-amber-700 shadow-2xs" title="{{ $oil['label'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            <span>Oil Due Soon</span>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[0.625rem] font-bold text-emerald-700 shadow-2xs" title="{{ $oil['label'] }}">
                                            <span class="h-1 w-1 rounded-full bg-emerald-600"></span>
                                            <span>Oil OK</span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Health Score Progress Bar --}}
                            @php $health = $vehicle->health_score; @endphp
                            <td class="whitespace-nowrap px-4 py-3.5">
                                <div class="flex flex-col items-start gap-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-black
                                            {{ $health['color'] === 'emerald' ? 'text-emerald-700' : ($health['color'] === 'amber' ? 'text-amber-700' : 'text-red-700') }}">
                                            {{ $health['score'] }}%
                                        </span>
                                        <span class="rounded px-1.5 py-px text-[0.5625rem] font-extrabold uppercase tracking-wide
                                            {{ $health['color'] === 'emerald' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200/80' : ($health['color'] === 'amber' ? 'bg-amber-50 text-amber-800 border border-amber-200/80' : 'bg-red-50 text-red-800 border border-red-200/80') }}">
                                            {{ $health['label'] }}
                                        </span>
                                    </div>
                                    <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden border border-slate-200/40">
                                        <div class="h-full rounded-full transition-all duration-300
                                            {{ $health['color'] === 'emerald' ? 'bg-emerald-500' : ($health['color'] === 'amber' ? 'bg-amber-400' : 'bg-red-500') }}"
                                            style="width: {{ $health['score'] }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-3.5">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[0.6875rem] font-semibold text-slate-700 border border-slate-200/60">{{ $vehicle->vehicle_type ?: '—' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3.5 text-slate-500 font-medium">{{ $vehicle->fuel_type ?: '—' }}</td>

                            <td class="whitespace-nowrap px-4 py-3.5">
                                <span class="inline-flex items-center gap-1 text-slate-700 font-semibold text-[0.6875rem]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    {{ $vehicle->location ?: '—' }}
                                </span>
                            </td>

                            <td class="sticky right-0 whitespace-nowrap bg-white px-5 py-3.5 text-right shadow-[-6px_0_8px_-6px_rgba(15,23,42,0.15)] transition duration-150 group-hover:bg-slate-50">
                                <x-vehicle::row-actions :vehicle="$vehicle" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- GRID VIEW --}}
    <div id="vehicle-view-grid" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
        @foreach ($vehicles as $vehicle)
            <div class="vehicle-card overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" data-category="{{ $vehicleCategory($vehicle->vehicle_type) }}">
                {{-- Photo + status badge + kebab menu --}}
                <div class="relative">
                    @if ($vehicle->photo_url)
                        <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->plate_number }}" class="h-40 w-full object-cover">
                    @else
                        <div class="flex h-40 w-full items-center justify-center bg-slate-100 text-slate-400">
                            @include('vehicle::partials.vehicle-type-icon', ['type' => $vehicle->vehicle_type, 'class' => 'h-10 w-10', 'stroke' => '1.5'])
                        </div>
                    @endif

                    {{-- Assignment badge (the app has no stored vehicle status, so show what is actually known) --}}
                    <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[0.6875rem] font-bold shadow-sm ring-1 {{ $vehicle->assignedDriver ? 'bg-emerald-50/95 text-emerald-700 ring-emerald-100' : 'bg-slate-50/95 text-slate-600 ring-slate-200' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 17h4V5H2v12h3"/>
                            <path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/>
                            <circle cx="7" cy="17" r="2"/>
                            <circle cx="17" cy="17" r="2"/>
                        </svg>
                        {{ $vehicle->assignedDriver ? 'Driver Assigned' : 'No Driver' }}
                    </span>

                    {{-- Kebab menu --}}
                    <div class="absolute right-2 top-2 rounded-lg bg-white/95 shadow-sm">
                        <x-vehicle::row-actions :vehicle="$vehicle" :menu="true" />
                    </div>
                </div>

                {{-- Details --}}
                <div class="space-y-2 p-4">
                    <p class="flex items-center gap-1.5 text-[0.9375rem] font-bold text-slate-900">
                        @include('vehicle::partials.vehicle-type-icon', ['type' => $vehicle->vehicle_type, 'class' => 'h-4 w-4 shrink-0 text-[#005a36]', 'stroke' => '2'])
                        <span class="truncate">{{ $vehicle->brand }} {{ $vehicle->model }}{{ $vehicle->year ? ' ' . $vehicle->year : '' }}</span>
                    </p>

                    <div class="space-y-1.5 text-[0.8125rem] text-slate-500">
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="7" width="20" height="10" rx="2"/><path d="M6 7V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/>
                            </svg>
                            <span class="truncate">{{ $vehicle->plate_number }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 3H5a2 2 0 0 0-2 2v4M9 3v18M9 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H9"/>
                            </svg>
                            <span class="truncate">{{ $vehicle->chassis_number ?: '—' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/>
                            </svg>
                            <span class="truncate">{{ $vehicle->engine_number ?: '—' }}</span>
                        </div>

                        {{-- Odometer with Quick Update button (CarMind style hero card) --}}
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-2.5 py-1.5 border border-slate-100 mt-1">
                            <div>
                                <span class="text-[0.625rem] font-bold uppercase tracking-wider text-slate-400 block">Odometer</span>
                                <span class="text-xs font-black text-slate-800" id="card-odo-display-{{ $vehicle->id }}">{{ number_format($vehicle->odometer) }} km</span>
                            </div>
                            <button
                                type="button"
                                onclick="openQuickOdometerModal({{ $vehicle->id }}, @js($vehicle->plate_number), {{ $vehicle->odometer }})"
                                class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2 py-1 text-[0.6875rem] font-bold text-white hover:bg-emerald-700 transition cursor-pointer shadow-2xs"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                Update
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 border-t border-slate-100 pt-2 text-[0.8125rem] text-slate-600">
                        <span class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 17h14M5 17a2 2 0 1 0 4 0m6 0a2 2 0 1 0 4 0M5 17V9l2-5h10l2 5v8"/>
                            </svg>
                            {{ $vehicle->vehicle_type ?: '—' }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 22V4a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v18"/><path d="M14 13h4a2 2 0 0 1 2 2v4a2 2 0 0 0 2 2h0"/>
                            </svg>
                            {{ $vehicle->fuel_type ?: '—' }}
                        </span>
                    </div>

                    <div class="flex items-start gap-1.5 pt-0.5 text-[0.8125rem] font-medium text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span class="truncate">{{ $vehicle->location ?: 'No location assigned' }}</span>
                    </div>

                    {{-- Vehicle Health Score --}}
                    @php $health = $vehicle->health_score; @endphp
                    <div class="border-t border-slate-100 pt-2 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[0.625rem] font-bold uppercase tracking-wider text-slate-400">Vehicle Health</span>
                            <span class="text-xs font-extrabold
                                {{ $health['color'] === 'emerald' ? 'text-emerald-700' : ($health['color'] === 'amber' ? 'text-amber-600' : 'text-red-600') }}">
                                {{ $health['score'] }}% {{ $health['label'] }}
                            </span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all
                                {{ $health['color'] === 'emerald' ? 'bg-emerald-500' : ($health['color'] === 'amber' ? 'bg-amber-400' : 'bg-red-500') }}"
                                style="width: {{ $health['score'] }}%"></div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-[0.625rem] font-semibold">
                            @if($health['overdue'] > 0)
                                <span class="flex items-center gap-0.5 text-red-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                    {{ $health['overdue'] }} Overdue
                                </span>
                            @endif
                            @if($health['due_soon'] > 0)
                                <span class="flex items-center gap-0.5 text-amber-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                    {{ $health['due_soon'] }} Due Soon
                                </span>
                            @endif
                            @if($health['on_track'] > 0)
                                <span class="flex items-center gap-0.5 text-emerald-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    {{ $health['on_track'] }} On Track
                                </span>
                            @endif
                            @if($health['overdue'] === 0 && $health['due_soon'] === 0 && $health['on_track'] === 0)
                                <span class="text-slate-400">No PMS items yet</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div id="vehicle-empty-filter-state" class="hidden rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
        <p class="text-sm font-medium text-gray-900">No vehicles in this category on this page</p>
        <p class="mt-1 text-sm text-gray-500">Try another category, or clear the filter.</p>
    </div>

    {{-- BOTTOM PAGINATION BAR --}}
    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 font-medium">
            <div>
                Showing <span class="font-bold text-slate-700">{{ $vehicles->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-700">{{ $vehicles->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-700">{{ $vehicles->total() }}</span> vehicles
            </div>

            <div class="flex items-center gap-2">
                <span>Rows per page:</span>
                <select id="vehicle-per-page-select" onchange="changeVehiclePerPage(this.value)" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                    @foreach ([10, 15, 20, 25, 50, 75, 100] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center gap-1">
            <button type="button" onclick="goToVehiclePage(1)" aria-label="First page" @disabled($vehicles->onFirstPage()) class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &laquo;
            </button>
            <button type="button" onclick="goToVehiclePage({{ max(1, $vehicles->currentPage() - 1) }})" aria-label="Previous page" @disabled($vehicles->onFirstPage()) class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &lt;
            </button>

            @foreach ($vehicles->getUrlRange(max(1, $vehicles->currentPage() - 1), min($vehicles->lastPage(), $vehicles->currentPage() + 1)) as $page => $url)
                <button type="button" onclick="goToVehiclePage({{ $page }})" @if ($page == $vehicles->currentPage()) aria-current="page" @endif class="inline-flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-bold transition {{ $page == $vehicles->currentPage() ? 'bg-[#005a36] text-white border-[#005a36]' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    {{ $page }}
                </button>
            @endforeach

            <button type="button" onclick="goToVehiclePage({{ min($vehicles->lastPage(), $vehicles->currentPage() + 1) }})" aria-label="Next page" @disabled(!$vehicles->hasMorePages()) class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &gt;
            </button>
            <button type="button" onclick="goToVehiclePage({{ $vehicles->lastPage() }})" aria-label="Last page" @disabled(!$vehicles->hasMorePages()) class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &raquo;
            </button>
        </div>
    </div>
@endif
