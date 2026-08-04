<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') &middot; {{ config('app.name', 'Fleet Monitoring') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50 text-gray-900">
    @php
        $user = auth()->user();
        $hasPhoto = filled($user?->avatar_url ?? null);
    @endphp

    <div class="flex min-h-screen">
        {{-- Mobile sidebar overlay --}}
        <div
            id="sidebar-overlay"
            onclick="closeSidebar()"
            class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden"
        ></div>

        {{-- Sidebar --}}
        <aside
            id="sidebar"
            class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-[#0b1220] transition-transform duration-200 lg:static lg:translate-x-0 lg:flex lg:shrink-0"
        >
            <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-green-600 text-white font-bold text-sm shadow">
                    FM
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-white">{{ config('app.name', 'Fleet Monitoring') }}</p>
                    <p class="text-xs text-emerald-400">Vehicle Monitoring</p>
                </div>

                <button
                    type="button"
                    onclick="closeSidebar()"
                    class="ml-auto rounded-md p-1.5 text-gray-400 hover:bg-white/5 hover:text-white lg:hidden"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <a
                    href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-emerald-600 to-green-600 text-white shadow' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9.5 12 3l9 6.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V9.5Z"/>
                    </svg>
                    Dashboard
                </a>

                @can('viewAny', App\Models\User::class)
                    <a
                        href="{{ route('admin.index') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.*') ? 'bg-gradient-to-r from-emerald-600 to-green-600 text-white shadow' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>
                        </svg>
                        Admins
                    </a>
                @endcan

                <a
                    href="#"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 13h2l2-6h10l2 6h2"/>
                        <path d="M5 13v5a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-5"/>
                        <circle cx="7.5" cy="16.5" r="1.5"/>
                        <circle cx="16.5" cy="16.5" r="1.5"/>
                    </svg>
                    Vehicles
                </a>
            </nav>

            {{-- Logged-in user + logout --}}
            <div class="border-t border-white/10 px-3 py-4 space-y-3">
                <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                    @if ($hasPhoto)
                        <img
                            src="{{ $user->avatar_url }}"
                            alt="{{ $user->name }}"
                            class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white/10"
                        >
                    @else
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 ring-2 ring-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"/>
                                <path d="M4 21a8 8 0 0 1 16 0 1 1 0 0 1-1 1H5a1 1 0 0 1-1-1Z"/>
                            </svg>
                        </div>
                    @endif
                    <div class="min-w-0 leading-tight">
                        <p class="truncate text-sm font-medium text-white">{{ $user?->name }}</p>
                        <p class="truncate text-xs text-gray-400">{{ $user?->email }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="document.getElementById('logout-modal').classList.remove('hidden'); document.getElementById('logout-modal').classList.add('flex')"
                    class="flex w-full items-center gap-3 rounded-lg bg-red-500/10 px-3 py-2.5 text-sm font-medium text-red-400 hover:bg-red-500/20 hover:text-red-300 transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <path d="M16 17l5-5-5-5"/>
                        <path d="M21 12H9"/>
                    </svg>
                    Log out
                </button>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex flex-1 flex-col min-w-0">
            {{-- Topbar --}}
            <header class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        type="button"
                        onclick="openSidebar()"
                        class="shrink-0 rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 lg:hidden"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 6h16"/>
                            <path d="M4 12h16"/>
                            <path d="M4 18h16"/>
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide truncate">
                            {{ $user?->role?->label() ?? 'Guest' }}
                        </p>
                        <h1 class="truncate text-lg font-semibold text-gray-900 sm:text-xl">@yield('page-title', 'Dashboard')</h1>
                    </div>
                </div>

                @if ($hasPhoto)
                    <img
                        src="{{ $user->avatar_url }}"
                        alt="{{ $user->name }}"
                        class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-emerald-100"
                    >
                @else
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 ring-2 ring-emerald-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"/>
                            <path d="M4 21a8 8 0 0 1 16 0 1 1 0 0 1-1 1H5a1 1 0 0 1-1-1Z"/>
                        </svg>
                    </div>
                @endif
            </header>

            <main class="flex-1 bg-gray-50 p-4 sm:p-6">
                @if (session('success'))
                    <div id="flash-success" class="mb-6 flex items-start justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        <span>{{ session('success') }}</span>
                        <button type="button" onclick="document.getElementById('flash-success').remove()" class="shrink-0 rounded-md p-0.5 text-emerald-500 hover:bg-emerald-100 hover:text-emerald-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div id="flash-error" class="mb-6 flex items-start justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <span>{{ session('error') }}</span>
                        <button type="button" onclick="document.getElementById('flash-error').remove()" class="shrink-0 rounded-md p-0.5 text-red-500 hover:bg-red-100 hover:text-red-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    {{-- Logout confirmation modal --}}
    <div id="logout-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <path d="M16 17l5-5-5-5"/>
                    <path d="M21 12H9"/>
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-semibold text-gray-900">Log out?</h2>
            <p class="mt-1 text-sm text-gray-500">You'll need to log in again to access your dashboard.</p>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    onclick="document.getElementById('logout-modal').classList.add('hidden'); document.getElementById('logout-modal').classList.remove('flex')"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                >
                    Cancel
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition"
                    >
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openSidebar() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.remove('hidden');
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.add('hidden');
        }
    </script>
</body>
</html>
