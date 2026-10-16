<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') · @endif Fleet Management System</title>
    @include('partials.text-size')

    <script>
        window.notificationBellData = function(allNotifIds) {
            var storedReadNotifs = [];
            try {
                storedReadNotifs = JSON.parse(localStorage.getItem('fleet_read_notifs') || '[]');
            } catch (e) {
                storedReadNotifs = [];
            }
            return {
                open: false,
                activeOpt: null,
                readNotifs: Array.isArray(storedReadNotifs) ? storedReadNotifs : [],
                allNotifIds: allNotifIds || [],
                isRead(id) {
                    return this.readNotifs.includes(id);
                },
                markRead(id) {
                    if (!this.readNotifs.includes(id)) {
                        this.readNotifs.push(id);
                        localStorage.setItem('fleet_read_notifs', JSON.stringify(this.readNotifs));
                    }
                },
                markUnread(id) {
                    this.readNotifs = this.readNotifs.filter(function(i) { return i !== id; });
                    localStorage.setItem('fleet_read_notifs', JSON.stringify(this.readNotifs));
                },
                toggleRead(id) {
                    if (this.isRead(id)) {
                        this.markUnread(id);
                    } else {
                        this.markRead(id);
                    }
                    this.activeOpt = null;
                },
                markAllAsRead() {
                    this.readNotifs = Array.from(new Set([...this.readNotifs, ...this.allNotifIds]));
                    localStorage.setItem('fleet_read_notifs', JSON.stringify(this.readNotifs));
                },
                get unreadCount() {
                    var self = this;
                    return this.allNotifIds.filter(function(id) { return !self.readNotifs.includes(id); }).length;
                },
                init() {
                    var self = this;
                    this.$nextTick(function() {
                        var container = document.getElementById('notif-list-container');
                        if (!container) return;
                        var cards = Array.from(container.children);
                        cards.sort(function(a, b) {
                            var aRead = self.isRead(a.dataset.notifId);
                            var bRead = self.isRead(b.dataset.notifId);
                            if (aRead !== bRead) return aRead ? 1 : -1;
                            return (parseInt(b.dataset.notifTime) || 0) - (parseInt(a.dataset.notifTime) || 0);
                        });
                        cards.forEach(function(c) { container.appendChild(c); });
                    });
                }
            };
        };
    </script>

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
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.08));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06), 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        #sidebar .sidebar-active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 22%;
            bottom: 22%;
            width: 3px;
            border-radius: 0 999px 999px 0;
            background: #f2a51a;
        }

        /*
         * Sidebar groups. The label is a plain heading in the group's color (--section): no icon, box or hover,
         * so it never reads as a button. The group's links sit indented beside a guide line in the same color.
         */
        #sidebar .sidebar-section-label {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin: 0 0 0.375rem;
            padding: 0 0.75rem;
            cursor: default;
            user-select: none;
        }
        #sidebar .sidebar-section-text {
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--section);
        }
        #sidebar .sidebar-section-rule {
            height: 1px;
            flex: 1;
            background: linear-gradient(90deg, color-mix(in srgb, var(--section) 40%, transparent), transparent);
        }
        #sidebar .sidebar-section-items {
            margin-left: 1.25rem;
            padding-left: 0.375rem;
            border-left: 1px solid color-mix(in srgb, var(--section) 30%, transparent);
        }
        /* Link icons carry a soft tint of their group's color */
        #sidebar .sidebar-section .sidebar-item:not(.sidebar-active) .sidebar-item-icon {
            color: color-mix(in srgb, var(--section) 78%, white);
        }
        #sidebar .sidebar-section .sidebar-item:not(.sidebar-active):hover .sidebar-item-icon {
            color: #fff;
        }

        #sidebar .sidebar-nav {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.18) transparent;
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
        /* On the rail a group becomes a short bar in its color; pending counts become a dot on the icon */
        #sidebar.sidebar-collapsed .sidebar-section-items {
            margin-left: 0;
            padding-left: 0;
            border-left: 0;
        }
        #sidebar.sidebar-collapsed .sidebar-section-label {
            height: 3px;
            width: 1.5rem;
            margin: 0.25rem auto 0.5rem;
            padding: 0;
            border-radius: 999px;
            background: color-mix(in srgb, var(--section) 70%, transparent);
        }
        #sidebar.sidebar-collapsed .sidebar-rail-dot {
            display: block;
        }
        /* Tighter group spacing on the rail so all icons fit on shorter tablet screens */
        #sidebar.sidebar-collapsed .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        #sidebar.sidebar-collapsed .sidebar-nav > * {
            margin-block: 0 !important;
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
                transform: translateY(-40px) scale(0.9);
            }
            60% {
                opacity: 1;
                transform: translateY(8px) scale(1.03);
            }
            85% {
                transform: translateY(-2px) scale(0.99);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .toast-animate-in {
            animation: toastSlideDown 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        /* Thin, subtle scrollbar for notification list */
        #notif-list-container {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #e2e8f0 transparent;
        }
        #notif-list-container::-webkit-scrollbar {
            width: 4px;
        }
        #notif-list-container::-webkit-scrollbar-track {
            background: transparent;
        }
        #notif-list-container::-webkit-scrollbar-thumb {
            background-color: #e2e8f0;
            border-radius: 99px;
        }
    </style>
