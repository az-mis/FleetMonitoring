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

    {{-- Page header --}}
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Travel Requests</h1>
            <p class="text-sm text-slate-500">Review and confirm staff travel requests submitted via QR code</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <form method="GET" action="{{ route('vehicle-requests.index') }}" id="travel-search-form" class="flex w-full min-w-0 items-center gap-2 sm:w-72 xl:w-96">
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-600/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        id="travel-search-input"
                        value="{{ request('search') }}"
                        autocomplete="off"
                        placeholder="Search requester, plate, destination..."
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-8 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                </div>
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('location'))
                    <input type="hidden" name="location" value="{{ request('location') }}">
                @endif
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif

                @if(request('search'))
                    <a href="{{ route('vehicle-requests.index', array_filter(['status' => request('status'), 'location' => request('location'), 'per_page' => request('per_page')])) }}" class="shrink-0 text-xs font-semibold text-slate-500 hover:text-slate-700">Clear</a>
                @endif
            </form>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Location filter dropdown --}}
                <div class="relative" id="travel-location-dropdown">
                    <button
                        type="button"
                        id="travel-location-menu-btn"
                        onclick="toggleTravelLocationMenu()"
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
                        id="travel-location-menu"
                        class="absolute left-0 z-20 mt-2 hidden w-64 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5 sm:left-auto sm:right-0"
                    >
                        <a
                            href="{{ route('vehicle-requests.index', array_filter(['status' => request('status'), 'search' => request('search'), 'per_page' => request('per_page')])) }}"
                            class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 {{ empty($selectedLocation) ? 'bg-emerald-50 text-emerald-800' : '' }}"
                        >
                            <span>All Locations</span>
                            @if (empty($selectedLocation))
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </a>
                        @foreach ($locationOptions as $loc)
                            <a
                                href="{{ route('vehicle-requests.index', array_filter(['location' => $loc, 'status' => request('status'), 'search' => request('search'), 'per_page' => request('per_page')])) }}"
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
                            <th class="px-3 py-3 font-semibold">VEHICLE</th>
                            <th class="px-3 py-3 font-semibold">REFERENCE / TRIP NO.</th>
                            <th class="px-3 py-3 font-semibold">PLATE & TRAVEL DATE</th>
                            <th class="px-3 py-3 font-semibold">OFFICE PROVINCE</th>
                            <th class="px-3 py-3 font-semibold">DRIVER</th>
                            <th class="px-3 py-3 font-semibold">REQUESTER</th>
                            <th class="px-3 py-3 font-semibold">PASSENGERS</th>
                            <th class="px-3 py-3 font-semibold">DESTINATION</th>
                            <th class="px-3 py-3 font-semibold">PURPOSE</th>
                            <th class="px-3 py-3 font-semibold">STATUS</th>
                            <th class="px-3 py-3 font-semibold">APPROVER</th>
                            <th class="px-3 py-3 text-right font-semibold">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-slate-700">
                        @foreach ($requests as $item)
                            <tr class="hover:bg-slate-50/70 transition">
                                {{-- Vehicle Thumbnail --}}
                                <td class="whitespace-nowrap px-3 py-3">
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
                                <td class="whitespace-nowrap px-3 py-3">
                                    @if ($item->trip_ticket_number)
                                        <span class="inline-block rounded bg-emerald-50 px-2 py-0.5 font-mono text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200">
                                            {{ $item->trip_ticket_number }}
                                        </span>
                                    @else
                                        <div>
                                            <span class="inline-block rounded bg-indigo-50 px-2 py-0.5 font-mono text-[11px] font-bold text-indigo-700 ring-1 ring-indigo-200">
                                                {{ $item->reference_code }}
                                            </span>
                                            <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Pending approval</p>
                                        </div>
                                    @endif
                                </td>

                                {{-- Plate & Travel Date --}}
                                <td class="whitespace-nowrap px-3 py-3">
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
                                <td class="whitespace-nowrap px-3 py-3 text-slate-600 font-medium">
                                    {{ $item->location ?: '—' }}
                                </td>

                                {{-- Driver --}}
                                <td class="whitespace-nowrap px-3 py-3">
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
                                        <span class="text-slate-400 italic text-[11px]">Unassigned</span>
                                    @endif
                                </td>

                                {{-- Requester --}}
                                <td class="whitespace-nowrap px-3 py-3">
                                    <p class="font-bold text-slate-900">{{ $item->requester_name }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $item->requester_office ?: $item->location }}</p>
                                </td>

                                {{-- Passengers --}}
                                <td class="whitespace-nowrap px-3 py-3">
                                    @php
                                        $passengerList = is_array($item->passengers) ? array_values(array_filter($item->passengers)) : [];
                                        $pCount = count($passengerList);
                                    @endphp
                                    @if ($item->requester_is_passenger || $pCount > 0)
                                        <div class="text-xs">
                                            @if ($item->requester_is_passenger)
                                                <p class="font-semibold text-slate-800">
                                                    {{ $item->requester_name }} <span class="text-slate-400 font-normal">(Requester)</span>
                                                </p>
                                            @endif
                                            @if ($pCount > 0)
                                                <div id="passengers-collapsed-{{ $item->id }}">
                                                    <button
                                                        type="button"
                                                        onclick="document.getElementById('passengers-collapsed-{{ $item->id }}').classList.add('hidden'); document.getElementById('passengers-expanded-{{ $item->id }}').classList.remove('hidden');"
                                                        class="font-bold text-emerald-700 hover:text-emerald-800 cursor-pointer underline text-[11px] block mt-0.5"
                                                    >
                                                        +{{ $pCount }} more
                                                    </button>
                                                </div>
                                                <div id="passengers-expanded-{{ $item->id }}" class="hidden mt-0.5 space-y-0.5">
                                                    @foreach ($passengerList as $pName)
                                                        <p class="text-[11px] font-medium text-slate-700">&bull; {{ $pName }}</p>
                                                    @endforeach
                                                    <button
                                                        type="button"
                                                        onclick="document.getElementById('passengers-expanded-{{ $item->id }}').classList.add('hidden'); document.getElementById('passengers-collapsed-{{ $item->id }}').classList.remove('hidden');"
                                                        class="font-bold text-slate-500 hover:text-slate-700 cursor-pointer underline text-[10px] block pt-0.5"
                                                    >
                                                        Show less
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">None</span>
                                    @endif
                                </td>

                                {{-- Destination --}}
                                <td class="whitespace-nowrap px-3 py-3">
                                    <span class="inline-flex items-center gap-1 font-semibold text-slate-800 text-xs">
                                        <span class="text-rose-500">📍</span> {{ $item->destination }}
                                    </span>
                                </td>

                                {{-- Purpose --}}
                                <td class="whitespace-nowrap px-3 py-3 text-slate-600 font-medium max-w-[140px] truncate" title="{{ $item->purpose }}">
                                    {{ $item->purpose ?: '—' }}
                                </td>

                                {{-- Status --}}
                                <td class="whitespace-nowrap px-3 py-3">
                                    @if($item->status === 'approved')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100/80 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-800 uppercase tracking-wide">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span> APPROVED
                                        </span>
                                    @elseif($item->status === 'declined')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100/80 px-2.5 py-0.5 text-[10px] font-extrabold text-red-800 uppercase tracking-wide">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span> DECLINED
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100/90 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-800 uppercase tracking-wide ring-1 ring-amber-300/50">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-600 animate-pulse"></span> PENDING
                                        </span>
                                    @endif
                                </td>

                                {{-- Approver --}}
                                <td class="whitespace-nowrap px-3 py-3 text-slate-600 font-medium">
                                    @if($item->approvedBy)
                                        <div class="flex items-center gap-1.5">
                                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-800 text-[10px] font-bold text-white shadow-xs">
                                                {{ strtoupper(substr($item->approvedBy->name, 0, 1)) }}
                                            </div>
                                            <span class="text-xs font-bold text-slate-900">{{ $item->approvedBy->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Action --}}
                                <td class="whitespace-nowrap px-3 py-3 text-right">
                                    @php
                                        $modalData = [
                                            'id' => $item->id,
                                            'code' => $item->reference_code,
                                            'requester' => $item->requester_name,
                                            'location' => $item->location,
                                            'is_passenger' => $item->requester_is_passenger,
                                            'office' => $item->requester_office ?: $item->location,
                                            'contact' => $item->requester_contact ?: '—',
                                            'vehicle' => $item->vehicle?->plate_number ? ($item->vehicle->plate_number . ' · ' . $item->vehicle->brand . ' ' . $item->vehicle->model) : 'Unassigned',
                                            'destination' => $item->destination,
                                            'travel_date' => $item->travel_date->format('M d, Y') . ($item->travel_date_end ? (' – ' . $item->travel_date_end->format('M d, Y')) : ''),
                                            'purpose' => $item->purpose,
                                            'passengers' => is_array($item->passengers) ? implode(', ', array_filter($item->passengers)) : '',
                                            'driver_id' => $item->confirmed_driver_id ?: $item->vehicle?->assigned_driver_id,
                                            'driver_name' => $item->confirmedDriver?->name ?: ($item->vehicle?->assignedDriver?->name ?? 'Assigned Driver'),
                                            'status' => $item->status,
                                            'trip_ticket_number' => $item->trip_ticket_number,
                                            'approved_by_name' => $item->approvedBy?->name,
                                            'trip_ticket_url' => $item->trip_ticket_number ? route('trip-ticket', $item) : null,
                                        ];
                                    @endphp

                                    @if($item->status === 'pending')
                                        <button
                                            type="button"
                                            onclick='openReviewModal(@json($modalData))'
                                            class="inline-flex items-center justify-center rounded-lg bg-[#008751] hover:bg-[#007345] px-3.5 py-1.5 text-xs font-bold text-white shadow-xs transition cursor-pointer"
                                        >
                                            Review
                                        </button>
                                    @else
                                        <div class="inline-flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                onclick='openReviewModal(@json($modalData))'
                                                class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                            >
                                                View
                                            </button>
                                            @if($item->status === 'approved')
                                                <a
                                                    href="{{ route('trip-ticket', $item) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 hover:bg-emerald-100 transition cursor-pointer"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="6 9 6 2 18 2 18 9"/>
                                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                                        <rect width="12" height="8" x="6" y="14"/>
                                                    </svg>
                                                    <span>Print</span>
                                                </a>
                                            @endif
                                        </div>
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

    {{-- Review Request Modal --}}
    <div id="review-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-slate-900/60 p-3 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-2xl bg-white p-4 shadow-2xl overflow-hidden max-h-[92vh] flex flex-col">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wide" id="modal-title">Review Request</h3>
                <button type="button" onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600 text-base font-bold cursor-pointer leading-none">&times;</button>
            </div>

            <div class="mt-2.5 overflow-y-auto pr-1 space-y-2.5 text-xs flex-1">
                {{-- Request Reference Code Banner --}}
                <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-blue-600">REQUEST REFERENCE CODE</p>
                            <p class="text-sm font-black font-mono tracking-widest text-slate-900 leading-tight mt-0.5" id="modal-ref-code"></p>
                        </div>
                        <button
                            type="button"
                            onclick="copyModalCode()"
                            class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition cursor-pointer"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                            </svg>
                            <span id="modal-copy-btn-txt">Copy Code</span>
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-1">The requester can use this code to check or ask about this pending request.</p>
                </div>

                {{-- Key-Value Details List with Icons (Compact) --}}
                <div class="grid grid-cols-1 gap-2 pl-0.5">
                    {{-- Requester --}}
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">REQUESTER</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-requester"></p>
                        </div>
                    </div>

                    {{-- Trip Ticket No. (shown for approved) --}}
                    <div id="modal-trip-ticket-row" class="flex items-start gap-2 hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/>
                            <path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">TRIP TICKET NO.</p>
                            <p class="font-bold font-mono text-emerald-700 text-[11px] leading-snug tracking-wide" id="modal-detail-trip-ticket"></p>
                        </div>
                    </div>


                    {{-- Location --}}
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="m12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                            <path d="M2 12h20"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">LOCATION</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-location"></p>
                        </div>
                    </div>

                    {{-- Riding Along --}}
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">RIDING ALONG</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-riding"></p>
                        </div>
                    </div>

                    {{-- Office --}}
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                            <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                            <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
                            <path d="M10 6h4"/>
                            <path d="M10 10h4"/>
                            <path d="M10 14h4"/>
                            <path d="M10 18h4"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">OFFICE</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-office"></p>
                        </div>
                    </div>

                    {{-- Contact --}}
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">CONTACT</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-contact"></p>
                        </div>
                    </div>

                    {{-- Vehicle --}}
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 17h4V5H2v12h3"/>
                            <path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/>
                            <circle cx="7" cy="17" r="2"/>
                            <circle cx="17" cy="17" r="2"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">VEHICLE</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-vehicle"></p>
                        </div>
                    </div>

                    {{-- Destination --}}
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">DESTINATION</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-destination"></p>
                        </div>
                    </div>

                    {{-- Travel Date --}}
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">TRAVEL DATE</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-travel-date"></p>
                        </div>
                    </div>

                    {{-- Purpose --}}
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">PURPOSE</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-purpose"></p>
                        </div>
                    </div>

                    {{-- Passengers --}}
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">PASSENGERS</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-passengers"></p>
                        </div>
                        </div>
                    </div>

                    {{-- Approved By (shown for approved/declined) --}}
                    <div id="modal-approved-by-row" class="flex items-start gap-2 hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">APPROVED BY</p>
                            <p class="font-bold text-slate-900 text-[11px] leading-snug truncate" id="modal-detail-approved-by"></p>
                        </div>
                    </div>
                </div>

                {{-- ── Action Section (two modes: pending / view) ── --}}
                <div id="modal-action-section" class="pt-2 border-t border-gray-100 space-y-2.5">

                    {{-- Status Banner (approved / declined) --}}
                    <div id="modal-status-banner" class="hidden rounded-xl px-3 py-2 text-[11px] font-semibold">
                        <span id="modal-status-banner-text"></span>
                    </div>

                    {{-- MODE 1: Pending — Review Form --}}
                    <div id="modal-pending-actions">
                        <form id="review-form" method="POST" action="" class="space-y-2.5">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" id="modal-status-input" value="approved">

                            {{-- Driver Availability Selection (only when the vehicle has a default driver) --}}
                            <div id="driver-availability-block">
                                <p class="text-[11px] font-bold text-slate-800 mb-1.5">
                                    Is <span id="modal-driver-name" class="font-extrabold"></span> available for this trip?
                                </p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        id="driver-avail-yes-btn"
                                        onclick="setDriverAvailability(true)"
                                        class="rounded-xl border border-gray-200 bg-white py-2 px-2.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                                    >
                                        Yes, available
                                    </button>
                                    <button
                                        type="button"
                                        id="driver-avail-no-btn"
                                        onclick="setDriverAvailability(false)"
                                        class="rounded-xl border border-gray-200 bg-white py-2 px-2.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                                    >
                                        Not available
                                    </button>
                                </div>
                            </div>

                            {{-- Dropdown for selecting alternative driver when needed --}}
                            <div id="modal-driver-select-container" class="hidden">
                                <label id="modal-driver-select-label" class="block text-[11px] font-bold text-slate-700 mb-1">Select Driver</label>
                                <select name="confirmed_driver_id" id="modal-driver-select" class="w-full rounded-xl border border-slate-300 bg-white p-2 text-xs font-semibold text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    <option value="">Select Driver</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" data-location="{{ $driver->location }}">{{ $driver->name }} ({{ $driver->location ?: 'No location' }})</option>
                                    @endforeach
                                </select>
                                <p id="modal-driver-error" class="mt-1 hidden text-[11px] font-semibold text-red-600"></p>
                            </div>

                            {{-- Decline Reason Container --}}
                            <div id="modal-decline-reason-container" class="hidden">
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Reason for Declining</label>
                                <textarea name="decline_reason" rows="2" class="w-full rounded-xl border border-slate-300 bg-white p-2 text-xs text-slate-700 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="State reason..."></textarea>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex gap-2 pt-0.5">
                                <button
                                    type="button"
                                    onclick="triggerDecline()"
                                    id="modal-decline-action-btn"
                                    class="flex-1 rounded-xl border border-red-200 bg-white py-2 text-xs font-bold text-red-500 hover:bg-red-50 transition cursor-pointer text-center"
                                >
                                    Decline
                                </button>
                                <button
                                    type="button"
                                    onclick="openApprovalConfirm()"
                                    id="modal-approve-action-btn"
                                    class="flex-1 rounded-xl bg-[#008751] hover:bg-[#007345] py-2 text-xs font-bold text-white shadow-xs transition cursor-pointer text-center"
                                >
                                    Approve Request
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- MODE 2: View (Approved / Declined) --}}
                    <div id="modal-view-actions" class="hidden space-y-2.5">
                        {{-- Print + Change Driver buttons --}}
                        <div class="flex gap-2">
                            <a
                                id="modal-print-btn"
                                href="#"
                                target="_blank"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-emerald-200 bg-white py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50 transition cursor-pointer"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 6 2 18 2 18 9"/>
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                    <rect width="12" height="8" x="6" y="14"/>
                                </svg>
                                Print Ticket
                            </a>
                            <button
                                type="button"
                                onclick="toggleChangeDriver()"
                                id="modal-change-driver-btn"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                </svg>
                                Change Driver
                            </button>
                        </div>

                        {{-- Change Driver Expandable Section --}}
                        <div id="modal-change-driver-section" class="hidden rounded-xl border border-slate-200 bg-slate-50/60 p-3 space-y-2">
                            <div>
                                <p class="text-[11px] font-extrabold text-slate-800">Update Assigned Driver</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">Select driver for this trip</p>
                            </div>
                            <form id="change-driver-form" method="POST" action="" class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <select name="confirmed_driver_id" id="modal-change-driver-select" class="w-full rounded-xl border border-slate-300 bg-white p-2 text-xs font-semibold text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" data-location="{{ $driver->location }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                                <div class="flex gap-2">
                                    <button type="button" onclick="toggleChangeDriver()" class="flex-1 rounded-xl border border-slate-200 bg-white py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                        Cancel
                                    </button>
                                    <button type="submit" class="flex-1 rounded-xl bg-[#008751] hover:bg-[#007345] py-2 text-xs font-bold text-white shadow-xs transition cursor-pointer">
                                        Save Driver
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- Confirm Request Approval Modal --}}
    <div id="confirm-approval-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl transition-all scale-100">
            <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wide">Confirm Request Approval?</h4>
                <button type="button" onclick="closeApprovalConfirm()" class="text-slate-400 hover:text-slate-600 text-base font-bold cursor-pointer leading-none">&times;</button>
            </div>

            <div class="mt-3.5 space-y-3">
                <div class="flex items-start gap-2.5 rounded-xl border border-emerald-200/80 bg-emerald-50/70 p-3 text-xs text-slate-700">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 font-bold">
                        ⚠️
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-800">
                            Are you sure you want to approve this travel request for <strong id="confirm-modal-requester" class="font-extrabold text-slate-900"></strong>?
                        </p>
                        <div class="mt-2 space-y-0.5 text-[11px] text-slate-600 font-medium">
                            <p>&bull; Vehicle: <span id="confirm-modal-vehicle" class="font-bold text-slate-800"></span></p>
                            <p>&bull; Destination: <span id="confirm-modal-destination" class="font-bold text-slate-800"></span></p>
                            <p>&bull; Assigned Driver: <span id="confirm-modal-driver" class="font-bold text-slate-800"></span></p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 pt-1">
                    <button
                        type="button"
                        onclick="closeApprovalConfirm()"
                        class="flex-1 rounded-xl border border-slate-200 bg-white py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        onclick="submitApprovalForm()"
                        class="flex-1 rounded-xl bg-[#008751] hover:bg-[#007345] py-2 text-xs font-bold text-white shadow-xs transition cursor-pointer"
                    >
                        Yes, Approve Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentModalCode = '';

        function openReviewModal(data) {
            currentModalCode = data.code || '';

            // ── Basic detail fields ──────────────────────────────────────────
            document.getElementById('modal-ref-code').textContent        = data.code || '—';
            document.getElementById('modal-detail-requester').textContent = data.requester || '—';
            document.getElementById('modal-detail-location').textContent  = data.location || '—';
            document.getElementById('modal-detail-riding').textContent    = data.is_passenger ? 'Yes — requester is also a passenger' : 'No';
            document.getElementById('modal-detail-office').textContent    = data.office || '—';
            document.getElementById('modal-detail-contact').textContent   = data.contact || '—';
            document.getElementById('modal-detail-vehicle').textContent   = data.vehicle || '—';
            document.getElementById('modal-detail-destination').textContent = data.destination || '—';
            document.getElementById('modal-detail-travel-date').textContent = data.travel_date || '—';
            document.getElementById('modal-detail-purpose').textContent   = data.purpose || '—';
            document.getElementById('modal-detail-passengers').textContent = data.passengers || 'None';
            document.getElementById('modal-driver-name').textContent      = data.driver_name || 'Assigned Driver';

            const isPending = !data.status || data.status === 'pending';

            // ── Modal title ──────────────────────────────────────────────────
            document.getElementById('modal-title').textContent = isPending ? 'Review Request' : 'Request Details';

            // ── Trip Ticket No. row ──────────────────────────────────────────
            const tripTicketRow = document.getElementById('modal-trip-ticket-row');
            const tripTicketEl  = document.getElementById('modal-detail-trip-ticket');
            if (data.trip_ticket_number) {
                tripTicketEl.textContent = data.trip_ticket_number;
                tripTicketRow.classList.remove('hidden');
            } else {
                tripTicketRow.classList.add('hidden');
            }

            // ── Approved By row ──────────────────────────────────────────────
            const approvedByRow = document.getElementById('modal-approved-by-row');
            const approvedByEl  = document.getElementById('modal-detail-approved-by');
            if (data.approved_by_name) {
                approvedByEl.textContent = data.approved_by_name;
                approvedByRow.classList.remove('hidden');
            } else {
                approvedByRow.classList.add('hidden');
            }

            // ── Status banner ────────────────────────────────────────────────
            const banner     = document.getElementById('modal-status-banner');
            const bannerText = document.getElementById('modal-status-banner-text');
            if (!isPending) {
                const label = data.status === 'approved' ? 'Approved' : 'Declined';
                let parts = [label];
                if (data.driver_name && data.driver_name !== 'Assigned Driver') parts.push('Driver: ' + data.driver_name);
                if (data.approved_by_name) parts.push('Approver: ' + data.approved_by_name);
                bannerText.textContent = parts.join(' · ');
                if (data.status === 'approved') {
                    banner.className = 'rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-semibold text-emerald-800';
                } else {
                    banner.className = 'rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-[11px] font-semibold text-red-800';
                }
                banner.classList.remove('hidden');
            } else {
                banner.classList.add('hidden');
            }

            // ── Toggle action modes ──────────────────────────────────────────
            const pendingActions = document.getElementById('modal-pending-actions');
            const viewActions    = document.getElementById('modal-view-actions');
            const actionSection  = document.getElementById('modal-action-section');
            actionSection.classList.remove('hidden');

            if (isPending) {
                pendingActions.classList.remove('hidden');
                viewActions.classList.add('hidden');
            } else {
                pendingActions.classList.add('hidden');
                viewActions.classList.remove('hidden');

                // Set print button URL
                const printBtn = document.getElementById('modal-print-btn');
                if (printBtn) printBtn.href = data.trip_ticket_url || '#';
                // Hide print button for declined
                printBtn.style.display = data.status === 'approved' ? '' : 'none';

                // Set change-driver form action
                const cdForm = document.getElementById('change-driver-form');
                if (cdForm) cdForm.action = `/vehicle-requests/${data.id}/status`;

                // Reset change driver panel
                document.getElementById('modal-change-driver-section').classList.add('hidden');

                // Filter change-driver select by location
                const cdSelect = document.getElementById('modal-change-driver-select');
                Array.from(cdSelect.options).forEach(opt => {
                    if (!opt.value) { opt.hidden = false; return; }
                    const drvLoc = opt.getAttribute('data-location');
                    opt.hidden = !!(data.location && drvLoc !== data.location && String(opt.value) !== String(data.driver_id));
                });
                if (data.driver_id) cdSelect.value = data.driver_id;
            }

            // ── Pending-form driver select filter ────────────────────────────
            const reviewForm = document.getElementById('review-form');
            reviewForm.action = `/vehicle-requests/${data.id}/status`;

            const driverSelect = document.getElementById('modal-driver-select');
            Array.from(driverSelect.options).forEach(opt => {
                if (!opt.value) { opt.hidden = false; return; }
                const drvLoc = opt.getAttribute('data-location');
                opt.hidden = !(!data.location || drvLoc === data.location || String(opt.value) === String(data.driver_id));
            });
            if (data.driver_id) { driverSelect.value = data.driver_id; } else { driverSelect.value = ''; }

            // Reset pending-form state
            window.reviewDefaultDriverId = data.driver_id ? String(data.driver_id) : '';
            setDriverAvailability(true);
            const noDefaultDriver = !window.reviewDefaultDriverId;
            const driverLabel = document.getElementById('modal-driver-select-label');
            document.getElementById('driver-availability-block').classList.toggle('hidden', noDefaultDriver);
            if (noDefaultDriver) {
                // No default driver: the reviewer MUST pick one, so skip the yes/no question.
                document.getElementById('modal-driver-select-container').classList.remove('hidden');
                driverLabel.innerHTML = 'Assign a driver for this trip <span class="text-red-500">*</span> <span class="font-normal text-slate-500">&mdash; this vehicle has no default driver</span>';
            } else {
                driverLabel.textContent = 'Select Driver';
            }
            document.getElementById('modal-driver-error').classList.add('hidden');
            driverSelect.onchange = () => document.getElementById('modal-driver-error').classList.add('hidden');
            document.getElementById('modal-decline-reason-container').classList.add('hidden');
            document.getElementById('modal-status-input').value = 'approved';
            const declineBtn = document.getElementById('modal-decline-action-btn');
            if (declineBtn) {
                declineBtn.textContent = 'Decline';
                declineBtn.type = 'button';
                declineBtn.className = 'flex-1 rounded-xl border border-red-200 bg-white py-2 text-xs font-bold text-red-500 hover:bg-red-50 transition cursor-pointer text-center';
            }

            document.getElementById('review-modal').classList.remove('hidden');
        }

        function toggleChangeDriver() {
            const section = document.getElementById('modal-change-driver-section');
            section.classList.toggle('hidden');
        }

        function closeReviewModal() {
            document.getElementById('review-modal').classList.add('hidden');
        }

        function openApprovalConfirm() {
            // A driver must be chosen whenever the picker is showing: either the vehicle
            // has no default driver, or the reviewer said the default driver is not available.
            const pickerBox = document.getElementById('modal-driver-select-container');
            const pickerSel = document.getElementById('modal-driver-select');
            const pickerErr = document.getElementById('modal-driver-error');
            if (!pickerBox.classList.contains('hidden') && !pickerSel.value) {
                pickerErr.textContent = window.reviewDefaultDriverId
                    ? 'Please select the substitute driver for this trip.'
                    : 'Please select a driver before approving. This vehicle has no default driver.';
                pickerErr.classList.remove('hidden');
                pickerSel.focus();
                return;
            }
            pickerErr.classList.add('hidden');

            document.getElementById('confirm-modal-requester').textContent = document.getElementById('modal-detail-requester').textContent;
            document.getElementById('confirm-modal-vehicle').textContent = document.getElementById('modal-detail-vehicle').textContent;
            document.getElementById('confirm-modal-destination').textContent = document.getElementById('modal-detail-destination').textContent;

            const isYesDriver = !document.getElementById('modal-driver-select-container').classList.contains('hidden');
            let driverName = document.getElementById('modal-driver-name').textContent;
            if (isYesDriver) {
                const sel = document.getElementById('modal-driver-select');
                if (sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].text) {
                    driverName = sel.options[sel.selectedIndex].text.split('(')[0].trim();
                }
            }
            document.getElementById('confirm-modal-driver').textContent = driverName;

            document.getElementById('confirm-approval-modal').classList.remove('hidden');
        }

        function closeApprovalConfirm() {
            document.getElementById('confirm-approval-modal').classList.add('hidden');
        }

        function submitApprovalForm() {
            document.getElementById('modal-status-input').value = 'approved';
            document.getElementById('review-form').submit();
        }

        function copyModalCode() {
            if (!currentModalCode) return;
            navigator.clipboard.writeText(currentModalCode).then(() => {
                const txt = document.getElementById('modal-copy-btn-txt');
                if (txt) {
                    txt.textContent = 'Copied!';
                    setTimeout(() => { txt.textContent = 'Copy Code'; }, 2000);
                }
            }).catch(() => {
                alert('Code: ' + currentModalCode);
            });
        }

        function setDriverAvailability(isAvailable) {
            const yesBtn = document.getElementById('driver-avail-yes-btn');
            const noBtn = document.getElementById('driver-avail-no-btn');
            const selectContainer = document.getElementById('modal-driver-select-container');
            const pickSel = document.getElementById('modal-driver-select');
            const defId = window.reviewDefaultDriverId || '';
            document.getElementById('modal-driver-error').classList.add('hidden');
            // "Not available" means someone ELSE must drive: the default driver can't be re-picked.
            Array.from(pickSel.options).forEach((o) => { o.disabled = !isAvailable && !!o.value && o.value === defId; });
            if (isAvailable) { if (defId) pickSel.value = defId; }
            else if (pickSel.value === defId) { pickSel.value = ''; }

            if (isAvailable) {
                yesBtn.className = 'rounded-xl border border-emerald-500 bg-emerald-50 py-2.5 px-3 text-xs font-bold text-emerald-800 shadow-xs transition cursor-pointer';
                noBtn.className = 'rounded-xl border border-gray-200 bg-white py-2.5 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer';
                selectContainer.classList.add('hidden');
            } else {
                noBtn.className = 'rounded-xl border border-amber-500 bg-amber-50 py-2.5 px-3 text-xs font-bold text-amber-800 shadow-xs transition cursor-pointer';
                yesBtn.className = 'rounded-xl border border-gray-200 bg-white py-2.5 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer';
                selectContainer.classList.remove('hidden');
            }
        }

        function triggerDecline() {
            const reasonContainer = document.getElementById('modal-decline-reason-container');
            const statusInput = document.getElementById('modal-status-input');
            const form = document.getElementById('review-form');

            if (reasonContainer.classList.contains('hidden')) {
                reasonContainer.classList.remove('hidden');
                statusInput.value = 'declined';
                document.getElementById('modal-decline-action-btn').textContent = 'Confirm Decline';
                document.getElementById('modal-decline-action-btn').type = 'submit';
                document.getElementById('modal-decline-action-btn').className = 'flex-1 rounded-xl bg-red-600 hover:bg-red-700 py-2.5 text-xs font-bold text-white shadow-xs transition cursor-pointer text-center';
            }
        }

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
