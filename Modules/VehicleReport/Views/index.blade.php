@extends('layouts.app')

@section('title', 'Vehicle Reports')
@section('page-title', 'Vehicle Reports')

@section('content')
@php
    $statusStyles = [
        'submitted'              => ['label' => 'Submitted',              'bg' => 'bg-amber-100',  'text' => 'text-amber-800'],
        'reviewed'                => ['label' => 'Reviewed',               'bg' => 'bg-sky-100',    'text' => 'text-sky-700'],
        'maintenance_scheduled'  => ['label' => 'Maintenance Scheduled',  'bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
        'ongoing'                 => ['label' => 'Work Ongoing',           'bg' => 'bg-orange-100', 'text' => 'text-orange-700'],
        'resolved'                => ['label' => 'Resolved',               'bg' => 'bg-green-100',  'text' => 'text-green-700'],
        'dismissed'               => ['label' => 'Dismissed',              'bg' => 'bg-slate-100',  'text' => 'text-slate-500'],
    ];
    $priorityStyles = [
        'normal' => 'text-green-700',
        'high'   => 'text-amber-600',
        'urgent' => 'text-red-600',
    ];
    $issueTypes = ['Tire', 'Engine', 'Brake', 'Battery', 'Electrical', 'Body / Exterior', 'Other'];
@endphp

{{-- ── Page header ─────────────────────────────────────────────────── --}}
<div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $isAdmin ? 'Vehicle Reports' : 'Report Vehicle Issue' }}</h1>
        <p class="text-sm text-slate-500">
            {{ $isAdmin ? $reports->count() . ' ' . \Illuminate\Support\Str::plural('driver report', $reports->count()) : 'Tell your admin about a problem with your assigned vehicle' }}
        </p>
    </div>

    @unless($isAdmin)
        <button type="button" onclick="openReportModal()" @disabled(! $assignedVehicle)
            class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-[#005a36] px-4 text-xs font-extrabold text-white shadow-sm transition hover:bg-[#00432a] cursor-pointer disabled:cursor-not-allowed disabled:opacity-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            <span>Report an Issue</span>
        </button>
    @endunless
</div>

{{-- session('success') / session('error') are shown as toasts by the layout --}}
@if($isAdmin && $errors->any())
    {{-- The review modal is closed after a redirect, so surface validation errors on the page --}}
    <div role="alert" class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

{{-- ── Stat cards ──────────────────────────────────────────────────── --}}
<div class="mb-4 grid grid-cols-1 gap-2.5 sm:grid-cols-3">
    <div class="rounded-xl border border-gray-100 bg-white p-3.5 shadow-sm">
        <div class="text-[0.6875rem] font-bold uppercase text-amber-700">{{ $isAdmin ? 'New Reports' : 'My Submitted' }}</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">{{ $counts['submitted'] }}</div>
    </div>
    <div class="rounded-xl border border-gray-100 bg-white p-3.5 shadow-sm">
        <div class="text-[0.6875rem] font-bold uppercase text-sky-700">In Progress</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">{{ $counts['active'] }}</div>
    </div>
    <div class="rounded-xl border border-gray-100 bg-white p-3.5 shadow-sm">
        <div class="text-[0.6875rem] font-bold uppercase text-green-700">Resolved</div>
        <div class="mt-1 text-2xl font-extrabold text-gray-900">{{ $counts['resolved'] }}</div>
    </div>
</div>

@unless($isAdmin)
    @unless($assignedVehicle)
        <div class="mb-3 rounded-lg border border-orange-200 bg-orange-50 px-3.5 py-2.5 text-xs text-orange-800">
            You have no permanently assigned vehicle, so you cannot submit a report yet. Please ask an admin to assign one.
        </div>
    @endunless
@endunless

{{-- ── Report list ─────────────────────────────────────────────────── --}}
@if($reports->isEmpty())
    <div class="grid min-h-[170px] place-items-center gap-1.5 rounded-xl border border-dashed border-gray-200 bg-white text-center text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
        <b class="text-sm text-gray-600">No vehicle reports yet</b>
        <span class="text-xs">{{ $isAdmin ? 'Driver-submitted issues will appear here.' : 'Use Report an Issue when you notice a problem.' }}</span>
    </div>
@else
    <div class="grid gap-2.5">
        @foreach($reports as $report)
            @php $status = $statusStyles[$report->status] ?? $statusStyles['submitted']; @endphp
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="text-[0.6875rem] font-bold uppercase text-gray-400">{{ $report->plate_number }} &middot; {{ $report->vehicle_label }}</div>
                        <div class="mt-0.5 text-[0.9375rem] font-extrabold text-gray-900">
                            {{ $report->issue_type }} issue
                            <span class="text-xs font-bold {{ $priorityStyles[$report->priority] ?? 'text-green-700' }}">&middot; {{ $report->priority }}</span>
                        </div>
                    </div>
                    <span class="rounded-full {{ $status['bg'] }} {{ $status['text'] }} px-2.5 py-1 text-[0.6875rem] font-extrabold">{{ $status['label'] }}</span>
                </div>
                <p class="my-2 whitespace-pre-line break-words text-[0.8125rem] leading-relaxed text-gray-600">{{ $report->description }}</p>
                <div class="text-xs text-gray-400">
                    Reported by <b class="text-gray-600">{{ $report->driver_name }}</b>
                    @if($report->related_maintenance_title)
                        &middot; Linked maintenance: <b class="text-gray-600">{{ $report->related_maintenance_title }}</b>
                    @endif
                </div>
                @if($report->admin_notes)
                    <div class="mt-2 whitespace-pre-line break-words border-l-2 border-emerald-500 bg-gray-50 px-2.5 py-2 text-xs text-gray-700"><b>Admin update:</b> {{ $report->admin_notes }}</div>
                @endif
                @if($isAdmin)
                    <button type="button"
                        onclick="openReviewModal({{ $report->id }}, {{ Illuminate\Support\Js::from($report->plate_number.' · '.$report->issue_type) }}, {{ Illuminate\Support\Js::from($report->driver_name.': '.$report->description) }}, {{ Illuminate\Support\Js::from($report->status) }}, {{ Illuminate\Support\Js::from($report->admin_notes) }}, {{ Illuminate\Support\Js::from($report->related_maintenance_id) }}, {{ $report->vehicle_id }})"
                        class="mt-2.5 inline-flex items-center gap-1.5 rounded-lg border border-green-200 bg-green-50 px-2.5 py-1.5 text-xs font-extrabold text-green-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        Review / Update
                    </button>
                @endif
            </div>
        @endforeach
    </div>
@endif

{{-- ── Report-issue modal (driver) ─────────────────────────────────── --}}
@unless($isAdmin)
<div id="report-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="report-modal-title" class="relative w-full max-w-md max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between rounded-t-2xl border-b border-gray-100 px-5 py-4">
            <h3 id="report-modal-title" class="text-base font-bold text-gray-900">Report Vehicle Issue</h3>
            <button type="button" onclick="closeReportModal()" aria-label="Close" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('vehicle-reports.store') }}" class="space-y-4 px-5 py-5" onsubmit="document.getElementById('report-submit-btn').disabled = true;">
            @csrf
            @if($errors->any())
                <div class="rounded-lg bg-rose-50 px-2.5 py-2 text-[0.8125rem] text-rose-700">{{ $errors->first() }}</div>
            @endif
            @if(isset($assignedVehicles) && $assignedVehicles->count() > 1)
                @php
                    $initialSelectedId = old('vehicle_id', $assignedVehicles->first()->id);
                @endphp
                <div x-data="{
                    open: false,
                    selectedId: '{{ $initialSelectedId }}',
                    vehicles: {{ json_encode($assignedVehicles->map(fn($v) => [
                        'id' => $v->id,
                        'plate' => $v->plate_number,
                        'name' => $v->brand . ' ' . $v->model,
                        'type' => $v->vehicle_type ?? '',
                        'photo' => $v->photo_url,
                    ])) }}
                }" @keydown.escape="if (open) { open = false; $event.stopPropagation(); }" class="relative">
                    <label class="mb-1 block text-xs font-bold text-gray-600">Assigned Vehicle <span class="text-red-500">*</span></label>
                    
                    {{-- Hidden input for form submission --}}
                    <input type="hidden" name="vehicle_id" :value="selectedId" required>

                    {{-- Dropdown Trigger Button --}}
                    <button type="button" @click="open = !open" @click.outside="open = false"
                        class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white p-2.5 text-left text-sm transition hover:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        <template x-for="v in vehicles" :key="v.id">
                            <div x-show="selectedId == v.id" class="flex items-center gap-3 min-w-0">
                                <div class="flex h-10 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 border border-slate-200 overflow-hidden">
                                    <template x-if="v.photo">
                                        <img :src="v.photo" :alt="v.name" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!v.photo">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
                                        </svg>
                                    </template>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800 text-xs truncate" x-text="v.plate + ' — ' + v.name"></div>
                                    <div class="text-[0.6875rem] text-slate-500" x-text="v.type || 'Vehicle'"></div>
                                </div>
                            </div>
                        </template>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    {{-- Dropdown Menu Options with Photos --}}
                    <div x-show="open" x-transition.origin.top.duration.150ms style="display: none;"
                        class="absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl space-y-1">
                        <template x-for="v in vehicles" :key="v.id">
                            <button type="button" @click="selectedId = v.id; open = false"
                                class="flex w-full cursor-pointer items-center justify-between rounded-lg p-2 text-left transition hover:bg-emerald-50"
                                :class="selectedId == v.id ? 'bg-emerald-50/70 border border-emerald-200/60' : 'border border-transparent'">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-10 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 border border-slate-200 overflow-hidden">
                                        <template x-if="v.photo">
                                            <img :src="v.photo" :alt="v.name" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!v.photo">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
                                            </svg>
                                        </template>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 truncate" x-text="v.plate + ' — ' + v.name"></p>
                                        <p class="text-[0.6875rem] text-slate-500" x-text="v.type || 'Vehicle'"></p>
                                    </div>
                                </div>
                                <div x-show="selectedId == v.id" class="text-emerald-600 shrink-0 pr-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            @elseif($assignedVehicle)
                <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-2.5 text-[0.8125rem] text-emerald-900">
                    <input type="hidden" name="vehicle_id" value="{{ $assignedVehicle->id }}">
                    <div class="flex h-10 w-14 shrink-0 items-center justify-center rounded-lg bg-white border border-emerald-200 overflow-hidden shadow-2xs">
                        @if($assignedVehicle->photo_url)
                            <img src="{{ $assignedVehicle->photo_url }}" alt="{{ $assignedVehicle->model }}" class="h-full w-full object-cover">
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <b class="text-xs font-black text-emerald-950">{{ $assignedVehicle->plate_number }}</b>
                        <p class="text-[0.6875rem] text-emerald-800 font-medium">{{ $assignedVehicle->brand }} {{ $assignedVehicle->model }}</p>
                    </div>
                </div>
            @endif
            <div>
                <label for="report-issue-type" class="mb-1 block text-xs font-bold text-gray-600">Issue Type <span class="text-red-500">*</span></label>
                <select name="issue_type" id="report-issue-type" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    @foreach($issueTypes as $type)
                        <option value="{{ $type }}" @selected(old('issue_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="report-priority" class="mb-1 block text-xs font-bold text-gray-600">Priority <span class="text-red-500">*</span></label>
                <select name="priority" id="report-priority" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normal</option>
                    <option value="high" @selected(old('priority') === 'high')>High</option>
                    <option value="urgent" @selected(old('priority') === 'urgent')>Urgent — unsafe to use</option>
                </select>
            </div>
            <div>
                <label for="report-description" class="mb-1 block text-xs font-bold text-gray-600">What did you observe? <span class="text-red-500">*</span></label>
                <textarea name="description" id="report-description" required placeholder="Example: Front-left tire is visibly worn and losing air."
                    class="min-h-[105px] w-full resize-y rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">{{ old('description') }}</textarea>
            </div>
            <button type="submit" id="report-submit-btn" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                Submit Report
            </button>
        </form>
    </div>
</div>
@endunless

{{-- ── Review modal (admin) ────────────────────────────────────────── --}}
@if($isAdmin)
<div id="review-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="review-modal-title" class="relative w-full max-w-md max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between rounded-t-2xl border-b border-gray-100 px-5 py-4">
            <h3 id="review-modal-title" class="text-base font-bold text-gray-900">Review Driver Report</h3>
            <button type="button" onclick="closeReviewModal()" aria-label="Close" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="review-form" method="POST" class="space-y-4 px-5 py-5" onsubmit="document.getElementById('review-submit-btn').disabled = true;">
            @csrf
            @method('PUT')
            @if($errors->any())
                <div class="rounded-lg bg-rose-50 px-2.5 py-2 text-[0.8125rem] text-rose-700">{{ $errors->first() }}</div>
            @endif
            <div class="grid gap-0.5 rounded-lg bg-green-50 px-3 py-2.5 text-[0.8125rem] text-green-800">
                <b id="review-title"></b>
                <span id="review-subtitle"></span>
            </div>
            <div>
                <label for="review-status" class="mb-1 block text-xs font-bold text-gray-600">Progress status <span class="text-red-500">*</span></label>
                <select name="status" id="review-status" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    @foreach($statusStyles as $value => $meta)
                        <option value="{{ $value }}">{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>
            {{-- Not linked yet: pick an existing record, or create one from this report --}}
            <div id="review-link-picker" class="space-y-4">
                <div>
                    <label for="review-maintenance" class="mb-1 block text-xs font-bold text-gray-600">Link an existing maintenance record</label>
                    <select name="related_maintenance_id" id="review-maintenance" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        <option value="">No maintenance linked yet</option>
                    </select>
                </div>
                <button type="button" onclick="createMaintenanceFromReport()"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-green-200 bg-green-50 px-2.5 py-1.5 text-xs font-extrabold text-green-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    Create Maintenance from This Report
                </button>
            </div>

            {{-- Already linked: show that record (read-only). The hidden input keeps the link when the form is saved. --}}
            <div id="review-linked" class="hidden">
                <label class="mb-1 block text-xs font-bold text-gray-600">Linked maintenance record</label>
                <input type="hidden" name="related_maintenance_id" id="review-linked-id" disabled>
                <div class="flex items-start gap-2.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    <div class="min-w-0">
                        <div id="review-linked-title" class="text-[0.8125rem] font-bold text-emerald-900"></div>
                        <div id="review-linked-meta" class="text-xs text-emerald-800/80"></div>
                    </div>
                </div>
            </div>
            <div>
                <label for="review-admin-notes" class="mb-1 block text-xs font-bold text-gray-600">Update for the driver</label>
                <textarea name="admin_notes" id="review-admin-notes" placeholder="Example: Tire replacement has been scheduled for tomorrow."
                    class="min-h-[90px] w-full resize-y rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"></textarea>
            </div>
            <button type="submit" id="review-submit-btn" class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">Save Update</button>
        </form>
    </div>
</div>
@endif
@endsection

@if($isAdmin)
@push('scripts')
@php
    // Built here (not inside @json) because Blade splits @json(...) arguments on every comma.
    $maintenanceOptions = $maintenanceByVehicle->map(fn ($list) => $list->map(fn ($m) => [
        'id'             => $m->id,
        'title'          => $m->title,
        'scheduled_date' => $m->scheduled_date?->format('M j, Y'),
        'status'         => $m->status,
    ])->values())->all();
@endphp
<script>
    const maintenanceByVehicle = @json($maintenanceOptions);
    let currentReportId = null;

    function openReviewModal(id, title, subtitle, status, adminNotes, relatedMaintenanceId, vehicleId) {
        currentReportId = id;
        document.getElementById('review-form').action = `/vehicle-reports/${id}`;
        document.getElementById('review-title').textContent = title;
        document.getElementById('review-subtitle').textContent = subtitle;
        document.getElementById('review-status').value = status;
        document.getElementById('review-admin-notes').value = adminNotes || '';

        const select = document.getElementById('review-maintenance');
        const picker = document.getElementById('review-link-picker');
        const linked = document.getElementById('review-linked');
        const linkedInput = document.getElementById('review-linked-id');
        const options = maintenanceByVehicle[vehicleId] || [];

        if (relatedMaintenanceId) {
            // Already linked: show the record itself (no dropdown, no "create" button).
            const record = options.find((m) => String(m.id) === String(relatedMaintenanceId));
            const meta = record ? [record.scheduled_date, record.status ? record.status.charAt(0).toUpperCase() + record.status.slice(1) : null].filter(Boolean).join(' · ') : '';

            document.getElementById('review-linked-title').textContent = record ? record.title : `Maintenance #${relatedMaintenanceId}`;
            document.getElementById('review-linked-meta').textContent = meta;

            linkedInput.value = relatedMaintenanceId;
            linkedInput.disabled = false;
            select.disabled = true;
            picker.classList.add('hidden');
            linked.classList.remove('hidden');
        } else {
            select.innerHTML = '<option value="">No maintenance linked yet</option>';
            options.forEach((m) => {
                const opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = `${m.title} (${m.scheduled_date})`;
                select.appendChild(opt);
            });
            select.value = '';

            select.disabled = false;
            linkedInput.value = '';
            linkedInput.disabled = true;
            linked.classList.add('hidden');
            picker.classList.remove('hidden');
        }

        document.getElementById('review-submit-btn').disabled = false;
        document.getElementById('review-modal').classList.remove('hidden');
        document.getElementById('review-modal').classList.add('flex');
    }

    function closeReviewModal() {
        document.getElementById('review-modal').classList.add('hidden');
        document.getElementById('review-modal').classList.remove('flex');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !document.getElementById('review-modal').classList.contains('hidden')) closeReviewModal();
    });

    function createMaintenanceFromReport() {
        if (! currentReportId) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/vehicle-reports/${currentReportId}/maintenance`;
        form.innerHTML = `@csrf`;
        document.body.appendChild(form);
        form.submit();
    }
</script>
@endpush
@else
@push('scripts')
<script>
    function openReportModal() {
        document.getElementById('report-submit-btn').disabled = false;
        document.getElementById('report-modal').classList.remove('hidden');
        document.getElementById('report-modal').classList.add('flex');
    }
    function closeReportModal() {
        document.getElementById('report-modal').classList.add('hidden');
        document.getElementById('report-modal').classList.remove('flex');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !document.getElementById('report-modal').classList.contains('hidden')) closeReportModal();
    });

    @if($errors->any())
        // Validation failed on submit — reopen the modal so the error and
        // the driver's typed input (restored via old()) are visible, instead
        // of leaving them looking at a closed modal.
        document.addEventListener('DOMContentLoaded', openReportModal);
    @endif
</script>
@endpush
@endif
