@extends('layouts.app')

@section('title', 'Display & Text Size')
@section('page-title', 'Display & Text Size')

{{-- Drivers' settings page. Admins and Super Admins find the same text size choice in System Settings. --}}
@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Display &amp; Text Size</h2>
            <p class="mt-1 text-sm text-gray-500">Make the Fleet Management System easier to read on this device.</p>
        </div>

        @include('partials.text-size-picker')

        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-extrabold text-slate-900">Account</h3>
            <p class="mt-1 text-sm text-gray-500">Update your name, phone number and photo from <a href="{{ route('my-profile') }}" class="font-semibold text-emerald-700 hover:underline">My Profile</a>.</p>
        </section>
    </div>
@endsection
