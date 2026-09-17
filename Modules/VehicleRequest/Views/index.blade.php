@extends('layouts.app')

@section('title', 'Travel Requests')
@section('page-title', 'Travel Requests')

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
        $statusTab = request('status', 'pending');
    @endphp

    {{-- Top Gradient Banner --}}
    <div class="mb-4 rounded-2xl bg-gradient-to-br from-[#155f35] via-[#1d8148] to-[#269d59] p-4 text-white shadow-lg shadow-emerald-900/15">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-extrabold tracking-tight">Travel Requests</h2>
                    <p class="truncate text-xs text-white/80">Review and confirm staff travel requests submitted via QR code.</p>
                </div>
            </div>

            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-end">
                <form method="GET" action="{{ route('vehicle-requests.index') }}" id="travel-search-form" class="flex w-full min-w-0 items-center gap-2 md:w-56 xl:w-80">
                    <div class="relative w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input
                            type="text"
                            name="search"
                            id="travel-search-input"
                            value="{{ request('search') }}"
                            placeholder="Search requester, plate, destination..."
                            class="w-full rounded-xl border-0 bg-white py-2 pl-9 pr-4 text-xs text-slate-800 placeholder-slate-400 shadow-inner focus:outline-none focus:ring-2 focus:ring-emerald-300"
                        >
                    </div>
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    @if(request('location'))
                        <input type="hidden" name="location" value="{{ request('location') }}">
                    @endif
                </form>

                {{-- Location filter dropdown --}}
                <div class="relative shrink-0" id="travel-location-dropdown">
                    <button
                        type="button"
                        id="travel-location-menu-btn"
                        onclick="toggleTravelLocationMenu()"
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
                        id="travel-location-menu"
                        class="absolute right-0 top-full z-30 mt-1.5 hidden w-56 overflow-hidden rounded-xl border border-gray-100 bg-white p-1.5 shadow-xl ring-1 ring-black/5"
                    >
                        <a
                            href="{{ route('vehicle-requests.index', array_filter(['status' => request('status'), 'search' => request('search'), 'per_page' => request('per_page')])) }}"
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
                                href="{{ route('vehicle-requests.index', array_filter(['location' => $loc, 'status' => request('status'), 'search' => request('search'), 'per_page' => request('per_page')])) }}"
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
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Requests --}}
        <div class="flex items-center gap-3 rounded-2xl border border-gray-200/80 bg-white p-3.5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-700 text-white shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">TOTAL REQUESTS</p>
                <p class="text-lg font-black text-slate-800 leading-none mt-0.5">{{ $counts['total'] }}</p>
            </div>
        </div>

        {{-- Pending --}}
        <div class="flex items-center gap-3 rounded-2xl border border-gray-200/80 bg-white p-3.5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">PENDING</p>
                <p class="text-lg font-black text-amber-500 leading-none mt-0.5">{{ $counts['pending'] }}</p>
            </div>
        </div>

        {{-- Approved --}}
        <div class="flex items-center gap-3 rounded-2xl border border-gray-200/80 bg-white p-3.5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">APPROVED</p>
                <p class="text-lg font-black text-emerald-600 leading-none mt-0.5">{{ $counts['approved'] }}</p>
            </div>
        </div>

        {{-- Declined --}}
        <div class="flex items-center gap-3 rounded-2xl border border-gray-200/80 bg-white p-3.5 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-500 text-white shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">DECLINED</p>
                <p class="text-lg font-black text-red-500 leading-none mt-0.5">{{ $counts['declined'] }}</p>
            </div>
        </div>
    </div>

    {{-- Filter Pills Row (Pending, Approved, Declined, All) --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <button
            type="button"
            onclick="filterTravelStatus('pending')"
            id="tab-btn-pending"
            class="travel-tab-btn inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-bold transition shadow-xs cursor-pointer {{ $statusTab === 'pending' ? 'bg-emerald-700 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
        >
            <span>Pending</span>
            <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $statusTab === 'pending' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">
                {{ $counts['pending'] }}
            </span>
        </button>

        <button
            type="button"
            onclick="filterTravelStatus('approved')"
            id="tab-btn-approved"
            class="travel-tab-btn inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-bold transition shadow-xs cursor-pointer {{ $statusTab === 'approved' ? 'bg-emerald-700 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
        >
            <span>Approved</span>
            <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $statusTab === 'approved' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">
                {{ $counts['approved'] }}
            </span>
        </button>

        <button
            type="button"
            onclick="filterTravelStatus('declined')"
            id="tab-btn-declined"
            class="travel-tab-btn inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-bold transition shadow-xs cursor-pointer {{ $statusTab === 'declined' ? 'bg-emerald-700 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
        >
            <span>Declined</span>
            <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $statusTab === 'declined' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">
                {{ $counts['declined'] }}
            </span>
        </button>

        <button
            type="button"
            onclick="filterTravelStatus('all')"
            id="tab-btn-all"
            class="travel-tab-btn inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-bold transition shadow-xs cursor-pointer {{ $statusTab === 'all' ? 'bg-emerald-700 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
        >
            <span>All</span>
            <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $statusTab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">
                {{ $counts['total'] }}
            </span>
        </button>
    </div>

    {{-- Content / Requests List --}}
    @if ($requests->isEmpty())
        <div class="rounded-2xl border border-gray-200/80 bg-white py-16 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 border border-slate-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <h3 class="mt-3 text-sm font-bold text-slate-800">No requests found</h3>
            <p class="mt-1 text-xs text-slate-400">Requests submitted by staff will appear here.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-left text-xs">
                    <thead class="bg-gray-50/80 font-bold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-semibold">VEHICLE</th>
                            <th class="px-4 py-3 font-semibold">REFERENCE / TRIP NO.</th>
                            <th class="px-4 py-3 font-semibold">PLATE & TRAVEL DATE</th>
                            <th class="px-4 py-3 font-semibold">OFFICE PROVINCE</th>
                            <th class="px-4 py-3 font-semibold">DRIVER</th>
                            <th class="px-4 py-3 font-semibold">REQUESTER</th>
                            <th class="px-4 py-3 text-right font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-slate-700">
                        @foreach ($requests as $item)
                            <tr class="hover:bg-slate-50/70 transition">
                                {{-- Vehicle Thumbnail --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($item->vehicle?->photo_url)
                                        <img src="{{ $item->vehicle->photo_url }}" alt="{{ $item->vehicle->plate_number }}" class="h-9 w-12 shrink-0 rounded object-cover shadow-sm border border-slate-100">
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
                                </td>

                                {{-- Reference / Trip No. --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($item->trip_ticket_number)
                                        <span class="inline-block rounded bg-emerald-50 px-2 py-0.5 font-mono text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200">
                                            {{ $item->trip_ticket_number }}
                                        </span>
                                    @else
                                        <span class="inline-block rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">
                                            {{ $item->reference_code }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Plate & Travel Date --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    <p class="font-extrabold text-slate-900">{{ $item->vehicle?->plate_number ?? '—' }}</p>
                                    <p class="text-[11px] text-slate-500 font-medium">
                                        {{ $item->travel_date->format('M d') }}
                                        @if($item->travel_date_end && $item->travel_date_end != $item->travel_date)
                                            - {{ $item->travel_date_end->format('d, Y') }}
                                        @else
                                            , {{ $item->travel_date->format('Y') }}
                                        @endif
                                    </p>
                                </td>

                                {{-- Office Province / Location --}}
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600 font-medium">
                                    {{ $item->location ?: '—' }}
                                </td>

                                {{-- Driver --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    @php
                                        $assignedDriver = $item->confirmedDriver ?? $item->vehicle?->assignedDriver;
                                    @endphp
                                    @if ($assignedDriver)
                                        <div class="flex items-center gap-2">
                                            <div class="relative flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-800 text-white text-xs font-bold shadow-sm">
                                                @if ($assignedDriver->avatar_url)
                                                    <img src="{{ $assignedDriver->avatar_url }}" alt="{{ $assignedDriver->name }}" class="h-full w-full object-cover">
                                                @else
                                                    <span>{{ strtoupper(substr($assignedDriver->name, 0, 1)) }}</span>
                                                @endif
                                            </div>
                                            <span class="text-xs font-bold text-slate-900">{{ $assignedDriver->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">No driver</span>
                                    @endif
                                </td>

                                {{-- Requester --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    <p class="font-bold text-slate-900">{{ $item->requester_name }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $item->requester_office ?: $item->destination }}</p>
                                </td>

                                {{-- Actions --}}
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    @if($item->status === 'pending')
                                        <div class="inline-flex items-center gap-1.5">
                                            <form method="POST" action="{{ route('vehicle-requests.status', $item) }}" class="inline-flex items-center gap-1.5">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <select name="confirmed_driver_id" class="rounded-lg border-slate-300 bg-white py-1 pl-2.5 pr-7 text-xs font-semibold text-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                                    <option value="">Select Driver</option>
                                                    @foreach($drivers as $driver)
                                                        <option value="{{ $driver->id }}" @selected($item->vehicle && $item->vehicle->assigned_driver_id === $driver->id)>
                                                            {{ $driver->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-emerald-700 shadow-xs">
                                                    Approve
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('vehicle-requests.status', $item) }}" class="inline-flex">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="declined">
                                                <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-600 hover:text-white shadow-xs">
                                                    Decline
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        @if($item->status === 'approved')
                                            <a href="{{ route('trip-ticket', $item) }}" target="_blank" class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-emerald-700 hover:bg-slate-100">
                                                Trip Ticket
                                            </a>
                                        @else
                                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-bold text-red-700 ring-1 ring-red-200">
                                                Declined
                                            </span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bottom Pagination Bar --}}
        <div class="mt-4 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 font-medium">
                <div>
                    Showing <span class="font-bold text-slate-700">{{ $requests->firstItem() ?? 0 }}</span> to <span class="font-bold text-slate-700">{{ $requests->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-700">{{ $requests->total() }}</span> requests
                </div>

                <div class="flex items-center gap-2">
                    <span>Rows per page:</span>
                    <select id="travel-per-page-select" onchange="changeTravelPerPage(this.value)" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer">
                        @foreach ([10, 15, 20, 25, 50, 75, 100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <a href="{{ $requests->url(1) }}" @if($requests->onFirstPage()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &laquo;
                </a>
                <a href="{{ $requests->previousPageUrl() ?: '#' }}" @if($requests->onFirstPage()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &lt;
                </a>

                @foreach ($requests->getUrlRange(max(1, $requests->currentPage() - 1), min($requests->lastPage(), $requests->currentPage() + 1)) as $page => $url)
                    <a href="{{ $url }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border text-xs font-bold transition {{ $page == $requests->currentPage() ? 'bg-emerald-700 text-white border-emerald-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                        {{ $page }}
                    </a>
                @endforeach

                <a href="{{ $requests->nextPageUrl() ?: '#' }}" @if(!$requests->hasMorePages()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &gt;
                </a>
                <a href="{{ $requests->url($requests->lastPage()) }}" @if(!$requests->hasMorePages()) class="pointer-events-none opacity-30 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500" @else class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 transition hover:bg-slate-50" @endif>
                    &raquo;
                </a>
            </div>
        </div>
    @endif

    <script>
        function toggleTravelLocationMenu() {
            const menu = document.getElementById('travel-location-menu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('travel-location-dropdown');
            const menu = document.getElementById('travel-location-menu');
            if (dropdown && menu && !dropdown.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        function changeTravelPerPage(size) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', size);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }

        function filterTravelStatus(status) {
            const url = new URL(window.location.href);
            if (status === 'pending') {
                url.searchParams.delete('status'); // Keep clean URL for default 'pending' tab
            } else {
                url.searchParams.set('status', status);
            }
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }
    </script>
@endsection
