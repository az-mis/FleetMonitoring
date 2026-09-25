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
    <div id="vehicle-view-list" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                <thead class="bg-gray-50 font-bold uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Plate</th>
                        <th class="px-4 py-3 font-semibold">Brand / Model</th>
                        <th class="px-4 py-3 font-semibold">Assigned Driver</th>
                        <th class="px-4 py-3 font-semibold">Year</th>
                        <th class="px-4 py-3 font-semibold">Color</th>
                        <th class="px-4 py-3 font-semibold">Odometer</th>
                        <th class="px-4 py-3 font-semibold">Type</th>
                        <th class="px-4 py-3 font-semibold">Fuel</th>
                        <th class="px-4 py-3 font-semibold">Location</th>
                        <th class="px-4 py-3 text-right font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-slate-700">
                    @foreach ($vehicles as $vehicle)
                        <tr class="vehicle-row hover:bg-slate-50/70 transition" data-category="{{ $vehicleCategory($vehicle->vehicle_type) }}">
                            {{-- Plate Number with vehicle thumbnail --}}
                            <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-900">
                                <div class="flex items-center gap-3">
                                    @if ($vehicle->photo_url)
                                        <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->plate_number }}" class="h-9 w-12 shrink-0 rounded-lg object-cover border border-slate-100">
                                    @else
                                        <div class="flex h-9 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 border border-slate-200/60">
                                            @include('vehicle::partials.vehicle-type-icon', ['type' => $vehicle->vehicle_type, 'class' => 'h-5 w-5', 'stroke' => '1.75'])
                                        </div>
                                    @endif
                                    <span>{{ $vehicle->plate_number }}</span>
                                </div>
                            </td>

                            {{-- Brand / Model --}}
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">
                                {{ $vehicle->brand }} {{ $vehicle->model }}
                            </td>

                            {{-- Assigned Driver --}}
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($vehicle->assignedDriver)
                                    <div class="flex items-center gap-2.5">
                                        <div class="relative flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#005a36] text-white text-xs font-bold">
                                            @if ($vehicle->assignedDriver->avatar_url)
                                                <img src="{{ $vehicle->assignedDriver->avatar_url }}" alt="{{ $vehicle->assignedDriver->name }}" class="h-full w-full object-cover">
                                            @else
                                                <span>{{ strtoupper(substr($vehicle->assignedDriver->name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0 leading-tight">
                                            <p class="truncate text-xs font-bold text-slate-900">{{ $vehicle->assignedDriver->name }}</p>
                                            <p class="truncate text-[10px] text-slate-400">{{ $vehicle->assignedDriver->phone_number ?: '—' }}</p>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">{{ $vehicle->year }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">{{ $vehicle->color }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">{{ number_format($vehicle->odometer) }} km</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">{{ $vehicle->vehicle_type ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">{{ $vehicle->fuel_type ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">{{ $vehicle->location ?: '—' }}</td>

                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <x-vehicle::row-actions :vehicle="$vehicle" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- GRID VIEW --}}
    <div id="vehicle-view-grid" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
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

                    {{-- Status badge --}}
                    <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-emerald-50/95 px-2.5 py-1 text-[11px] font-bold text-emerald-700 shadow-sm ring-1 ring-emerald-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 17h4V5H2v12h3"/>
                            <path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/>
                            <circle cx="7" cy="17" r="2"/>
                            <circle cx="17" cy="17" r="2"/>
                        </svg>
                        Active
                    </span>

                    {{-- Kebab menu --}}
                    <div class="absolute right-2 top-2 rounded-lg bg-white/95 shadow-sm">
                        <x-vehicle::row-actions :vehicle="$vehicle" :menu="true" />
                    </div>
                </div>

                {{-- Details --}}
                <div class="space-y-2 p-4">
                    <p class="flex items-center gap-1.5 text-[15px] font-bold text-slate-900">
                        @include('vehicle::partials.vehicle-type-icon', ['type' => $vehicle->vehicle_type, 'class' => 'h-4 w-4 shrink-0 text-[#005a36]', 'stroke' => '2'])
                        <span class="truncate">{{ $vehicle->brand }} {{ $vehicle->model }} {{ $vehicle->year }}</span>
                    </p>

                    <div class="space-y-1.5 text-[13px] text-slate-500">
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
                    </div>

                    <div class="flex items-center gap-4 border-t border-slate-100 pt-2 text-[13px] text-slate-600">
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

                    <div class="flex items-start gap-1.5 pt-0.5 text-[13px] font-medium text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span class="truncate">{{ $vehicle->location ?: 'No location assigned' }}</span>
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
            <button type="button" onclick="goToVehiclePage(1)" @disabled($vehicles->onFirstPage()) class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &laquo;
            </button>
            <button type="button" onclick="goToVehiclePage({{ max(1, $vehicles->currentPage() - 1) }})" @disabled($vehicles->onFirstPage()) class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &lt;
            </button>

            @foreach ($vehicles->getUrlRange(max(1, $vehicles->currentPage() - 1), min($vehicles->lastPage(), $vehicles->currentPage() + 1)) as $page => $url)
                <button type="button" onclick="goToVehiclePage({{ $page }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-bold transition {{ $page == $vehicles->currentPage() ? 'bg-[#005a36] text-white border-[#005a36]' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    {{ $page }}
                </button>
            @endforeach

            <button type="button" onclick="goToVehiclePage({{ min($vehicles->lastPage(), $vehicles->currentPage() + 1) }})" @disabled(!$vehicles->hasMorePages()) class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &gt;
            </button>
            <button type="button" onclick="goToVehiclePage({{ $vehicles->lastPage() }})" @disabled(!$vehicles->hasMorePages()) class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &raquo;
            </button>
        </div>
    </div>
@endif
