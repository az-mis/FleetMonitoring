@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
    <div class="mx-auto max-w-2xl"><div class="mb-6"><h2 class="text-lg font-semibold text-gray-900">Display Settings</h2><p class="mt-1 text-sm text-gray-500">Customize how Fleet Management behaves on this device.</p></div>
        <section class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-5 shadow-sm"><div class="flex items-center justify-between gap-4"><div><h3 class="font-semibold text-emerald-900">Footer driving animation</h3><p class="mt-1 text-sm text-emerald-800/70">Show the reference project’s small fleet animation at the bottom of the dashboard.</p></div><input id="car-animation" type="checkbox" aria-label="Footer driving animation" class="h-5 w-5 shrink-0 rounded border-gray-300 accent-emerald-600 text-emerald-600 focus:ring-emerald-500"></div></section>
        <section class="mt-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between gap-4"><div><h3 class="font-semibold text-gray-900">Interface effects</h3><p class="mt-1 text-sm text-gray-500">Enable subtle transitions and visual effects.</p></div><input id="ui-effects" type="checkbox" aria-label="Interface effects" class="h-5 w-5 shrink-0 rounded border-gray-300 accent-emerald-600 text-emerald-600 focus:ring-emerald-500"></div></section>
        <section class="mt-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-gray-900">Account</h3><p class="mt-1 text-sm text-gray-500">Update your personal information from <a href="{{ route('my-profile') }}" class="font-semibold text-emerald-700 hover:underline">My Profile</a>.</p></section>
    </div>
    <script>
        const car = document.getElementById('car-animation');
        car.checked = localStorage.getItem('fleet_car_animation_enabled') !== 'false';
        car.addEventListener('change', () => localStorage.setItem('fleet_car_animation_enabled', String(car.checked)));
        const effects = document.getElementById('ui-effects');
        effects.checked = localStorage.getItem('fleet_ui_effects_enabled') !== 'false';
        effects.addEventListener('change', () => {
            localStorage.setItem('fleet_ui_effects_enabled', String(effects.checked));
            document.body.classList.toggle('disable-ui-effects', !effects.checked);
        });
    </script>
@endsection
