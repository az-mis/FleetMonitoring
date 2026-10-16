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
                <span class="rounded-full px-2.5 py-1 text-[0.625rem] font-black uppercase tracking-wide ring-1 {{ $isSuperAdmin ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">
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
                <label for="f-location" class="text-[0.6875rem] font-bold uppercase tracking-wide text-slate-500">Office</label>
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
            <input type="date" name="from" aria-label="From date" value="{{ request('from', $rangeStart->toDateString()) }}" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <span class="text-xs text-slate-400">to</span>
            <input type="date" name="to" aria-label="To date" value="{{ request('to', $rangeEnd->toDateString()) }}" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <button type="submit" class="rounded-lg bg-slate-800 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-slate-900 cursor-pointer">Apply</button>
        </div>

        <p class="text-[0.6875rem] text-slate-400 lg:ml-auto">Monthly trend charts always show the last 6 months.</p>
    </form>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 2xl:grid-cols-6">
        @foreach($kpis as $kpi)
            <div class="min-w-0 rounded-2xl border bg-white p-4 shadow-sm {{ explode(' ', $tones[$kpi['tone']])[0] }}">
                <p class="text-[0.625rem] font-black uppercase tracking-wider text-slate-500">{{ $kpi['label'] }}</p>
                <p class="mt-1 break-words text-xl font-black leading-tight tabular-nums sm:text-2xl {{ explode(' ', $tones[$kpi['tone']])[1] }}">{{ $kpi['value'] }}</p>
                <p class="mt-0.5 truncate text-[0.6875rem] text-slate-500" title="{{ $kpi['hint'] }}">{{ $kpi['hint'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- CARMIND-STYLE COST PER KM & PERIOD INSIGHTS --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @php
        $ca = $costAnalysis ?? [
            'total_spent' => 0, 'fuel_spent' => 0, 'maint_spent' => 0, 'total_km' => 0,
            'cost_per_km' => 0, 'entry_count' => 0, 'prev_spent' => 0, 'diff_cost' => 0,
            'diff_direction' => 'more', 'pct_change' => 0, 'vehicles' => [], 'insights' => []
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Hero Cost Card --}}
        <section class="min-w-0 rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-900 to-[#004d2e] p-5 text-white shadow-md xl:col-span-1 flex flex-col justify-between">
            <div>
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-700/60 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-200">Total Spent &middot; {{ $rangeLabels[$range] ?? 'Period' }}</span>
                    <span class="rounded-full bg-emerald-800/80 px-2 py-0.5 text-[0.625rem] font-extrabold text-emerald-100 ring-1 ring-emerald-500/30">
                        {{ $ca['entry_count'] }} entries
                    </span>
                </div>

                {{-- Big Amount --}}
                <div class="mt-4">
                    <p class="break-words text-2xl font-black tracking-tight text-white tabular-nums sm:text-4xl">
                        ₱{{ number_format($ca['total_spent'], 2) }}
                    </p>
                    @if($ca['prev_spent'] > 0)
                        <p class="mt-1 flex items-center gap-1.5 text-xs font-bold {{ $ca['diff_direction'] === 'more' ? 'text-amber-300' : 'text-emerald-200' }}">
                            <span>{{ $ca['diff_direction'] === 'more' ? '▲' : '▼' }}</span>
                            <span>₱{{ number_format($ca['diff_cost'], 2) }} ({{ $ca['pct_change'] }}%) {{ $ca['diff_direction'] }} than previous period</span>
                        </p>
                    @else
                        <p class="mt-1 text-xs text-emerald-300/80">No prior period spending recorded for comparison.</p>
                    @endif
                </div>

                {{-- Cost per km & Distance boxes: side by side, stacking when large text no longer fits --}}
                <div class="mt-6 flex flex-wrap gap-3">
                    <div class="min-w-[8.5rem] flex-1 rounded-xl bg-white/10 p-3 backdrop-blur-xs ring-1 ring-white/10">
                        <p class="text-[0.625rem] font-bold uppercase tracking-wider text-emerald-200">Cost per km</p>
                        <p class="mt-1 break-words text-lg font-black text-white tabular-nums sm:text-xl">₱{{ number_format($ca['cost_per_km'], 2) }}</p>
                        <p class="text-[0.625rem] text-emerald-200/80">Fuel &amp; service rate</p>
                    </div>
                    <div class="min-w-[8.5rem] flex-1 rounded-xl bg-white/10 p-3 backdrop-blur-xs ring-1 ring-white/10">
                        <p class="text-[0.625rem] font-bold uppercase tracking-wider text-emerald-200">Distance</p>
                        <p class="mt-1 break-words text-lg font-black text-white tabular-nums sm:text-xl">{{ number_format($ca['total_km'], 1) }} km</p>
                        <p class="text-[0.625rem] text-emerald-200/80">Logged trips</p>
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-emerald-700/60 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-[0.6875rem] text-emerald-200/80">
                <span>Fuel: <strong>₱{{ number_format($ca['fuel_spent'], 2) }}</strong></span>
                <span>Maintenance: <strong>₱{{ number_format($ca['maint_spent'], 2) }}</strong></span>
            </div>
        </section>

        {{-- Insights & Breakdown (2 cols) --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2 flex flex-col justify-between">
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M2 12h5l3 7 4-14 3 7h5"/></svg>
                        </span>
                        <h3 class="text-sm font-extrabold text-slate-900">Period Insights</h3>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[0.625rem] font-extrabold text-slate-600 uppercase tracking-wide">Worth a Look</span>
                </div>

                {{-- Smart Insight Cards --}}
                <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($ca['insights'] as $ins)
                        @php
                            $toneClass = match($ins['tone']) {
                                'amber'   => 'border-amber-200 bg-amber-50/60 text-amber-900',
                                'emerald' => 'border-emerald-200 bg-emerald-50/60 text-emerald-900',
                                'violet'  => 'border-violet-200 bg-violet-50/60 text-violet-900',
                                default   => 'border-sky-200 bg-sky-50/60 text-sky-900',
                            };
                            $tagClass = match($ins['tone']) {
                                'amber'   => 'text-amber-700',
                                'emerald' => 'text-emerald-700',
                                'violet'  => 'text-violet-700',
                                default   => 'text-sky-700',
                            };
                        @endphp
                        <div class="rounded-xl border p-3 {{ $toneClass }}">
                            <p class="text-[0.5625rem] font-black uppercase tracking-wider {{ $tagClass }}">{{ $ins['tag'] }}</p>
                            <p class="mt-1 text-xs font-bold">{{ $ins['title'] }}</p>
                            <p class="mt-0.5 text-[0.6875rem] opacity-80 leading-snug">{{ $ins['desc'] }}</p>
                        </div>
                    @empty
                        <div class="col-span-full py-6 text-center text-xs text-slate-500">
                            Log vehicle trips with distance and card transactions to generate insights.
                        </div>
                    @endforelse
                </div>

                {{-- Top Vehicles by Cost Table Preview --}}
                @if(!empty($ca['vehicles']))
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-800">Top Vehicles by Spend</span>
                            <span class="text-[0.6875rem] text-slate-400">Showing top {{ min(4, count($ca['vehicles'])) }} vehicles</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-[0.625rem] font-extrabold uppercase text-slate-400 border-b border-slate-100">
                                        <th class="py-1.5 pr-2">Plate</th>
                                        <th class="py-1.5 pr-2">Model</th>
                                        <th class="py-1.5 pr-2 text-right">Distance</th>
                                        <th class="py-1.5 pr-2 text-right">Total Spent</th>
                                        <th class="py-1.5 text-right">Cost / km</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @foreach(array_slice($ca['vehicles'], 0, 4) as $vRow)
                                        <tr class="hover:bg-slate-50/60">
                                            <td class="py-2 pr-2 font-bold text-slate-900">{{ $vRow['plate'] }}</td>
                                            <td class="py-2 pr-2 text-slate-600">{{ $vRow['label'] }}</td>
                                            <td class="py-2 pr-2 text-right text-slate-600">{{ number_format($vRow['total_km'], 1) }} km</td>
                                            <td class="py-2 pr-2 text-right font-bold text-slate-900">₱{{ number_format($vRow['total_spent'], 2) }}</td>
                                            <td class="py-2 text-right font-extrabold text-emerald-700">₱{{ number_format($vRow['cost_per_km'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-3 pt-2 text-right text-[0.6875rem] text-slate-400">
                Formula: Total Spent (Fuel + Maintenance) &divide; Total Distance (km)
            </div>
        </section>
    </div>

    {{-- Super Admin only: office comparison --}}
    @if($isSuperAdmin && ! $location && isset($locationBreakdown))
        @php $maxTrips = max(1, collect($locationBreakdown)->max('trips')); @endphp
        <section class="rounded-2xl border border-violet-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="font-bold text-gray-900">Office Comparison</h3>
                    <p class="text-[0.6875rem] text-slate-400">Visible to Super Admin only. Click an office to drill in.</p>
                </div>
                <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[0.625rem] font-black uppercase tracking-wide text-violet-700 ring-1 ring-violet-200">Super Admin</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-[0.6875rem] uppercase tracking-wide text-gray-400">
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
                                <td class="py-2.5 pr-4 font-bold text-gray-800"><a href="{{ route('reports.index', array_filter(['location' => $row['location'], 'range' => $range, 'from' => request('from'), 'to' => request('to')])) }}" class="hover:underline focus-visible:underline focus-visible:outline-none" onclick="event.stopPropagation()">{{ $row['location'] }}</a></td>
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
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
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
            <div id="maintenance-cost-wrap" class="relative h-64"><canvas id="chart-maintenance-cost"></canvas></div>
            <div id="maintenance-cost-empty" class="hidden h-64 flex-col items-center justify-center gap-1 text-center">
                <p class="text-sm font-semibold text-gray-600">No maintenance costs recorded</p>
                <p class="text-xs text-gray-400">Costs will appear here once maintenance records have an amount for this period.</p>
            </div>
        </section>
    </div>

    {{-- Trip log --}}
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3">
            <div>
                <h3 class="font-bold text-gray-900">Trip Log</h3>
                <p class="text-[0.6875rem] text-slate-400">Showing {{ $tripLogPreview->count() }} of {{ $tripLog->count() }} requests in this period. Export for the full list.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-[0.6875rem] uppercase tracking-wide text-gray-400">
                        <th class="px-4 py-2.5 font-bold">Ticket No.</th>
                        <th class="px-4 py-2.5 font-bold">Travel Date</th>
                        <th class="px-4 py-2.5 font-bold">Requester</th>
                        @if($isSuperAdmin)<th class="px-4 py-2.5 font-bold">Office</th>@endif
                        <th class="px-4 py-2.5 font-bold">Vehicle</th>
                        <th class="px-4 py-2.5 font-bold">Destination</th>
                        <th class="px-4 py-2.5 font-bold">Status</th>
                        <th class="relative px-4 py-2.5"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tripLogPreview as $r)
                        <tr class="border-t border-gray-100 hover:bg-slate-50/60">
                            <td class="px-4 py-2.5 font-mono text-[0.6875rem] text-gray-600">{{ $r->trip_ticket_number ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ $r->travel_date?->format('M j, Y') }}{{ $r->travel_date_end && $r->travel_date_end->ne($r->travel_date) ? ' – ' . $r->travel_date_end->format('M j') : '' }}</td>
                            <td class="px-4 py-2.5 font-semibold text-gray-800">{{ $r->requester_name }}</td>
                            @if($isSuperAdmin)<td class="px-4 py-2.5 text-gray-600">{{ $r->location }}</td>@endif
                            <td class="px-4 py-2.5 text-gray-600">{{ $r->vehicle?->plate_number ?: '—' }}</td>
                            <td class="max-w-[220px] truncate px-4 py-2.5 text-gray-600" title="{{ str_replace(';;', ', ', (string) $r->destination) }}">{{ str_replace(';;', ', ', (string) $r->destination) }}</td>
                            <td class="px-4 py-2.5"><span class="rounded-full px-2.5 py-0.5 text-[0.625rem] font-bold capitalize ring-1 {{ $statusStyles[$r->status] ?? 'bg-slate-50 text-slate-600 ring-slate-200' }}">{{ $r->status }}</span></td>
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
            data: { labels: @json($topDestinations['labels']), datasets: [{ label: 'Requests', data: @json($topDestinations['data']), backgroundColor: '#3b82f6', borderRadius: 6, maxBarThickness: 36 }] },
            options: { ...base, indexAxis: 'y', scales: { x: { beginAtZero: true, grid, ticks: { precision: 0 } }, y: { grid: { display: false } } } },
        });

        make('chart-trips-vehicle', {
            type: 'bar',
            data: { labels: @json($tripsPerVehicle['labels']), datasets: [{ label: 'Trips', data: @json($tripsPerVehicle['data']), backgroundColor: '#8b5cf6', borderRadius: 6, maxBarThickness: 56 }] },
            options: base,
        });

        const maintenanceCosts = @json($maintenanceCostPerMonth['data']);
        if (!maintenanceCosts.some((n) => Number(n) > 0)) {
            document.getElementById('maintenance-cost-wrap').classList.add('hidden');
            const emptyBox = document.getElementById('maintenance-cost-empty');
            emptyBox.classList.remove('hidden');
            emptyBox.classList.add('flex');
        } else make('chart-maintenance-cost', {
            type: 'bar',
            data: { labels: @json($maintenanceCostPerMonth['labels']), datasets: [{ label: 'Cost', data: @json($maintenanceCostPerMonth['data']), backgroundColor: '#f59e0b', borderRadius: 6, maxBarThickness: 56 }] },
            options: { ...base, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => '₱' + Number(c.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2 }) } } } },
        });
    });
</script>
@endsection
