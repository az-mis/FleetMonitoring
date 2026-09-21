@extends('layouts.app')

@section('title', 'History of Travel')
@section('page-title', 'History of Travel')

@php
    $today = now()->startOfDay();

    $mappedTrips = $trips->map(function ($trip) use ($today) {
        $startDate = $trip->travel_date ? \Carbon\Carbon::parse($trip->travel_date)->startOfDay() : null;
        $endDate = $trip->travel_date_end ? \Carbon\Carbon::parse($trip->travel_date_end)->startOfDay() : $startDate;
        $isUpcoming = $endDate ? $endDate >= $today : true;

        if ($trip->travel_date_end && $trip->travel_date && \Carbon\Carbon::parse($trip->travel_date_end)->ne(\Carbon\Carbon::parse($trip->travel_date))) {
            $formattedDate = \Carbon\Carbon::parse($trip->travel_date)->format('M j') . ' – ' . \Carbon\Carbon::parse($trip->travel_date_end)->format('j, Y');
        } else {
            $formattedDate = $trip->travel_date ? \Carbon\Carbon::parse($trip->travel_date)->format('M j, Y') : 'N/A';
        }

        $passengers = [];
        if (!empty($trip->requester_name)) {
            $passengers[] = $trip->requester_name . ' (Requester)';
        }

        $extraPassengers = is_array($trip->passengers)
            ? $trip->passengers
            : (is_string($trip->passengers) ? json_decode($trip->passengers, true) ?: [] : []);

        foreach ($extraPassengers as $p) {
            $name = is_array($p) ? ($p['name'] ?? $p['passenger_name'] ?? '') : (string) $p;
            if ($name && strtolower((string)$name) !== strtolower((string)$trip->requester_name)) {
                $passengers[] = trim((string)$name);
            }
        }

        return [
            'id' => $trip->id,
            'destination' => $trip->destination ?: 'N/A',
            'purpose' => $trip->purpose ?: '',
            'requester_name' => $trip->requester_name ?: 'N/A',
            'requester_office' => $trip->requester_office ?: '',
            'requester_contact' => $trip->requester_contact ?: '',
            'location' => $trip->location ?: '',
            'approved_by_name' => is_object($trip->approvedBy) ? $trip->approvedBy->name : ($trip->approved_by ?: 'Administrator'),
            'vehicle_plate' => $trip->vehicle?->plate_number ?: 'N/A',
            'vehicle_full' => $trip->vehicle ? trim(($trip->vehicle->brand ?? '') . ' ' . ($trip->vehicle->model ?? '') . ' — ' . $trip->vehicle->plate_number) : 'N/A',
            'formatted_date' => $formattedDate,
            'is_upcoming' => $isUpcoming,
            'more_passengers_count' => max(0, count($passengers) - 1),
            'all_passengers' => count($passengers) > 0 ? $passengers : [$trip->requester_name ?: 'Requester'],
            'ticket_url' => route('trip-ticket', $trip->id),
            'search_text' => strtolower(e(($trip->destination ?: '') . ' ' . ($trip->requester_name ?: '') . ' ' . ($trip->vehicle?->plate_number ?: '') . ' ' . ($trip->purpose ?: '') . ' ' . implode(' ', $passengers))),
        ];
    });

    $upcomingCount = $mappedTrips->where('is_upcoming', true)->count();
    $completedCount = $mappedTrips->where('is_upcoming', false)->count();
    $totalCount = $mappedTrips->count();
@endphp

