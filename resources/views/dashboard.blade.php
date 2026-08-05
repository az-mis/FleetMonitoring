@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900">
            Welcome back, {{ Illuminate\Support\Str::upper(auth()->user()->name) }} 👋
        </h2>
        <p class="mt-1 text-sm text-gray-500">Here's what's happening with your fleet today.</p>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="relative overflow-hidden rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-blue-600"></div>
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-blue-900/70">Total Admins</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-semibold text-blue-950">
                {{ \App\Models\User::where('role', \Modules\Admin\Enums\UserRole::ADMIN)->count() }}
            </p>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-green-600"></div>
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-emerald-900/70">Total Drivers</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>
                        <path d="M9 13.5 12 16l3-2.5"/>
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-semibold text-emerald-950">
                {{ \App\Models\User::where('role', \Modules\Admin\Enums\UserRole::DRIVER)->count() }}
            </p>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-500"></div>
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-amber-900/70">Total Vehicles</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 13h2l2-6h10l2 6h2"/>
                        <path d="M5 13v5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-5"/>
                        <circle cx="7.5" cy="16.5" r="1.5"/>
                        <circle cx="16.5" cy="16.5" r="1.5"/>
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-semibold text-amber-950">
                {{ \Modules\Vehicle\Models\Vehicle::count() }}
            </p>
        </div>
    </div>
@endsection
