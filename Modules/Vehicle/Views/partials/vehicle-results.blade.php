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
    <div id="vehicle-view-list" class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                <thead class="bg-gray-50/80 font-bold uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Plate ▲</th>
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
                        <tr class="hover:bg-slate-50/70 transition">
                            {{-- Plate Number with vehicle thumbnail --}}
                            <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-900">
                                <div class="flex items-center gap-3">
                                    @if ($vehicle->photo_url)
                                        <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->plate_number }}" class="h-9 w-12 shrink-0 rounded object-cover shadow-sm border border-slate-100">
                                    @else
                                        <div class="flex h-9 w-12 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-400 border border-slate-200/60">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M10 17h4V5H2v12h3"/>
                                                <path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/>
                                                <circle cx="7" cy="17" r="2"/>
                                                <circle cx="17" cy="17" r="2"/>
                                            </svg>
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
                                        <div class="relative flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-600 text-white text-xs font-bold shadow-sm">
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

                            {{-- Year --}}
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">
                                {{ $vehicle->year }}
                            </td>

                            {{-- Color --}}
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">
                                {{ $vehicle->color }}
                            </td>

                            {{-- Odometer --}}
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">
                                {{ number_format($vehicle->odometer) }} km
                            </td>

                            {{-- Type --}}
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium uppercase">
                                {{ $vehicle->vehicle_type ?: '—' }}
                            </td>

                            {{-- Fuel --}}
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">
                                {{ $vehicle->fuel_type ?: '—' }}
                            </td>

                            {{-- Location --}}
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-medium">
                                {{ $vehicle->location ?: '—' }}
                            </td>

                            {{-- Actions --}}
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
    <div id="vehicle-view-grid" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
        @foreach ($vehicles as $vehicle)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm flex flex-col justify-between">
                <div>
                    {{-- Top Section: Vehicle Thumbnail / Photo + Edit/Delete Actions --}}
                    <div class="relative p-3">
                        @if ($vehicle->photo_url)
                            <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->plate_number }}" class="h-40 w-full rounded-xl object-cover border border-gray-200">
                        @else
                            <div class="flex h-40 w-full items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 17h4V5H2v12h3"/>
                                    <path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/>
                                    <circle cx="7" cy="17" r="2"/>
                                    <circle cx="17" cy="17" r="2"/>
                                </svg>
                            </div>
                        @endif

                        {{-- Floating Action Buttons (Edit / Delete) top-right --}}
                        <div class="absolute top-5 right-5 flex items-center gap-1 rounded-lg bg-white/80 p-1 backdrop-blur-md shadow-xs">
                            <x-vehicle::row-actions :vehicle="$vehicle" />
                        </div>
                    </div>

                    {{-- Vehicle Core Details --}}
                    <div class="p-4 space-y-2">
                        <div>
                            <p class="truncate text-base font-bold text-slate-900">{{ $vehicle->plate_number }}</p>
                            <p class="truncate text-xs font-semibold text-slate-500">
                                {{ $vehicle->brand }} {{ $vehicle->model }} · {{ $vehicle->year }}
                            </p>
                        </div>

                        {{-- Metadata list with icons --}}
                        <div class="space-y-1 text-xs text-slate-500 pt-1">
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span class="truncate">{{ $vehicle->location ?: 'No location assigned' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3v18"/></svg>
                                <span>{{ $vehicle->color ?: 'N/A' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 12l3 -3"/></svg>
                                <span>{{ number_format($vehicle->odometer) }} km</span>
                            </div>
                            @if ($vehicle->vehicle_type || $vehicle->fuel_type)
                                <div class="flex items-center gap-1.5 pt-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22V4a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v18"/><path d="M14 13h4a2 2 0 0 1 2 2v4a2 2 0 0 0 2 2h0"/></svg>
                                    <span>{{ strtoupper(implode(' · ', array_filter([$vehicle->vehicle_type, $vehicle->fuel_type]))) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Assigned Driver Section --}}
                <div class="p-4 pt-0">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1 mb-2">
                            <span>⚡</span> ASSIGNED DRIVER
                        </p>

                        @if ($vehicle->assignedDriver)
                            <div class="flex items-start gap-3">
                                <div class="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-800 text-white text-base font-bold shadow-sm mt-0.5">
                                    @if ($vehicle->assignedDriver->avatar_url)
                                        <img src="{{ $vehicle->assignedDriver->avatar_url }}" alt="{{ $vehicle->assignedDriver->name }}" class="h-full w-full object-cover">
                                    @else
                                        <span>{{ strtoupper(substr($vehicle->assignedDriver->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0 leading-tight space-y-0.5 pt-0.5">
                                    <p class="truncate text-xs font-bold text-slate-900">{{ $vehicle->assignedDriver->name }}</p>
                                    <p class="truncate text-[11px] text-slate-500">{{ $vehicle->assignedDriver->email }}</p>
                                    <p class="truncate text-[11px] text-slate-500">{{ $vehicle->assignedDriver->phone_number ?: '—' }}</p>
                                    <p class="truncate text-[11px] text-slate-500 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        {{ $vehicle->assignedDriver->location ?: ($vehicle->location ?: '—') }}
                                    </p>
                                </div>
                            </div>

                            {{-- License & Expiry Box --}}
                            <div class="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-white p-2 text-[10px] border border-slate-100 shadow-2xs">
                                <div>
                                    <span class="block text-slate-400 font-medium uppercase">LICENSE NO.</span>
                                    <span class="block font-bold text-slate-800 truncate">{{ $vehicle->assignedDriver->license_number ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="block text-slate-400 font-medium uppercase">VALID UNTIL</span>
                                    <span class="block font-bold text-slate-800 truncate">
                                        {{ optional($vehicle->assignedDriver->license_expiration_date)->format('M d, Y') ?: '—' }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="py-2 text-center text-xs text-slate-400 font-medium">
                                No driver assigned
                            </div>
                        @endif
                    </div>
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
                <select id="vehicle-per-page-select" onchange="changeVehiclePerPage(this.value)" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                    @foreach ([10, 15, 20, 25, 50, 75, 100] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center gap-1">
            <button type="button" onclick="goToVehiclePage(1)" @disabled($vehicles->onFirstPage()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &laquo;
            </button>
            <button type="button" onclick="goToVehiclePage({{ max(1, $vehicles->currentPage() - 1) }})" @disabled($vehicles->onFirstPage()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &lt;
            </button>

            @foreach ($vehicles->getUrlRange(max(1, $vehicles->currentPage() - 1), min($vehicles->lastPage(), $vehicles->currentPage() + 1)) as $page => $url)
                <button type="button" onclick="goToVehiclePage({{ $page }})" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border text-xs font-bold transition {{ $page == $vehicles->currentPage() ? 'bg-emerald-700 text-white border-emerald-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    {{ $page }}
                </button>
            @endforeach

            <button type="button" onclick="goToVehiclePage({{ min($vehicles->lastPage(), $vehicles->currentPage() + 1) }})" @disabled(!$vehicles->hasMorePages()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &gt;
            </button>
            <button type="button" onclick="goToVehiclePage({{ $vehicles->lastPage() }})" @disabled(!$vehicles->hasMorePages()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &raquo;
            </button>
        </div>
    </div>
@endif
