@if ($drivers->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
        <p class="text-sm font-bold text-gray-900">No drivers found</p>
        <p class="mt-1 text-sm text-gray-500">
            @if (request('search'))
                No results match "{{ request('search') }}".
            @else
                Get started by registering your first driver.
            @endif
        </p>
    </div>
@else
    {{-- LIST VIEW --}}
    <div id="driver-view-list" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Name</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Email</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Phone</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Assigned Location</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">License Expiry</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($drivers as $driver)
                        @php
                            $initial = Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($driver->name, 0, 1));
                        @endphp
                        <tr class="hover:bg-gray-50/70">
                            <td class="whitespace-nowrap px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    @if ($driver->avatar_url)
                                        <img src="{{ $driver->avatar_url }}" alt="{{ $driver->name }}" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-gray-200">
                                    @else
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">
                                            {{ $initial }}
                                        </div>
                                    @endif
                                    <span class="text-sm font-semibold text-gray-900">{{ $driver->name }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-sm text-slate-500">{{ $driver->email }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-sm text-slate-500">{{ $driver->phone_number ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-sm font-semibold text-emerald-800">{{ $driver->location ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-sm text-slate-500">{{ optional($driver->license_expiration_date)->format('M d, Y') ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        onclick="openEditDriverModal(this)"
                                        data-name="{{ $driver->name }}"
                                        data-email="{{ $driver->email }}"
                                        data-avatar="{{ $driver->avatar_url }}"
                                        data-address="{{ $driver->address }}"
                                        data-birth-date="{{ optional($driver->birth_date)->format('Y-m-d') }}"
                                        data-license-number="{{ $driver->license_number }}"
                                        data-license-expiration="{{ optional($driver->license_expiration_date)->format('Y-m-d') }}"
                                        data-license-front="{{ $driver->license_front_url }}"
                                        data-license-back="{{ $driver->license_back_url }}"
                                        data-phone-number="{{ $driver->phone_number }}"
                                        data-location="{{ $driver->location }}"
                                        data-update-url="{{ route('driver.update', $driver) }}"
                                        title="Edit"
                                        class="text-blue-500 hover:text-blue-700 p-1"
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
                                        class="text-red-500 hover:text-red-700 p-1"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 6h18"/>
                                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- GRID VIEW --}}
    <div id="driver-view-grid" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
        @foreach ($drivers as $driver)
            @php
                $initial = Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($driver->name, 0, 1));
                $assignedVehicles = $driver->assignedVehicles;
            @endphp
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm flex flex-col justify-between">
                <div>
                    {{-- Top Section: Driver Photo Banner + Floating Edit/Delete Actions --}}
                    <div class="relative aspect-square w-full p-3">
                        @if ($driver->avatar_url)
                            <img src="{{ $driver->avatar_url }}" alt="{{ $driver->name }}" class="h-full w-full rounded-xl object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center rounded-xl bg-slate-800 text-4xl font-bold text-white">
                                {{ $initial }}
                            </div>
                        @endif

                        {{-- Floating Action Buttons (Edit / Delete) top-right --}}
                        <div class="absolute top-5 right-5 flex items-center gap-1 rounded-lg bg-white/80 p-1 backdrop-blur-md shadow-xs">
                            <button
                                type="button"
                                onclick="openEditDriverModal(this)"
                                data-name="{{ $driver->name }}"
                                data-email="{{ $driver->email }}"
                                data-avatar="{{ $driver->avatar_url }}"
                                data-initial="{{ $initial }}"
                                data-address="{{ $driver->address }}"
                                data-birth-date="{{ optional($driver->birth_date)->format('Y-m-d') }}"
                                data-license-number="{{ $driver->license_number }}"
                                data-license-expiration="{{ optional($driver->license_expiration_date)->format('Y-m-d') }}"
                                data-phone-number="{{ $driver->phone_number }}"
                                data-location="{{ $driver->location }}"
                                data-update-url="{{ route('driver.update', $driver) }}"
                                title="Edit driver"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition shadow-xs"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"/>
                                </svg>
                            </button>
                            <button
                                type="button"
                                onclick="openDeleteDriverModal(this)"
                                data-name="{{ $driver->name }}"
                                data-delete-url="{{ route('driver.destroy', $driver) }}"
                                title="Delete driver"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition shadow-xs"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18"/>
                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Driver Info Details --}}
                    <div class="p-4 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-base font-bold text-slate-900">{{ $driver->name }}</p>
                            <span class="inline-flex items-center gap-1 shrink-0 rounded-full bg-emerald-100/70 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                Active
                            </span>
                        </div>

                        {{-- Info list with icons --}}
                        <div class="space-y-1 text-xs text-slate-500 pt-1">
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span class="truncate">{{ $driver->email }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span>{{ $driver->phone_number ?: '—' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-emerald-800 font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span class="truncate">{{ $driver->location ?: 'No location assigned' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Assigned Vehicle(s) Block --}}
                <div class="p-4 pt-0">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1 mb-2">
                            <span>🚗</span> ASSIGNED VEHICLE(S)
                        </p>

                        @if ($assignedVehicles->isNotEmpty())
                            <div class="space-y-2">
                                @foreach ($assignedVehicles as $v)
                                    <div class="flex items-center gap-2.5">
                                        @if ($v->photo_url)
                                            <img src="{{ $v->photo_url }}" alt="{{ $v->brand }} {{ $v->model }}" class="h-9 w-12 shrink-0 rounded-md object-cover ring-1 ring-slate-200">
                                        @else
                                            <div class="flex h-9 w-12 shrink-0 items-center justify-center rounded-md bg-slate-200 text-slate-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C2.05 11.2 2 11.6 2 12v4c0 .6.4 1 1 1h2"/>
                                                    <circle cx="7" cy="17" r="2"/>
                                                    <path d="M9 17h6"/>
                                                    <circle cx="17" cy="17" r="2"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-bold text-slate-900">{{ $v->brand }} {{ $v->model }}</p>
                                            <span class="inline-block rounded bg-slate-200/80 px-1.5 py-0.5 text-[9px] font-extrabold tracking-wide text-slate-700">
                                                {{ $v->plate_number }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-2 text-center text-xs text-slate-400 font-medium">
                                No assigned vehicle
                            </div>
                        @endif

                        {{-- License & Expiry Box --}}
                        <div class="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-white p-2 text-[10px] border border-slate-100 shadow-2xs">
                            <div>
                                <span class="block text-slate-400 font-medium uppercase">LICENSE NO.</span>
                                <span class="block font-bold text-slate-800 truncate">{{ $driver->license_number ?: '—' }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-400 font-medium uppercase">VALID UNTIL</span>
                                <span class="block font-bold text-slate-800 truncate">
                                    {{ optional($driver->license_expiration_date)->format('M d, Y') ?: '—' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- BOTTOM PAGINATION BAR --}}
    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 font-medium">
            <div>
                Showing <span class="font-bold text-slate-700">{{ $drivers->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-700">{{ $drivers->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-700">{{ $drivers->total() }}</span> drivers
            </div>

            <div class="flex items-center gap-2">
                <span>Rows per page:</span>
                <select id="driver-per-page-select" onchange="changeDriverPerPage(this.value)" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                    @foreach ([10, 15, 20, 25, 50, 75, 100] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center gap-1">
            <button type="button" onclick="goToDriverPage(1)" @disabled($drivers->onFirstPage()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &laquo;
            </button>
            <button type="button" onclick="goToDriverPage({{ max(1, $drivers->currentPage() - 1) }})" @disabled($drivers->onFirstPage()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &lt;
            </button>

            @foreach ($drivers->getUrlRange(max(1, $drivers->currentPage() - 1), min($drivers->lastPage(), $drivers->currentPage() + 1)) as $page => $url)
                <button type="button" onclick="goToDriverPage({{ $page }})" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border text-xs font-bold transition {{ $page == $drivers->currentPage() ? 'bg-emerald-700 text-white border-emerald-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    {{ $page }}
                </button>
            @endforeach

            <button type="button" onclick="goToDriverPage({{ min($drivers->lastPage(), $drivers->currentPage() + 1) }})" @disabled(!$drivers->hasMorePages()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &gt;
            </button>
            <button type="button" onclick="goToDriverPage({{ $drivers->lastPage() }})" @disabled(!$drivers->hasMorePages()) class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none">
                &raquo;
            </button>
        </div>
    </div>
@endif
