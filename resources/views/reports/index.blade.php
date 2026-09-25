@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
@php
    $rangeLabels = ['week' => 'This Week', 'month' => 'This Month', 'quarter' => 'This Quarter', 'custom' => 'Custom'];
@endphp

<div class="space-y-5">
    {{-- Header + filters --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Reports</h1>
            <p class="text-sm text-slate-500">Trends and comparisons across requests, vehicles and maintenance</p>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-center gap-2">
            @if($isSuperAdmin)
                <select name="location" onchange="this.form.submit()" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600">
                    <option value="">All Locations</option>
                    @foreach($locationOptions as $opt)
                        <option value="{{ $opt }}" @selected($location === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            @endif
            <select name="range" onchange="this.form.submit()" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600">
                @foreach($rangeLabels as $key => $label)
                    <option value="{{ $key }}" @selected($range === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <p class="-mt-3 text-xs text-gray-400">Destinations and vehicle-trip figures cover {{ $rangeStart->format('M j, Y') }} – {{ $rangeEnd->format('M j, Y') }}. Monthly trend charts always show the last 6 months.</p>

    {{-- Charts --}}
    <div class="grid gap-5 xl:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 font-bold text-gray-900">Requests per Month</h3>
            <canvas id="chart-requests-month" height="220"></canvas>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 font-bold text-gray-900">Top Destinations</h3>
            <canvas id="chart-destinations" height="220"></canvas>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 font-bold text-gray-900">Trips per Vehicle</h3>
            <canvas id="chart-trips-vehicle" height="220"></canvas>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 font-bold text-gray-900">Maintenance Cost per Month</h3>
            <canvas id="chart-maintenance-cost" height="220"></canvas>
        </section>
    </div>

    {{-- Super Admin: location comparison --}}
    @if($isSuperAdmin && ! $location && isset($locationComparison))
        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 font-bold text-gray-900">Location Comparison</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-500">
                            <th class="py-2 pr-4 font-semibold">Location</th>
                            <th class="py-2 pr-4 font-semibold">Vehicles</th>
                            <th class="py-2 pr-4 font-semibold">Pending Requests</th>
                            <th class="py-2 pr-4 font-semibold">Approved Trips</th>
                            <th class="py-2 pr-4 font-semibold">Active Maintenance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($locationComparison as $row)
                            <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('reports.index', ['location' => $row['location'], 'range' => $range]) }}'">
                                <td class="py-2.5 pr-4 font-semibold text-gray-800">{{ $row['location'] }}</td>
                                <td class="py-2.5 pr-4 text-gray-600">{{ $row['vehicles'] }}</td>
                                <td class="py-2.5 pr-4 text-amber-700 font-semibold">{{ $row['pending'] }}</td>
                                <td class="py-2.5 pr-4 text-emerald-700 font-semibold">{{ $row['trips'] }}</td>
                                <td class="py-2.5 pr-4 text-gray-600">{{ $row['maintenance'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Chart) return;

        const barOptions = {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } },
        };

        new window.Chart(document.getElementById('chart-requests-month'), {
            type: 'line',
            data: {
                labels: @json($requestsPerMonth['labels']),
                datasets: [{
                    label: 'Requests',
                    data: @json($requestsPerMonth['data']),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.1)',
                    fill: true,
                    tension: 0.3,
                }],
            },
            options: barOptions,
        });

        new window.Chart(document.getElementById('chart-destinations'), {
            type: 'bar',
            data: {
                labels: @json($topDestinations['labels']),
                datasets: [{ label: 'Trips', data: @json($topDestinations['data']), backgroundColor: '#3b82f6' }],
            },
            options: { ...barOptions, indexAxis: 'y' },
        });

        new window.Chart(document.getElementById('chart-trips-vehicle'), {
            type: 'bar',
            data: {
                labels: @json($tripsPerVehicle['labels']),
                datasets: [{ label: 'Trips', data: @json($tripsPerVehicle['data']), backgroundColor: '#8b5cf6' }],
            },
            options: barOptions,
        });

        new window.Chart(document.getElementById('chart-maintenance-cost'), {
            type: 'bar',
            data: {
                labels: @json($maintenanceCostPerMonth['labels']),
                datasets: [{ label: 'Cost', data: @json($maintenanceCostPerMonth['data']), backgroundColor: '#f59e0b' }],
            },
            options: barOptions,
        });
    });
</script>
@endsection
