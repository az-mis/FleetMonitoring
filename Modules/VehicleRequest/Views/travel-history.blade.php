@extends('layouts.app')

@section('title', 'History of Travel')
@section('page-title', 'History of Travel')

@php
    $today = now()->toDateString();
    $upcoming = $trips->filter(fn ($trip) => ($trip->travel_date_end?->toDateString() ?: $trip->travel_date->toDateString()) >= $today);
    $completed = $trips->count() - $upcoming->count();
@endphp

@section('content')
    <div class="mb-6"><h2 class="text-lg font-semibold text-gray-900">History of Travel</h2><p class="mt-1 text-sm text-gray-500">Complete record of your assigned and completed dispatch travels.</p></div>
    <div class="mb-5 grid grid-cols-3 gap-3"><div class="rounded-xl border border-blue-100 bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Upcoming / Active</p><p class="mt-1 text-2xl font-bold text-blue-700">{{ $upcoming->count() }}</p></div><div class="rounded-xl border border-emerald-100 bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Completed Travels</p><p class="mt-1 text-2xl font-bold text-emerald-700">{{ $completed }}</p></div><div class="rounded-xl border border-violet-100 bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Total Dispatches</p><p class="mt-1 text-2xl font-bold text-violet-700">{{ $trips->count() }}</p></div></div>
    <div class="space-y-3">
        @forelse($trips as $trip)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-semibold text-gray-900">{{ $trip->destination }}</p><p class="mt-1 text-xs text-gray-500">{{ $trip->travel_date->format('M j, Y') }}{{ $trip->travel_date_end ? ' – ' . $trip->travel_date_end->format('M j, Y') : '' }} · {{ $trip->requester_name }}</p></div><a href="{{ route('trip-ticket', $trip) }}" target="_blank" class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Print Ticket</a></div><p class="mt-3 text-sm text-gray-600">{{ $trip->purpose }}</p><p class="mt-2 text-xs text-gray-500">Vehicle: {{ $trip->vehicle?->plate_number ?? '—' }} · Ticket: {{ $trip->trip_ticket_number ?? '—' }}</p></div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white p-10 text-center text-sm text-gray-500">No approved trips assigned to you yet.</div>
        @endforelse
    </div>
@endsection
