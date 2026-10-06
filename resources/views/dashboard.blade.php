@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    // $isAdmin, $isSuperAdmin, $dashboardLocation, $locationOptions, $alerts, $kpis,
    // $fleetStatus and $todaysTrips are supplied by DashboardController@index.
    $user = auth()->user();
    $roleValue = $user?->role?->value ?? $user?->role;
    $isDriver = $roleValue === 'driver';
    $locationParam = $dashboardLocation ? ['location' => $dashboardLocation] : [];

    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $announcementKey = $isDriver ? 'driver_announcement' : 'admin_announcement';
    try {
        $announcement = \Modules\Settings\Models\FleetSetting::where('setting_key', $announcementKey)->value('setting_value') ?? ['enabled' => false, 'message' => ''];
    } catch (\Throwable $exception) {
        $announcement = ['enabled' => false, 'message' => ''];
    }

    // Driver specific calculations
    if ($isDriver) {
        $missingProfileItems = [];
        if (blank($user?->address)) {
            $missingProfileItems[] = 'Residential Address';
        }
        if (blank($user?->phone_number)) {
            $missingProfileItems[] = 'Phone Number';
        }
        if (blank($user?->license_number)) {
            $missingProfileItems[] = 'License Number';
        }
        if (blank($user?->license_expiration_date)) {
            $missingProfileItems[] = 'License Expiration Date';
        }

        $driverVehicle = \Modules\Vehicle\Models\Vehicle::where('assigned_driver_id', $user?->id)->first();
        
        try {
            $driverModulesSetting = \Modules\Settings\Models\FleetSetting::where('setting_key', 'driver_modules')->value('setting_value');
            $showAssignedVehicleSetting = $driverModulesSetting['showAssignedVehicle'] ?? true;
        } catch (\Throwable $e) {
            $showAssignedVehicleSetting = true;
        }

        $driverRequestsQuery = \Modules\VehicleRequest\Models\VehicleRequest::query()
            ->where(function ($q) use ($user, $driverVehicle) {
                if ($user?->id) {
                    $q->where('confirmed_driver_id', $user->id);
                }
                if ($driverVehicle) {
                    $q->orWhere('vehicle_id', $driverVehicle->id);
                }
            });

        $driverCompletedTrips = (clone $driverRequestsQuery)
            ->where('status', 'approved')
            ->whereDate('travel_date', '<', now()->toDateString())
            ->latest('travel_date')
            ->get();
        $completedTravelsCount = $driverCompletedTrips->count();

        $driverFutureTrips = (clone $driverRequestsQuery)
            ->where('status', 'approved')
            ->whereDate('travel_date', '>=', now()->toDateString())
            ->orderBy('travel_date')
            ->get();
        $futureTripsCount = $driverFutureTrips->count();

        $activeTripsTodayCount = (clone $driverRequestsQuery)
            ->where('status', 'approved')
            ->whereDate('travel_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('travel_date_end')
                  ->orWhereDate('travel_date_end', '>=', now()->toDateString());
            })
            ->count();

        $totalDispatchesCount = (clone $driverRequestsQuery)
            ->where('status', 'approved')
            ->count();
    }
@endphp

