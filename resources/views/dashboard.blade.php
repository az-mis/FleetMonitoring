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
    <div class="grid grid-cols-1 gap-4 sm:max-w-xs">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500">Total Admins</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-semibold text-gray-900">
                {{ \App\Models\User::where('role', \Modules\Admin\Enums\UserRole::ADMIN)->count() }}
            </p>
        </div>
    </div>
@endsection
