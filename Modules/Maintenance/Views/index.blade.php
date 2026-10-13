@extends('layouts.app')

@section('title', 'Maintenance')
@section('page-title', 'Maintenance')

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

    $statusStyles = [
        'scheduled' => ['label' => 'Scheduled', 'bg' => 'bg-sky-100',   'text' => 'text-sky-700',   'ring' => 'ring-sky-200'],
        'ongoing'   => ['label' => 'Ongoing',   'bg' => 'bg-orange-100', 'text' => 'text-orange-700','ring' => 'ring-orange-200'],
        'completed' => ['label' => 'Completed', 'bg' => 'bg-green-100',  'text' => 'text-green-700', 'ring' => 'ring-green-200'],
        'cancelled' => ['label' => 'Cancelled', 'bg' => 'bg-slate-100',  'text' => 'text-slate-500', 'ring' => 'ring-slate-200'],
    ];

    $priorityStyles = [
        'normal' => ['label' => 'Normal', 'text' => 'text-green-700'],
        'high'   => ['label' => 'High',   'text' => 'text-amber-600'],
        'urgent' => ['label' => 'Urgent', 'text' => 'text-red-600'],
    ];

    $maintenanceTypes = ['Preventive','Repair','Inspection','Oil Change','Tire','Battery','Other'];
    $statusFilters    = ['active','scheduled','ongoing','completed','cancelled','all'];

    $editRecord = session('edit_record') ?? null;
@endphp