</head>
<body class="antialiased bg-gray-50 text-gray-900">
    @php
        $user = auth()->user();
        $isSuperAdmin = $user?->role === \Modules\Admin\Enums\UserRole::SUPER_ADMIN;
        $hasPhoto = filled($user?->avatar_url ?? null);
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

        // Notifications initialization for topbar bell (available for all user roles)
        $pendingTravelCount = 0;
        $dueMaintenanceItems = collect();
        $ongoingDueItems = collect();
        $oilDueVehicles = collect();
        $allNotifIds = [];
        $allNotifications = [];

        if (in_array(auth()->user()?->role, [\Modules\Admin\Enums\UserRole::SUPER_ADMIN, \Modules\Admin\Enums\UserRole::ADMIN], true)) {
            try {
                $userLoc = auth()->user()?->role === \Modules\Admin\Enums\UserRole::ADMIN ? auth()->user()->location : null;
                $pendingTravelCount = \Modules\VehicleRequest\Models\VehicleRequest::where('status', 'pending')
                    ->when($userLoc, fn ($q) => $q->where('location', $userLoc))
                    ->count();
                if ($pendingTravelCount > 0) {
                    $allNotifIds[] = 'pending_travel';
                }

                // Scheduled maintenance starting today or past due -> prompt to set Ongoing
                $dueMaintenanceItems = \Modules\Maintenance\Models\VehicleMaintenance::query()
                    ->where('status', 'scheduled')
                    ->whereDate('scheduled_date', '<=', now()->toDateString())
                    ->when($userLoc, fn ($q) => $q->where('location', $userLoc))
                    ->latest('scheduled_date')
                    ->get();
                foreach ($dueMaintenanceItems as $mItem) {
                    $allNotifIds[] = 'maint_' . $mItem->id;
                }

                // Ongoing maintenance reaching past end date -> prompt to set Completed starting the day after end date
                $ongoingDueItems = \Modules\Maintenance\Models\VehicleMaintenance::query()
                    ->where('status', 'ongoing')
                    ->where(function ($q) {
                        $q->where(function ($sq) {
                            $sq->whereNotNull('scheduled_date_end')
                               ->whereDate('scheduled_date_end', '<', now()->toDateString());
                        })->orWhere(function ($sq) {
                            $sq->whereNull('scheduled_date_end')
                               ->whereDate('scheduled_date', '<', now()->toDateString());
                        });
                    })
                    ->when($userLoc, fn ($q) => $q->where('location', $userLoc))
                    ->latest('scheduled_date_end')
                    ->get();
                foreach ($ongoingDueItems as $ongItem) {
                    $allNotifIds[] = 'maint_ong_' . $ongItem->id;
                }

                $oilDueVehicles = \Modules\Vehicle\Models\Vehicle::query()
                    ->when($userLoc, fn ($q) => $q->where('location', $userLoc))
                    ->get()
                    ->filter(fn ($v) => in_array($v->change_oil_status['status'], ['overdue', 'due_soon']));
                foreach ($oilDueVehicles as $oVehicle) {
                    $allNotifIds[] = 'oil_' . $oVehicle->id;
                    $activeMaint = \Modules\Maintenance\Models\VehicleMaintenance::query()
                        ->where('vehicle_id', $oVehicle->id)
                        ->whereIn('status', ['scheduled', 'ongoing'])
                        ->latest('scheduled_date')
                        ->first();
                    $oVehicle->created_maintenance_id = $activeMaint?->id;
                }
            } catch (\Throwable $e) {
                $pendingTravelCount = 0;
                $dueMaintenanceItems = collect();
                $ongoingDueItems = collect();
                $oilDueVehicles = collect();
                $allNotifIds = [];
            }

            if (($pendingTravelCount ?? 0) > 0) {
                $allNotifications[] = ['type' => 'pending_travel', 'time' => now(), 'ago' => 'now'];
            }
            foreach ($dueMaintenanceItems as $mItem) {
                $t = $mItem->scheduled_date ? \Carbon\Carbon::parse($mItem->scheduled_date) : now();
                $allNotifications[] = ['type' => 'maint', 'item' => $mItem, 'time' => $t, 'ago' => $t->diffForHumans(['short' => true])];
            }
            foreach ($ongoingDueItems as $ongItem) {
                $t = $ongItem->scheduled_date_end ? \Carbon\Carbon::parse($ongItem->scheduled_date_end) : ($ongItem->scheduled_date ? \Carbon\Carbon::parse($ongItem->scheduled_date) : now());
                $allNotifications[] = ['type' => 'maint_ong', 'item' => $ongItem, 'time' => $t, 'ago' => $t->diffForHumans(['short' => true])];
            }
            foreach ($oilDueVehicles as $oVehicle) {
                $t = $oVehicle->updated_at ?? now();
                $allNotifications[] = ['type' => 'oil', 'item' => $oVehicle, 'time' => $t, 'ago' => $t->diffForHumans(['short' => true])];
            }
            usort($allNotifications, fn($a, $b) => $b['time']->timestamp - $a['time']->timestamp);
        }
        $totalNotifCount = count($allNotifIds);
    @endphp

    <div class="flex h-screen supports-[height:100dvh]:h-dvh overflow-hidden">
        {{-- Sidebar overlay (for expanded drawer mode) --}}
        <div
            id="sidebar-overlay"
            onclick="closeSidebar()"
            class="fixed inset-0 z-40 hidden bg-black/40 transition-opacity duration-200"
        ></div>

        {{-- Sidebar — static part of layout on desktop (starts open), off-canvas on mobile --}}
        <aside
            id="sidebar"
            class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-[#004a2c] bg-[linear-gradient(180deg,#005a36_0%,#004a2c_55%,#003b23_100%)] shadow-xl shadow-black/10 transition-all duration-200 ease-in-out md:static md:w-64 md:translate-x-0 md:shrink-0 md:shadow-none"
        >
            <div class="flex items-center gap-3 border-b border-white/10 px-4 py-4">
                <img src="{{ $appLogoUrl ?? asset('da-logo.png') }}" alt="Agency logo" class="h-9 w-9 shrink-0 rounded-full bg-white object-contain p-0.5 shadow ring-2 ring-white/15">
                <div class="sidebar-label-block min-w-0 leading-tight">
                    <p class="truncate text-[0.9375rem] font-extrabold tracking-tight text-white">Fleet Management</p>
                    <p class="truncate text-[0.65625rem] font-semibold uppercase tracking-[0.12em] text-emerald-200/60">{{ config('app.name') }}</p>
                </div>

                <button
                    id="sidebar-close-btn"
                    type="button"
                    onclick="closeSidebar()"
                    class="ml-auto rounded-md p-1.5 text-emerald-200 hover:bg-white/10 hover:text-white transition md:hidden"
                    title="Close sidebar"
                    aria-label="Close navigation"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            @php
                $isFleetAdmin = in_array($user?->role, [\Modules\Admin\Enums\UserRole::SUPER_ADMIN, \Modules\Admin\Enums\UserRole::ADMIN], true);
                $locked = fn (string $module) => ! $isSuperAdmin && ! ($moduleFlags[$module] ?? true);
            @endphp

            {{-- Grouped sections instead of a dropdown, so every page is one click away and the list stays short --}}
            <nav class="sidebar-nav flex-1 space-y-4 overflow-y-auto px-3 py-4" aria-label="Main navigation">
                <div class="space-y-0.5">
                    <x-sidebar-link :href="route('dashboard')" label="Dashboard" :active="request()->routeIs('dashboard')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    </x-sidebar-link>
                </div>

                @if ($isFleetAdmin)
                    <x-sidebar-section id="nav-operations" label="Operations" color="#7dd3fc">
                        <x-sidebar-link :href="route('vehicle-requests.index')" label="Travel Requests" :active="request()->routeIs('vehicle-requests.*')" :badge="$pendingTravelCount > 0 ? $pendingTravelCount : null" :locked="$locked('vehicleRequests')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6v3H9z"/><rect width="16" height="16" x="4" y="5" rx="2"/><path d="M8 11h8M8 15h5"/></svg>
                        </x-sidebar-link>
                        @can('manage-drivers')
                            <x-sidebar-link :href="route('driver.index')" label="Drivers" :active="request()->routeIs('driver.*')" :locked="$locked('drivers')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </x-sidebar-link>
                        @endcan
                        <x-sidebar-link :href="route('vehicle-assigning.index')" label="Vehicle Assigning" :active="request()->routeIs('vehicle-assigning.*')" :locked="$locked('vehicleAssigning')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>
                        </x-sidebar-link>
                    </x-sidebar-section>

                    <x-sidebar-section id="nav-vehicles" label="Vehicles" color="#fdba74">
                        <x-sidebar-link :href="route('vehicles.index')" label="Vehicles List" :active="request()->routeIs('vehicles.*') && ! request()->routeIs('vehicles.inspections.*')" :locked="$locked('vehicles')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('vehicles.inspections.page')" label="Vehicle Inspections" :active="request()->routeIs('vehicles.inspections.*')" :locked="$locked('vehicles')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('maintenance.index')" label="Maintenance" :active="request()->routeIs('maintenance.*')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('vehicle-reports.index')" label="Vehicle Reports" :active="request()->routeIs('vehicle-reports.*')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('fleet-cards.index')" label="Fleet Cards" :active="request()->routeIs('fleet-cards.*')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        </x-sidebar-link>
                    </x-sidebar-section>

                    <x-sidebar-section id="nav-administration" label="Administration" color="#c4b5fd">
                        <x-sidebar-link :href="route('reports.index')" label="Reports" :active="request()->routeIs('reports.*')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 16v-5"/><path d="M12 16V8"/><path d="M17 16v-8"/></svg>
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('destinations.index')" label="Destinations" :active="request()->routeIs('destinations.*')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('approving-officers.index')" label="Approving Officers" :active="request()->routeIs('approving-officers.*')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/><path d="m16 18 2 2 4-4"/></svg>
                        </x-sidebar-link>
                        @can('viewAny', App\Models\User::class)
                            <x-sidebar-link :href="route('admin.index')" label="Admins" :active="request()->routeIs('admin.*')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </x-sidebar-link>
                        @endcan
                    </x-sidebar-section>
                @endif

                @if ($user?->role === \Modules\Admin\Enums\UserRole::DRIVER)
                    <x-sidebar-section id="nav-my-work" label="My Work" color="#7dd3fc">
                        <x-sidebar-link :href="route('travel-history')" label="Travel History" :active="request()->routeIs('travel-history')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s-8-6-8-12a8 8 0 0 1 16 0c0 6-8 12-8 12z"/><path d="M12 7v3l2 2"/></svg>
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('vehicle-reports.index')" label="Report Vehicle Issue" :active="request()->routeIs('vehicle-reports.*')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </x-sidebar-link>
                    </x-sidebar-section>
                @endif
            </nav>

            {{-- Signed-in user, display only. My Profile and Log out are in the account menu at the top right --}}
            <div class="border-t border-white/10 p-3">
                <div title="{{ $user?->name }}" class="user-profile-link flex cursor-default select-none items-center gap-3 rounded-xl px-2 py-2">
                    @if ($hasPhoto)
                        <img src="{{ $user->avatar_url }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white/15">
                    @else
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-[0.8125rem] font-bold text-white ring-2 ring-white/15" aria-hidden="true">
                            {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($user?->name ?? 'A', 0, 1)) }}
                        </span>
                    @endif
                    <span class="sidebar-label-block min-w-0 flex-1 leading-tight">
                        <span class="block truncate text-[0.8125rem] font-semibold text-white">{{ $user?->name }}</span>
                        <span class="block truncate text-[0.6875rem] text-emerald-200/70">{{ $user?->role?->label() }}{{ filled($user?->location) ? ' · '.$user->location : '' }}</span>
                    </span>
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
                        id="sidebar-toggle-btn"
                        onclick="toggleSidebar()"
                        aria-label="Toggle navigation"
                        aria-controls="sidebar"
                        aria-expanded="false"
                        class="shrink-0 rounded-md p-2 text-white hover:bg-white/10 sm:text-gray-500 sm:hover:bg-gray-100 sm:hover:text-gray-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 6h16"/>
                            <path d="M4 12h16"/>
                            <path d="M4 18h16"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    {{-- Notification Bell Dropdown --}}
                    <div class="relative" x-data="notificationBellData({{ json_encode($allNotifIds ?? []) }})">
                        <button
                            type="button"
                            @click="open = !open"
                            class="relative rounded-full p-2 text-white hover:bg-white/10 sm:text-slate-500 sm:hover:bg-slate-100 sm:hover:text-slate-700 transition cursor-pointer"
                            title="Notifications"
                            aria-label="Notifications"
                            :aria-expanded="open.toString()"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                            </svg>
                            {{-- Notification badge dot (if unread notifications exist) --}}
                            <span x-show="unreadCount > 0" x-cloak class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                            </span>
                        </button>

                        {{-- Dropdown Menu --}}
                        <div
                            x-show="open"
                            x-cloak
                            @click.outside="open = false; activeOpt = null"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="fixed left-2 right-2 top-16 z-50 rounded-2xl bg-white p-4 shadow-2xl border border-slate-100
                                   sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-96"
                            style="display: none;"
                        >
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">Notifications</h4>
                                <div class="flex items-center gap-2">
                                    <button type="button" 
                                            x-show="unreadCount > 0" 
                                            @click="markAllAsRead()" 
                                            class="text-[0.625rem] font-semibold text-emerald-600 hover:text-emerald-800 hover:underline">
                                        Mark all read
                                    </button>
                                    <span x-show="unreadCount > 0" class="rounded-full bg-red-50 px-2 py-0.5 text-[0.625rem] font-bold text-red-600 border border-red-100">
                                        <span x-text="unreadCount"></span> New
                                    </span>
                                    <span x-show="unreadCount === 0" class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.625rem] font-medium text-slate-500">
                                        All read
                                    </span>
                                </div>
                            </div>

                            <div id="notif-list-container" class="space-y-2 max-h-[60vh] sm:max-h-80 overflow-y-auto pr-0.5">
                                @foreach ($allNotifications as $notif)
                                    @php
                                        $notifType = $notif['type'];
                                        $notifAgo  = $notif['ago'];
                                        $notifTs   = $notif['time']->timestamp;
                                    @endphp

                                    {{-- ── pending_travel card ─────────────────────────────────────── --}}
                                    @if ($notifType === 'pending_travel')
                                        <div class="relative group flex items-center justify-between gap-2 rounded-xl p-2 transition border"
                                             data-notif-id="pending_travel"
                                             data-notif-time="{{ $notifTs }}"
                                             :class="isRead('pending_travel') ? 'bg-slate-50/50 border-slate-100 text-slate-400' : 'bg-amber-50/20 border-amber-100/50 text-slate-800'">
                                            <a href="{{ route('vehicle-requests.index') }}" @click="markRead('pending_travel')" class="flex items-start gap-2.5 flex-1 min-w-0">
                                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 border border-amber-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 9v4M12 17h.01"/>
                                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-xs font-bold" :class="isRead('pending_travel') ? 'text-slate-500' : 'text-slate-800'">Pending Travel Requests</p>
                                                    <p class="text-[0.6875rem] text-slate-500">You have {{ $pendingTravelCount }} trip request(s) awaiting approval.</p>
                                                    <p class="text-[0.625rem] text-slate-400 mt-0.5">{{ $notifAgo }}</p>
                                                </div>
                                                <span x-show="!isRead('pending_travel')" class="h-2 w-2 rounded-full bg-amber-500 shrink-0 mt-1"></span>
                                            </a>
                                            <div class="relative shrink-0 ml-1" @click.outside="if (activeOpt === 'pending_travel') activeOpt = null">
                                                <button type="button" @click.stop.prevent="activeOpt = (activeOpt === 'pending_travel' ? null : 'pending_travel')" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition cursor-pointer" title="Options">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                                                </button>
                                                <div x-show="activeOpt === 'pending_travel'" x-cloak x-transition class="absolute right-6 top-1/2 -translate-y-1/2 z-50 rounded-xl bg-white p-1 shadow-xl border border-slate-200 text-left whitespace-nowrap" style="display: none;">
                                                    <button type="button" @click.stop.prevent="toggleRead('pending_travel')" class="w-full text-left px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-lg flex items-center gap-2 cursor-pointer">
                                                        <span x-show="isRead('pending_travel')" class="flex items-center gap-1.5 text-amber-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Mark Unread</span><span x-show="!isRead('pending_travel')" class="flex items-center gap-1.5 text-emerald-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Mark Read</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                    {{-- ── maint (scheduled due) card ──────────────────────────────── --}}
                                    @elseif ($notifType === 'maint')
                                        @php
                                            $mItem = $notif['item'];
                                            $mId = 'maint_' . $mItem->id;
                                            $mIsToday = $mItem->scheduled_date && \Carbon\Carbon::parse($mItem->scheduled_date)->isToday();
                                        @endphp
                                        <div class="relative group flex items-center justify-between gap-2 rounded-xl p-2 transition border"
                                             data-notif-id="{{ $mId }}"
                                             data-notif-time="{{ $notifTs }}"
                                             :class="isRead('{{ $mId }}') ? 'bg-slate-50/50 border-slate-100 text-slate-400' : 'bg-blue-50/20 border-l-4 border-l-red-500 border-blue-200/80 shadow-2xs text-slate-800'">
                                            <a href="{{ route('maintenance.index', array_filter(['highlight_id' => $mItem->id, 'auto_edit' => $mIsToday ? 1 : null])) }}" @click="markRead('{{ $mId }}')" class="flex items-start gap-2.5 flex-1 min-w-0">
                                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600 border border-blue-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between gap-1">
                                                        <p class="text-xs font-bold truncate" :class="isRead('{{ $mId }}') ? 'text-slate-500' : 'text-slate-800'">{{ $mIsToday ? 'Maintenance Due Today' : 'Maintenance Scheduled' }}</p>
                                                        <span class="text-[0.5625rem] font-semibold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded shrink-0">{{ $mIsToday ? 'Set Ongoing' : 'Scheduled' }}</span>
                                                    </div>
                                                    <p class="text-[0.6875rem] font-semibold mt-0.5" :class="isRead('{{ $mId }}') ? 'text-slate-400' : 'text-slate-700'">{{ $mItem->vehicle_label ?: $mItem->plate_number }} — {{ $mItem->title }}</p>
                                                    <p class="text-[0.625rem] text-slate-400">Scheduled: {{ $mItem->scheduled_date?->format('M d, Y') }}</p>
                                                    <p class="text-[0.625rem] text-slate-400 mt-0.5">{{ $notifAgo }}</p>
                                                </div>
                                                <span x-show="!isRead('{{ $mId }}')" class="h-2 w-2 rounded-full bg-red-500 shrink-0 mt-1"></span>
                                            </a>
                                            <div class="relative shrink-0 ml-1" @click.outside="if (activeOpt === '{{ $mId }}') activeOpt = null">
                                                <button type="button" @click.stop.prevent="activeOpt = (activeOpt === '{{ $mId }}' ? null : '{{ $mId }}')" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition cursor-pointer" title="Options">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                                                </button>
                                                <div x-show="activeOpt === '{{ $mId }}'" x-cloak x-transition class="absolute right-6 top-1/2 -translate-y-1/2 z-50 rounded-xl bg-white p-1 shadow-xl border border-slate-200 text-left whitespace-nowrap" style="display: none;">
                                                    <button type="button" @click.stop.prevent="toggleRead('{{ $mId }}')" class="w-full text-left px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-lg flex items-center gap-2 cursor-pointer">
                                                        <span x-show="isRead('{{ $mId }}')" class="flex items-center gap-1.5 text-amber-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Mark Unread</span><span x-show="!isRead('{{ $mId }}')" class="flex items-center gap-1.5 text-emerald-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Mark Read</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                    {{-- ── maint_ong (ongoing due) card ────────────────────────────── --}}
                                    @elseif ($notifType === 'maint_ong')
                                        @php
                                            $ongItem = $notif['item'];
                                            $ongId = 'maint_ong_' . $ongItem->id;
                                            $ongIsToday = $ongItem->scheduled_date_end && \Carbon\Carbon::parse($ongItem->scheduled_date_end)->isToday();
                                        @endphp
                                        <div class="relative group flex items-center justify-between gap-2 rounded-xl p-2 transition border"
                                             data-notif-id="{{ $ongId }}"
                                             data-notif-time="{{ $notifTs }}"
                                             :class="isRead('{{ $ongId }}') ? 'bg-slate-50/50 border-slate-100 text-slate-400' : 'bg-emerald-50/20 border-l-4 border-l-red-500 border-emerald-200/80 shadow-2xs text-slate-800'">
                                            <a href="{{ route('maintenance.index', ['highlight_id' => $ongItem->id, 'auto_edit' => 1]) }}" @click="markRead('{{ $ongId }}')" class="flex items-start gap-2.5 flex-1 min-w-0">
                                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between gap-1">
                                                        <p class="text-xs font-bold truncate" :class="isRead('{{ $ongId }}') ? 'text-slate-500' : 'text-slate-800'">Maintenance Due for Completion</p>
                                                        <span class="text-[0.5625rem] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 shrink-0">Set Completed</span>
                                                    </div>
                                                    <p class="text-[0.6875rem] font-semibold mt-0.5" :class="isRead('{{ $ongId }}') ? 'text-slate-400' : 'text-slate-700'">{{ $ongItem->vehicle_label ?: $ongItem->plate_number }} — {{ $ongItem->title }}</p>
                                                    <p class="text-[0.625rem] text-slate-400">Scheduled End: {{ $ongItem->scheduled_date_end?->format('M d, Y') ?: $ongItem->scheduled_date?->format('M d, Y') }}</p>
                                                    <p class="text-[0.625rem] text-slate-400 mt-0.5">{{ $notifAgo }}</p>
                                                </div>
                                                <span x-show="!isRead('{{ $ongId }}')" class="h-2 w-2 rounded-full bg-red-500 shrink-0 mt-1"></span>
                                            </a>
                                            <div class="relative shrink-0 ml-1" @click.outside="if (activeOpt === '{{ $ongId }}') activeOpt = null">
                                                <button type="button" @click.stop.prevent="activeOpt = (activeOpt === '{{ $ongId }}' ? null : '{{ $ongId }}')" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition cursor-pointer" title="Options">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                                                </button>
                                                <div x-show="activeOpt === '{{ $ongId }}'" x-cloak x-transition class="absolute right-6 top-1/2 -translate-y-1/2 z-50 rounded-xl bg-white p-1 shadow-xl border border-slate-200 text-left whitespace-nowrap" style="display: none;">
                                                    <button type="button" @click.stop.prevent="toggleRead('{{ $ongId }}')" class="w-full text-left px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-lg flex items-center gap-2 cursor-pointer">
                                                        <span x-show="isRead('{{ $ongId }}')" class="flex items-center gap-1.5 text-amber-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Mark Unread</span><span x-show="!isRead('{{ $ongId }}')" class="flex items-center gap-1.5 text-emerald-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Mark Read</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                    {{-- ── oil card ────────────────────────────────────────────────── --}}
                                    @elseif ($notifType === 'oil')
                                        @php 
                                            $oVehicle = $notif['item'];
                                            $oState = $oVehicle->change_oil_status; 
                                            $oId = 'oil_' . $oVehicle->id;
                                        @endphp
                                        <div class="relative group flex items-center justify-between gap-2 rounded-xl p-2 transition border"
                                             data-notif-id="{{ $oId }}"
                                             data-notif-time="{{ $notifTs }}"
                                             :class="isRead('{{ $oId }}') ? 'bg-slate-50/50 border-slate-100 text-slate-400' : 'bg-amber-50/20 border-l-4 border-l-red-500 border-amber-200/80 shadow-2xs text-slate-800'">
                                            @if (!empty($oVehicle->created_maintenance_id))
                                                <a href="{{ route('maintenance.index', ['highlight_id' => $oVehicle->created_maintenance_id]) }}" @click="markRead('{{ $oId }}')" class="flex items-start gap-2.5 flex-1 min-w-0">
                                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center justify-between gap-1">
                                                            <p class="text-xs font-bold truncate" :class="isRead('{{ $oId }}') ? 'text-slate-500' : 'text-slate-800'">{{ $oVehicle->plate_number }} ({{ $oVehicle->brand }} {{ $oVehicle->model }})</p>
                                                            <span class="text-[0.5625rem] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 shrink-0">Scheduled</span>
                                                        </div>
                                                        <p class="text-[0.6875rem] font-semibold text-emerald-700 mt-0.5">Maintenance Created — View in table</p>
                                                        <p class="text-[0.625rem] text-slate-400">Current: {{ number_format($oVehicle->odometer) }} km | Last Oil: {{ number_format($oState['last_km']) }} km</p>
                                                        <p class="text-[0.625rem] text-slate-400 mt-0.5">{{ $notifAgo }}</p>
                                                    </div>
                                                    <span x-show="!isRead('{{ $oId }}')" class="h-2 w-2 rounded-full bg-emerald-500 shrink-0 mt-1"></span>
                                                </a>
                                            @else
                                                <a href="{{ route('maintenance.index', ['schedule_vehicle_id' => $oVehicle->id, 'type' => 'Oil Change']) }}" @click="markRead('{{ $oId }}')" class="flex items-start gap-2.5 flex-1 min-w-0">
                                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 border border-amber-100">
                                                        <span class="text-xs">🛢️</span>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center justify-between gap-1">
                                                            <p class="text-xs font-bold truncate" :class="isRead('{{ $oId }}') ? 'text-slate-500' : 'text-slate-800'">{{ $oVehicle->plate_number }} ({{ $oVehicle->brand }} {{ $oVehicle->model }})</p>
                                                            <span class="text-[0.5625rem] font-bold {{ $oState['status'] === 'overdue' ? 'text-red-600 bg-red-50' : 'text-amber-700 bg-amber-50' }} px-1.5 py-0.5 rounded border shrink-0">{{ $oState['status'] === 'overdue' ? 'Overdue' : 'Due Soon' }}</span>
                                                        </div>
                                                        <p class="text-[0.6875rem] mt-0.5" :class="isRead('{{ $oId }}') ? 'text-slate-400' : 'text-slate-600'">{{ $oState['label'] }}</p>
                                                        <p class="text-[0.625rem] text-slate-400">Current: {{ number_format($oVehicle->odometer) }} km | Last Oil: {{ number_format($oState['last_km']) }} km</p>
                                                        <p class="text-[0.625rem] text-slate-400 mt-0.5">{{ $notifAgo }}</p>
                                                    </div>
                                                    <span x-show="!isRead('{{ $oId }}')" class="h-2 w-2 rounded-full bg-amber-500 shrink-0 mt-1"></span>
                                                </a>
                                            @endif
                                            <div class="relative shrink-0 ml-1" @click.outside="if (activeOpt === '{{ $oId }}') activeOpt = null">
                                                <button type="button" @click.stop.prevent="activeOpt = (activeOpt === '{{ $oId }}' ? null : '{{ $oId }}')" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition cursor-pointer" title="Options">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                                                </button>
                                                <div x-show="activeOpt === '{{ $oId }}'" x-cloak x-transition class="absolute right-6 top-1/2 -translate-y-1/2 z-50 rounded-xl bg-white p-1 shadow-xl border border-slate-200 text-left whitespace-nowrap" style="display: none;">
                                                    <button type="button" @click.stop.prevent="toggleRead('{{ $oId }}')" class="w-full text-left px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-lg flex items-center gap-2 cursor-pointer">
                                                        <span x-show="isRead('{{ $oId }}')" class="flex items-center gap-1.5 text-amber-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Mark Unread</span><span x-show="!isRead('{{ $oId }}')" class="flex items-center gap-1.5 text-emerald-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Mark Read</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach

                                @if (($totalNotifCount ?? 0) === 0)
                                    <div class="py-6 text-center text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-7 w-7 text-slate-300 mb-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                                        </svg>
                                        <p class="text-xs font-semibold text-slate-600">No new notifications</p>
                                        <p class="text-[0.6875rem] text-slate-400">You're all caught up!</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Profile Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button
                            type="button"
                            @click="open = !open"
                            class="shrink-0 rounded-full focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
                            title="{{ $user?->name }}"
                            aria-label="Account menu for {{ $user?->name }}"
                            :aria-expanded="open.toString()"
                        >
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
                        </button>

                        {{-- Dropdown menu --}}
                        <div
                            x-show="open"
                            x-cloak
                            @click.outside="open = false"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 top-full z-50 mt-2 w-60 rounded-2xl border border-slate-100 bg-white p-2 shadow-2xl"
                            style="display: none;"
                        >
                            {{-- User info header: name and sign-in email (role and province are shown in the sidebar) --}}
                            <div class="mb-1 px-3 py-2 border-b border-slate-100">
                                <p class="text-xs font-bold text-slate-800 truncate">{{ $user?->name }}</p>
                                <p class="text-[0.6875rem] text-slate-500 truncate" title="{{ $user?->email }}">{{ $user?->email }}</p>
                            </div>

                            <a
                                href="{{ route('my-profile') }}"
                                @click="open = false"
                                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition {{ request()->routeIs('my-profile') ? 'bg-emerald-50 text-emerald-800' : '' }}"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                                My Profile
                            </a>

                            {{-- One settings entry per role: System Settings (which includes Display & Text Size) for
                                 Admins and Super Admins; drivers, who cannot open System Settings, get the text size page --}}
                            @if (auth()->user()?->role !== \Modules\Admin\Enums\UserRole::DRIVER)
                                <a
                                    href="{{ route('content-settings') }}"
                                    @click="open = false"
                                    class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition {{ request()->routeIs('content-settings*') ? 'bg-emerald-50 text-emerald-800' : '' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21v-7"/><path d="M4 10V3"/><path d="M12 21v-9"/><path d="M12 8V3"/><path d="M20 21v-5"/><path d="M20 12V3"/><path d="M2 14h4"/><path d="M10 8h4"/><path d="M18 16h4"/></svg>
                                    System Settings
                                </a>
                            @else
                                <a
                                    href="{{ route('settings') }}"
                                    @click="open = false"
                                    class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition {{ request()->routeIs('settings') ? 'bg-emerald-50 text-emerald-800' : '' }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
                                    Display &amp; Text Size
                                </a>
                            @endif

                            {{-- The only Log out entry: the sidebar shows the signed-in user but no logout button --}}
                            <button
                                type="button"
                                @click="open = false"
                                onclick="openLogoutModal(this)"
                                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-xs font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                                Log out
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6">
                @if (session('success'))
                    <div id="flash-success" role="status" aria-live="polite" class="toast-animate-in fixed top-4 left-1/2 -translate-x-1/2 md:left-[calc(50%+2.25rem)] lg:left-[calc(50%+8rem)] z-[9999] flex items-start gap-3 rounded-2xl border border-emerald-100 bg-white p-3.5 shadow-2xl shadow-gray-900/15 w-[90vw] max-w-md transition-all duration-300">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0 pt-0.5">
                            <p class="text-[0.6875rem] font-extrabold uppercase tracking-wider text-emerald-800">SUCCESS</p>
                            <p class="text-xs font-semibold text-gray-800 break-words">{{ session('success') }}</p>
                        </div>
                        <button type="button" aria-label="Dismiss message" onclick="(function(){ var el=document.getElementById('flash-success'); if(!el) return; el.style.opacity='0'; el.style.transform='translateY(-20px)'; setTimeout(function(){ el.remove(); }, 300); })()" class="shrink-0 rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <script>
                        setTimeout(function() {
                            var el = document.getElementById('flash-success');
                            if (!el) return;
                            el.style.opacity = '0';
                            el.style.transform = 'translateY(-20px)';
                            setTimeout(function() { el.remove(); }, 300);
                        }, 5000);
                    </script>
                @endif

                @if (session('error'))
                    <div id="flash-error" role="alert" class="toast-animate-in fixed top-4 left-1/2 -translate-x-1/2 md:left-[calc(50%+2.25rem)] lg:left-[calc(50%+8rem)] z-[9999] flex items-start gap-3 rounded-2xl border border-red-100 bg-white p-3.5 shadow-2xl shadow-gray-900/15 w-[90vw] max-w-md transition-all duration-300">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0 pt-0.5">
                            <p class="text-[0.6875rem] font-extrabold uppercase tracking-wider text-red-800">ERROR</p>
                            <p class="text-xs font-semibold text-gray-800 break-words">{{ session('error') }}</p>
                        </div>
                        <button type="button" aria-label="Dismiss message" onclick="(function(){ var el=document.getElementById('flash-error'); if(!el) return; el.style.opacity='0'; el.style.transform='translateY(-20px)'; setTimeout(function(){ el.remove(); }, 300); })()" class="shrink-0 rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <script>
                        setTimeout(function() {
                            var el = document.getElementById('flash-error');
                            if (!el) return;
                            el.style.opacity = '0';
                            el.style.transform = 'translateY(-20px)';
                            setTimeout(function() { el.remove(); }, 300);
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
    <div id="logout-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4" onclick="if (event.target === this) closeLogoutModal()">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="logout-modal-title" aria-describedby="logout-modal-desc">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <path d="M16 17l5-5-5-5"/>
                    <path d="M21 12H9"/>
                </svg>
            </div>

            <h2 id="logout-modal-title" class="mt-4 text-lg font-semibold text-gray-900">Log out?</h2>
            <p id="logout-modal-desc" class="mt-1 text-sm text-gray-500">You'll need to log in again to access your dashboard.</p>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    id="logout-cancel-btn"
                    onclick="closeLogoutModal()"
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

            let lastBreakpoint = null;

            function applyResponsiveSidebar() {
                const w = window.innerWidth;
                const breakpoint = w < 768 ? 'mobile' : (w < 1024 ? 'medium' : 'large');
                // Only reset on breakpoint changes so the user's own toggle survives minor resizes
                if (breakpoint === lastBreakpoint) return;
                lastBreakpoint = breakpoint;

                if (breakpoint === 'mobile') {
                    // Mobile: ensure off-canvas, remove collapsed class
                    sidebar.classList.remove('sidebar-collapsed');
                    if (!sidebar.classList.contains('-translate-x-full')) {
                        sidebar.classList.add('-translate-x-full');
                    }
                    document.getElementById('sidebar-overlay')?.classList.add('hidden');
                } else if (breakpoint === 'medium') {
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
                syncSidebarA11y();
            }

            // Apply on initial load
            applyResponsiveSidebar();

            // Re-apply on resize
            window.addEventListener('resize', applyResponsiveSidebar);

            // Escape closes the mobile drawer and the logout dialog
            document.addEventListener('keydown', function(event) {
                if (event.key !== 'Escape') return;
                const logoutModal = document.getElementById('logout-modal');
                if (logoutModal && !logoutModal.classList.contains('hidden')) {
                    closeLogoutModal();
                    return;
                }
                if (window.innerWidth < 768 && !sidebar.classList.contains('-translate-x-full')) {
                    closeSidebar();
                    document.getElementById('sidebar-toggle-btn')?.focus();
                }
            });
        })();

        function syncSidebarA11y() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebar-toggle-btn');
            if (!sidebar) return;
            const isMobile = window.innerWidth < 768;
            const expanded = isMobile
                ? !sidebar.classList.contains('-translate-x-full')
                : !sidebar.classList.contains('sidebar-collapsed');
            toggleBtn?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            // Keep the off-canvas drawer out of the tab order while it is closed on mobile
            sidebar.inert = isMobile && !expanded;
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

            syncSidebarA11y();
            if (window.innerWidth < 768) {
                // Move keyboard focus into the drawer so it can be navigated immediately
                document.getElementById('sidebar-close-btn')?.focus();
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
            syncSidebarA11y();
        }

        let logoutModalTrigger = null;

        function openLogoutModal(trigger) {
            const modal = document.getElementById('logout-modal');
            if (!modal) return;
            logoutModalTrigger = trigger || document.activeElement;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('logout-cancel-btn')?.focus();
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logout-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            logoutModalTrigger?.focus?.();
            logoutModalTrigger = null;
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

        function dismissGlobalToast(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.style.opacity = '0';
            el.style.transform = 'translateY(-20px)';
            setTimeout(() => el.remove(), 300);
        }
    </script>

    @stack('scripts')
</body>
</html>