@section('content')
    <div class="space-y-5">
        @if(! $isSuperAdmin && ($announcement['enabled'] ?? false) && filled($announcement['message'] ?? null))
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/80 px-4 py-3.5 text-xs text-amber-900 shadow-2xs flex items-start gap-3">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 1 1.73-3Z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div>
                    <p class="font-extrabold text-amber-950">Announcement</p>
                    <p class="mt-0.5 text-amber-800 leading-relaxed">{{ $announcement['message'] }}</p>
                </div>
            </div>
        @endif

        {{-- Page header --}}
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Welcome back, {{ $user?->name }}</h1>
                <p class="text-sm text-slate-500">{{ $greeting }}<span class="mx-2">•</span>Signed in as <strong class="font-bold text-slate-700">{{ $user?->role?->label() ?? 'User' }}</strong><span class="mx-2">•</span>{{ now()->format('l, F j, Y') }}</p>
            </div>
            @if($isSuperAdmin)
                {{-- Location filter (Super Admin) --}}
                <div class="relative shrink-0">
                    <button type="button" onclick="toggleDashboardLocationMenu()" class="inline-flex h-10 w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 cursor-pointer md:w-auto">
                        <span class="flex items-center gap-1.5 truncate">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span class="max-w-[12rem] truncate">{{ $dashboardLocation ?: 'All Locations' }}</span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 text-slate-400 transition" id="dashboard-location-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <div id="dashboard-location-menu" class="absolute left-0 z-20 mt-2 hidden w-64 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5 md:left-auto md:right-0">
                        @foreach (array_merge([null], $locationOptions) as $opt)
                            @php $isCurrent = $opt === $dashboardLocation; @endphp
                            <a href="{{ $opt ? route('dashboard', ['location' => $opt]) : route('dashboard') }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-gray-50 {{ $isCurrent ? 'bg-emerald-50 text-emerald-800' : '' }}">
                                <span>{{ $opt ?? 'All Locations' }}</span>
                                @if($isCurrent)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                <script>
                    function toggleDashboardLocationMenu() {
                        const menu = document.getElementById('dashboard-location-menu');
                        const chevron = document.getElementById('dashboard-location-chevron');
                        const willOpen = menu.classList.contains('hidden');
                        menu.classList.toggle('hidden', !willOpen);
                        chevron.classList.toggle('rotate-180', willOpen);
                    }

                    document.addEventListener('click', function (e) {
                        const menu = document.getElementById('dashboard-location-menu');
                        if (menu && !menu.classList.contains('hidden') && !menu.contains(e.target) && !e.target.closest('button[onclick="toggleDashboardLocationMenu()"]')) {
                            menu.classList.add('hidden');
                            document.getElementById('dashboard-location-chevron')?.classList.remove('rotate-180');
                        }
                    });
                </script>
            @elseif($isAdmin)
                {{-- Admins are fixed to their assigned location --}}
                <div class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="max-w-[12rem] truncate">{{ $dashboardLocation ?: 'All Locations' }}</span>
                </div>
            @endif
        </div>

        @if($isAdmin)
            <div
                x-data="fleetDashboard({
                    initialAlerts: {{ Illuminate\Support\Js::from($alerts) }},
                    initialKpis: {{ Illuminate\Support\Js::from($kpis) }},
                    initialFleetStatus: {{ Illuminate\Support\Js::from($fleetStatus) }},
                    refreshUrl: '{{ route('dashboard.refresh', $locationParam) }}',
                })"
                x-init="init()"
                class="space-y-5"
            >
                {{-- 1. Needs attention strip --}}
                <section x-show="alerts.length > 0" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900">Needs Attention</h3>
                        <span class="text-[10px] text-gray-400">Updated <span x-text="lastUpdatedLabel"></span></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5 md:grid-cols-3 xl:grid-cols-6">
                        <template x-for="alert in alerts" :key="alert.key">
                            <a :href="alert.url"
                               class="rounded-lg border p-3 transition hover:shadow-sm"
                               :class="{
                                   'border-red-200 bg-red-50': alert.severity === 'high',
                                   'border-amber-200 bg-amber-50': alert.severity === 'medium',
                                   'border-gray-200 bg-gray-50': alert.severity === 'low',
                               }">
                                <p class="text-xl font-black"
                                   :class="{
                                       'text-red-700': alert.severity === 'high',
                                       'text-amber-700': alert.severity === 'medium',
                                       'text-gray-700': alert.severity === 'low',
                                   }"
                                   x-text="alert.value"></p>
                                <p class="mt-0.5 text-[11px] font-semibold text-gray-800" x-text="alert.label"></p>
                                <p class="text-[10px] text-gray-500" x-text="alert.detail" x-show="alert.detail"></p>
                            </a>
                        </template>
                    </div>
                </section>

                {{-- 2. Clickable KPI cards --}}
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4" :class="kpis.length === 5 ? 'lg:grid-cols-5' : 'lg:grid-cols-4'">
                    <template x-for="(kpi, i) in kpis" :key="kpi.key">
                        <a :href="kpi.url" :class="(kpis.length % 2 === 1 && i === kpis.length - 1) ? 'col-span-2 md:col-span-1' : ''" class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:shadow-md hover:border-gray-300">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                      :class="`bg-${kpi.color}-100 text-${kpi.color}-700`"
                                      x-html="kpiIcon(kpi.icon)"></span>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-semibold uppercase leading-tight tracking-wide text-gray-500 sm:truncate" x-text="kpi.label"></p>
                                    <p class="flex items-baseline gap-1.5">
                                        <span class="text-xl font-bold text-gray-900" x-text="kpi.value"></span>
                                        <span class="text-[10px] text-gray-400" x-text="kpi.sub" x-show="kpi.sub"></span>
                                    </p>
                                    <p class="text-[10px] font-bold" x-show="kpi.trend"
                                       :class="kpi.trend && kpi.trend.direction === 'up' ? 'text-emerald-600' : 'text-red-500'">
                                        <span x-text="kpi.trend ? (kpi.trend.direction === 'up' ? '▲' : '▼') + ' ' + kpi.trend.percent + '%' : ''"></span>
                                        <span class="font-normal text-gray-400">vs last month</span>
                                    </p>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>

                {{-- 3. Admin Fleet Calendar Widget --}}
                <section class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm" x-data="adminDashboardCalendar({{ Illuminate\Support\Js::from($calendarEvents ?? []) }})">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-black text-slate-900 text-sm">Fleet Activity Calendar</h3>
                                <p class="text-[11px] text-slate-500 font-medium">Overview of Approved Trips, Pending Requests, &amp; Scheduled Maintenance</p>
                            </div>
                        </div>

                        {{-- Month Navigation & Legend --}}
                        <div class="flex items-center gap-4">
                            {{-- Status Badges Legend --}}
                            <div class="hidden sm:flex items-center gap-3 text-xs font-bold">
                                <span class="flex items-center gap-1.5 text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    <span class="h-2 w-2 rounded-full bg-emerald-600"></span> Approved
                                </span>
                                <span class="flex items-center gap-1.5 text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span> Pending
                                </span>
                                <span class="flex items-center gap-1.5 text-red-800 bg-red-50 px-2 py-0.5 rounded border border-red-200">
                                    <span class="h-2 w-2 rounded-full bg-red-500"></span> Maintenance
                                </span>
                            </div>

                            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg">
                                <button type="button" @click="prevMonth()" class="rounded p-1 text-slate-600 hover:bg-white hover:shadow-2xs transition cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
                                </button>
                                <span class="px-2 text-xs font-black text-slate-800 min-w-[100px] text-center" x-text="monthYearTitle"></span>
                                <button type="button" @click="nextMonth()" class="rounded p-1 text-slate-600 hover:bg-white hover:shadow-2xs transition cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Calendar Grid --}}
                    <div class="w-full overflow-x-auto">
                        <div class="min-w-[600px]">
                            <div class="grid grid-cols-7 text-center text-[11px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">
                                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                            </div>
                            <div class="grid grid-cols-7 gap-1 pt-1">
                                <template x-for="day in calendarDays" :key="day.dateStr">
                                    <div class="min-h-[85px] rounded-lg border p-1.5 transition flex flex-col justify-between"
                                         :class="{
                                             'bg-slate-50/50 border-slate-100 text-slate-300': !day.inMonth,
                                             'bg-white border-slate-200': day.inMonth &amp;&amp; !day.isToday,
                                             'bg-emerald-50/40 border-emerald-400 ring-1 ring-emerald-400': day.isToday
                                         }">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-extrabold"
                                                  :class="{
                                                      'text-slate-400': !day.inMonth,
                                                      'text-slate-800': day.inMonth &amp;&amp; !day.isToday,
                                                      'text-emerald-900 bg-emerald-600 text-white rounded-full h-5 w-5 flex items-center justify-center': day.isToday
                                                  }"
                                                  x-text="day.dayNum"></span>
                                            <span x-show="day.events.length > 0" class="text-[9px] font-extrabold px-1 rounded bg-slate-100 text-slate-600" x-text="day.events.length"></span>
                                        </div>

                                        <div class="mt-1 space-y-1 overflow-y-auto max-h-[55px] scrollbar-thin">
                                            <template x-for="ev in day.events" :key="ev.title + ev.start">
                                                <a :href="ev.url"
                                                   class="block text-[10px] font-bold px-1.5 py-0.5 rounded truncate transition border"
                                                   :class="{
                                                       'bg-emerald-100 border-emerald-300 text-emerald-900 hover:bg-emerald-200': ev.type === 'approved',
                                                       'bg-amber-100 border-amber-300 text-amber-900 hover:bg-amber-200': ev.type === 'pending',
                                                       'bg-red-100 border-red-300 text-red-900 hover:bg-red-200': ev.type === 'maintenance'
                                                   }"
                                                   :title="ev.title + ' (' + ev.sub + ')'">
                                                    <span x-text="ev.title"></span>
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 4. Fleet status today --}}
                <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <section class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="font-bold text-gray-900">Fleet Status Today</h3>
                            <span class="rounded-md bg-gray-50 px-2 py-1 text-[10px] font-bold text-gray-600" x-text="fleetStatus.total + ' vehicles'"></span>
                        </div>
                        <div class="flex flex-col items-center gap-4 sm:flex-row">
                            <div class="relative h-40 w-40 shrink-0">
                                <canvas x-ref="fleetChart" width="160" height="160"></canvas>
                            </div>
                            <div class="w-full space-y-2">
                                <template x-for="seg in fleetStatus.segments" :key="seg.label">
                                    <a :href="seg.url" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-xs hover:bg-gray-50">
                                        <span class="flex items-center gap-2 text-gray-600">
                                            <span class="h-2.5 w-2.5 rounded-full" :style="`background:${seg.color}`"></span>
                                            <span x-text="seg.label"></span>
                                        </span>
                                        <span class="font-bold text-gray-900" x-text="seg.value"></span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </section>

                    <section class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="font-bold text-gray-900">Today's Trips</h3>
                            <a href="{{ route('vehicle-requests.index', $locationParam + ['status' => 'approved']) }}" class="text-xs font-semibold text-emerald-700 hover:underline">View all →</a>
                        </div>
                        <div class="space-y-2">
                            @forelse($todaysTrips as $trip)
                                <div class="rounded-lg border border-emerald-100 bg-emerald-50/50 px-3 py-2.5">
                                    <div class="flex justify-between gap-3">
                                        <p class="text-sm font-semibold text-emerald-900">{{ $trip->destination }}</p>
                                        <span class="shrink-0 text-xs font-bold text-emerald-700">{{ $trip->vehicle?->plate_number ?? '—' }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-emerald-800">{{ $trip->requester_name }} · {{ $trip->travel_date->format('M j') }}{{ $trip->travel_date_end ? ' – '.$trip->travel_date_end->format('M j') : '' }}</p>
                                </div>
                            @empty
                                <p class="py-8 text-center text-sm text-gray-500">No trips out today.</p>
                            @endforelse
                        </div>
                    </section>
                </div>

                {{-- 5. Chart previews (full versions on the Reports page) --}}
                <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <section class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="font-bold text-gray-900">Requests per Month</h3>
                            <a href="{{ route('reports.index', $locationParam) }}" class="text-xs font-semibold text-emerald-700 hover:underline">Full report →</a>
                        </div>
                        <div class="relative w-full"><canvas id="chart-requests-month" height="160"></canvas></div>
                    </section>
                    <section class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="font-bold text-gray-900">Top Destinations</h3>
                            <a href="{{ route('reports.index', $locationParam) }}" class="text-xs font-semibold text-emerald-700 hover:underline">Full report →</a>
                        </div>
                        <div class="relative w-full"><canvas id="chart-destinations" height="160"></canvas></div>
                    </section>
                </div>

                @if($isSuperAdmin && ! $dashboardLocation && isset($locationComparison))
                    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="font-bold text-gray-900">Location Comparison</h3>
                            <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-emerald-700 hover:underline">Full report →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-gray-100 text-gray-500">
                                        <th class="py-2 pr-4 font-semibold">Location</th>
                                        <th class="py-2 pr-4 font-semibold">Vehicles</th>
                                        <th class="py-2 pr-4 font-semibold">Pending</th>
                                        <th class="py-2 pr-4 font-semibold">Trips</th>
                                        <th class="py-2 pr-4 font-semibold">Maintenance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($locationComparison as $row)
                                        <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('dashboard', ['location' => $row['location']]) }}'">
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
                    const opts = { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } };
                    new window.Chart(document.getElementById('chart-requests-month'), {
                        type: 'line',
                        data: {
                            labels: @json($requestsPerMonth['labels']),
                            datasets: [{ data: @json($requestsPerMonth['data']), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3 }],
                        },
                        options: opts,
                    });
                    new window.Chart(document.getElementById('chart-destinations'), {
                        type: 'bar',
                        data: {
                            labels: @json($topDestinations['labels']),
                            datasets: [{ data: @json($topDestinations['data']), backgroundColor: '#3b82f6' }],
                        },
                        options: {
                            responsive: true,
                            indexAxis: 'y',
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true, ticks: { stepSize: 1 } }
                            }
                        },
                    });
                });

                function adminDashboardCalendar(rawEvents) {
                    return {
                        viewYear: new Date().getFullYear(),
                        viewMonth: new Date().getMonth(),
                        events: rawEvents || [],

                        get monthYearTitle() {
                            return new Date(this.viewYear, this.viewMonth, 1).toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
                        },

                        get calendarDays() {
                            const days = [];
                            const firstDay = new Date(this.viewYear, this.viewMonth, 1);
                            const lastDay = new Date(this.viewYear, this.viewMonth + 1, 0);
                            const startDayOfWeek = firstDay.getDay();
                            const totalDays = lastDay.getDate();
                            const todayStr = new Date().toISOString().split('T')[0];

                            // Prev month padding
                            const prevMonthLastDay = new Date(this.viewYear, this.viewMonth, 0).getDate();
                            for (let i = startDayOfWeek - 1; i >= 0; i--) {
                                const d = prevMonthLastDay - i;
                                const dateObj = new Date(this.viewYear, this.viewMonth - 1, d);
                                const dateStr = dateObj.toISOString().split('T')[0];
                                days.push({
                                    dayNum: d,
                                    inMonth: false,
                                    isToday: dateStr === todayStr,
                                    dateStr: dateStr,
                                    events: this.getEventsForDate(dateStr)
                                });
                            }

                            // Current month days
                            for (let d = 1; d <= totalDays; d++) {
                                const dateObj = new Date(this.viewYear, this.viewMonth, d);
                                // Local date ISO string format YYYY-MM-DD
                                const monthPadded = String(this.viewMonth + 1).padStart(2, '0');
                                const dayPadded = String(d).padStart(2, '0');
                                const dateStr = `${this.viewYear}-${monthPadded}-${dayPadded}`;
                                days.push({
                                    dayNum: d,
                                    inMonth: true,
                                    isToday: dateStr === todayStr,
                                    dateStr: dateStr,
                                    events: this.getEventsForDate(dateStr)
                                });
                            }

                            // Next month padding
                            const totalCells = days.length;
                            const nextPadding = (7 - (totalCells % 7)) % 7;
                            for (let d = 1; d <= nextPadding; d++) {
                                const dateObj = new Date(this.viewYear, this.viewMonth + 1, d);
                                const dateStr = dateObj.toISOString().split('T')[0];
                                days.push({
                                    dayNum: d,
                                    inMonth: false,
                                    isToday: dateStr === todayStr,
                                    dateStr: dateStr,
                                    events: this.getEventsForDate(dateStr)
                                });
                            }

                            return days;
                        },

                        getEventsForDate(dateStr) {
                            return this.events.filter(e => {
                                if (!e.start) return false;
                                const end = e.end || e.start;
                                return dateStr >= e.start && dateStr <= end;
                            });
                        },

                        nextMonth() {
                            if (this.viewMonth === 11) {
                                this.viewMonth = 0;
                                this.viewYear++;
                            } else {
                                this.viewMonth++;
                            }
                        },

                        prevMonth() {
                            if (this.viewMonth === 0) {
                                this.viewMonth = 11;
                                this.viewYear--;
                            } else {
                                this.viewMonth--;
                            }
                        }
                    };
                }

                function fleetDashboard({ initialAlerts, initialKpis, initialFleetStatus, refreshUrl }) {
                    return {
                        alerts: initialAlerts,
                        kpis: initialKpis,
                        fleetStatus: initialFleetStatus,
                        lastUpdatedLabel: 'just now',
                        chart: null,
                        init() {
                            this.$nextTick(() => this.renderChart());
                            setInterval(() => this.refresh(), 60000);
                        },
                        kpiIcon(name) {
                            const icons = {
                                clock: '◷', check: '✓', truck: '▣', users: '♙', shield: '♢',
                            };
                            return icons[name] ?? '●';
                        },
                        renderChart() {
                            const ctx = this.$refs.fleetChart?.getContext('2d');
                            if (!ctx || !window.Chart) return;
                            const segments = this.fleetStatus.segments;
                            if (this.chart) this.chart.destroy();
                            this.chart = new window.Chart(ctx, {
                                type: 'doughnut',
                                data: {
                                    labels: segments.map(s => s.label),
                                    datasets: [{
                                        data: segments.map(s => s.value),
                                        backgroundColor: segments.map(s => s.color),
                                        borderWidth: 0,
                                    }],
                                },
                                options: {
                                    cutout: '65%',
                                    plugins: { legend: { display: false } },
                                    onClick: (evt, elements) => {
                                        if (elements.length) {
                                            window.location = segments[elements[0].index].url;
                                        }
                                    },
                                },
                            });
                        },
                        refresh() {
                            fetch(refreshUrl, { headers: { 'Accept': 'application/json' } })
                                .then(r => r.ok ? r.json() : Promise.reject())
                                .then(data => {
                                    this.alerts = data.alerts;
                                    this.kpis = data.kpis;
                                    this.fleetStatus = data.fleetStatus;
                                    this.lastUpdatedLabel = 'just now';
                                    this.$nextTick(() => this.renderChart());
                                })
                                .catch(() => {});
                        },
                    };
                }
            </script>
        @elseif($isDriver)
            {{-- Profile Incomplete Warning Banner --}}
            @if(count($missingProfileItems) > 0)
                <div class="rounded-xl border border-amber-200/80 bg-amber-50/80 p-4 shadow-2xs">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 shadow-2xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-extrabold text-amber-900">
                                    Profile Incomplete ({{ count($missingProfileItems) }} missing item{{ count($missingProfileItems) > 1 ? 's' : '' }})
                                </h4>
                                <p class="text-xs text-amber-800">
                                    Please complete your driver details: <span class="font-bold text-amber-950">{{ implode(', ', $missingProfileItems) }}</span>.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('my-profile') }}" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-amber-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"/><path d="16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                            </svg>
                            Complete Profile
                        </a>
                    </div>
                </div>
            @endif

            {{-- 4 Stat Cards --}}
            <div class="grid grid-cols-2 gap-2.5 md:grid-cols-4">
                {{-- Completed Travels --}}
                <div class="rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 truncate">Completed Travels</p>
                            <p class="text-[11px] font-medium text-slate-500 truncate"><strong class="text-xs font-black text-slate-900">{{ $completedTravelsCount }}</strong> Past trips finished</p>
                        </div>
                    </div>
                </div>

                {{-- Future Assigned Trips --}}
                <div class="rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" x2="16" y1="2" y2="6"/>
                                <line x1="8" x2="8" y1="2" y2="6"/>
                                <line x1="3" x2="21" y1="10" y2="10"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 truncate">Future Assigned Trips</p>
                            <p class="text-[11px] font-medium text-slate-500 truncate"><strong class="text-xs font-black text-slate-900">{{ $futureTripsCount }}</strong> Scheduled ahead</p>
                        </div>
                    </div>
                </div>

                {{-- Active Trips Today --}}
                <div class="rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 truncate">Active Trips Today</p>
                            <p class="text-[11px] font-medium text-slate-500 truncate"><strong class="text-xs font-black text-slate-900">{{ $activeTripsTodayCount }}</strong> Happening now</p>
                        </div>
                    </div>
                </div>

                {{-- Total Dispatches --}}
                <div class="rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-100 text-purple-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 truncate">Total Dispatches</p>
                            <p class="text-[11px] font-medium text-slate-500 truncate"><strong class="text-xs font-black text-slate-900">{{ $totalDispatchesCount }}</strong> All time trips</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- My Permanent Assigned Vehicle Card --}}
            @if($showAssignedVehicleSetting)
                <div class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-2xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-emerald-700 mb-2">My Permanent Assigned Vehicle</p>
                    @if($driverVehicle)
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-16 shrink-0 items-center justify-center rounded-lg bg-slate-100 p-0.5 border border-slate-200 overflow-hidden">
                                    @if($driverVehicle->photo_url)
                                        <img src="{{ $driverVehicle->photo_url }}" alt="{{ $driverVehicle->model }}" class="h-full w-full object-cover rounded">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-xs font-black text-slate-900">
                                        {{ $driverVehicle->brand }} {{ $driverVehicle->model }} — <span class="font-extrabold text-slate-800">{{ $driverVehicle->plate_number }}</span>
                                    </h3>
                                    <p class="mt-0.5 text-[11px] font-medium text-slate-500">
                                        Color: <span class="font-semibold text-slate-700">{{ $driverVehicle->color ?: 'N/A' }}</span> ·
                                        Year: <span class="font-semibold text-slate-700">{{ $driverVehicle->year ?: 'N/A' }}</span> ·
                                        Type: <span class="font-semibold text-slate-700">{{ $driverVehicle->vehicle_type ?: 'N/A' }}</span>
                                    </p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 border border-emerald-200/80 shadow-2xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                Ready for Dispatch
                            </span>
                        </div>
                    @else
                        <div class="flex items-center gap-2.5 rounded-lg bg-slate-50 p-2.5 border border-dashed border-slate-200">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-200/80 text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">No Permanent Vehicle Assigned</p>
                                <p class="text-[10px] text-slate-500">Refer to your Administrator to assign a permanent vehicle to your profile.</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Two Column Travels Grid --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                {{-- Future Assigned Travels --}}
                <div class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-1.5 border-b border-slate-100 pb-2 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" x2="16" y1="2" y2="6"/>
                                <line x1="8" x2="8" y1="2" y2="6"/>
                                <line x1="3" x2="21" y1="10" y2="10"/>
                            </svg>
                            <h3 class="text-xs font-extrabold text-slate-900">Future Assigned Travels ({{ $futureTripsCount }})</h3>
                        </div>

                        <div class="space-y-2">
                            @forelse($driverFutureTrips as $trip)
                                <div class="rounded-lg border border-emerald-200/80 bg-emerald-50/60 p-3 shadow-2xs">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-red-500 text-xs shrink-0">📍</span>
                                                <h4 class="text-xs font-extrabold text-slate-900 truncate">{{ $trip->destination }}</h4>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-700">
                                                Requester: <span class="font-extrabold text-slate-950">{{ $trip->requester_name }}</span>
                                                @if($trip->requester_office)
                                                    <span class="text-slate-600 font-medium">({{ $trip->requester_office }})</span>
                                                @endif
                                            </p>
                                            <p class="mt-0.5 text-xs text-slate-600">
                                                Vehicle: <span class="font-bold text-slate-800">{{ $trip->vehicle?->plate_number ?? 'N/A' }}</span> ·
                                                Purpose: <span class="font-bold text-slate-800">{{ $trip->purpose ?: 'N/A' }}</span>
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-xs font-bold text-slate-900">
                                                {{ $trip->travel_date?->format('M j') }}{{ $trip->travel_date_end ? ' – ' . $trip->travel_date_end->format('j, Y') : ($trip->travel_date ? ', ' . $trip->travel_date->format('Y') : '') }}
                                            </span>
                                            <div class="mt-1">
                                                <span class="inline-flex rounded bg-emerald-200/80 px-1.5 py-0.5 text-[9px] font-extrabold text-emerald-900 tracking-wider">
                                                    APPROVED
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-8 text-center text-[11px] text-slate-400 font-medium">
                                    No future assigned travels yet.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Travels Already Made --}}
                <div class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                            <div class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z"/>
                                </svg>
                                <h3 class="text-xs font-extrabold text-slate-900">Travels Already Made ({{ $completedTravelsCount }})</h3>
                            </div>
                            <span class="text-[11px] font-bold text-emerald-700 cursor-pointer">View full history &rsaquo;</span>
                        </div>

                        <div class="space-y-2">
                            @forelse($driverCompletedTrips as $trip)
                                <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-3 shadow-2xs">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-slate-400 text-xs">📍</span>
                                            <h4 class="text-xs font-bold text-slate-800">{{ $trip->destination }}</h4>
                                        </div>
                                        <span class="text-[11px] text-slate-500 font-medium">
                                            {{ $trip->travel_date?->format('M j, Y') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-600">
                                        Requester: <span class="font-semibold text-slate-700">{{ $trip->requester_name }}</span>
                                    </p>
                                </div>
                            @empty
                                <div class="py-8 text-center text-[11px] text-slate-400 font-medium">
                                    No completed trips yet.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"><h3 class="text-lg font-bold text-gray-900">Welcome to Fleet Management</h3><p class="mt-1 text-sm text-gray-500">Your assigned trips and vehicle information will appear here.</p></section>
        @endif
    </div>
@endsection