@section('content')
    <div class="space-y-4" x-data="travelHistoryApp()">
        {{-- Header Banner --}}
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#155f35] via-[#1d8148] to-[#269d59] px-5 py-3 text-white shadow-lg shadow-emerald-900/15 sm:px-6">
            <div class="absolute -right-8 -top-12 h-40 w-40 rounded-full bg-white/10"></div>
            <div class="relative flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-extrabold tracking-tight">History of Travel</h2>
                    <p class="mt-0.5 text-xs text-white/80">Complete record and details of all your assigned and completed dispatch travels.</p>
                </div>
            </div>
        </section>

        {{-- 3 KPI Stat Cards --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            {{-- Upcoming / Active --}}
            <div class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" x2="16" y1="2" y2="6"/>
                            <line x1="8" x2="8" y1="2" y2="6"/>
                            <line x1="3" x2="21" y1="10" y2="10"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">UPCOMING / ACTIVE</p>
                        <p class="text-xl font-black text-slate-900">{{ $upcomingCount }}</p>
                    </div>
                </div>
            </div>

            {{-- Completed Travels --}}
            <div class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">COMPLETED TRAVELS</p>
                        <p class="text-xl font-black text-slate-900">{{ $completedCount }}</p>
                    </div>
                </div>
            </div>

            {{-- Total Dispatches --}}
            <div class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-700 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" x2="11" y1="2" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">TOTAL DISPATCHES</p>
                        <p class="text-xl font-black text-slate-900">{{ $totalCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Container Card --}}
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-2xs space-y-4">
            {{-- Tabs & Search Bar --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                {{-- Filter Tabs --}}
                <div class="inline-flex rounded-lg bg-slate-100 p-1">
                    <button type="button" @click="activeTab = 'upcoming'" :class="activeTab === 'upcoming' ? 'bg-white text-slate-900 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900 font-semibold'" class="rounded-md px-3 py-1.5 text-xs transition cursor-pointer">
                        Active / Upcoming ({{ $upcomingCount }})
                    </button>
                    <button type="button" @click="activeTab = 'completed'" :class="activeTab === 'completed' ? 'bg-white text-slate-900 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900 font-semibold'" class="rounded-md px-3 py-1.5 text-xs transition cursor-pointer">
                        Completed ({{ $completedCount }})
                    </button>
                    <button type="button" @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-white text-slate-900 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900 font-semibold'" class="rounded-md px-3 py-1.5 text-xs transition cursor-pointer">
                        All ({{ $totalCount }})
                    </button>
                </div>

                {{-- Search Box --}}
                <div class="relative w-full sm:w-72">
                    <input type="text" x-model="searchQuery" placeholder="Search destination, passenger..." class="w-full rounded-lg border border-slate-200 bg-slate-50/70 pl-8 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" x2="16.65" y1="21" y2="16.65"/>
                    </svg>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto rounded-lg border border-slate-100">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-2.5 px-3">Destination & Purpose</th>
                            <th class="py-2.5 px-3">Travel Date</th>
                            <th class="py-2.5 px-3">Vehicle</th>
                            <th class="py-2.5 px-3">Passengers</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @forelse($mappedTrips as $trip)
                            <tr x-show="matchesRow('{{ $trip['is_upcoming'] ? 'upcoming' : 'completed' }}', '{{ $trip['search_text'] }}')" class="hover:bg-slate-50/50 transition">
                                {{-- Destination & Purpose --}}
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-red-500 text-xs shrink-0">📍</span>
                                        <span class="font-extrabold text-slate-900">{{ $trip['destination'] }}</span>
                                    </div>
                                    @if($trip['purpose'])
                                        <p class="mt-0.5 text-[11px] text-slate-600 font-medium">{{ $trip['purpose'] }}</p>
                                    @endif
                                    @if($trip['requester_office'])
                                        <p class="text-[10px] text-slate-500">Office: {{ $trip['requester_office'] }}</p>
                                    @endif
                                </td>

                                {{-- Travel Date --}}
                                <td class="py-3 px-3 font-semibold text-slate-800 whitespace-nowrap">{{ $trip['formatted_date'] }}</td>

                                {{-- Vehicle --}}
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
                                        </svg>
                                        <span class="font-bold text-slate-900">{{ $trip['vehicle_plate'] }}</span>
                                    </div>
                                </td>

                                {{-- Passengers --}}
                                <td class="py-3 px-3">
                                    <div class="font-semibold text-slate-800">{{ $trip['requester_name'] }} (Requester)</div>
                                    @if($trip['more_passengers_count'] > 0)
                                        <button type="button" @click="openModal({{ json_encode($trip) }})" class="text-[10px] font-bold text-emerald-700 hover:underline cursor-pointer">
                                            +{{ $trip['more_passengers_count'] }} more
                                        </button>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="py-3 px-3 whitespace-nowrap">
                                    @if($trip['is_upcoming'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700 border border-blue-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            Scheduled
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            Completed
                                        </span>
                                    @endif
                                </td>

                                {{-- Action --}}
                                <td class="py-3 px-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click="openModal({{ json_encode($trip) }})" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition cursor-pointer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            View Details
                                        </button>
                                        <a href="{{ $trip['ticket_url'] }}" target="_blank" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 hover:bg-emerald-100 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                            Ticket
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-xs text-slate-400 font-medium">
                                    No travel records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Bottom Pagination & Rows Per Page Controls --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 pt-2 border-t border-slate-100">
                <div class="flex items-center gap-3">
                    <span>
                        Showing 1 to {{ $totalCount }} of {{ $totalCount }} trips
                    </span>
                    <div class="flex items-center gap-1">
                        <span>Rows per page:</span>
                        <select class="rounded border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 focus:outline-none focus:border-emerald-500">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <button type="button" disabled class="rounded border border-slate-200 px-2 py-1 text-xs opacity-40 cursor-not-allowed">&laquo;</button>
                    <button type="button" disabled class="rounded border border-slate-200 px-2 py-1 text-xs opacity-40 cursor-not-allowed">&lt;</button>
                    <span class="px-2 py-1 font-bold text-emerald-800 bg-emerald-50 rounded border border-emerald-200">1</span>
                    <button type="button" disabled class="rounded border border-slate-200 px-2 py-1 text-xs opacity-40 cursor-not-allowed">&gt;</button>
                    <button type="button" disabled class="rounded border border-slate-200 px-2 py-1 text-xs opacity-40 cursor-not-allowed">&raquo;</button>
                </div>
            </div>
        </div>

        {{-- Travel Dispatch Details Modal --}}
        <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4" style="display: none;">
            <div @click.away="isModalOpen = false" class="w-full max-w-lg rounded-2xl bg-white shadow-2xl overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-150">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h3 class="text-sm font-extrabold text-slate-900">Travel Dispatch Details</h3>
                    <button type="button" @click="isModalOpen = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                {{-- Modal Content --}}
                <template x-if="selectedTrip">
                    <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
                        {{-- Destination Green Header Box --}}
                        <div class="flex items-center justify-between rounded-xl bg-emerald-50/70 border border-emerald-200/80 p-3.5">
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-wider text-emerald-800">DESTINATION</p>
                                <div class="mt-0.5 flex items-center gap-1.5">
                                    <span class="text-red-500 text-sm">📍</span>
                                    <h4 class="text-sm font-black text-emerald-950" x-text="selectedTrip.destination"></h4>
                                </div>
                            </div>
                            <a :href="selectedTrip.ticket_url" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-800 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-900 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                Printable Ticket
                            </a>
                        </div>

                        {{-- Details List --}}
                        <div class="space-y-3 text-xs text-slate-700">
                            {{-- Date Range --}}
                            <div class="flex items-start gap-2.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase text-slate-400">DATE RANGE</p>
                                    <p class="font-bold text-slate-800 mt-0.5" x-text="selectedTrip.formatted_date"></p>
                                </div>
                            </div>

                            {{-- Vehicle --}}
                            <div class="flex items-start gap-2.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase text-slate-400">ASSIGNED VEHICLE</p>
                                    <p class="font-bold text-slate-800 mt-0.5" x-text="selectedTrip.vehicle_full"></p>
                                </div>
                            </div>

                            {{-- Requester --}}
                            <div class="flex items-start gap-2.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase text-slate-400">REQUESTER</p>
                                    <p class="font-bold text-slate-800 mt-0.5">
                                        <span x-text="selectedTrip.requester_name"></span>
                                        <span class="text-slate-500 font-medium" x-text="selectedTrip.requester_office ? '(' + selectedTrip.requester_office + ')' : ''"></span>
                                    </p>
                                </div>
                            </div>

                            {{-- Contact --}}
                            <div class="flex items-start gap-2.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase text-slate-400">CONTACT</p>
                                    <p class="font-bold text-slate-800 mt-0.5" x-text="selectedTrip.requester_contact || 'N/A'"></p>
                                </div>
                            </div>

                            {{-- Pick-up Location --}}
                            <div class="flex items-start gap-2.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase text-slate-400">PICK-UP LOCATION</p>
                                    <p class="font-bold text-slate-800 mt-0.5" x-text="selectedTrip.location || 'N/A'"></p>
                                </div>
                            </div>

                            {{-- Approved By --}}
                            <div class="flex items-start gap-2.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase text-slate-400">APPROVED BY</p>
                                    <p class="font-bold text-slate-800 mt-0.5" x-text="selectedTrip.approved_by_name || 'Administrator'"></p>
                                </div>
                            </div>

                            {{-- Trip Purpose --}}
                            <div class="flex items-start gap-2.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase text-slate-400">TRIP PURPOSE</p>
                                    <p class="font-bold text-slate-800 mt-0.5" x-text="selectedTrip.purpose || 'N/A'"></p>
                                </div>
                            </div>

                            {{-- Authorized Passengers --}}
                            <div class="flex items-start gap-2.5">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                                <div class="w-full">
                                    <p class="text-[10px] font-extrabold uppercase text-slate-400">AUTHORIZED PASSENGERS</p>
                                    <ul class="mt-1 space-y-1 list-disc list-inside font-bold text-slate-800">
                                        <template x-for="passenger in selectedTrip.all_passengers" :key="passenger">
                                            <li x-text="passenger"></li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Modal Footer --}}
                <div class="flex justify-end border-t border-slate-100 px-5 py-3 bg-slate-50/50">
                    <button type="button" @click="isModalOpen = false" class="rounded-xl border border-slate-200 bg-white px-4 py-1.5 text-xs font-extrabold text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function travelHistoryApp() {
            return {
                activeTab: 'upcoming',
                searchQuery: '',
                isModalOpen: false,
                selectedTrip: null,

                matchesRow(status, searchText) {
                    // Filter tab
                    if (this.activeTab !== 'all' && this.activeTab !== status) {
                        return false;
                    }

                    // Search query
                    if (this.searchQuery.trim()) {
                        const q = this.searchQuery.toLowerCase();
                        if (!searchText.includes(q)) {
                            return false;
                        }
                    }

                    return true;
                },

                openModal(trip) {
                    this.selectedTrip = trip;
                    this.isModalOpen = true;
                }
            };
        }
    </script>
@endsection
