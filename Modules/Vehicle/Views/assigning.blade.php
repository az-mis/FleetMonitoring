@extends('layouts.app')

@section('title', 'Vehicle Assigning')
@section('page-title', 'Vehicle Assigning')

@section('content')
    <div class="mb-6"><h2 class="text-lg font-semibold text-gray-900">Vehicle Assignments</h2><p class="mt-1 text-sm text-gray-500">Assign one permanent driver and location to each vehicle.</p></div>
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse($vehicles as $vehicle)
            <form method="POST" action="{{ route('vehicle-assigning.update', $vehicle) }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                @csrf @method('PATCH')
                <div class="flex items-start justify-between gap-3"><div><h3 class="font-semibold text-gray-900">{{ $vehicle->plate_number }}</h3><p class="text-xs text-gray-500">{{ $vehicle->brand }} {{ $vehicle->model }} · {{ $vehicle->color }}</p></div><span class="rounded-full bg-emerald-50 px-2 py-1 text-xs text-emerald-700">{{ $vehicle->assignedDriver?->name ?? 'Unassigned' }}</span></div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2"><div><label class="mb-1 block text-xs font-semibold text-gray-700">Permanent Driver</label><select name="assigned_driver_id" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"><option value="">Unassigned</option>@foreach($drivers as $driver)<option value="{{ $driver->id }}" @selected($vehicle->assigned_driver_id === $driver->id)>{{ $driver->name }}</option>@endforeach</select></div><div><label class="mb-1 block text-xs font-semibold text-gray-700">Location / Province</label><input name="location" value="{{ $vehicle->location }}" placeholder="e.g. Oriental Mindoro" class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></div></div>
                <div class="mt-4 flex justify-end"><button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Save Assignment</button></div>
            </form>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500">No vehicles registered.</div>
        @endforelse
    </div>
@endsection
