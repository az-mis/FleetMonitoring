@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    $user = auth()->user();
    $roleValue = $user?->role?->value ?? $user?->role;
    $isAdmin = in_array($roleValue, ['admin', 'super_admin'], true);
    $isSuperAdmin = $roleValue === 'super_admin';
    $isDriver = $roleValue === 'driver';

    $dashboardLocation = $roleValue === 'admin' ? $user->location : null;
    $adminCount = \App\Models\User::where('role', \Modules\Admin\Enums\UserRole::ADMIN)->count();
    $driverCount = \App\Models\User::where('role', \Modules\Admin\Enums\UserRole::DRIVER)->count();
    $vehicleCount = \Modules\Vehicle\Models\Vehicle::count();
    $requestQuery = fn () => \Modules\VehicleRequest\Models\VehicleRequest::query()->when($dashboardLocation, fn ($query) => $query->where('location', $dashboardLocation));
    $requestCounts = $requestQuery()->selectRaw("status, count(*) as total")->groupBy('status')->pluck('total', 'status');
    $locationTotals = $requestQuery()->selectRaw('location, count(*) as total')->groupBy('location')->orderByDesc('total')->limit(6)->pluck('total', 'location');
    $recentRequests = $requestQuery()->with('vehicle')->latest()->limit(5)->get();
    $upcomingTrips = $requestQuery()->with('vehicle')->where('status', 'approved')->whereDate('travel_date', '>=', now()->toDateString())->orderBy('travel_date')->limit(3)->get();
    
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
        @if(($announcement['enabled'] ?? false) && filled($announcement['message'] ?? null))
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/80 px-4 py-3.5 text-xs text-amber-900 shadow-2xs flex items-start gap-3">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
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

        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#155f35] via-[#1d8148] to-[#269d59] px-5 py-5 text-white shadow-lg shadow-emerald-900/15 sm:px-6">
            <div class="absolute -right-8 -top-12 h-40 w-40 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-20 right-16 h-32 w-32 rounded-full bg-white/5"></div>
            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div>
                    <span class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-semibold">✦ {{ $greeting }}</span>
                    <h2 class="text-xl font-extrabold tracking-tight">Welcome back, {{ $user?->name }}</h2>
                    <p class="mt-1 text-xs text-white/80">Signed in as <strong class="text-white">{{ $user?->role?->label() ?? 'User' }}</strong><span class="mx-2">•</span>{{ now()->format('l, F j, Y') }}</p>
                </div>
                @if($isAdmin)
                    <a href="{{ route('vehicle-requests.index') }}" class="rounded-lg border border-white/30 bg-white/10 px-3 py-2 text-xs font-semibold backdrop-blur hover:bg-white/20">Review Requests →</a>
                @endif
            </div>
        </section>

        @if($isAdmin)
            @php
                $kpiCards = [
                    ['Pending Requests', $requestCounts['pending'] ?? 0, 'amber', '◷'],
                    ['Approved Trips', $requestCounts['approved'] ?? 0, 'emerald', '✓'],
                    ['Total Vehicles', $vehicleCount, 'blue', '▣'],
                    ['Total Drivers', $driverCount, 'violet', '♙'],
                ];
                if ($isSuperAdmin) {
                    $kpiCards[] = ['Admins', $adminCount, 'orange', '♢'];
                }
            @endphp
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-{{ count($kpiCards) }}">
                @foreach($kpiCards as [$label, $value, $color, $icon])
                    <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm"><div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-{{ $color }}-100 text-{{ $color }}-700">{{ $icon }}</span><div><p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p><p class="text-xl font-bold text-gray-900">{{ $value }}</p></div></div></div>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-2"><a href="{{ route('vehicle-requests.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">▣ Review Requests ({{ $requestCounts['pending'] ?? 0 }})</a><a href="{{ route('vehicles.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">▣ Manage Vehicles</a><a href="{{ route('vehicle-assigning.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">♙ Driver Assignments</a></div>

            <div class="grid gap-5 xl:grid-cols-2"><section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="mb-4 flex items-center justify-between"><h3 class="font-bold text-gray-900">Trip Tickets by Location</h3><span class="rounded-md bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700">{{ $locationTotals->sum() }} total</span></div><div class="space-y-3">@forelse($locationTotals as $location => $total)<div class="flex items-center gap-3 text-xs"><span class="w-28 truncate text-gray-500">{{ $location }}</span><div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ max(8, min(100, ($total / max(1, $locationTotals->max())) * 100)) }}%"></div></div><span class="w-12 text-right text-gray-500">{{ $total }}</span></div>@empty<p class="py-5 text-center text-sm text-gray-400">No location data yet.</p>@endforelse</div></section><section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="mb-4 flex items-center justify-between"><h3 class="font-bold text-gray-900">Request Status Breakdown</h3><span class="rounded-md bg-blue-50 px-2 py-1 text-[10px] font-bold text-blue-700">{{ $requestCounts->sum() }} total</span></div><div class="space-y-4"><div class="h-3 overflow-hidden rounded-full bg-gray-100"><div class="h-full bg-emerald-500" style="width: {{ ($requestCounts['approved'] ?? 0) && $requestCounts->sum() ? (($requestCounts['approved'] / $requestCounts->sum()) * 100) : 0 }}%"></div></div><div class="flex flex-wrap gap-4 text-xs text-gray-600"><span class="text-emerald-700">● Approved: {{ $requestCounts['approved'] ?? 0 }}</span><span class="text-amber-600">● Pending: {{ $requestCounts['pending'] ?? 0 }}</span><span class="text-red-600">● Declined: {{ $requestCounts['declined'] ?? 0 }}</span></div></div></section></div>

            <div class="grid gap-5 xl:grid-cols-2">
                <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="mb-3 flex items-center justify-between"><h3 class="font-bold text-gray-900">Recent Requests</h3><a href="{{ route('vehicle-requests.index') }}" class="text-xs font-semibold text-emerald-700 hover:underline">View all →</a></div><div class="space-y-2">
                    @forelse($recentRequests as $item)
                        <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2.5"><div class="min-w-0"><p class="truncate text-sm font-semibold text-gray-900">{{ $item->requester_name }} <span class="ml-1 rounded bg-{{ $item->status === 'approved' ? 'emerald' : ($item->status === 'declined' ? 'red' : 'amber') }}-100 px-1.5 py-0.5 text-[10px] uppercase">{{ $item->status }}</span></p><p class="truncate text-xs text-gray-500">{{ $item->vehicle?->plate_number ?? 'No vehicle' }} · {{ $item->destination }}</p></div><span class="ml-3 shrink-0 text-[11px] text-gray-400">{{ $item->travel_date->format('M j') }}</span></div>
                    @empty <p class="py-8 text-center text-sm text-gray-500">No requests yet.</p>
                    @endforelse
                </div></section>

                <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="mb-3 flex items-center justify-between"><h3 class="font-bold text-gray-900">Upcoming Approved Trips</h3><a href="{{ route('vehicle-requests.index', ['status' => 'approved']) }}" class="text-xs font-semibold text-emerald-700 hover:underline">View all →</a></div><div class="space-y-2">
                    @forelse($upcomingTrips as $trip)
                        <div class="rounded-lg border border-emerald-100 bg-emerald-50/50 px-3 py-2.5"><div class="flex justify-between gap-3"><p class="text-sm font-semibold text-emerald-900">{{ $trip->destination }}</p><span class="shrink-0 text-xs font-bold text-emerald-700">{{ $trip->travel_date->format('M j, Y') }}</span></div><p class="mt-1 text-xs text-emerald-800">Vehicle: {{ $trip->vehicle?->plate_number ?? '—' }} · {{ $trip->requester_name }}</p></div>
                    @empty <p class="py-8 text-center text-sm text-gray-500">No upcoming approved trips.</p>
                    @endforelse
                </div></section>
            </div>

            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="mb-3 flex items-center justify-between"><h3 class="font-bold text-gray-900">Fleet Overview</h3><a href="{{ route('vehicles.index') }}" class="text-xs font-semibold text-emerald-700 hover:underline">Manage vehicles →</a></div><div class="grid gap-3 sm:grid-cols-3"><div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Vehicles in fleet</p><p class="mt-1 text-2xl font-bold text-gray-900">{{ $vehicleCount }}</p></div><div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Active drivers</p><p class="mt-1 text-2xl font-bold text-gray-900">{{ $driverCount }}</p></div><div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Requests awaiting review</p><p class="mt-1 text-2xl font-bold text-amber-600">{{ $requestCounts['pending'] ?? 0 }}</p></div></div></section>
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
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                {{-- Completed Travels --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Completed Travels</p>
                            <p class="text-xl font-black text-slate-900 leading-tight">{{ $completedTravelsCount }}</p>
                            <p class="text-[10px] font-medium text-slate-400">{{ $completedTravelsCount }} Past trips finished</p>
                        </div>
                    </div>
                </div>

                {{-- Future Assigned Trips --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs">
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
                            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Future Assigned Trips</p>
                            <p class="text-xl font-black text-slate-900 leading-tight">{{ $futureTripsCount }}</p>
                            <p class="text-[10px] font-medium text-slate-400">{{ $futureTripsCount }} Scheduled ahead</p>
                        </div>
                    </div>
                </div>

                {{-- Active Trips Today --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Active Trips Today</p>
                            <p class="text-xl font-black text-slate-900 leading-tight">{{ $activeTripsTodayCount }}</p>
                            <p class="text-[10px] font-medium text-slate-400">{{ $activeTripsTodayCount }} Happening now</p>
                        </div>
                    </div>
                </div>

                {{-- Total Dispatches --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Total Dispatches</p>
                            <p class="text-xl font-black text-slate-900 leading-tight">{{ $totalDispatchesCount }}</p>
                            <p class="text-[10px] font-medium text-slate-400">{{ $totalDispatchesCount }} All time trips</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- My Permanent Assigned Vehicle Card --}}
            @if($showAssignedVehicleSetting)
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
                    <p class="text-[10px] font-black uppercase tracking-wider text-emerald-700 mb-3">My Permanent Assigned Vehicle</p>
                    @if($driverVehicle)
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="flex h-16 w-24 shrink-0 items-center justify-center rounded-xl bg-slate-100 p-1 border border-slate-200 overflow-hidden">
                                    @if($driverVehicle->photo_url)
                                        <img src="{{ $driverVehicle->photo_url }}" alt="{{ $driverVehicle->model }}" class="h-full w-full object-contain">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-slate-900">
                                        {{ $driverVehicle->brand }} {{ $driverVehicle->model }} — <span class="font-extrabold text-slate-800">{{ $driverVehicle->plate_number }}</span>
                                    </h3>
                                    <p class="mt-0.5 text-xs font-medium text-slate-500">
                                        Color: <span class="font-semibold text-slate-700">{{ $driverVehicle->color ?: 'N/A' }}</span> ·
                                        Year: <span class="font-semibold text-slate-700">{{ $driverVehicle->year ?: 'N/A' }}</span> ·
                                        Type: <span class="font-semibold text-slate-700">{{ $driverVehicle->vehicle_type ?: 'N/A' }}</span>
                                    </p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3.5 py-1.5 text-xs font-bold text-emerald-700 border border-emerald-200 shadow-2xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                Ready for Dispatch
                            </span>
                        </div>
                    @else
                        <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-4 border border-dashed border-slate-200">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-200/80 text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">No Permanent Vehicle Assigned</p>
                                <p class="text-[11px] text-slate-500">Refer to your Administrator to assign a permanent vehicle to your profile.</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Two Column Travels Grid --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Future Assigned Travels --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 border-b border-slate-100 pb-3 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" x2="16" y1="2" y2="6"/>
                                <line x1="8" x2="8" y1="2" y2="6"/>
                                <line x1="3" x2="21" y1="10" y2="10"/>
                            </svg>
                            <h3 class="text-sm font-extrabold text-slate-900">Future Assigned Travels ({{ $futureTripsCount }})</h3>
                        </div>

                        <div class="space-y-3">
                            @forelse($driverFutureTrips as $trip)
                                <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 shadow-2xs">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-red-500">📍</span>
                                            <h4 class="text-sm font-extrabold text-slate-900">{{ $trip->destination }}</h4>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs font-bold text-slate-800">
                                                {{ $trip->travel_date?->format('M j') }}{{ $trip->travel_date_end ? ' – ' . $trip->travel_date_end->format('j, Y') : ($trip->travel_date ? ', ' . $trip->travel_date->format('Y') : '') }}
                                            </span>
                                            <div class="mt-1">
                                                <span class="inline-flex rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-extrabold text-emerald-800">
                                                    APPROVED
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-600">
                                        Requester: <span class="font-bold text-slate-800">{{ $trip->requester_name }}</span>
                                        @if($trip->requester_office)
                                            <span class="text-slate-500">({{ $trip->requester_office }})</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">
                                        Vehicle: <span class="font-semibold text-slate-700">{{ $trip->vehicle?->plate_number ?? 'N/A' }}</span> ·
                                        Purpose: <span class="font-semibold text-slate-700">{{ $trip->purpose ?: 'N/A' }}</span>
                                    </p>
                                </div>
                            @empty
                                <div class="py-12 text-center text-xs text-slate-400 font-medium">
                                    No future assigned travels yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Travels Already Made --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z"/>
                                </svg>
                                <h3 class="text-sm font-extrabold text-slate-900">Travels Already Made ({{ $completedTravelsCount }})</h3>
                            </div>
                            <span class="text-xs font-bold text-emerald-700">View full history &rsaquo;</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($driverCompletedTrips as $trip)
                                <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4 shadow-2xs">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-slate-400">📍</span>
                                            <h4 class="text-sm font-bold text-slate-800">{{ $trip->destination }}</h4>
                                        </div>
                                        <span class="text-xs text-slate-500 font-medium">
                                            {{ $trip->travel_date?->format('M j, Y') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-600">
                                        Requester: <span class="font-semibold text-slate-700">{{ $trip->requester_name }}</span>
                                    </p>
                                </div>
                            @empty
                                <div class="py-12 text-center text-xs text-slate-400 font-medium">
                                    No completed trips yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Driver Dashboard Bottom Vehicle Graphic Illustration --}}
            <div class="mt-6 flex justify-center items-center opacity-90">
                <div class="relative flex flex-col items-center">
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <div class="flex items-center gap-4 bg-emerald-800/10 px-6 py-3 rounded-2xl border border-emerald-800/20 shadow-xs">
                                <div class="text-2xl">🛻</div>
                                <div>
                                    <span class="rounded-full bg-emerald-700 px-3 py-1 text-[11px] font-black text-white">
                                        Ops.
                                    </span>
                                    <p class="text-[11px] font-bold text-emerald-900 mt-1">Fleet Dispatch Ready</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"><h3 class="text-lg font-bold text-gray-900">Welcome to Fleet Management</h3><p class="mt-1 text-sm text-gray-500">Your assigned trips and vehicle information will appear here.</p></section>
        @endif
    </div>
@endsection