{{-- ── Page header ─────────────────────────────────────────────────── --}}
<div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Maintenance</h1>
        <p class="text-sm text-slate-500">Schedule and track vehicle service and repairs</p>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        {{-- Search --}}
        <form method="GET" action="{{ route('maintenance.index') }}" id="maintenance-search-form" class="flex w-full min-w-0 items-center gap-2 sm:w-72 xl:w-96" onsubmit="return false;">
            <input type="hidden" name="status"   value="{{ request('status', 'active') }}">
            <input type="hidden" name="location" value="{{ request('location') }}">
            <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
            <div class="relative w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-600/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input
                    type="text"
                    name="search"
                    id="maintenance-search-input"
                    value="{{ request('search') }}"
                    autocomplete="off"
                    placeholder="Search plate, type, shop..."
                    oninput="onMaintenanceSearchInput()"
                    class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-8 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                >
                <svg id="maintenance-search-spinner" xmlns="http://www.w3.org/2000/svg" class="hidden absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            </div>
            <a href="{{ route('maintenance.index', array_filter(['status' => request('status', 'active'), 'location' => request('location'), 'per_page' => request('per_page', 10)])) }}"
               id="maintenance-search-clear"
               class="shrink-0 text-xs font-semibold text-slate-500 hover:text-slate-700 {{ request('search') ? '' : 'hidden' }}">Clear</a>
        </form>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Location filter: dropdown for super admins, fixed location for admins --}}
            @if($isSuperAdmin)
            <div class="relative" id="location-dropdown-wrap">
                <button type="button" onclick="toggleLocationDropdown()"
                    class="inline-flex h-10 items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="max-w-[9rem] truncate">{{ $selectedLocation ?: 'All Locations' }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="location-dropdown" class="absolute left-0 top-full z-30 mt-2 hidden w-64 sm:left-auto sm:right-0 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5">
                    <a href="{{ route('maintenance.index', array_filter(['search' => request('search'), 'status' => request('status','active'), 'per_page' => request('per_page',10)])) }}"
                       class="block rounded-lg px-3 py-2 text-xs font-semibold {{ !$selectedLocation ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-gray-50' }}">All Locations</a>
                    @foreach($locationOptions as $loc)
                        <a href="{{ route('maintenance.index', array_filter(['search' => request('search'), 'status' => request('status','active'), 'location' => $loc, 'per_page' => request('per_page',10)])) }}"
                           class="block rounded-lg px-3 py-2 text-xs font-semibold {{ $selectedLocation === $loc ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-gray-50' }}">{{ $loc }}</a>
                    @endforeach
                </div>
            </div>
            @else
            <div class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span class="max-w-[9rem] truncate">{{ $selectedLocation ?: 'All Locations' }}</span>
            </div>
            @endif

            {{-- Add Maintenance button --}}
            <button type="button" onclick="openMaintenanceModal()"
                class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-[#005a36] px-4 text-xs font-extrabold text-white shadow-sm hover:bg-[#00432a] transition cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Add Maintenance
            </button>
        </div>
    </div>
</div>

{{-- ── Flash messages ───────────────────────────────────────────────── --}}
@if(session('success'))
    <div id="flash-success" class="mb-3 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
        {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

{{-- ── Stat cards ───────────────────────────────────────────────────── --}}
<div class="mb-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
    @foreach([
        ['label'=>'Active',    'value'=>$counts['active'],    'color'=>'text-orange-600', 'icon'=>'alert-triangle'],
        ['label'=>'Scheduled', 'value'=>$counts['scheduled'], 'color'=>'text-sky-600',    'icon'=>'calendar'],
        ['label'=>'Ongoing',   'value'=>$counts['ongoing'],   'color'=>'text-amber-600',  'icon'=>'clock'],
        ['label'=>'Completed', 'value'=>$counts['completed'], 'color'=>'text-emerald-600','icon'=>'check-circle'],
    ] as $stat)
    <div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 shadow-xs">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 {{ $stat['color'] }}">
            @if($stat['icon'] === 'alert-triangle')
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            @elseif($stat['icon'] === 'calendar')
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            @elseif($stat['icon'] === 'clock')
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            @endif
        </div>
        <div>
            <p class="text-xl font-extrabold text-gray-900">{{ $stat['value'] }}</p>
            <p class="text-xs text-gray-500">{{ $stat['label'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Status filter pills ──────────────────────────────────────────── --}}
<div class="mb-3 flex flex-wrap gap-2">
    @foreach($statusFilters as $sf)
    <a href="{{ route('maintenance.index', array_filter(['search' => request('search'), 'location' => $selectedLocation, 'status' => $sf, 'per_page' => request('per_page',10)])) }}"
       class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-bold transition
              {{ $statusFilter === $sf
                 ? 'bg-emerald-600 text-white shadow-sm'
                 : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
        <span>{{ ucfirst($sf) }}</span>
        <span class="rounded-full px-1.5 text-[10px] {{ $statusFilter === $sf ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">
            {{ $counts[$sf] ?? 0 }}
        </span>
    </a>
    @endforeach
</div>

{{-- ── Table ────────────────────────────────────────────────────────── --}}
@if($records->isEmpty())
    <div class="flex flex-col items-center justify-center rounded-2xl border border-gray-100 bg-white px-6 py-16 text-center shadow-xs">
        <svg xmlns="http://www.w3.org/2000/svg" class="mb-3 h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        <p class="font-bold text-gray-700">No maintenance records found</p>
        <p class="mt-1 text-xs text-gray-400">Add scheduled repairs, preventive service, inspections, and other vehicle work here.</p>
    </div>
@else
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Vehicle</th>
                        <th class="px-4 py-3 text-left">Maintenance</th>
                        <th class="px-4 py-3 text-left">Schedule</th>
                        <th class="px-4 py-3 text-left">Priority</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Driver</th>
                        <th class="px-4 py-3 text-left">Cost / Vendor</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($records as $record)
                    @php
                        $ss  = $statusStyles[$record->status]   ?? $statusStyles['cancelled'];
                        $ps  = $priorityStyles[$record->priority] ?? $priorityStyles['normal'];
                        $driver = $record->vehicle?->assignedDriver;
                        $driverName = $driver?->name ?? $record->vehicle?->assigned_driver_name ?? null;
                        $driverInitial = strtoupper(substr($driverName ?? 'D', 0, 1));
                    @endphp
                    <tr id="maintenance-row-{{ $record->id }}" class="hover:bg-gray-50 transition border-l-4 border-transparent">
                        {{-- Vehicle --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                @if($record->vehicle?->photo_url)
                                    <img src="{{ $record->vehicle->photo_url }}" alt="vehicle" class="h-8 w-8 rounded-lg object-cover ring-1 ring-gray-100">
                                @else
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-bold text-gray-900">{{ $record->plate_number }}</p>
                                    <p class="text-xs text-gray-400">{{ $record->vehicle_label ?? 'Vehicle' }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Maintenance info --}}
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $record->title }}</p>
                            <p class="text-xs text-gray-400">{{ $record->type }}</p>
                        </td>

                        {{-- Schedule --}}
                        <td class="px-4 py-3 text-xs text-gray-500">
                            <p class="font-medium text-slate-700">{{ $record->scheduled_date->format('M d, Y') }}
                                @if($record->scheduled_date_end && $record->scheduled_date_end->ne($record->scheduled_date))
                                    – {{ $record->scheduled_date_end->format('M d, Y') }}
                                @endif
                            </p>
                            @if($record->trigger_km)
                                @php $trig = $record->trigger_status; @endphp
                                <div class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-semibold">
                                    <span class="text-slate-500">At {{ number_format($record->trigger_km) }} km</span>
                                    @if($trig === 'overdue')
                                        <span class="rounded bg-red-100 px-1 py-0.2 text-[10px] font-bold text-red-700">KM Overdue</span>
                                    @elseif($trig === 'due_soon')
                                        <span class="rounded bg-amber-100 px-1 py-0.2 text-[10px] font-bold text-amber-700">KM Due Soon</span>
                                    @endif
                                </div>
                            @endif
                            @if($record->completed_date)
                                <p class="text-emerald-600 font-medium">Completed: {{ $record->completed_date->format('M d, Y') }}</p>
                            @endif
                        </td>

                        {{-- Priority --}}
                        <td class="px-4 py-3 text-xs font-bold {{ $ps['text'] }}">{{ $ps['label'] }}</td>

                        {{-- Status badge --}}
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 {{ $ss['bg'] }} {{ $ss['text'] }} {{ $ss['ring'] }}">
                                {{ $ss['label'] }}
                            </span>
                        </td>

                        {{-- Driver --}}
                        <td class="px-4 py-3">
                            @if($driverName)
                                <div class="flex items-center gap-2">
                                    @if($driver?->avatar_url)
                                        <img src="{{ $driver->avatar_url }}" alt="{{ $driverName }}" class="h-6 w-6 rounded-full object-cover">
                                    @else
                                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[10px] font-bold text-emerald-700">{{ $driverInitial }}</div>
                                    @endif
                                    <span class="text-xs font-medium text-gray-700">{{ $driverName }}</span>
                                </div>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>

                        {{-- Cost / Vendor --}}
                        <td class="px-4 py-3 text-xs text-gray-500">
                            @if($record->cost !== null)
                                <p class="font-semibold">₱{{ number_format($record->cost, 2) }}</p>
                            @else
                                <p>—</p>
                            @endif
                            @if($record->vendor)<p class="text-gray-400">{{ $record->vendor }}</p>@endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button"
                                    onclick="openMaintenanceModal({{ $record->id }})"
                                    class="rounded-md p-1.5 text-blue-500 hover:bg-blue-50 transition" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                @if($isSuperAdmin)
                                <button type="button"
                                    onclick="openDeleteConfirm({{ $record->id }}, '{{ addslashes($record->title) }}', '{{ addslashes($record->plate_number) }}')"
                                    class="rounded-md p-1.5 text-red-400 hover:bg-red-50 transition" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($records->hasPages())
        <div class="flex items-center justify-between border-t border-gray-100 px-4 py-3">
            <p class="text-xs text-gray-500">
                Showing {{ $records->firstItem() }}–{{ $records->lastItem() }} of {{ $records->total() }} maintenance records
            </p>
            <div class="flex items-center gap-1">
                {{-- Per page --}}
                <select onchange="changePerPage(this.value)"
                    class="mr-2 rounded-lg border border-gray-200 py-1 pl-2 pr-6 text-xs text-gray-600 focus:outline-none focus:ring-1 focus:ring-emerald-400">
                    @foreach([10,25,50] as $pp)
                        <option value="{{ $pp }}" {{ request('per_page',10) == $pp ? 'selected' : '' }}>{{ $pp }} / page</option>
                    @endforeach
                </select>
                {{-- Prev --}}
                @if($records->onFirstPage())
                    <span class="rounded-lg border border-gray-100 px-3 py-1 text-xs text-gray-300">← Prev</span>
                @else
                    <a href="{{ $records->previousPageUrl() }}" class="rounded-lg border border-gray-200 px-3 py-1 text-xs text-gray-600 hover:bg-gray-50">← Prev</a>
                @endif
                {{-- Pages --}}
                @foreach($records->getUrlRange(max(1,$records->currentPage()-2), min($records->lastPage(),$records->currentPage()+2)) as $page => $url)
                    <a href="{{ $url }}" class="rounded-lg border px-3 py-1 text-xs font-medium transition
                        {{ $page === $records->currentPage() ? 'border-emerald-500 bg-emerald-600 text-white' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">{{ $page }}</a>
                @endforeach
                {{-- Next --}}
                @if($records->hasMorePages())
                    <a href="{{ $records->nextPageUrl() }}" class="rounded-lg border border-gray-200 px-3 py-1 text-xs text-gray-600 hover:bg-gray-50">Next →</a>
                @else
                    <span class="rounded-lg border border-gray-100 px-3 py-1 text-xs text-gray-300">Next →</span>
                @endif
            </div>
        </div>
        @endif
    </div>
@endif

{{-- ══════════════════════════════════════════════════════════════════
     ADD / EDIT MODAL
══════════════════════════════════════════════════════════════════ --}}
<div id="maintenance-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="relative w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
        {{-- Header --}}
        <div class="sticky top-0 z-10 flex items-center justify-between rounded-t-2xl border-b border-gray-100 bg-white px-5 py-4">
            <h3 id="modal-title" class="text-base font-bold text-gray-900">Add Maintenance</h3>
            <button type="button" onclick="closeMaintenanceModal()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Form --}}
        <form id="maintenance-form" method="POST" action="{{ route('maintenance.store') }}" class="px-5 py-5 space-y-4">
            @csrf
            <input type="hidden" name="_method" id="form-method" value="POST">
            <input type="hidden" name="source_report_id" id="field-source-report-id" value="">
            <input type="hidden" name="_redirect_search"   value="{{ request('search') }}">
            <input type="hidden" name="_redirect_location" value="{{ $selectedLocation }}">
            <input type="hidden" name="_redirect_status"   value="{{ $statusFilter }}">
            <input type="hidden" name="_redirect_per_page" value="{{ request('per_page',10) }}">

            {{-- Created from a driver's Vehicle Report --}}
            <div id="source-report-banner" class="hidden rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2.5 text-xs text-emerald-800">
                <b>Created from driver report:</b> <span id="source-report-banner-text"></span> You can edit every field below before saving.
            </div>

            {{-- Notification Auto-Edit Prompt Banner --}}
            <div id="status-prompt-banner" class="hidden rounded-xl border border-blue-200 bg-blue-50/90 p-3 text-xs text-blue-900 shadow-2xs space-y-1">
                <div class="flex items-center gap-1.5 font-extrabold text-blue-900">
                    <span>💡 Maintenance Action Required:</span>
                </div>
                <p class="text-[11.5px] text-blue-800">This maintenance is scheduled for today. Update the <b>Status</b> below to <span class="font-bold text-blue-950 underline">Ongoing</span> if servicing has started, or <span class="font-bold text-emerald-950 underline">Completed</span> if finished.</p>
            </div>

            {{-- Vehicle (custom picker so every option can show the vehicle's photo) --}}
            <div class="relative" id="vehicle-picker">
                <label class="mb-1 block text-xs font-bold text-gray-600">Vehicle <span class="text-red-500">*</span></label>

                {{-- The real field: keeps required-validation, setField() and form submit working --}}
                <select name="vehicle_id" id="field-vehicle-id" required tabindex="-1" aria-hidden="true"
                    class="pointer-events-none absolute bottom-0 left-0 h-px w-full opacity-0">
                    <option value="">Select vehicle</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" data-location="{{ $v->location }}">{{ $v->plate_number }} – {{ $v->brand }} {{ $v->model }}</option>
                    @endforeach
                </select>

                <button type="button" id="vehicle-picker-trigger" onclick="toggleVehiclePicker()"
                    class="flex w-full items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    <span id="vehicle-picker-thumb" class="hidden h-9 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-100 bg-white"></span>
                    <span id="vehicle-picker-label" class="min-w-0 flex-1 truncate text-gray-400">Select vehicle</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <div id="vehicle-picker-panel" class="absolute left-0 right-0 z-50 mt-1 hidden rounded-xl border border-gray-200 bg-white p-2 shadow-xl">
                    <input type="text" id="vehicle-picker-search" oninput="filterVehiclePicker()" placeholder="Search plate or model..."
                        class="mb-2 w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    <div class="max-h-64 space-y-0.5 overflow-y-auto">
                        @foreach($vehicles as $v)
                            <button type="button" onclick="pickVehicle('{{ $v->id }}')"
                                class="vehicle-picker-option flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-left transition hover:bg-emerald-50"
                                data-id="{{ $v->id }}"
                                data-plate="{{ $v->plate_number }}"
                                data-model="{{ trim($v->brand.' '.$v->model) }}"
                                data-odometer="{{ $v->odometer }}"
                                data-search="{{ strtolower($v->plate_number.' '.$v->brand.' '.$v->model.' '.$v->location) }}">
                                <span class="vp-thumb flex h-10 w-14 shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-100 bg-white">
                                    @if($v->photo_url)
                                        <img src="{{ $v->photo_url }}" alt="" class="h-full w-full object-contain">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-gray-900">{{ $v->plate_number }}</span>
                                    <span class="block truncate text-xs text-gray-500">{{ trim($v->brand.' '.$v->model) }}@if($v->location) &middot; {{ $v->location }}@endif</span>
                                </span>
                            </button>
                        @endforeach
                        <p id="vehicle-picker-empty" class="hidden px-3 py-4 text-center text-xs text-gray-400">No vehicles found.</p>
                    </div>
                </div>
            </div>

            {{-- Existing active maintenance warning --}}
            <div id="active-maintenance-warning" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                <p class="mb-1 flex items-center gap-1 font-bold">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Existing active maintenance for this vehicle
                </p>
                <div id="active-maintenance-list" class="space-y-1"></div>
                <p class="mt-1.5 text-amber-700">This is a scheduling reminder only. You may still save another record for the same date(s).</p>
            </div>

            {{-- Type & Priority --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600">Type <span class="text-red-500">*</span></label>
                    <select name="type" id="field-type" required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        @foreach($maintenanceTypes as $mt)
                            <option value="{{ $mt }}">{{ $mt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600">Priority <span class="text-red-500">*</span></label>
                    <select name="priority" id="field-priority" required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            {{-- Title --}}
            <div>
                <label class="mb-1 block text-xs font-bold text-gray-600">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="field-title" required placeholder="e.g. Replace worn tires"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
            </div>

            {{-- Description --}}
            <div>
                <label class="mb-1 block text-xs font-bold text-gray-600">Description</label>
                <textarea name="description" id="field-description" rows="3" placeholder="Work needed, parts, observations..."
                    class="w-full resize-y rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"></textarea>
            </div>

            {{-- Scheduled dates --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" name="scheduled_date" id="field-scheduled-date" required
                        onchange="onDateChange()"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600">End Date</label>
                    <input type="date" name="scheduled_date_end" id="field-scheduled-date-end"
                        onchange="onDateChange()"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>
            </div>

            {{-- Mini calendar --}}
            <div id="schedule-calendar-wrap" class="hidden rounded-xl border border-blue-100 bg-blue-50/50 p-3">
                <div class="mb-2 flex items-center justify-between">
                    <button type="button" onclick="calendarPrev()" class="flex h-7 w-7 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 text-base">‹</button>
                    <div class="text-center">
                        <p class="text-xs font-extrabold text-slate-700">Maintenance Schedule</p>
                        <p id="calendar-month-label" class="text-[11px] text-gray-400"></p>
                    </div>
                    <button type="button" onclick="calendarNext()" class="flex h-7 w-7 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 text-base">›</button>
                </div>
                <div id="calendar-grid" class="grid grid-cols-7 gap-0.5 text-center text-[10px]"></div>
                <div id="calendar-overlap-warning" class="hidden mt-2 rounded-lg bg-red-50 px-3 py-2 text-[11px] font-bold text-red-700">
                    Your selected date(s) overlap existing maintenance. You may still continue if both jobs are intended.
                </div>
                <div class="mt-2 flex flex-wrap gap-3 text-[10.5px] text-gray-500">
                    <span class="flex items-center gap-1"><i class="inline-block h-2.5 w-2.5 rounded-sm bg-amber-400"></i> Existing maintenance</span>
                    <span class="flex items-center gap-1"><i class="inline-block h-2.5 w-2.5 rounded-sm border-2 border-teal-500 bg-white"></i> Selected date(s)</span>
                </div>
            </div>

            {{-- Status & Completed Date --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="field-status" required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600">Completed Date</label>
                    <input type="date" name="completed_date" id="field-completed-date"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>
            </div>

            {{-- Odometer & Cost --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600">Odometer at Service (km)</label>
                    <div class="relative flex items-center rounded-lg border border-gray-200 bg-white focus-within:ring-2 focus-within:ring-emerald-400">
                        <input type="number" name="odometer" id="field-odometer" min="0" placeholder="e.g. 25000"
                            class="w-full rounded-l-lg border-0 bg-transparent px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0">
                        <div class="flex h-9 shrink-0 items-center justify-center border-l border-gray-200 bg-slate-50/80 px-3 text-xs font-semibold text-slate-500 rounded-r-lg">
                            km
                        </div>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600">
                        Trigger Odometer (km)
                        <span class="ml-1 font-normal text-slate-400" title="Maintenance becomes due when vehicle reaches this km reading">ⓘ</span>
                    </label>
                    <div class="relative flex items-center rounded-lg border border-gray-200 bg-white focus-within:ring-2 focus-within:ring-amber-400">
                        <input type="number" name="trigger_km" id="field-trigger-km" min="0" placeholder="e.g. 30000"
                            class="w-full rounded-l-lg border-0 bg-transparent px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0">
                        <div class="flex h-9 shrink-0 items-center justify-center border-l border-gray-200 bg-amber-50 px-3 text-xs font-semibold text-amber-600 rounded-r-lg">
                            km
                        </div>
                    </div>
                    <p class="mt-0.5 text-[10px] text-slate-400">Due when odometer reaches this km</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600">Cost (₱)</label>
                    <input type="number" name="cost" id="field-cost" min="0" step="0.01" placeholder="0.00"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>
            </div>

            {{-- Vendor --}}
            <div>
                <label class="mb-1 block text-xs font-bold text-gray-600">Vendor / Shop</label>
                <input type="text" name="vendor" id="field-vendor" placeholder="Shop name or supplier"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
            </div>

            {{-- Notes --}}
            <div>
                <label class="mb-1 block text-xs font-bold text-gray-600">Notes</label>
                <textarea name="notes" id="field-notes" rows="2" placeholder="Admin notes"
                    class="w-full resize-y rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"></textarea>
            </div>

            <button type="submit" id="modal-submit-btn"
                class="w-full rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 py-2.5 text-sm font-extrabold text-white shadow-sm hover:from-emerald-700 hover:to-emerald-800 transition">
                Add Maintenance
            </button>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     DELETE CONFIRM MODAL
══════════════════════════════════════════════════════════════════ --}}
<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </div>
        <h3 class="mb-1 text-base font-bold text-gray-900">Delete Maintenance Record?</h3>
        <p id="delete-modal-message" class="mb-5 text-sm text-gray-500"></p>
        <div class="flex gap-2">
            <button type="button" onclick="closeDeleteConfirm()"
                class="flex-1 rounded-xl border border-gray-200 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">Cancel</button>
            <form id="delete-form" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="w-full rounded-xl bg-red-600 px-6 py-2 text-sm font-bold text-white hover:bg-red-700 transition">Delete</button>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════════════ --}}
@php
    // Built here (instead of inline inside @json) because Blade's directive
    // parser only tracks round-bracket balance, not square brackets — a
    // multi-line array literal with 3+ keys directly inside @json(...) gets
    // silently truncated at compile time and breaks the view.
    $maintenanceRecordsForJs = $records->map(fn($r) => [
        'id'                 => $r->id,
        'vehicle_id'         => $r->vehicle_id,
        'plate_number'       => $r->plate_number,
        'vehicle_label'      => $r->vehicle_label,
        'type'               => $r->type,
        'title'              => $r->title,
        'description'        => $r->description,
        'status'             => $r->status,
        'priority'           => $r->priority,
        'scheduled_date'     => $r->scheduled_date?->format('Y-m-d'),
        'scheduled_date_end' => $r->scheduled_date_end?->format('Y-m-d'),
        'completed_date'     => $r->completed_date?->format('Y-m-d'),
        'odometer'           => $r->odometer,
        'trigger_km'         => $r->trigger_km,
        'cost'               => $r->cost,
        'vendor'             => $r->vendor,
        'notes'              => $r->notes,
    ])->values();
@endphp
<script>
// ── Record data from PHP → JS ──────────────────────────────────────────────
const maintenanceRecords = @json($maintenanceRecordsForJs);
const sourceReportFromDriver = @json($sourceReport);

document.addEventListener('DOMContentLoaded', function () {
    if (sourceReportFromDriver) {
        openMaintenanceModalFromReport(sourceReportFromDriver);
    }
});


// All active (scheduled/ongoing) records across all pages for calendar conflict detection
// We only have the current page here, but that's enough for a scheduling hint.
const allActiveRecords = maintenanceRecords.filter(r => r.status === 'scheduled' || r.status === 'ongoing');

// ── Modal open/close ───────────────────────────────────────────────────────
let currentEditId = null;

function openMaintenanceModal(id = null, showPrompt = false) {
    currentEditId = id;
    const modal   = document.getElementById('maintenance-modal');
    const form    = document.getElementById('maintenance-form');
    const title   = document.getElementById('modal-title');
    const btn     = document.getElementById('modal-submit-btn');
    const method  = document.getElementById('form-method');

    resetForm();

    const promptBanner = document.getElementById('status-prompt-banner');
    if (promptBanner) {
        promptBanner.classList.toggle('hidden', !showPrompt);
    }

    if (id) {
        const rec = maintenanceRecords.find(r => r.id == id);
        if (!rec) return;
        title.textContent  = 'Edit Maintenance';
        btn.textContent    = 'Save Changes';
        method.value       = 'PUT';
        form.action        = `/maintenance/${id}`;

        setField('vehicle_id',         rec.vehicle_id);
        setField('type',               rec.type);
        setField('priority',           rec.priority);
        setField('title',              rec.title);
        setField('description',        rec.description ?? '');
        setField('status',             (showPrompt && rec.status === 'scheduled') ? 'ongoing' : rec.status);
        setField('scheduled_date',     rec.scheduled_date ?? '');
        setField('scheduled_date_end', rec.scheduled_date_end ?? '');
        setField('completed_date',     rec.completed_date ?? '');
        setField('odometer',           rec.odometer ?? '');
        setField('trigger_km',         rec.trigger_km ?? '');
        setField('cost',               rec.cost ?? '');
        setField('vendor',             rec.vendor ?? '');
        setField('notes',              rec.notes ?? '');

        onVehicleChange(rec.vehicle_id, id);
        updateCalendar();
    } else {
        title.textContent = 'Add Maintenance';
        btn.textContent   = 'Add Maintenance';
        method.value      = 'POST';
        form.action       = '{{ route("maintenance.store") }}';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeMaintenanceModal() {
    document.getElementById('maintenance-modal').classList.add('hidden');
    document.getElementById('maintenance-modal').classList.remove('flex');
    const promptBanner = document.getElementById('status-prompt-banner');
    if (promptBanner) promptBanner.classList.add('hidden');
    currentEditId = null;
    if (window.location.search.includes('reportId')) {
        const url = new URL(window.location.href);
        url.searchParams.delete('reportId');
        window.history.replaceState({}, '', url);
    }
}

function resetForm() {
    const fields = ['vehicle_id','type','priority','title','description','status','scheduled_date','scheduled_date_end','completed_date','odometer','trigger_km','cost','vendor','notes'];
    fields.forEach(f => setField(f, f === 'type' ? 'Preventive' : f === 'priority' ? 'normal' : f === 'status' ? 'scheduled' : ''));
    document.getElementById('field-source-report-id').value = '';
    document.getElementById('source-report-banner').classList.add('hidden');
    document.getElementById('active-maintenance-warning').classList.add('hidden');
    document.getElementById('schedule-calendar-wrap').classList.add('hidden');
}

// ── Prefill from a driver's Vehicle Report (?reportId=... on this page) ────
function openMaintenanceModalFromReport(report) {
    currentEditId = null;
    const modal  = document.getElementById('maintenance-modal');
    const form   = document.getElementById('maintenance-form');
    const title  = document.getElementById('modal-title');
    const btn    = document.getElementById('modal-submit-btn');
    const method = document.getElementById('form-method');

    resetForm();

    title.textContent = 'Add Maintenance';
    btn.textContent    = 'Add Maintenance';
    method.value       = 'POST';
    form.action        = '{{ route("maintenance.store") }}';

    const typeMap = { Tire: 'Tire', Battery: 'Battery', Other: 'Other' };

    setField('vehicle_id',  report.vehicle_id);
    setField('type',        typeMap[report.issue_type] || 'Repair');
    setField('priority',    report.priority);
    setField('title',       `Driver report: ${report.issue_type} issue`);
    setField('description', report.description);
    setField('notes',       `Reported by ${report.driver_name}.`);
    document.getElementById('field-source-report-id').value = report.id;

    const banner = document.getElementById('source-report-banner');
    document.getElementById('source-report-banner-text').textContent =
        `${report.driver_name} reported a ${report.issue_type.toLowerCase()} issue.`;
    banner.classList.remove('hidden');

    onVehicleChange(report.vehicle_id);

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}


function setField(name, value) {
    const el = document.getElementById(`field-${name.replace(/_/g,'-')}`);
    if (el) el.value = value ?? '';
    if (name === 'vehicle_id') syncVehiclePicker();
}

// ── Vehicle picker (dropdown with photos) ──────────────────────────────────
function toggleVehiclePicker(force) {
    const panel = document.getElementById('vehicle-picker-panel');
    const open  = typeof force === 'boolean' ? force : panel.classList.contains('hidden');
    panel.classList.toggle('hidden', !open);
    if (open) {
        const search = document.getElementById('vehicle-picker-search');
        search.value = '';
        filterVehiclePicker();
        setTimeout(() => search.focus(), 0);
    }
}

function filterVehiclePicker() {
    const q = document.getElementById('vehicle-picker-search').value.trim().toLowerCase();
    let shown = 0;
    document.querySelectorAll('.vehicle-picker-option').forEach(opt => {
        const match = !q || opt.dataset.search.includes(q);
        opt.classList.toggle('hidden', !match);
        if (match) shown++;
    });
    document.getElementById('vehicle-picker-empty').classList.toggle('hidden', shown > 0);
}

function pickVehicle(id) {
    document.getElementById('field-vehicle-id').value = id;
    syncVehiclePicker();
    toggleVehiclePicker(false);
    onVehicleChange(id);
}

// Make the visible button reflect whatever the hidden <select> currently holds.
function syncVehiclePicker() {
    const sel   = document.getElementById('field-vehicle-id');
    const thumb = document.getElementById('vehicle-picker-thumb');
    const label = document.getElementById('vehicle-picker-label');
    if (!sel || !thumb || !label) return;
    const opt = sel.value ? document.querySelector(`.vehicle-picker-option[data-id="${sel.value}"]`) : null;
    if (!opt) {
        thumb.classList.add('hidden');
        thumb.classList.remove('flex');
        label.className = 'min-w-0 flex-1 truncate text-gray-400';
        label.textContent = 'Select vehicle';
        return;
    }
    thumb.innerHTML = opt.querySelector('.vp-thumb').innerHTML;
    thumb.classList.remove('hidden');
    thumb.classList.add('flex');
    label.className = 'min-w-0 flex-1 truncate';
    label.innerHTML = `<span class="font-bold text-gray-900">${escHtml(opt.dataset.plate)}</span> <span class="text-gray-500">${escHtml(opt.dataset.model)}</span>`;
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('#vehicle-picker')) toggleVehiclePicker(false);
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') toggleVehiclePicker(false);
});
document.addEventListener('change', (e) => {
    if (e.target && e.target.id === 'field-status' && e.target.value === 'completed') {
        const completedInput = document.getElementById('field-completed-date');
        if (completedInput && !completedInput.value) {
            completedInput.value = new Date().toISOString().split('T')[0];
        }
    }
});

// ── Vehicle change: show active maintenance warning ───────────────────────
function onVehicleChange(vehicleId, excludeId = null) {
    excludeId = excludeId ?? currentEditId;
    const active = allActiveRecords.filter(r => r.vehicle_id == vehicleId && r.id != excludeId);
    const wrap   = document.getElementById('active-maintenance-warning');
    const list   = document.getElementById('active-maintenance-list');

    // Auto fill odometer from selected vehicle if empty or creating new
    const opt = document.querySelector(`.vehicle-picker-option[data-id="${vehicleId}"]`);
    if (opt && opt.dataset.odometer) {
        const odoInput = document.getElementById('field-odometer');
        if (odoInput && (!currentEditId || !odoInput.value)) {
            odoInput.value = opt.dataset.odometer;
        }
    }

    if (active.length === 0) {
        wrap.classList.add('hidden');
    } else {
        list.innerHTML = active.map(r => {
            const start = r.scheduled_date ? formatDate(r.scheduled_date) : '—';
            const end   = r.scheduled_date_end && r.scheduled_date_end !== r.scheduled_date ? ` – ${formatDate(r.scheduled_date_end)}` : '';
            return `<div class="rounded-md bg-white border border-amber-100 px-2 py-1"><b>${escHtml(r.title)}</b> · ${start}${end} · ${capitalize(r.status)}</div>`;
        }).join('');
        wrap.classList.remove('hidden');
    }

    updateCalendar();
}

// ── Mini scheduling calendar ───────────────────────────────────────────────
let calYear, calMonth;

function onDateChange() { updateCalendar(); }

function updateCalendar() {
    const vehicleId  = document.getElementById('field-vehicle-id')?.value;
    const startDate  = document.getElementById('field-scheduled-date')?.value;
    const endDate    = document.getElementById('field-scheduled-date_end')?.value
                    || document.getElementById('field-scheduled-date-end')?.value;
    const wrap       = document.getElementById('schedule-calendar-wrap');

    if (!vehicleId) { wrap?.classList.add('hidden'); return; }
    wrap?.classList.remove('hidden');

    if (startDate) {
        const [sy, sm] = startDate.split('-').map(Number);
        if (sy && sm) { calYear = sy; calMonth = sm - 1; }
    }
    if (!calYear) {
        const today = new Date();
        calYear = today.getFullYear();
        calMonth = today.getMonth();
    }

    renderCalendar(vehicleId, startDate, endDate || startDate);
}

function onCalendarDayClick(dateStr) {
    const startInput = document.getElementById('field-scheduled-date');
    const endInput   = document.getElementById('field-scheduled-date-end') || document.getElementById('field-scheduled-date_end');
    
    const sVal = startInput ? startInput.value : '';
    const eVal = endInput ? endInput.value : '';

    if (!sVal || (sVal && eVal && sVal !== eVal)) {
        // First click or reset: set Start Date to clicked date, clear or set End Date
        if (startInput) startInput.value = dateStr;
        if (endInput) endInput.value = dateStr;
    } else if (sVal && (sVal === eVal || !eVal)) {
        // Second click: select range
        if (dateStr < sVal) {
            if (endInput) endInput.value = sVal;
            if (startInput) startInput.value = dateStr;
        } else {
            if (endInput) endInput.value = dateStr;
        }
    }

    updateCalendar();
}

function renderCalendar(vehicleId, startDate, endDate) {
    if (!calYear && calYear !== 0) return;

    const label   = document.getElementById('calendar-month-label');
    const grid    = document.getElementById('calendar-grid');
    const overlap = document.getElementById('calendar-overlap-warning');

    const firstOfMonth  = new Date(calYear, calMonth, 1);
    const daysInMonth   = new Date(calYear, calMonth + 1, 0).getDate();
    if (label) label.textContent = firstOfMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

    // Build existing maintenance days for this vehicle
    const existingDays = new Map();
    const excludeId = currentEditId;
    allActiveRecords
        .filter(r => r.vehicle_id == vehicleId && r.id != excludeId)
        .forEach(r => {
            const s = new Date(`${r.scheduled_date}T00:00:00`);
            const e = new Date(`${r.scheduled_date_end || r.scheduled_date}T00:00:00`);
            for (const d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) {
                const k = localKey(d);
                existingDays.set(k, [...(existingDays.get(k) || []), r.title]);
            }
        });

    // Build selected days
    const selectedDays = new Set();
    if (startDate) {
        const s = new Date(`${startDate}T00:00:00`);
        const e = new Date(`${endDate || startDate}T00:00:00`);
        if (e >= s) for (const d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) selectedDays.add(localKey(d));
    }

    const hasOverlap = [...selectedDays].some(k => existingDays.has(k));
    if (overlap) overlap.classList.toggle('hidden', !hasOverlap);

    const dayHeaders = ['S','M','T','W','T','F','S'].map(d => `<div class="py-0.5 text-[9px] font-bold text-gray-400">${d}</div>`).join('');
    const blanks = Array(firstOfMonth.getDay()).fill('<div></div>').join('');
    const cells  = Array.from({ length: daysInMonth }, (_, i) => {
        const day = i + 1;
        const k   = `${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        const ex  = existingDays.get(k);
        const sel = selectedDays.has(k);
        const title = ex ? `Existing: ${ex.join(', ')}` : 'Click to select date';
        let cls = 'flex items-center justify-center rounded text-[11px] h-6 cursor-pointer transition transform hover:scale-105 select-none ';
        if (ex) {
            cls += 'bg-amber-400 text-white font-bold hover:bg-amber-500 ';
        } else if (sel) {
            cls += 'bg-teal-50 font-extrabold text-teal-800 ring-2 ring-teal-500 shadow-sm ';
        } else {
            cls += 'bg-white text-slate-600 ring-1 ring-gray-200 hover:bg-teal-50 hover:text-teal-700 hover:ring-2 hover:ring-teal-400 ';
        }
        return `<div class="${cls}" title="${escHtml(title)}" onclick="onCalendarDayClick('${k}')">${day}</div>`;
    }).join('');

    if (grid) grid.innerHTML = dayHeaders + blanks + cells;
}

function calendarPrev() {
    const d = new Date(calYear, calMonth - 1, 1);
    calYear = d.getFullYear(); calMonth = d.getMonth();
    const vId = document.getElementById('field-vehicle-id')?.value;
    const s   = document.getElementById('field-scheduled-date')?.value;
    const e   = document.getElementById('field-scheduled-date-end')?.value;
    if (vId) renderCalendar(vId, s, e || s);
}
function calendarNext() {
    const d = new Date(calYear, calMonth + 1, 1);
    calYear = d.getFullYear(); calMonth = d.getMonth();
    const vId = document.getElementById('field-vehicle-id')?.value;
    const s   = document.getElementById('field-scheduled-date')?.value;
    const e   = document.getElementById('field-scheduled-date-end')?.value;
    if (vId) renderCalendar(vId, s, e || s);
}

// ── Delete confirm ─────────────────────────────────────────────────────────
function openDeleteConfirm(id, title, plate) {
    document.getElementById('delete-modal-message').innerHTML =
        `Delete <strong>${escHtml(title)}</strong> for <strong>${escHtml(plate)}</strong>?`;
    document.getElementById('delete-form').action = `/maintenance/${id}`;
    document.getElementById('delete-modal').classList.remove('hidden');
    document.getElementById('delete-modal').classList.add('flex');
}
function closeDeleteConfirm() {
    document.getElementById('delete-modal').classList.add('hidden');
    document.getElementById('delete-modal').classList.remove('flex');
}

// ── Location dropdown ──────────────────────────────────────────────────────
function toggleLocationDropdown() {
    document.getElementById('location-dropdown')?.classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
    if (!document.getElementById('location-dropdown-wrap')?.contains(e.target)) {
        document.getElementById('location-dropdown')?.classList.add('hidden');
    }
});

// ── Search with debounce ───────────────────────────────────────────────────
let searchTimer;
function onMaintenanceSearchInput() {
    clearTimeout(searchTimer);
    document.getElementById('maintenance-search-spinner')?.classList.remove('hidden');
    document.getElementById('maintenance-search-clear')?.classList.add('hidden');
    searchTimer = setTimeout(() => {
        document.getElementById('maintenance-search-form')?.submit();
    }, 420);
}

// ── Per-page change ────────────────────────────────────────────────────────
function changePerPage(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

// ── Helpers ───────────────────────────────────────────────────────────────
function localKey(date) {
    return `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
}
function formatDate(str) {
    if (!str) return '—';
    const [y,m,d] = str.split('-');
    return new Date(y,m-1,d).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
}
function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Auto-open schedule modal or highlight record from Notification Bell redirect ──
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const highlightId = params.get('highlight_id');
    const autoEdit = params.get('auto_edit');
    const vehicleId = params.get('schedule_vehicle_id');
    const type = params.get('type');

    if (highlightId) {
        const row = document.getElementById(`maintenance-row-${highlightId}`);
        if (row) {
            setTimeout(() => {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('bg-emerald-50/80', 'ring-2', 'ring-emerald-500', 'border-l-emerald-600', 'shadow-md');
                
                let pulseCount = 0;
                const interval = setInterval(() => {
                    row.classList.toggle('bg-emerald-100');
                    pulseCount++;
                    if (pulseCount >= 6) {
                        clearInterval(interval);
                        row.classList.remove('ring-2', 'ring-emerald-500', 'bg-emerald-100', 'shadow-md');
                        setTimeout(() => {
                            row.classList.remove('bg-emerald-50/80', 'border-l-emerald-600');
                        }, 1200);
                    }
                }, 350);
            }, 150);
        }

        // Auto-open Edit Modal ONLY if auto_edit=1 flag is passed AND record is action-due
        if (autoEdit === '1' || autoEdit === 'true') {
            const rec = maintenanceRecords.find(r => r.id == highlightId);
            const todayStr = localKey(new Date());
            const isActionDue = rec && (
                (rec.status === 'scheduled' && rec.scheduled_date === todayStr) ||
                (rec.status === 'ongoing' && (rec.scheduled_date_end ? rec.scheduled_date_end <= todayStr : rec.scheduled_date <= todayStr))
            );
            if (isActionDue) {
                setTimeout(() => {
                    openMaintenanceModal(highlightId, true);
                }, 400);
            }
        }
    } else if (vehicleId) {
        openMaintenanceModal();
        pickVehicle(vehicleId);
        if (type) setField('type', type);
        setField('title', type ? `${type} Service` : 'Routine Maintenance');
        const today = new Date().toISOString().split('T')[0];
        setField('scheduled_date', today);
    }
});

// ── Auto-dismiss flash ────────────────────────────────────────────────────
const flash = document.getElementById('flash-success');
if (flash) setTimeout(() => flash.style.display = 'none', 4000);
</script>
@endsection
