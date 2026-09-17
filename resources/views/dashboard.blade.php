@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    $user = auth()->user();
    $isAdmin = in_array($user?->role?->value, ['admin', 'super_admin'], true);
    $dashboardLocation = $user?->role?->value === 'admin' ? $user->location : null;
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
    $announcementKey = $user?->role?->value === 'driver' ? 'driver_announcement' : 'admin_announcement';
    try {
        $announcement = \Modules\Settings\Models\FleetSetting::where('setting_key', $announcementKey)->value('setting_value') ?? ['enabled' => false, 'message' => ''];
    } catch (\Throwable $exception) {
        $announcement = ['enabled' => false, 'message' => ''];
    }
@endphp

@section('content')
    <div class="space-y-5">
        @if(($announcement['enabled'] ?? false) && filled($announcement['message'] ?? null))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><p class="font-semibold">Announcement</p><p class="mt-1">{{ $announcement['message'] }}</p></div>
        @endif
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#155f35] via-[#1d8148] to-[#269d59] px-5 py-5 text-white shadow-lg shadow-emerald-900/15 sm:px-6">
            <div class="absolute -right-8 -top-12 h-40 w-40 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-20 right-16 h-32 w-32 rounded-full bg-white/5"></div>
            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div><span class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-semibold">✦ {{ $greeting }}</span><h2 class="text-xl font-extrabold tracking-tight">Welcome back, {{ $user?->name }}</h2><p class="mt-1 text-xs text-white/80">Signed in as <strong class="text-white">{{ $user?->role?->label() ?? 'User' }}</strong><span class="mx-2">•</span>{{ now()->format('l, F j, Y') }}</p></div>
                @if($isAdmin)<a href="{{ route('vehicle-requests.index') }}" class="rounded-lg border border-white/30 bg-white/10 px-3 py-2 text-xs font-semibold backdrop-blur hover:bg-white/20">Review Requests →</a>@endif
            </div>
        </section>

        @if($isAdmin)
            @php
                $isSuperAdmin = $user?->role?->value === 'super_admin';
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
        @else
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"><h3 class="text-lg font-bold text-gray-900">Welcome to Fleet Management</h3><p class="mt-1 text-sm text-gray-500">Your assigned trips and vehicle information will appear here.</p></section>
        @endif
    </div>
@endsection
