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
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Vehicle</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Plate Number</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Type</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Color</th>
                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Odometer</th>
                        <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($vehicles as $vehicle)
                        <tr class="hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($vehicle->photo_url)
                                        <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->plate_number }}" class="h-8 w-8 shrink-0 rounded-lg object-cover">
                                    @else
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 13h2l2-6h10l2 6h2"/>
                                                <path d="M5 13v5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-5"/>
                                                <circle cx="7.5" cy="16.5" r="1.5"/>
                                                <circle cx="16.5" cy="16.5" r="1.5"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-gray-900">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                                        <p class="truncate text-xs text-gray-500">{{ $vehicle->year }} &middot; {{ $vehicle->color }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20">
                                    {{ $vehicle->plate_number }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-600">{{ $vehicle->vehicle_type ? ucfirst($vehicle->vehicle_type) : '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-600">{{ $vehicle->color }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-gray-500">{{ number_format($vehicle->odometer) }} km</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                <x-vehicle::row-actions :vehicle="$vehicle" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- GRID VIEW --}}
    <div id="vehicle-view-grid" class="hidden grid grid-cols-1 gap-4 xs:grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($vehicles as $vehicle)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                @if ($vehicle->photo_url)
                    <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->plate_number }}" class="h-44 w-full object-cover">
                @else
                    <div class="flex h-44 w-full items-center justify-center bg-emerald-50 text-emerald-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 13h2l2-6h10l2 6h2"/>
                            <path d="M5 13v5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-5"/>
                            <circle cx="7.5" cy="16.5" r="1.5"/>
                            <circle cx="16.5" cy="16.5" r="1.5"/>
                        </svg>
                    </div>
                @endif

                <div class="p-4">
                    <p class="truncate text-base font-semibold text-gray-900">{{ $vehicle->plate_number }}</p>
                    <p class="mt-0.5 truncate text-sm text-gray-500">
                        {{ $vehicle->brand }} {{ $vehicle->model }} &middot; {{ $vehicle->year }} &middot; {{ $vehicle->color }}
                    </p>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if ($vehicle->vehicle_type)
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                {{ ucfirst($vehicle->vehicle_type) }}
                            </span>
                        @endif
                        @if ($vehicle->fuel_type)
                            <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-1 text-xs font-medium text-purple-700">
                                {{ ucfirst($vehicle->fuel_type) }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3">
                        <span class="text-xs text-gray-400">{{ number_format($vehicle->odometer) }} km</span>
                        <x-vehicle::row-actions :vehicle="$vehicle" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 overflow-x-auto">
        {{ $vehicles->links() }}
    </div>
@endif
