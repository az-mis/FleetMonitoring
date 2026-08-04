<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Fleet Monitoring'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-emerald-50 via-white to-green-50 text-gray-900 min-h-screen">
    <div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <div class="mb-8 flex flex-col items-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-green-700 text-white font-bold text-xl shadow-lg shadow-emerald-900/10">
                FM
            </div>
            <span class="mt-3 text-sm font-semibold text-emerald-800 tracking-wide uppercase">Fleet Monitoring</span>
        </div>

        <div class="w-full max-w-md">
            <div class="rounded-2xl border border-emerald-100 bg-white p-8 shadow-xl shadow-emerald-900/5">
                @yield('content')
            </div>
        </div>

        <p class="mt-8 text-xs text-emerald-700/60">&copy; {{ date('Y') }} {{ config('app.name', 'Fleet Monitoring') }}. All rights reserved.</p>
    </div>
</body>
</html>
