<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') &middot; {{ config('app.name', 'Fleet Monitoring') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        button:not(:disabled),
        [type="button"]:not(:disabled),
        [type="submit"]:not(:disabled),
        [role="button"]:not(:disabled),
        a[href],
        select,
        .sidebar-item,
        #sidebar button {
            cursor: pointer;
        }
        button:disabled,
        [type="button"]:disabled,
        [type="submit"]:disabled {
            cursor: not-allowed;
        }
        #sidebar .sidebar-active {
            position: relative;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        #sidebar .sidebar-active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 999px;
            background: #f2a51a;
        }

        /* Collapsed icon-rail state — applied on desktop via JS */
        #sidebar.sidebar-collapsed {
            width: 4.5rem !important;
        }
        #sidebar.sidebar-collapsed .sidebar-label,
        #sidebar.sidebar-collapsed .sidebar-label-block,
        #sidebar.sidebar-collapsed .sidebar-footer-text {
            display: none !important;
        }
        #sidebar.sidebar-collapsed nav a,
        #sidebar.sidebar-collapsed nav button {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        #sidebar.sidebar-collapsed .logout-btn-container button {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        #sidebar.sidebar-collapsed .user-profile-link {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        /* Hide the X close button when collapsed (nothing to close) */
        #sidebar.sidebar-collapsed #sidebar-close-btn {
            display: none !important;
        }

        /* Animated Toast Banner Entry with Spring Popdown */
        @keyframes toastSlideDown {
            0% {
                opacity: 0;
                transform: translate(-50%, -40px) scale(0.9);
            }
            60% {
                opacity: 1;
                transform: translate(-50%, 8px) scale(1.03);
            }
            85% {
                transform: translate(-50%, -2px) scale(0.99);
            }
            100% {
                opacity: 1;
                transform: translate(-50%, 0) scale(1);
            }
        }
        .toast-animate-in {
            animation: toastSlideDown 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
    </style>
</head>
<body class="antialiased bg-gray-50 text-gray-900">
    @php
        $user = auth()->user();
        $isSuperAdmin = $user?->role === \Modules\Admin\Enums\UserRole::SUPER_ADMIN;
        $hasPhoto = filled($user?->avatar_url ?? null);
        $vehiclesOpen = request()->routeIs('vehicles.*') || request()->routeIs('vehicle-assigning.*') || request()->routeIs('maintenance.*') || request()->routeIs('vehicle-reports.*') || request()->is('vehicles*') || request()->is('vehicle-assigning*') || request()->is('maintenance*') || request()->is('vehicle-reports*');
        $moduleFlags = ['vehicles' => true, 'vehicleAssigning' => true, 'vehicleRequests' => true, 'drivers' => true];
        try {
            $storedFlags = \Modules\Settings\Models\FleetSetting::where('setting_key', 'admin_modules')->value('setting_value');
            $moduleFlags = array_replace($moduleFlags, $storedFlags ?? []);
        } catch (\Throwable $exception) {
            // Keep defaults until the settings migration is available.
        }

        $currentModuleKey = null;
        $currentModuleName = '';
        if (request()->routeIs('driver.*') || request()->is('driver*')) {
            $currentModuleKey = 'drivers';
            $currentModuleName = 'Drivers Management';
        } elseif (request()->routeIs('vehicles.*') || request()->is('vehicles*')) {
            $currentModuleKey = 'vehicles';
            $currentModuleName = 'Vehicles List';
        } elseif (request()->routeIs('vehicle-assigning.*') || request()->is('vehicle-assigning*')) {
            $currentModuleKey = 'vehicleAssigning';
            $currentModuleName = 'Vehicle Assigning';
        } elseif (request()->routeIs('vehicle-requests.*') || request()->is('vehicle-requests*')) {
            $currentModuleKey = 'vehicleRequests';
            $currentModuleName = 'Travel Requests';
        }

        $isModuleLocked = !$isSuperAdmin && $currentModuleKey && !($moduleFlags[$currentModuleKey] ?? true);
    @endphp

    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar overlay (for expanded drawer mode) --}}
        <div
            id="sidebar-overlay"
            onclick="closeSidebar()"
            class="fixed inset-0 z-40 hidden bg-black/40 transition-opacity duration-200"
        ></div>

        {{-- Sidebar — static part of layout on desktop (starts open), off-canvas on mobile --}}
        <aside
            id="sidebar"
            class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-[#005a36] transition-all duration-200 ease-in-out md:static md:w-64 md:translate-x-0 md:shrink-0"
        >
            <div class="flex items-center gap-3 px-4 py-4 border-b border-white/10">
                <img src="{{ $appLogoUrl ?? asset('da-logo.png') }}" alt="Agency logo" class="h-8 w-8 shrink-0 rounded-full bg-white object-contain p-0.5 shadow">
                <div class="sidebar-label-block leading-tight">
                    <p class="text-sm font-extrabold text-white">Fleet Management</p>
                </div>

                <button
                    id="sidebar-close-btn"
                    type="button"
                    onclick="closeSidebar()"
                    class="ml-auto rounded-md p-1.5 text-emerald-200 hover:bg-white/10 hover:text-white transition md:hidden"
                    title="Close sidebar"
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
                    title="Dashboard"
                    class="sidebar-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'sidebar-active' : 'text-emerald-50/90 hover:bg-white/10 hover:text-white' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    <span class="sidebar-label">Dashboard</span>
                </a>

                <a
                    href="{{ route('my-profile') }}"
                    title="My Profile"
                    class="sidebar-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('my-profile') ? 'sidebar-active' : 'text-emerald-50/90 hover:bg-white/10 hover:text-white' }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>
                    </svg>
                    <span class="sidebar-label">My Profile</span>
                </a>

                @can('viewAny', App\Models\User::class)
                    <a
                        href="{{ route('admin.index') }}"
                        title="Admins"
                        class="sidebar-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('admin.*') ? 'sidebar-active' : 'text-emerald-50/90 hover:bg-white/10 hover:text-white' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <span class="sidebar-label">Admins</span>
                    </a>
                @endcan

                @can('manage-drivers')
                    <a
                        href="{{ route('driver.index') }}"
                        title="Drivers"
                        class="sidebar-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('driver.*') ? 'sidebar-active' : 'text-emerald-50/90 hover:bg-white/10 hover:text-white' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span class="sidebar-label flex flex-1 items-center justify-between min-w-0">
                            <span>Drivers</span>
                            @if(!$isSuperAdmin && !($moduleFlags['drivers'] ?? true))
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-300 ml-auto shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" title="Disabled by Super Admin">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            @endif
                        </span>
                    </a>
                @endcan
                @if (auth()->user()?->role?->value === 'driver')
                    <a href="{{ route('travel-history') }}" title="Travel History" class="sidebar-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('travel-history') ? 'sidebar-active' : 'text-emerald-50/90 hover:bg-white/10 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M12 3v18"/><circle cx="12" cy="12" r="8"/></svg>
                        <span class="sidebar-label">Travel History</span>
                    </a>
                    <a href="{{ route('vehicle-reports.index') }}" title="Report Vehicle Issue" class="sidebar-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('vehicle-reports.*') ? 'sidebar-active' : 'text-emerald-50/90 hover:bg-white/10 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span class="sidebar-label">Report Vehicle Issue</span>
                    </a>
                @endif

                @if (in_array(auth()->user()?->role, [\Modules\Admin\Enums\UserRole::SUPER_ADMIN, \Modules\Admin\Enums\UserRole::ADMIN], true))
                    <div id="vehicles-menu-container" class="{{ $vehiclesOpen ? 'rounded-lg bg-white/10' : '' }}">
                        <button
                            type="button"
                            onclick="toggleVehiclesMenu(event)"
                            title="Vehicles"
                            class="sidebar-item w-full flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $vehiclesOpen ? 'sidebar-active' : 'text-emerald-50/90 hover:bg-white/10 hover:text-white' }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10 17h4V5H2v12h3"/>
                                <path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/>
                                <circle cx="7" cy="17" r="2"/>
                                <circle cx="17" cy="17" r="2"/>
                            </svg>
                            <span class="sidebar-label flex flex-1 items-center justify-between min-w-0 pr-1">
                                <span>Vehicles</span>
                                @if(!$isSuperAdmin && (!($moduleFlags['vehicles'] ?? true) && !($moduleFlags['vehicleAssigning'] ?? true)))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-300 ml-auto shrink-0 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" title="Disabled by Super Admin">
                                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                @endif
                            </span>
                            <svg id="vehicles-chevron" xmlns="http://www.w3.org/2000/svg" class="sidebar-label ml-auto h-4 w-4 transition-transform duration-200 {{ $vehiclesOpen ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>
                        <div id="vehicles-submenu" class="sidebar-label-block {{ $vehiclesOpen ? '' : 'hidden' }} space-y-1 pb-2 pl-8 pr-2">
                            <a href="{{ route('vehicles.index') }}" onclick="expandSidebarForVehicles()" class="flex items-center justify-between gap-2 rounded-md px-3 py-2 text-xs font-medium {{ (request()->routeIs('vehicles.*') || request()->is('vehicles*')) ? 'text-white font-bold bg-white/10' : 'text-emerald-50/80 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/></svg>
                                    Vehicles List
                                </span>
                                @if(!$isSuperAdmin && !($moduleFlags['vehicles'] ?? true))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" title="Disabled by Super Admin">
                                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                @endif
                            </a>
                            <a href="{{ route('vehicle-assigning.index') }}" onclick="expandSidebarForVehicles()" class="flex items-center justify-between gap-2 rounded-md px-3 py-2 text-xs font-medium {{ (request()->routeIs('vehicle-assigning.*') || request()->is('vehicle-assigning*')) ? 'text-white font-bold bg-white/10' : 'text-emerald-50/80 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>
                                    Vehicle Assigning
                                </span>
                                @if(!$isSuperAdmin && !($moduleFlags['vehicleAssigning'] ?? true))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" title="Disabled by Super Admin">
                                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                @endif
                            </a>
                            <a href="{{ route('maintenance.index') }}" onclick="expandSidebarForVehicles()" class="flex items-center gap-2 rounded-md px-3 py-2 text-xs font-medium {{ request()->routeIs('maintenance.*') ? 'text-white font-bold bg-white/10' : 'text-emerald-50/80 hover:bg-white/10 hover:text-white' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                                Maintenance
                            </a>
                            <a href="{{ route('vehicle-reports.index') }}" onclick="expandSidebarForVehicles()" class="flex items-center gap-2 rounded-md px-3 py-2 text-xs font-medium {{ request()->routeIs('vehicle-reports.*') ? 'text-white font-bold bg-white/10' : 'text-emerald-50/80 hover:bg-white/10 hover:text-white' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                Vehicle Reports
                            </a>
                        </div>
                    </div>

                    @php
                        $pendingTravelCount = 0;
                        try {
                            $userLoc = auth()->user()?->role === \Modules\Admin\Enums\UserRole::ADMIN ? auth()->user()->location : null;
                            $pendingTravelCount = \Modules\VehicleRequest\Models\VehicleRequest::where('status', 'pending')
                                ->when($userLoc, fn ($q) => $q->where('location', $userLoc))
                                ->count();
                        } catch (\Throwable $e) {
                            $pendingTravelCount = 0;
                        }
                    @endphp

                    <a
                        href="{{ route('vehicle-requests.index') }}"
                        title="Travel Requests"
                        class="sidebar-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('vehicle-requests.*') ? 'sidebar-active' : 'text-emerald-50/90 hover:bg-white/10 hover:text-white' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 3h6v3H9z"/>
                            <rect width="16" height="16" x="4" y="5" rx="2"/>
                            <path d="M8 11h8M8 15h5"/>
                        </svg>
                        <span class="sidebar-label flex flex-1 items-center justify-between min-w-0">
                            <span class="truncate">Travel Requests</span>
                            @if ($pendingTravelCount > 0)
                                <span class="ml-2 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-600 px-1.5 text-[11px] font-bold text-white shadow-xs">
                                    {{ $pendingTravelCount }}
                                </span>
                            @elseif(!$isSuperAdmin && !($moduleFlags['vehicleRequests'] ?? true))
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-300 ml-auto shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" title="Disabled by Super Admin">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            @endif
                        </span>
                    </a>

                    <a href="{{ route('approving-officers.index') }}" title="Approving Officers" class="sidebar-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('approving-officers.*') ? 'sidebar-active' : 'text-emerald-50/90 hover:bg-white/10 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/><path d="m16 18 2 2 4-4"/></svg>
                        <span class="sidebar-label">Approving Officers</span>
                    </a>

                    @if (in_array(auth()->user()?->role, [\Modules\Admin\Enums\UserRole::SUPER_ADMIN, \Modules\Admin\Enums\UserRole::ADMIN], true))
                        <a href="{{ route('content-settings') }}" title="Content Settings" class="sidebar-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('content-settings*') ? 'sidebar-active' : 'text-emerald-50/90 hover:bg-white/10 hover:text-white' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21v-7"/><path d="M4 10V3"/><path d="M12 21v-9"/><path d="M12 8V3"/><path d="M20 21v-5"/><path d="M20 12V3"/><path d="M2 14h4"/><path d="M10 8h4"/><path d="M18 16h4"/></svg>
                            <span class="sidebar-label">Content Settings</span>
                        </a>
                    @endif
                @endif
            </nav>

            {{-- Logged-in user + logout --}}
            <div class="border-t border-white/10 px-3 py-3 space-y-2">
                <a href="{{ route('my-profile') }}" title="My Profile" class="user-profile-link flex items-center gap-3 rounded-lg px-2 py-1.5 text-gray-300 hover:bg-white/5 hover:text-white transition">
                    @if ($hasPhoto)
                        <img
                            src="{{ $user->avatar_url }}"
                            alt="{{ $user->name }}"
                            class="h-8 w-8 shrink-0 rounded-full object-cover ring-2 ring-white/10"
                        >
                    @else
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white ring-1 ring-white/20">
                            {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($user?->name ?? 'A', 0, 1)) }}
                        </div>
                    @endif
                    <div class="sidebar-label-block min-w-0 leading-tight">
                        <p class="truncate text-xs font-bold text-white">{{ $user?->name }}</p>
                        <p class="truncate text-[11px] text-emerald-200/80">{{ $user?->role?->value === 'super_admin' ? 'Super Admin' : ($user?->role?->name ?? 'Admin') }}</p>
                    </div>
                </a>

                <div class="logout-btn-container">
                    <button
                        type="button"
                        onclick="document.getElementById('logout-modal').classList.remove('hidden'); document.getElementById('logout-modal').classList.add('flex')"
                        class="flex w-full items-center justify-center gap-2.5 rounded-lg bg-red-600 px-3 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-red-700"
                        title="Log out"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <path d="M16 17l5-5-5-5"/>
                            <path d="M21 12H9"/>
                        </svg>
                        <span class="sidebar-label">Logout</span>
                    </button>
                </div>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex flex-1 flex-col min-w-0 overflow-hidden">
            {{-- Topbar --}}
            <header class="relative flex min-h-[60px] items-center justify-between border-b border-emerald-800/30 bg-[#0f5d33] px-4 py-3 shadow-sm sm:border-gray-200 sm:bg-white sm:px-6">
                <div class="pointer-events-none absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 items-center gap-2.5">
                    <img src="{{ $appLogoUrl ?? asset('da-logo.png') }}" alt="Agency logo" class="h-8 w-8 rounded-full object-contain">
                    <span class="whitespace-nowrap text-lg font-bold tracking-tight text-white sm:hidden">FMS</span>
                    <span class="hidden whitespace-nowrap text-lg font-bold tracking-tight text-[#155f35] sm:inline">Fleet Management System</span>
                </div>

                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        onclick="toggleSidebar()"
                        class="shrink-0 rounded-md p-2 text-white hover:bg-white/10 sm:text-gray-500 sm:hover:bg-gray-100 sm:hover:text-gray-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 6h16"/>
                            <path d="M4 12h16"/>
                            <path d="M4 18h16"/>
                        </svg>
                    </button>
                </div>

                @if ($hasPhoto)
                    <img
                        src="{{ $user->avatar_url }}"
                        alt="{{ $user->name }}"
                        class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white/40 sm:ring-emerald-100"
                    >
                @else
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 ring-2 ring-white/40 sm:bg-gray-100 sm:ring-emerald-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white sm:text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"/>
                            <path d="M4 21a8 8 0 0 1 16 0 1 1 0 0 1-1 1H5a1 1 0 0 1-1-1Z"/>
                        </svg>
                    </div>
                @endif
            </header>

            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6">
                @if (session('success'))
                    <div id="flash-success" class="toast-animate-in fixed top-4 left-1/2 -translate-x-1/2 z-50 flex items-start gap-3 rounded-2xl border border-emerald-100 bg-white p-3.5 shadow-xl shadow-gray-900/10 min-w-[280px] max-w-md transition-all duration-300">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0 pt-0.5">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-800">SUCCESS</p>
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ session('success') }}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('flash-success').remove()" class="shrink-0 rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <script>
                        setTimeout(() => {
                            const el = document.getElementById('flash-success');
                            if (el) {
                                el.style.opacity = '0';
                                el.style.transform = 'translate(-50%, -20px)';
                                setTimeout(() => el.remove(), 300);
                            }
                        }, 5000);
                    </script>
                @endif

                @if (session('error'))
                    <div id="flash-error" class="toast-animate-in fixed top-4 left-1/2 -translate-x-1/2 z-50 flex items-start gap-3 rounded-2xl border border-red-100 bg-white p-3.5 shadow-xl shadow-gray-900/10 min-w-[280px] max-w-md transition-all duration-300">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0 pt-0.5">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-red-800">ERROR</p>
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ session('error') }}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('flash-error').remove()" class="shrink-0 rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <script>
                        setTimeout(() => {
                            const el = document.getElementById('flash-error');
                            if (el) {
                                el.style.opacity = '0';
                                el.style.transform = 'translate(-50%, -20px)';
                                setTimeout(() => el.remove(), 300);
                            }
                        }, 5000);
                    </script>
                @endif

                @if ($isModuleLocked)
                    <div class="mx-auto max-w-3xl py-12 px-4 text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-amber-50 text-amber-600 ring-8 ring-amber-50/50 shadow-sm border border-amber-200/60 mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100/80 px-3.5 py-1 text-xs font-extrabold text-amber-800 ring-1 ring-amber-200/80 mb-3 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Module Access Restricted
                        </span>

                        <h2 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">{{ $currentModuleName }} Disabled</h2>
                        
                        <p class="mt-3 text-sm text-slate-600 max-w-lg mx-auto leading-relaxed">
                            Access to this feature has been temporarily disabled by your <strong>Super Admin</strong>. Please refer to your Super Admin for more information.
                        </p>

                        <div class="mt-8 flex justify-center gap-3">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-extrabold text-white shadow-sm hover:bg-emerald-800 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                                </svg>
                                Return to Dashboard
                            </a>
                        </div>
                    </div>
                @else
                    @yield('content')
                @endif
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
        (function() {
            const sidebar = document.getElementById('sidebar');
            if (!sidebar) return;

            function applyResponsiveSidebar() {
                const w = window.innerWidth;
                if (w < 768) {
                    // Mobile: ensure off-canvas, remove collapsed class
                    sidebar.classList.remove('sidebar-collapsed');
                    if (!sidebar.classList.contains('-translate-x-full')) {
                        sidebar.classList.add('-translate-x-full');
                    }
                    document.getElementById('sidebar-overlay')?.classList.add('hidden');
                } else if (w < 1024) {
                    // Medium (md–lg): auto-collapse to icon rail
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('sidebar-collapsed');
                    document.getElementById('sidebar-overlay')?.classList.add('hidden');
                } else {
                    // Large (lg+): full sidebar open
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.remove('sidebar-collapsed');
                    document.getElementById('sidebar-overlay')?.classList.add('hidden');
                }
            }

            // Apply on initial load
            applyResponsiveSidebar();

            // Re-apply on resize
            window.addEventListener('resize', applyResponsiveSidebar);
        })();

        function expandSidebarForVehicles() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar && window.innerWidth >= 768) {
                // Expand from icon-rail to full sidebar
                sidebar.classList.remove('sidebar-collapsed');
            }
        }

        function toggleVehiclesMenu(event) {
            if (event) event.preventDefault();
            const sidebar = document.getElementById('sidebar');
            // On desktop, expand sidebar if it is collapsed so the submenu is visible
            if (sidebar && window.innerWidth >= 768 && sidebar.classList.contains('sidebar-collapsed')) {
                openSidebar();
            }
            const submenu = document.getElementById('vehicles-submenu');
            const chevron = document.getElementById('vehicles-chevron');
            if (submenu) {
                submenu.classList.toggle('hidden');
            }
            if (chevron) {
                chevron.classList.toggle('rotate-180');
            }
        }

        function openSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar) return;
            if (window.innerWidth >= 768) {
                // Desktop: remove collapsed icon-rail → show full sidebar
                sidebar.classList.remove('sidebar-collapsed');
            } else {
                // Mobile: slide in from off-canvas + show backdrop
                sidebar.classList.remove('-translate-x-full');
                if (overlay) overlay.classList.remove('hidden');
            }

            // Ensure active dropdowns (like Vehicles) are open when sidebar is expanded
            const isVehiclesActive = {{ $vehiclesOpen ? 'true' : 'false' }};
            if (isVehiclesActive) {
                const submenu = document.getElementById('vehicles-submenu');
                const chevron = document.getElementById('vehicles-chevron');
                submenu?.classList.remove('hidden');
                chevron?.classList.add('rotate-180');
            }
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar) return;
            if (window.innerWidth >= 768) {
                // Desktop: collapse to icon-rail (no overlay)
                sidebar.classList.add('sidebar-collapsed');
            } else {
                // Mobile: slide back off-canvas + hide backdrop
                sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (!sidebar) return;
            if (window.innerWidth >= 768) {
                // Desktop toggle: full ↔ icon-rail
                if (sidebar.classList.contains('sidebar-collapsed')) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            } else {
                // Mobile toggle: hidden ↔ visible overlay
                if (sidebar.classList.contains('-translate-x-full')) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>

