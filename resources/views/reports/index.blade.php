@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
@php
    $rangeLabels = ['week' => 'This Week', 'month' => 'This Month', 'quarter' => 'This Quarter', 'year' => 'This Year', 'custom' => 'Custom'];
    $baseQuery = array_filter(['location' => $location, 'range' => $range, 'from' => $range === 'custom' ? request('from') : null, 'to' => $range === 'custom' ? request('to') : null]);
    $peso = fn ($v) => '₱' . number_format((float) $v, 2);
    $statusStyles = [
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'declined' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];
    $kpis = [
        ['label' => 'Total Requests', 'value' => $summary['total'], 'hint' => 'travel dates in range', 'tone' => 'slate'],
        ['label' => 'Approved Trips', 'value' => $summary['approved'], 'hint' => $summary['approval_rate'] === null ? 'no decisions yet' : $summary['approval_rate'] . '% approval rate', 'tone' => 'emerald'],
        ['label' => 'Pending', 'value' => $summary['pending'], 'hint' => 'awaiting decision', 'tone' => 'amber'],
        ['label' => 'Fleet Vehicles', 'value' => $summary['fleet'], 'hint' => $location ?: 'all offices', 'tone' => 'sky'],
        ['label' => 'Active Maintenance', 'value' => $summary['active_maintenance'], 'hint' => 'scheduled / ongoing', 'tone' => 'violet'],
        ['label' => 'Maintenance Cost', 'value' => $peso($summary['maintenance_cost']), 'hint' => $summary['maintenance_done'] . ' completed in range', 'tone' => 'orange'],
    ];
    $tones = [
        'slate' => 'border-slate-200 text-slate-900', 'emerald' => 'border-emerald-200 text-emerald-700',
        'amber' => 'border-amber-200 text-amber-700', 'sky' => 'border-sky-200 text-sky-700',
        'violet' => 'border-violet-200 text-violet-700', 'orange' => 'border-orange-200 text-orange-700',
    ];
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Reports</h1>
                <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ring-1 {{ $isSuperAdmin ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">
                    {{ $isSuperAdmin ? 'Super Admin' : 'Admin' }} &middot; {{ $scopeLabel }}
                </span>
            </div>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $rangeStart->format('M j, Y') }} – {{ $rangeEnd->format('M j, Y') }}
                &middot; {{ $isSuperAdmin ? 'Reports across every office, or drill into one.' : 'Figures for your office only.' }}
            </p>
        </div>

        {{-- Export --}}
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('reports.print', $baseQuery) }}" target="_blank" class="inline-flex h-10 items-center gap-1.5 rounded-xl bg-[#005a36] px-4 text-xs font-extrabold text-white shadow-sm transition hover:bg-[#00432a]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print / PDF
            </a>
            <a href="{{ route('reports.export', ['format' => 'xlsx'] + $baseQuery) }}" class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-emerald-200 bg-white px-4 text-xs font-extrabold text-emerald-700 transition hover:bg-emerald-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 13 6 6M15 13l-6 6"/></svg>
                Excel
            </a>
            <a href="{{ route('reports.export', ['format' => 'docx'] + $baseQuery) }}" class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-sky-200 bg-white px-4 text-xs font-extrabold text-sky-700 transition hover:bg-sky-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
                Word
            </a>
        </div>
    </div>

    @if($errors->has('export'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-semibold text-rose-700">{{ $errors->first('export') }}</div>
    @endif

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('reports.index') }}" id="filter-form" class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm lg:flex-row lg:items-center">
        @if($isSuperAdmin)
            <div class="flex items-center gap-2">
                <label for="f-location" class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Office</label>
                <select id="f-location" name="location" onchange="this.form.submit()" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">All Offices</option>
                    @foreach($locationOptions as $opt)
                        <option value="{{ $opt }}" @selected($location === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="hidden h-6 w-px bg-gray-200 lg:block"></div>
        @endif

        <input type="hidden" name="range" id="f-range" value="{{ $range }}">
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach($rangeLabels as $key => $label)
                <button type="button" onclick="setRange('{{ $key }}')" class="rounded-full px-3.5 py-1.5 text-xs font-bold transition cursor-pointer {{ $range === $key ? 'bg-[#005a36] text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</button>
            @endforeach
        </div>

        <div id="custom-dates" class="{{ $range === 'custom' ? 'flex' : 'hidden' }} flex-wrap items-center gap-2">
            <input type="date" name="from" value="{{ request('from', $rangeStart->toDateString()) }}" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <span class="text-xs text-slate-400">to</span>
            <input type="date" name="to" value="{{ request('to', $rangeEnd->toDateString()) }}" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <button type="submit" class="rounded-lg bg-slate-800 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-slate-900 cursor-pointer">Apply</button>
        </div>

        <p class="text-[11px] text-slate-400 lg:ml-auto">Monthly trend charts always show the last 6 months.</p>
    </form>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @foreach($kpis as $kpi)
            <div class="rounded-2xl border bg-white p-4 shadow-sm {{ explode(' ', $tones[$kpi['tone']])[0] }}">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $kpi['label'] }}</p>
                <p class="mt-1 truncate text-2xl font-black leading-tight {{ explode(' ', $tones[$kpi['tone']])[1] }}">{{ $kpi['value'] }}</p>
                <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ $kpi['hint'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Super Admin only: office comparison --}}
    @if($isSuperAdmin && ! $location && isset($locationBreakdown))
        @php $maxTrips = max(1, collect($locationBreakdown)->max('trips')); @endphp
        <section class="rounded-2xl border border-violet-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="font-bold text-gray-900">Office Comparison</h3>
                    <p class="text-[11px] text-slate-400">Visible to Super Admin only. Click an office to drill in.</p>
                </div>
                <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-violet-700 ring-1 ring-violet-200">Super Admin</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] uppercase tracking-wide text-gray-400">
                            <th class="py-2 pr-4 font-bold">Office</th>
                            <th class="py-2 pr-4 font-bold text-right">Vehicles</th>
                            <th class="py-2 pr-4 font-bold text-right">Requests</th>
                            <th class="py-2 pr-4 font-bold text-right">Pending</th>
                            <th class="py-2 pr-4 font-bold w-48">Approved Trips</th>
                            <th class="py-2 pr-4 font-bold text-right">Approval</th>
                            <th class="py-2 pr-4 font-bold text-right">Active Maint.</th>
                            <th class="py-2 font-bold text-right">Maint. Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($locationBreakdown as $row)
                            <tr class="cursor-pointer border-b border-gray-50 transition last:border-0 hover:bg-emerald-50/40" onclick="window.location='{{ route('reports.index', array_filter(['location' => $row['location'], 'range' => $range, 'from' => request('from'), 'to' => request('to')])) }}'">
                                <td class="py-2.5 pr-4 font-bold text-gray-800">{{ $row['location'] }}</td>
                                <td class="py-2.5 pr-4 text-right text-gray-600">{{ $row['vehicles'] }}</td>
                                <td class="py-2.5 pr-4 text-right text-gray-600">{{ $row['requests'] }}</td>
                                <td class="py-2.5 pr-4 text-right font-semibold text-amber-700">{{ $row['pending'] }}</td>
                                <td class="py-2.5 pr-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ round($row['trips'] / $maxTrips * 100) }}%"></div></div>
                                        <span class="w-6 text-right font-bold text-emerald-700">{{ $row['trips'] }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 pr-4 text-right text-gray-600">{{ $row['approval_rate'] === null ? '—' : $row['approval_rate'] . '%' }}</td>
                                <td class="py-2.5 pr-4 text-right text-gray-600">{{ $row['maintenance_active'] }}</td>
                                <td class="py-2.5 text-right font-semibold text-gray-800">{{ $peso($row['maintenance_cost']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @elseif($isSuperAdmin && $location)
        <a href="{{ route('reports.index', array_filter(['range' => $range, 'from' => request('from'), 'to' => request('to')])) }}" class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3.5 py-1.5 text-xs font-bold text-violet-700 ring-1 ring-violet-200 hover:bg-violet-100">&larr; Back to all offices</a>
    @endif

    {{-- Charts --}}
    <div class="grid gap-4 xl:grid-cols-3">
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm xl:col-span-2">
            <h3 class="mb-3 text-sm font-bold text-gray-900">Requests per Month</h3>
            <div class="relative h-64"><canvas id="chart-requests-month"></canvas></div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-bold text-gray-900">Request Status</h3>
            @if($summary['total'] > 0)
                <div class="relative h-64"><canvas id="chart-status"></canvas></div>
            @else
                <div class="flex h-64 items-center justify-center text-xs text-slate-400">No requests in this period</div>
            @endif
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-bold text-gray-900">Top Destinations</h3>
            @if(count($topDestinations['labels']))
                <div class="relative h-64"><canvas id="chart-destinations"></canvas></div>
            @else
                <div class="flex h-64 items-center justify-center text-xs text-slate-400">No destinations in this period</div>
            @endif
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-bold text-gray-900">Trips per Vehicle</h3>
            @if(count($tripsPerVehicle['labels']))
                <div class="relative h-64"><canvas id="chart-trips-vehicle"></canvas></div>
            @else
                <div class="flex h-64 items-center justify-center text-xs text-slate-400">No approved trips in this period</div>
            @endif
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-bold text-gray-900">Maintenance Cost per Month</h3>
            <div class="relative h-64"><canvas id="chart-maintenance-cost"></canvas></div>
        </section>
    </div>

    {{-- Trip log --}}
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3">
            <div>
                <h3 class="font-bold text-gray-900">Trip Log</h3>
                <p class="text-[11px] text-slate-400">Showing {{ $tripLogPreview->count() }} of {{ $tripLog->count() }} requests in this period. Export for the full list.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-[11px] uppercase tracking-wide text-gray-400">
                        <th class="px-4 py-2.5 font-bold">Ticket No.</th>
                        <th class="px-4 py-2.5 font-bold">Travel Date</th>
                        <th class="px-4 py-2.5 font-bold">Requester</th>
                        @if($isSuperAdmin)<th class="px-4 py-2.5 font-bold">Office</th>@endif
                        <th class="px-4 py-2.5 font-bold">Vehicle</th>
                        <th class="px-4 py-2.5 font-bold">Destination</th>
                        <th class="px-4 py-2.5 font-bold">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tripLogPreview as $r)
                        <tr class="border-t border-gray-100 hover:bg-slate-50/60">
                            <td class="px-4 py-2.5 font-mono text-[11px] text-gray-600">{{ $r->trip_ticket_number ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ $r->travel_date?->format('M j, Y') }}{{ $r->travel_date_end && $r->travel_date_end->ne($r->travel_date) ? ' – ' . $r->travel_date_end->format('M j') : '' }}</td>
                            <td class="px-4 py-2.5 font-semibold text-gray-800">{{ $r->requester_name }}</td>
                            @if($isSuperAdmin)<td class="px-4 py-2.5 text-gray-600">{{ $r->location }}</td>@endif
                            <td class="px-4 py-2.5 text-gray-600">{{ $r->vehicle?->plate_number ?: '—' }}</td>
                            <td class="max-w-[220px] truncate px-4 py-2.5 text-gray-600" title="{{ $r->destination }}">{{ $r->destination }}</td>
                            <td class="px-4 py-2.5"><span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold capitalize ring-1 {{ $statusStyles[$r->status] ?? 'bg-slate-50 text-slate-600 ring-slate-200' }}">{{ $r->status }}</span></td>
                            <td class="px-4 py-2.5 text-right">
                                @if($r->status === 'approved')
                                    <a href="{{ route('trip-ticket', $r) }}" target="_blank" class="font-bold text-emerald-700 hover:underline">Ticket</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isSuperAdmin ? 8 : 7 }}" class="px-4 py-12 text-center text-xs text-slate-400">No requests found for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    function setRange(key) {
        document.getElementById('f-range').value = key;
        if (key === 'custom') {
            document.getElementById('custom-dates').classList.remove('hidden');
            document.getElementById('custom-dates').classList.add('flex');
            return;
        }
        document.getElementById('filter-form').submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Chart) return;

        const grid = { color: 'rgba(148,163,184,0.15)' };
        const base = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid, ticks: { precision: 0 } }, x: { grid: { display: false } } },
        };
        const make = (id, cfg) => { const el = document.getElementById(id); if (el) new window.Chart(el, cfg); };

        make('chart-requests-month', {
            type: 'line',
            data: { labels: @json($requestsPerMonth['labels']), datasets: [{
                label: 'Requests', data: @json($requestsPerMonth['data']),
                borderColor: '#059669', backgroundColor: '#059669', borderWidth: 2.5, tension: 0.35, pointRadius: 4, pointHoverRadius: 6,
            }] },
            options: base,
        });

        make('chart-status', {
            type: 'doughnut',
            data: { labels: @json($statusBreakdown['labels']), datasets: [{
                data: @json($statusBreakdown['data']), backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'], borderWidth: 0,
            }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } } },
        });

        make('chart-destinations', {
            type: 'bar',
            data: { labels: @json($topDestinations['labels']), datasets: [{ label: 'Requests', data: @json($topDestinations['data']), backgroundColor: '#3b82f6', borderRadius: 6 }] },
            options: { ...base, indexAxis: 'y', scales: { x: { beginAtZero: true, grid, ticks: { precision: 0 } }, y: { grid: { display: false } } } },
        });

        make('chart-trips-vehicle', {
            type: 'bar',
            data: { labels: @json($tripsPerVehicle['labels']), datasets: [{ label: 'Trips', data: @json($tripsPerVehicle['data']), backgroundColor: '#8b5cf6', borderRadius: 6 }] },
            options: base,
        });

        make('chart-maintenance-cost', {
            type: 'bar',
            data: { labels: @json($maintenanceCostPerMonth['labels']), datasets: [{ label: 'Cost', data: @json($maintenanceCostPerMonth['data']), backgroundColor: '#f59e0b', borderRadius: 6 }] },
            options: { ...base, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => '₱' + Number(c.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2 }) } } } },
        });
    });
</script>
@endsection
