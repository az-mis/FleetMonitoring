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
                {{-- Location filter dropdown — Super Admin only --}}
                @if(auth()->user()?->role?->value === 'super_admin')
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
                @endif

                {{-- New Request Button --}}
                <button
                    type="button"
                    onclick="openManualRequestModal()"
                    class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-[#005a36] px-4 text-xs font-extrabold text-white shadow-sm hover:bg-[#00432a] transition cursor-pointer"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>New Request</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Manual Request Created Flash Banner --}}
    @if(session('manual_request_created'))
        @php $createdTrip = session('manual_request_created'); @endphp
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-300 bg-emerald-50/90 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-xs font-black text-emerald-950">Trip Request Created & Approved Successfully!</h4>
                    <p class="text-xs text-emerald-800">
                        Trip Ticket No. <strong class="font-extrabold font-mono text-emerald-900">{{ $createdTrip['ticket'] }}</strong> for <span class="font-bold">{{ $createdTrip['requester'] }}</span>.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ $createdTrip['ticket_url'] }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                    Print Trip Ticket
                </a>
            </div>
        </div>
    @endif

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

    {{-- Manual Request Entry Modal --}}
    <div id="manual-request-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-3 sm:p-4 backdrop-blur-xs overflow-y-auto">
        <div class="relative w-full max-w-3xl rounded-2xl bg-white shadow-2xl border border-slate-200 my-6 overflow-hidden max-h-[90vh] flex flex-col">
            {{-- Header (Clean, Crisp, High-Contrast) --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white shrink-0">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <line x1="9" y1="15" x2="15" y2="15"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-black text-slate-900 tracking-tight">New Manual Trip Request</h3>
                        <p class="text-xs font-medium text-slate-500">Create and immediately approve a trip ticket on behalf of staff (chat, text, or call).</p>
                    </div>
                </div>
                <button type="button" onclick="closeManualRequestModal()" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer" aria-label="Close modal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('vehicle-requests.admin-create') }}" id="manual-request-form" class="p-6 space-y-4 overflow-y-auto flex-1">
                @csrf
                <input type="hidden" name="_action" value="admin_manual_create">

                @if($errors->any() && old('_action') === 'admin_manual_create')
                    <div class="rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700 shadow-xs">
                        <div class="font-bold mb-1">Please fix the following issues:</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Section 1: Requester Details --}}
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4">
                    <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800 mb-3 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>
                        </svg>
                        Requester Information
                    </h4>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Requester Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="requester_name" value="{{ old('requester_name') }}" required placeholder="e.g. Juan Dela Cruz" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>

                        {{-- Searchable Office / Division / Section Dropdown --}}
                        <div class="relative" id="admin-office-dropdown-container">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Office / Division / Section</label>

                            {{-- Hidden input submitted with form --}}
                            <input type="hidden" name="requester_office" id="admin-requester-office-input" value="{{ old('requester_office') }}">

                            {{-- Trigger Button --}}
                            <button
                                type="button"
                                id="admin-office-dropdown-trigger"
                                onclick="toggleAdminOfficeDropdown()"
                                class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 transition cursor-pointer"
                            >
                                <span id="admin-office-dropdown-label" class="truncate {{ old('requester_office') ? 'text-slate-800' : 'text-slate-400' }}">
                                    {{ old('requester_office') ?: '— Select office / division —' }}
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform duration-200" id="admin-office-dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>

                            {{-- Dropdown Panel --}}
                            <div
                                id="admin-office-dropdown-panel"
                                class="absolute left-0 right-0 top-full z-50 mt-1 hidden rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
                            >
                                {{-- Search --}}
                                <div class="relative mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                                    </svg>
                                    <input
                                        type="text"
                                        id="admin-office-search-input"
                                        oninput="filterAdminOfficeOptions()"
                                        placeholder="Search office..."
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none"
                                    >
                                </div>

                                {{-- Options --}}
                                <div id="admin-office-options-list" class="max-h-52 overflow-y-auto space-y-0.5 pr-0.5">
                                    <div
                                        onclick="selectAdminOffice('', '— Select office / division —')"
                                        class="admin-office-option flex cursor-pointer items-center rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-slate-50 transition"
                                        data-label="— select office / division —"
                                    >
                                        <span>— Select office / division —</span>
                                    </div>
                                    @php
                                        $adminOffices = [
                                            ['value' => 'ORED', 'label' => 'Office of the Regional Executive Director (ORED)'],
                                            ['value' => 'RAFIS', 'label' => 'Regional Agriculture and Fisheries Information Section (RAFIS)'],
                                            ['value' => 'ORTDO', 'label' => 'Office of the Regional Technical Director for Operations (ORTDO)'],
                                            ['value' => 'FOD', 'label' => 'Field Operations Division (FOD)'],
                                            ['value' => 'FOD-RICE', 'label' => 'FOD - Rice Banner Program'],
                                            ['value' => 'FOD-CORN', 'label' => 'FOD - Corn Banner Program'],
                                            ['value' => 'FOD-LIVESTOCK', 'label' => 'FOD - Livestock Program'],
                                            ['value' => 'FOD-HVCDP', 'label' => 'FOD - High Value Crops Development Program (HVCDP)'],
                                            ['value' => 'FOD-IDS', 'label' => 'FOD - Institutional Development Section (IDS)'],
                                            ['value' => 'FOD-DRRM', 'label' => 'FOD - Disaster Risk Reduction and Management (DRRM)'],
                                            ['value' => 'FOD-RSBSA', 'label' => 'FOD - Registry System for Basic Sectors in Agriculture (RSBSA)'],
                                            ['value' => 'FOD-SAAD', 'label' => 'FOD - Special Area for Agricultural Development (SAAD)'],
                                            ['value' => 'FOD-4KS', 'label' => 'FOD - Kabuhayan at Kaunlaran ng Kababayang Katututbo (4Ks)'],
                                            ['value' => 'FOD-NUPAP', 'label' => 'FOD - National Urban and Peri-Urban Agriculture Program (NUPAP)'],
                                            ['value' => 'FOD-F2C2', 'label' => 'FOD - Farm and Fisheries Clustering and Consolidation Program (F2C2)'],
                                            ['value' => 'FOD-PAFES', 'label' => 'FOD - Province-led Agriculture and Fisheries Extension Systems (PAFES)'],
                                            ['value' => 'FOD-HFIDP', 'label' => 'FOD - Halal Food Industry Development Program (HFIDP)'],
                                            ['value' => 'FOD-PRDP', 'label' => 'FOD - Philippine Rural Development Program (PRDP)'],
                                            ['value' => 'ORTDRR', 'label' => 'Office of the Regional Technical Director for Research & Regulations (ORTDRR)'],
                                            ['value' => 'RD', 'label' => 'Research Division (RD)'],
                                            ['value' => 'ORMAES', 'label' => 'Oriental Mindoro Agricultural Experiment Station (ORMAES)'],
                                            ['value' => 'PRES', 'label' => 'Palawan Research Experiment Station (PRES)'],
                                            ['value' => 'PRES-LRC', 'label' => 'PRES - Livestock Resource Center (LRC)'],
                                            ['value' => 'PRES-DPDC', 'label' => 'PRES - Dairy Production and Development Center (DPDC)'],
                                            ['value' => 'PRES-PAC', 'label' => 'PRES - Palawan Agricultural Center (PAC)'],
                                            ['value' => 'PRES-ABOABO', 'label' => 'PRES - Abo-Abo Rural Agricultural Center'],
                                            ['value' => 'REGDIV', 'label' => 'Regulatory Division'],
                                            ['value' => 'REGDIV-OAP', 'label' => 'Regulatory Division - Organic Agriculture Program'],
                                            ['value' => 'AFD', 'label' => 'Administrative and Finance Division (AFD)'],
                                            ['value' => 'AFD-ACCTG', 'label' => 'AFD - Accounting Section'],
                                            ['value' => 'AFD-CASH', 'label' => 'AFD - Cashiering Unit'],
                                            ['value' => 'AFD-BUDGET', 'label' => 'AFD - Budget Section'],
                                            ['value' => 'AFD-HRS', 'label' => 'AFD - Human Resource Section'],
                                            ['value' => 'AFD-RECORDS', 'label' => 'AFD - Records Section'],
                                            ['value' => 'AFD-GSS', 'label' => 'AFD - General Services Section'],
                                            ['value' => 'AFD-PSU', 'label' => 'AFD - Property & Supply Unit'],
                                            ['value' => 'BAC', 'label' => 'Bids & Awards Committee (BAC)'],
                                            ['value' => 'PMED', 'label' => 'Planning, Monitoring & Evaluation Division (PMED)'],
                                            ['value' => 'MIS', 'label' => 'Management Information Systems Section (MIS)'],
                                            ['value' => 'RAFC', 'label' => 'Regional Agricultural and Fishery Council (RAFC)'],
                                            ['value' => 'AMAD', 'label' => 'Agribusiness & Marketing Assistance Division (AMAD)'],
                                            ['value' => 'ILD', 'label' => 'Integrated Laboratory Division (ILD)'],
                                            ['value' => 'ILD-RSL', 'label' => 'ILD - Regional Soils Laboratory'],
                                            ['value' => 'ILD-RCPC', 'label' => 'ILD - Regional Crop Protection Center (RCPC)'],
                                            ['value' => 'RAED', 'label' => 'Regional Agricultural Engineering Division (RAED)'],
                                            ['value' => 'APCO-OCCIDENTAL', 'label' => 'APCO - Occidental Mindoro (San Jose)'],
                                            ['value' => 'APCO-ORIENTAL', 'label' => 'APCO - Oriental Mindoro (Calapan City)'],
                                            ['value' => 'APCO-MARINDUQUE', 'label' => 'APCO - Marinduque (Boac)'],
                                            ['value' => 'APCO-ROMBLON', 'label' => 'APCO - Romblon (Odiongan)'],
                                            ['value' => 'APCO-PALAWAN', 'label' => 'APCO - Palawan (Puerto Princesa City)'],
                                            ['value' => 'COA', 'label' => 'Commission on Audit (COA)'],
                                        ];
                                    @endphp
                                    @foreach($adminOffices as $office)
                                        <div
                                            onclick="selectAdminOffice('{{ addslashes($office['label']) }}')"
                                            class="admin-office-option flex cursor-pointer items-center rounded-lg px-3 py-2 text-xs text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition"
                                            data-label="{{ strtolower($office['label'] . ' ' . $office['value']) }}"
                                        >
                                            <span>{{ $office['label'] }}</span>
                                        </div>
                                    @endforeach
                                    <div id="admin-no-office-found" class="hidden px-3 py-4 text-center text-xs text-slate-400">
                                        No office found matching search.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Contact Number <span class="text-red-500">*</span></label>
                            <input type="text" name="requester_contact" value="{{ old('requester_contact') }}" required placeholder="09XXXXXXXXX (11 digits)" pattern="^09\d{9}$" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>

                        <div class="flex items-center sm:pt-6">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="requester_is_passenger" value="1" @checked(old('requester_is_passenger', true)) class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-semibold text-slate-700">Requester is also a passenger</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Trip & Destination --}}
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4">
                    <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800 mb-3 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        Travel & Destination
                    </h4>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Pick-up Location / Station <span class="text-red-500">*</span></label>
                            @php
                                $currentUser = auth()->user();
                                $isSuperAdmin = $currentUser?->role?->value === 'super_admin';
                                $userLoc = $currentUser?->location;
                                $activeLoc = $userLoc ?: ($selectedLocation ?: 'Quezon City Satellite Office');
                            @endphp
                            <select name="location" id="manual-req-location" onchange="loadManualStationAvailability(); loadAdminDestinations();" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @if($isSuperAdmin)
                                    @foreach($locationOptions as $loc)
                                        <option value="{{ $loc }}" @selected(old('location', $activeLoc) === $loc)>{{ $loc }}</option>
                                    @endforeach
                                @else
                                    <option value="{{ $userLoc }}" selected>{{ $userLoc }}</option>
                                @endif
                            </select>
                        </div>

                        {{-- Searchable Destination Dropdown with Distance Badges --}}
                        <div class="relative" id="admin-dest-dropdown-container">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Destination <span class="text-red-500">*</span></label>
                            
                            {{-- Hidden inputs submitted with form --}}
                            <input type="hidden" name="destination" id="admin-dest-name-input" value="{{ old('destination') }}" required>
                            <input type="hidden" name="distance_km" id="admin-dest-distance-input" value="{{ old('distance_km') }}">

                            {{-- Trigger Button --}}
                            <button
                                type="button"
                                id="admin-dest-dropdown-trigger"
                                onclick="toggleAdminDestDropdown()"
                                class="flex w-full items-center justify-between rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 transition cursor-pointer"
                            >
                                <span id="admin-dest-dropdown-label" class="truncate {{ old('destination') ? 'text-slate-800 font-semibold' : 'text-slate-400' }}">
                                    @if(old('destination'))
                                        {{ old('destination') }} {{ old('distance_km') ? '('.old('distance_km').' kms)' : '' }}
                                    @else
                                        — Select destination —
                                    @endif
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform duration-200" id="admin-dest-dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>

                            {{-- Dropdown Panel --}}
                            <div
                                id="admin-dest-dropdown-panel"
                                class="absolute left-0 right-0 top-full z-50 mt-1 hidden rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
                            >
                                {{-- Search --}}
                                <div class="relative mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                                    </svg>
                                    <input
                                        type="text"
                                        id="admin-dest-search-input"
                                        oninput="filterAdminDestOptions()"
                                        placeholder="Search destination..."
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-3 text-xs text-slate-700 placeholder:text-slate-400 focus:border-emerald-400 focus:outline-none"
                                    >
                                </div>

                                {{-- Options List --}}
                                <div id="admin-dest-options-list" class="max-h-52 overflow-y-auto space-y-0.5 pr-0.5">
                                    {{-- Dynamically loaded via loadAdminDestinations() --}}
                                </div>
                            </div>

                            {{-- Fallback Manual Input (Shown if "Other" is selected) --}}
                            <div id="admin-dest-manual-container" class="mt-2 hidden">
                                <input
                                    type="text"
                                    id="admin-dest-manual-input"
                                    placeholder="Type custom destination..."
                                    oninput="onAdminDestManualInput(this.value)"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none"
                                >
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Purpose of Travel <span class="text-red-500">*</span></label>
                            <textarea name="purpose" rows="2" required placeholder="State official purpose or mission of travel..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('purpose') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Section 3: Interactive Calendar & Date Selection --}}
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4">
                    <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800 mb-2 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/>
                        </svg>
                        Travel Dates & Schedule Availability
                    </h4>
                    <p class="text-[11px] text-slate-500 mb-3">Click on the calendar to select travel dates, then pick an available vehicle below.</p>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 mb-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Travel Date (From) <span class="text-red-500">*</span></label>
                            <input type="date" name="travel_date" id="manual-req-travel-date" value="{{ old('travel_date', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Travel Date (To) <span class="text-red-500">*</span></label>
                            <input type="date" name="travel_date_end" id="manual-req-travel-date-end" value="{{ old('travel_date_end', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>

                    {{-- Mini-Calendar Widget matching QR Code Form --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-xs">
                        <div class="mb-2 flex items-center justify-between">
                            <button type="button" id="manual-cal-prev" onclick="changeManualMonth(-1)" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer">‹</button>
                            <span id="manual-cal-month-label" class="text-xs font-extrabold text-slate-800"></span>
                            <button type="button" id="manual-cal-next" onclick="changeManualMonth(1)" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer">›</button>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center">
                            @foreach(['S','M','T','W','T','F','S'] as $d)
                                <div class="py-0.5 text-[10px] font-extrabold text-slate-400">{{ $d }}</div>
                            @endforeach
                        </div>
                        <div id="manual-cal-grid" class="grid grid-cols-7 gap-1 text-center mt-1"></div>
                        
                        <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-2 text-[10px] font-semibold text-slate-600">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm border border-slate-300 bg-white"></span> Available</span>
                                <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm bg-emerald-600"></span> Approved Trip</span>
                                <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm bg-amber-500"></span> Pending Request</span>
                                <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm bg-red-600"></span> Maintenance</span>
                            </div>
                            <span class="text-[10px] text-slate-400">Click same date twice for 1-day trip</span>
                        </div>
                    </div>
                </div>

                {{-- Section 4: Available Vehicles Cards & Driver --}}
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4">
                    <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800 mb-2 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
                        </svg>
                        Vehicle & Driver Selection
                    </h4>
                    
                    <p id="manual-vehicle-hint" class="text-xs font-semibold text-slate-700 mb-2.5"></p>
                    <input type="hidden" name="vehicle_id" id="manual-vehicle-id-input" value="{{ old('vehicle_id') }}" required>

                    {{-- Vehicle Cards Grid --}}
                    <div id="manual-vehicle-cards" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-60 overflow-y-auto pr-1"></div>

                    {{-- Driver Assignment Dropdown --}}
                    <div class="mt-4 pt-3 border-t border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                Designated Driver <span class="text-red-500">*</span>
                            </label>
                            <select name="confirmed_driver_id" id="manual-req-driver" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <option value="">— Select Driver —</option>
                            </select>
                            <p class="mt-1 text-[10.5px] text-slate-500">
                                Automatically set to the chosen vehicle's default driver. You can change this if designating a substitute driver.
                            </p>
                        </div>

                        @if($isSuperAdmin)
                            <div class="sm:col-span-2 pt-1">
                                <label class="inline-flex items-center gap-2 cursor-pointer bg-amber-50 border border-amber-200 rounded-lg p-2.5">
                                    <input type="checkbox" name="override_conflict" value="1" class="h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                    <span class="text-xs font-semibold text-amber-900">
                                        Super Admin Override: Force approval even if there is a schedule conflict on this vehicle
                                    </span>
                                </label>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Section 5: Other Passengers --}}
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            Other Authorized Passengers
                        </h4>
                        <button type="button" onclick="addManualPassengerRow()" class="inline-flex items-center gap-1 rounded-lg border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 hover:bg-emerald-100 transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            Add Passenger
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500 mb-2">List any staff traveling with the team.</p>

                    <div id="manual-passengers-container" class="space-y-2"></div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeManualRequestModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 px-5 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>Create & Approve Trip Ticket</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const manualStationUrl = "{{ route('request-vehicle.station-availability') }}";
        const allAvailableDrivers = @json($allDrivers ?? []);
        let manualStationVehicles = [];
        let manualRangeConfirmed = true;
        let manualPickingEnd = false;
        let manualViewYear = new Date().getFullYear();
        let manualViewMonth = new Date().getMonth();

        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const fmtDay = (k) => new Date(k + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        const fmtRange = (a, b) => (a === b ? fmtDay(a) : `${fmtDay(a)} – ${fmtDay(b)}`);

        function toKey(y, m, d) {
            return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        }

        function eachDateKey(startStr, endStr, cb) {
            const start = new Date(startStr + 'T00:00:00');
            const end = endStr ? new Date(endStr + 'T00:00:00') : start;
            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                cb(toKey(d.getFullYear(), d.getMonth(), d.getDate()));
            }
        }

        const RANK = { pending: 1, approved: 2, maintenance: 3 };

        function manualStationDayMap() {
            const map = new Map();
            manualStationVehicles.forEach((v) => {
                const perVehicle = new Map();
                (v.bookings || []).forEach((b) => eachDateKey(b.start, b.end, (key) => {
                    if ((RANK[b.status] || 0) > (RANK[perVehicle.get(key)] || 0)) perVehicle.set(key, b.status);
                }));
                perVehicle.forEach((status, key) => {
                    const cur = map.get(key) || { worst: null, busy: 0 };
                    cur.busy += 1;
                    if ((RANK[status] || 0) > (RANK[cur.worst] || 0)) cur.worst = status;
                    map.set(key, cur);
                });
            });
            return map;
        }

        function manualConflictFor(vehicle, start, end) {
            const out = { maintenance: null, approved: null, pending: null };
            (vehicle.bookings || []).forEach((b) => {
                if (b.start <= end && start <= b.end && !out[b.status]) {
                    out[b.status] = b;
                }
            });
            return out;
        }

        function renderManualCalendar() {
            const startInput = document.getElementById('manual-req-travel-date');
            const endInput = document.getElementById('manual-req-travel-date-end');
            const grid = document.getElementById('manual-cal-grid');
            const label = document.getElementById('manual-cal-month-label');
            if (!grid || !label || !startInput) return;

            const map = manualStationDayMap();
            const start = startInput.value;
            const end = endInput && endInput.value && endInput.value >= start ? endInput.value : start;
            const range = start ? { start, end } : null;

            const firstOfMonth = new Date(manualViewYear, manualViewMonth, 1);
            const daysInMonth = new Date(manualViewYear, manualViewMonth + 1, 0).getDate();
            label.textContent = firstOfMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

            const colors = { maintenance: '#dc2626', approved: '#16a34a', pending: '#d97706' };

            let html = '';
            for (let i = 0; i < firstOfMonth.getDay(); i++) {
                html += '<div class="h-7"></div>';
            }
            for (let day = 1; day <= daysInMonth; day++) {
                const key = toKey(manualViewYear, manualViewMonth, day);
                const info = map.get(key);
                const inRange = range && key >= range.start && key <= range.end;
                const bg = inRange ? '#008751' : (info ? colors[info.worst] : '#ffffff');
                const color = inRange || info ? '#ffffff' : '#334155';
                const ring = inRange ? 'ring-2 ring-emerald-500 font-extrabold shadow-xs' : 'border border-slate-200';
                
                html += `
                    <button type="button" onclick="onManualCalDateClick('${key}')" class="h-7 w-full rounded-md text-[11px] font-bold flex items-center justify-center transition cursor-pointer hover:opacity-90 ${ring}" style="background-color:${bg};color:${color};">
                        ${day}
                    </button>
                `;
            }
            grid.innerHTML = html;
        }

        function onManualCalDateClick(key) {
            const startInput = document.getElementById('manual-req-travel-date');
            const endInput = document.getElementById('manual-req-travel-date-end');
            if (!startInput || !endInput) return;

            if (manualPickingEnd && startInput.value && key >= startInput.value) {
                manualRangeConfirmed = true;
                endInput.value = key;
                manualPickingEnd = false;
            } else {
                manualRangeConfirmed = false;
                startInput.value = key;
                endInput.value = key;
                manualPickingEnd = true;
            }

            renderManualCalendar();
            renderManualVehicleCards();
        }

        function changeManualMonth(delta) {
            const next = new Date(manualViewYear, manualViewMonth + delta, 1);
            manualViewYear = next.getFullYear();
            manualViewMonth = next.getMonth();
            renderManualCalendar();
        }

        const PLACEHOLDER_CAR_SVG = `<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>`;

        function renderManualVehicleCards() {
            const cardsBox = document.getElementById('manual-vehicle-cards');
            const hintBox = document.getElementById('manual-vehicle-hint');
            const vehicleIdInput = document.getElementById('manual-vehicle-id-input');
            const startInput = document.getElementById('manual-req-travel-date');
            const endInput = document.getElementById('manual-req-travel-date-end');
            if (!cardsBox) return;

            if (!manualStationVehicles.length) {
                if (hintBox) hintBox.textContent = 'No vehicles registered at this station yet.';
                cardsBox.innerHTML = '<p class="col-span-full py-4 text-center text-xs text-slate-400">No vehicles stationed at this location.</p>';
                return;
            }

            const start = startInput ? startInput.value : '';
            const end = (endInput && endInput.value && endInput.value >= start) ? endInput.value : start;

            if (!start) {
                if (hintBox) hintBox.textContent = 'Click a travel date above to view available vehicles.';
                cardsBox.innerHTML = '<p class="col-span-full py-4 text-center text-xs text-slate-400">Click a travel date on the calendar above to see vehicle availability.</p>';
                return;
            }

            if (hintBox) {
                hintBox.textContent = `Available Vehicles for ${fmtRange(start, end)}:`;
            }

            const rows = manualStationVehicles.map(v => ({ v, c: manualConflictFor(v, start, end) }));
            const severity = (c) => (c.maintenance ? 3 : c.approved ? 2 : c.pending ? 1 : 0);
            const sorted = rows.slice().sort((a, b) => severity(a.c) - severity(b.c) || a.v.plate.localeCompare(b.v.plate));

            cardsBox.innerHTML = sorted.map(({ v, c }) => {
                const isSelected = String(v.id) === String(vehicleIdInput.value);
                const hasConflict = Boolean(c.maintenance || c.approved || c.pending);
                
                let statusBadge = '';
                if (c.maintenance) {
                    statusBadge = `<span class="inline-flex items-center gap-1 rounded-md bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">⛔ Maintenance: ${fmtRange(c.maintenance.start, c.maintenance.end)}</span>`;
                } else if (c.approved) {
                    statusBadge = `<span class="inline-flex items-center gap-1 rounded-md bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">⚠ Approved Trip: ${fmtRange(c.approved.start, c.approved.end)}</span>`;
                } else if (c.pending) {
                    statusBadge = `<span class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">⚠ Pending Request: ${fmtRange(c.pending.start, c.pending.end)}</span>`;
                } else {
                    statusBadge = `<span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">✓ Available</span>`;
                }

                const borderClass = isSelected 
                    ? 'border-emerald-600 bg-emerald-50/50 ring-2 ring-emerald-500' 
                    : (hasConflict ? 'border-slate-200 bg-slate-50/60 hover:border-slate-300' : 'border-slate-200 bg-white hover:border-emerald-400');

                const photoHtml = v.photo 
                    ? `<img src="${esc(v.photo)}" alt="${esc(v.plate)}" class="h-14 w-auto max-w-[120px] object-contain rounded-lg">`
                    : PLACEHOLDER_CAR_SVG;

                const driverInitial = esc((v.driver || '?').trim().charAt(0).toUpperCase());
                const avatarHtml = v.driver_photo 
                    ? `<img src="${esc(v.driver_photo)}" alt="${esc(v.driver)}" class="h-5 w-5 rounded-full object-cover">`
                    : `<span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">${driverInitial}</span>`;

                return `
                    <div onclick="selectManualVehicle(${v.id}, ${v.driver_id || 'null'})" class="relative flex flex-col justify-between rounded-xl border-2 p-3 transition cursor-pointer ${borderClass}">
                        <div class="flex items-center justify-between gap-1 mb-2">
                            <span class="text-xs font-black tracking-wide text-slate-900">${esc(v.plate)}</span>
                            ${isSelected ? '<span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-extrabold text-white">✓ Selected</span>' : ''}
                        </div>

                        <div class="flex items-center gap-3 my-1">
                            <div class="flex h-14 w-20 shrink-0 items-center justify-center bg-slate-50 rounded-lg p-1">
                                ${photoHtml}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-800 truncate" title="${esc(v.name)}">${esc(v.name)}</p>
                                <div class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-600">
                                    ${avatarHtml}
                                    <span class="truncate font-medium">${esc(v.driver || 'No assigned driver')}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between">
                            ${statusBadge}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function selectManualVehicle(vehicleId, driverId) {
            const input = document.getElementById('manual-vehicle-id-input');
            const driverSelect = document.getElementById('manual-req-driver');
            if (input) input.value = vehicleId;
            if (driverSelect && driverId) {
                driverSelect.value = driverId;
            }
            renderManualVehicleCards();
        }

        function populateManualDriverDropdown(selectedLoc) {
            const drvSelect = document.getElementById('manual-req-driver');
            if (!drvSelect) return;
            const currentVal = drvSelect.value || '{{ old("confirmed_driver_id") }}';
            drvSelect.innerHTML = '<option value="">— Select Driver —</option>';
            const filteredDrivers = allAvailableDrivers.filter(d => !selectedLoc || d.location === selectedLoc || !d.location);
            filteredDrivers.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = `${d.name}${d.location ? ' (' + d.location + ')' : ''}`;
                if (String(d.id) === String(currentVal)) {
                    opt.selected = true;
                }
                drvSelect.appendChild(opt);
            });
        }

        function loadManualStationAvailability() {
            const locSelect = document.getElementById('manual-req-location');
            const cardsBox = document.getElementById('manual-vehicle-cards');
            if (!locSelect) return;

            const loc = locSelect.value;
            if (!loc) {
                manualStationVehicles = [];
                renderManualCalendar();
                renderManualVehicleCards();
                return;
            }

            if (cardsBox) {
                cardsBox.innerHTML = '<p class="col-span-full py-6 text-center text-xs text-slate-400">Loading vehicles for ' + esc(loc) + '...</p>';
            }

            populateManualDriverDropdown(loc);

            fetch(`${manualStationUrl}?location=${encodeURIComponent(loc)}`)
                .then(res => res.json())
                .then(data => {
                    manualStationVehicles = data.vehicles || [];
                    renderManualCalendar();
                    renderManualVehicleCards();

                    const oldVeh = '{{ old("vehicle_id") }}';
                    if (oldVeh && manualStationVehicles.some(v => String(v.id) === String(oldVeh))) {
                        const found = manualStationVehicles.find(v => String(v.id) === String(oldVeh));
                        selectManualVehicle(found.id, found.driver_id);
                    }
                })
                .catch(err => {
                    console.error('Failed to load station availability:', err);
                    if (cardsBox) cardsBox.innerHTML = '<p class="col-span-full py-4 text-center text-xs text-red-500">Failed to load vehicle availability.</p>';
                });
        }

        function openManualRequestModal() {
            const modal = document.getElementById('manual-request-modal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            const startInput = document.getElementById('manual-req-travel-date');
            const endInput = document.getElementById('manual-req-travel-date-end');
            if (startInput && !startInput.value) {
                const todayStr = new Date().toISOString().split('T')[0];
                startInput.value = todayStr;
                if (endInput && !endInput.value) {
                    endInput.value = todayStr;
                }
            }

            loadManualStationAvailability();
            loadAdminDestinations();
        }

        function closeManualRequestModal() {
            const modal = document.getElementById('manual-request-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function addManualPassengerRow(value = '') {
            const container = document.getElementById('manual-passengers-container');
            if (!container) return;
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <input type="text" name="passengers[]" value="${value}" placeholder="Passenger Full Name" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <button type="button" onclick="this.parentElement.remove()" class="rounded-lg p-1.5 text-red-500 hover:bg-red-50 transition cursor-pointer" title="Remove">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                </button>
            `;
            container.appendChild(row);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const startInput = document.getElementById('manual-req-travel-date');
            const endInput = document.getElementById('manual-req-travel-date-end');

            if (startInput) {
                startInput.addEventListener('input', () => {
                    manualRangeConfirmed = true;
                    if (endInput && endInput.value && endInput.value < startInput.value) {
                        endInput.value = startInput.value;
                    }
                    const [y, m] = startInput.value.split('-').map(Number);
                    if (y && m) {
                        manualViewYear = y;
                        manualViewMonth = m - 1;
                    }
                    renderManualCalendar();
                    renderManualVehicleCards();
                });
            }

            if (endInput) {
                endInput.addEventListener('input', () => {
                    manualRangeConfirmed = true;
                    renderManualCalendar();
                    renderManualVehicleCards();
                });
            }

            @if($errors->any() && old('_action') === 'admin_manual_create')
                openManualRequestModal();
                @if(old('passengers'))
                    @foreach(old('passengers') as $p)
                        addManualPassengerRow(@json($p));
                    @endforeach
                @endif
            @endif
        });

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

        // ── Admin Modal: Office / Division searchable dropdown ──────────────
        function toggleAdminOfficeDropdown() {
            const panel = document.getElementById('admin-office-dropdown-panel');
            const chevron = document.getElementById('admin-office-dropdown-chevron');
            const searchInput = document.getElementById('admin-office-search-input');
            if (!panel) return;
            const isHidden = panel.classList.contains('hidden');
            if (isHidden) {
                panel.classList.remove('hidden');
                chevron.style.transform = 'rotate(180deg)';
                searchInput.value = '';
                filterAdminOfficeOptions();
                searchInput.focus();
            } else {
                closeAdminOfficeDropdown();
            }
        }

        function closeAdminOfficeDropdown() {
            const panel = document.getElementById('admin-office-dropdown-panel');
            const chevron = document.getElementById('admin-office-dropdown-chevron');
            if (panel) panel.classList.add('hidden');
            if (chevron) chevron.style.transform = '';
        }

        function selectAdminOffice(val, displayLabel = null) {
            const hiddenInput = document.getElementById('admin-requester-office-input');
            const labelSpan = document.getElementById('admin-office-dropdown-label');
            if (hiddenInput) hiddenInput.value = val;
            if (labelSpan) {
                const display = val || (displayLabel || '— Select office / division —');
                labelSpan.textContent = display;
                labelSpan.className = labelSpan.className.replace(/text-slate-(400|800)/g, '');
                labelSpan.classList.add(val ? 'text-slate-800' : 'text-slate-400');
            }
            closeAdminOfficeDropdown();
        }

        function filterAdminOfficeOptions() {
            const query = (document.getElementById('admin-office-search-input')?.value || '').toLowerCase().trim();
            const items = document.querySelectorAll('.admin-office-option');
            let anyVisible = false;
            items.forEach(item => {
                const label = (item.dataset.label || '').toLowerCase();
                const match = !query || label.includes(query);
                item.classList.toggle('hidden', !match);
                if (match) anyVisible = true;
            });
            const noFound = document.getElementById('admin-no-office-found');
            if (noFound) noFound.classList.toggle('hidden', anyVisible);
        }

        document.addEventListener('click', (e) => {
            const container = document.getElementById('admin-office-dropdown-container');
            if (container && !container.contains(e.target)) {
                closeAdminOfficeDropdown();
            }
            const destContainer = document.getElementById('admin-dest-dropdown-container');
            if (destContainer && !destContainer.contains(e.target)) {
                closeAdminDestDropdown();
            }
        });

        // ── Admin Modal: Destination searchable dropdown & distance support ──
        let currentAdminDestinations = [];

        function loadAdminDestinations() {
            const location = document.getElementById('manual-req-location')?.value || 'Palawan';
            fetch(`/api/destinations-by-location?location=${encodeURIComponent(location)}`)
                .then(res => res.json())
                .then(data => {
                    currentAdminDestinations = data.destinations || [];
                    renderAdminDestOptions(currentAdminDestinations);
                })
                .catch(err => {
                    console.error('Failed to load destinations:', err);
                    renderAdminDestOptions([]);
                });
        }

        function renderAdminDestOptions(destinations) {
            const list = document.getElementById('admin-dest-options-list');
            if (!list) return;

            let html = `
                <div onclick="selectAdminDest('', null)" class="admin-dest-option flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-xs text-slate-400 hover:bg-slate-50 transition" data-label="— select destination —">
                    <span>— Select destination —</span>
                </div>
            `;

            destinations.forEach(d => {
                const escapedName = (d.name || '').replace(/'/g, "\\'");
                html += `
                    <div onclick="selectAdminDest('${escapedName}', ${d.distance_km})" class="admin-dest-option flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-xs text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition" data-label="${(d.name + ' ' + d.distance_km + 'km').toLowerCase()}">
                        <span class="font-semibold">${d.name}</span>
                        <span class="inline-flex items-center rounded-md bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 text-[10px] font-bold text-emerald-700 shrink-0">
                            ${d.distance_km} kms
                        </span>
                    </div>
                `;
            });

            html += `
                <div onclick="selectAdminDest('OTHER_MANUAL', null)" class="admin-dest-option flex cursor-pointer items-center gap-1.5 border-t border-slate-100 mt-1 pt-1.5 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50 transition" data-label="other type manually">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Other (Type manually)</span>
                </div>
            `;

            list.innerHTML = html;
        }

        function toggleAdminDestDropdown() {
            const panel = document.getElementById('admin-dest-dropdown-panel');
            const chevron = document.getElementById('admin-dest-dropdown-chevron');
            const searchInput = document.getElementById('admin-dest-search-input');
            if (!panel) return;
            const isHidden = panel.classList.contains('hidden');
            if (isHidden) {
                panel.classList.remove('hidden');
                if (chevron) chevron.style.transform = 'rotate(180deg)';
                if (searchInput) {
                    searchInput.value = '';
                    filterAdminDestOptions();
                    searchInput.focus();
                }
            } else {
                closeAdminDestDropdown();
            }
        }

        function closeAdminDestDropdown() {
            const panel = document.getElementById('admin-dest-dropdown-panel');
            const chevron = document.getElementById('admin-dest-dropdown-chevron');
            if (panel) panel.classList.add('hidden');
            if (chevron) chevron.style.transform = '';
        }

        function selectAdminDest(name, distanceKm) {
            const nameInput = document.getElementById('admin-dest-name-input');
            const distInput = document.getElementById('admin-dest-distance-input');
            const labelSpan = document.getElementById('admin-dest-dropdown-label');
            const manualContainer = document.getElementById('admin-dest-manual-container');
            const manualInput = document.getElementById('admin-dest-manual-input');

            if (name === 'OTHER_MANUAL') {
                if (manualContainer) manualContainer.classList.remove('hidden');
                if (manualInput) {
                    manualInput.focus();
                    if (nameInput) nameInput.value = manualInput.value;
                }
                if (distInput) distInput.value = '';
                if (labelSpan) {
                    labelSpan.textContent = manualInput?.value ? manualInput.value : '— Custom destination —';
                    labelSpan.className = 'truncate text-slate-800 font-semibold';
                }
            } else {
                if (manualContainer) manualContainer.classList.add('hidden');
                if (nameInput) nameInput.value = name;
                if (distInput) distInput.value = distanceKm !== null && distanceKm !== undefined ? distanceKm : '';

                if (labelSpan) {
                    if (name) {
                        const distSuffix = distanceKm !== null && distanceKm !== undefined ? ` (${distanceKm} kms)` : '';
                        labelSpan.textContent = name + distSuffix;
                        labelSpan.className = 'truncate text-slate-800 font-semibold';
                    } else {
                        labelSpan.textContent = '— Select destination —';
                        labelSpan.className = 'truncate text-slate-400';
                    }
                }
            }

            closeAdminDestDropdown();
        }

        function onAdminDestManualInput(val) {
            const nameInput = document.getElementById('admin-dest-name-input');
            const labelSpan = document.getElementById('admin-dest-dropdown-label');
            if (nameInput) nameInput.value = val;
            if (labelSpan) {
                labelSpan.textContent = val ? val : '— Custom destination —';
            }
        }

        function filterAdminDestOptions() {
            const query = (document.getElementById('admin-dest-search-input')?.value || '').toLowerCase().trim();
            const items = document.querySelectorAll('.admin-dest-option');
            items.forEach(item => {
                const label = (item.dataset.label || '').toLowerCase();
                const match = !query || label.includes(query);
                item.classList.toggle('hidden', !match);
            });
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
