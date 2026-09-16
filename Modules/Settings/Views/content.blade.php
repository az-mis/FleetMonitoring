@extends('layouts.app')

@section('title', 'Content Settings')
@section('page-title', 'Content Settings')

@section('content')
    <div class="mb-6"><h2 class="text-lg font-semibold text-gray-900">Content Settings</h2><p class="mt-1 text-sm text-gray-500">Control which modules and announcements are visible to staff.</p></div>
    <form method="POST" action="{{ route('content-settings.update') }}" class="space-y-5">
        @csrf @method('PUT')
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-gray-900">Admin modules</h3><div class="mt-4 grid gap-3 sm:grid-cols-2">@foreach(['vehicles' => 'Vehicles', 'vehicleAssigning' => 'Vehicle Assigning', 'vehicleRequests' => 'Vehicle Requests', 'drivers' => 'Drivers'] as $key => $label)<label class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-3 text-sm"><span>{{ $label }}</span><input type="hidden" name="admin_modules[{{ $key }}]" value="0"><input type="checkbox" name="admin_modules[{{ $key }}]" value="1" @checked($settings['admin_modules'][$key]) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"></label>@endforeach</div></section>
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-gray-900">Driver modules</h3><label class="mt-4 flex items-center justify-between rounded-lg bg-gray-50 px-3 py-3 text-sm"><span>Show assigned vehicle</span><span><input type="hidden" name="driver_modules[showAssignedVehicle]" value="0"><input type="checkbox" name="driver_modules[showAssignedVehicle]" value="1" @checked($settings['driver_modules']['showAssignedVehicle']) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"></span></label></section>
        @foreach(['admin_announcement' => 'Admin announcement', 'driver_announcement' => 'Driver announcement'] as $key => $label)<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-gray-900">{{ $label }}</h3><label class="mt-4 flex items-center gap-2 text-sm"><input type="hidden" name="{{ $key }}[enabled]" value="0"><input type="checkbox" name="{{ $key }}[enabled]" value="1" @checked($settings[$key]['enabled'] ?? false) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"> Enabled</label><textarea name="{{ $key }}[message]" rows="3" class="mt-3 w-full rounded-lg border-gray-300 text-sm" placeholder="Announcement message...">{{ $settings[$key]['message'] ?? '' }}</textarea></section>@endforeach
        <div class="flex justify-end"><button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Save Settings</button></div>
    </form>
@endsection
