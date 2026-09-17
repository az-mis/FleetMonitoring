@extends('layouts.app')

@section('title', 'Content Settings')
@section('page-title', 'Content Settings')

@section('content')
    {{-- Top Premium Banner --}}
    <div class="mb-6 rounded-2xl bg-gradient-to-br from-[#155f35] via-[#1d8148] to-[#269d59] p-5 text-white shadow-xl shadow-emerald-900/15">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-xl font-black tracking-tight text-white">
                        {{ $isSuperAdmin ? 'System Content & Branding Settings' : 'Content Settings' }}
                    </h2>
                    <p class="text-xs font-medium text-emerald-100/90">
                        {{ $isSuperAdmin ? 'Super Admin Portal — Manage application logo, navigation visibility, and targeted system announcements.' : 'Control what content is shown to Drivers on their Dashboard.' }}
                    </p>
                </div>
            </div>
            @if($isSuperAdmin)
                <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-emerald-100 ring-1 ring-white/20 backdrop-blur-xs">
                    <span class="h-2 w-2 rounded-full bg-emerald-300 animate-pulse"></span>
                    Super Admin Mode
                </span>
            @else
                <span class="hidden sm:inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-emerald-100 ring-1 ring-white/20 backdrop-blur-xs">
                    <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                    Admin Mode
                </span>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        @if($isSuperAdmin)
            {{-- Row 1: App Logo & Admin Modules --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- App Logo Card --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                    <circle cx="9" cy="9" r="2"/>
                                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">App Logo</h3>
                                <p class="text-[11px] text-slate-400">Logo shown across sidebar, top bar, and public screens.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-5">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-amber-50/80 p-2 border border-amber-200/80 shadow-xs">
                            <img id="logoPreview" src="{{ $customLogoUrl ?? asset('da-logo.png') }}" alt="Current logo" class="h-full w-full object-contain">
                        </div>
                        <div class="space-y-2">
                            <div>
                                <p class="text-xs font-extrabold text-slate-800">System Brand Identifier</p>
                                <p class="text-[11px] text-slate-400">Recommended size: Square PNG or SVG (Max 4MB)</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                @if($customLogoUrl)
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white shadow-xs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        Custom Logo Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-slate-500 px-2.5 py-1 text-[11px] font-bold text-white shadow-xs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        Default Logo Active
                                    </span>
                                @endif

                                {{-- Replace Button --}}
                                <label for="logoInput" class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-emerald-700 px-3 py-1 text-[11px] font-bold text-white shadow-xs hover:bg-emerald-800 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    Replace Logo
                                </label>

                                {{-- Remove Button (only if custom logo) --}}
                                @if($customLogoUrl)
                                    <button type="button" id="openRemoveModalBtn" class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-red-100 px-2.5 py-1 text-[11px] font-bold text-red-600 shadow-xs hover:bg-red-200 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                        Remove
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Driver Dashboard Module Access --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Driver Dashboard Content</h3>
                            <p class="text-[11px] text-slate-400">Control feature visibility for signed-in drivers.</p>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-between rounded-xl bg-slate-50/70 p-3.5 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Assigned Vehicle Card</p>
                                <p class="text-[10px] text-slate-400">Display permanently assigned vehicle on driver dashboard.</p>
                            </div>
                        </div>
                        
                        @php $isActive = $settings['driver_modules']['showAssignedVehicle'] ?? true; @endphp
                        <button type="button" 
                            onclick="promptToggleConfirmation('driver_modules', 'showAssignedVehicle', 'Assigned Vehicle Card', {{ $isActive ? 'false' : 'true' }})"
                            class="relative inline-flex items-center cursor-pointer">
                            <div class="w-10 h-5.5 {{ $isActive ? 'bg-emerald-600' : 'bg-slate-200' }} rounded-full transition-colors relative">
                                <div class="absolute top-[2px] left-[2px] bg-white border-slate-300 border rounded-full h-4.5 w-4.5 transition-transform {{ $isActive ? 'translate-x-[18px]' : '' }}"></div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Row 2: Admin Navigation Modules --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="7" height="7" x="3" y="3" rx="1"/>
                            <rect width="7" height="7" x="14" y="3" rx="1"/>
                            <rect width="7" height="7" x="14" y="14" rx="1"/>
                            <rect width="7" height="7" x="3" y="14" rx="1"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Admin Modules Access Control</h3>
                        <p class="text-[11px] text-slate-400">Toggle sidebar navigation entries for standard administrator accounts.</p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Vehicles List --}}
                    @php $vActive = $settings['admin_modules']['vehicles'] ?? true; @endphp
                    <div class="flex items-center justify-between rounded-xl bg-slate-50/70 p-3.5 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Vehicles List</p>
                                <p class="text-[10px] text-slate-400">Vehicle fleet records.</p>
                            </div>
                        </div>
                        <button type="button" 
                            onclick="promptToggleConfirmation('admin_modules', 'vehicles', 'Vehicles List', {{ $vActive ? 'false' : 'true' }})"
                            class="relative inline-flex items-center cursor-pointer">
                            <div class="w-9 h-5 {{ $vActive ? 'bg-emerald-600' : 'bg-slate-200' }} rounded-full transition-colors relative">
                                <div class="absolute top-[2px] left-[2px] bg-white border-slate-300 border rounded-full h-4 w-4 transition-transform {{ $vActive ? 'translate-x-[16px]' : '' }}"></div>
                            </div>
                        </button>
                    </div>

                    {{-- Vehicle Assigning --}}
                    @php $vaActive = $settings['admin_modules']['vehicleAssigning'] ?? true; @endphp
                    <div class="flex items-center justify-between rounded-xl bg-slate-50/70 p-3.5 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Vehicle Assigning</p>
                                <p class="text-[10px] text-slate-400">Driver assignments.</p>
                            </div>
                        </div>
                        <button type="button" 
                            onclick="promptToggleConfirmation('admin_modules', 'vehicleAssigning', 'Vehicle Assigning', {{ $vaActive ? 'false' : 'true' }})"
                            class="relative inline-flex items-center cursor-pointer">
                            <div class="w-9 h-5 {{ $vaActive ? 'bg-emerald-600' : 'bg-slate-200' }} rounded-full transition-colors relative">
                                <div class="absolute top-[2px] left-[2px] bg-white border-slate-300 border rounded-full h-4 w-4 transition-transform {{ $vaActive ? 'translate-x-[16px]' : '' }}"></div>
                            </div>
                        </button>
                    </div>

                    {{-- Travel Requests --}}
                    @php $vrActive = $settings['admin_modules']['vehicleRequests'] ?? true; @endphp
                    <div class="flex items-center justify-between rounded-xl bg-slate-50/70 p-3.5 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Travel Requests</p>
                                <p class="text-[10px] text-slate-400">Review trip requests.</p>
                            </div>
                        </div>
                        <button type="button" 
                            onclick="promptToggleConfirmation('admin_modules', 'vehicleRequests', 'Travel Requests', {{ $vrActive ? 'false' : 'true' }})"
                            class="relative inline-flex items-center cursor-pointer">
                            <div class="w-9 h-5 {{ $vrActive ? 'bg-emerald-600' : 'bg-slate-200' }} rounded-full transition-colors relative">
                                <div class="absolute top-[2px] left-[2px] bg-white border-slate-300 border rounded-full h-4 w-4 transition-transform {{ $vrActive ? 'translate-x-[16px]' : '' }}"></div>
                            </div>
                        </button>
                    </div>

                    {{-- Drivers --}}
                    @php $dActive = $settings['admin_modules']['drivers'] ?? true; @endphp
                    <div class="flex items-center justify-between rounded-xl bg-slate-50/70 p-3.5 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Drivers</p>
                                <p class="text-[10px] text-slate-400">Driver profiles list.</p>
                            </div>
                        </div>
                        <button type="button" 
                            onclick="promptToggleConfirmation('admin_modules', 'drivers', 'Drivers', {{ $dActive ? 'false' : 'true' }})"
                            class="relative inline-flex items-center cursor-pointer">
                            <div class="w-9 h-5 {{ $dActive ? 'bg-emerald-600' : 'bg-slate-200' }} rounded-full transition-colors relative">
                                <div class="absolute top-[2px] left-[2px] bg-white border-slate-300 border rounded-full h-4 w-4 transition-transform {{ $dActive ? 'translate-x-[16px]' : '' }}"></div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Row 3: Announcements with Dedicated Save & History Log --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Driver Announcement Card --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between">
                    <form method="POST" action="{{ route('content-settings.announcement.update', 'driver') }}" class="space-y-4">
                        @csrf
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-extrabold text-slate-900">Driver Broadcast Announcement</h3>
                                    <p class="text-[11px] text-slate-400">Displays a alert banner across Driver Portal pages.</p>
                                </div>
                            </div>

                            @php $driverAnnActive = $settings['driver_announcement']['enabled'] ?? false; @endphp
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold text-slate-600">Active</span>
                                <button type="button" 
                                    onclick="promptAnnouncementToggleConfirmation('driver', 'Driver Announcement', {{ $driverAnnActive ? 'false' : 'true' }})"
                                    class="relative inline-flex items-center cursor-pointer">
                                    <div class="w-9 h-5 {{ $driverAnnActive ? 'bg-emerald-600' : 'bg-slate-200' }} rounded-full transition-colors relative">
                                        <div class="absolute top-[2px] left-[2px] bg-white border-slate-300 border rounded-full h-4 w-4 transition-transform {{ $driverAnnActive ? 'translate-x-[16px]' : '' }}"></div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-xs font-bold text-slate-700">Announcement Message</label>
                            <textarea
                                name="message"
                                rows="3"
                                placeholder="e.g. Mandatory driver briefing scheduled for 8:00 AM on Monday."
                                class="w-full rounded-xl border border-slate-200 p-3 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 bg-slate-50/50"
                            >{{ $settings['driver_announcement']['message'] ?? '' }}</textarea>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <button type="button" id="openDriverHistoryBtn" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-emerald-700 transition cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                                History Log ({{ count($settings['driver_announcement']['history'] ?? []) }})
                            </button>

                            <button type="submit" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                Save Driver Announcement
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Admin Announcement Card --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between">
                    <form method="POST" action="{{ route('content-settings.announcement.update', 'admin') }}" class="space-y-4">
                        @csrf
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-extrabold text-slate-900">Admin Broadcast Announcement</h3>
                                    <p class="text-[11px] text-slate-400">Displays an alert banner across Admin Portal pages.</p>
                                </div>
                            </div>

                            @php $adminAnnActive = $settings['admin_announcement']['enabled'] ?? false; @endphp
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold text-slate-600">Active</span>
                                <button type="button" 
                                    onclick="promptAnnouncementToggleConfirmation('admin', 'Admin Announcement', {{ $adminAnnActive ? 'false' : 'true' }})"
                                    class="relative inline-flex items-center cursor-pointer">
                                    <div class="w-9 h-5 {{ $adminAnnActive ? 'bg-emerald-600' : 'bg-slate-200' }} rounded-full transition-colors relative">
                                        <div class="absolute top-[2px] left-[2px] bg-white border-slate-300 border rounded-full h-4 w-4 transition-transform {{ $adminAnnActive ? 'translate-x-[16px]' : '' }}"></div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-xs font-bold text-slate-700">Announcement Message</label>
                            <textarea
                                name="message"
                                rows="3"
                                placeholder="e.g. System maintenance scheduled for Sunday midnight."
                                class="w-full rounded-xl border border-slate-200 p-3 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 bg-slate-50/50"
                            >{{ $settings['admin_announcement']['message'] ?? '' }}</textarea>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <button type="button" id="openAdminHistoryBtn" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-emerald-700 transition cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                                History Log ({{ count($settings['admin_announcement']['history'] ?? []) }})
                            </button>

                            <button type="submit" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                Save Admin Announcement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            {{-- Standard Admin Cards --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Driver Dashboard Content --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Driver Dashboard Content</h3>
                            <p class="text-[11px] text-slate-400">Controls what signed-in drivers see on dashboard.</p>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-between rounded-xl bg-slate-50/70 p-3.5 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-600 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Assigned Vehicle Card</p>
                                <p class="text-[10px] text-slate-400">Vehicle assigned to the signed-in driver.</p>
                            </div>
                        </div>
                        
                        @php $isActive = $settings['driver_modules']['showAssignedVehicle'] ?? true; @endphp
                        <button type="button" 
                            onclick="promptToggleConfirmation('driver_modules', 'showAssignedVehicle', 'Assigned Vehicle Card', {{ $isActive ? 'false' : 'true' }})"
                            class="relative inline-flex items-center cursor-pointer">
                            <div class="w-10 h-5.5 {{ $isActive ? 'bg-emerald-600' : 'bg-slate-200' }} rounded-full transition-colors relative">
                                <div class="absolute top-[2px] left-[2px] bg-white border-slate-300 border rounded-full h-4.5 w-4.5 transition-transform {{ $isActive ? 'translate-x-[18px]' : '' }}"></div>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Driver Announcement Card --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between">
                    <form method="POST" action="{{ route('content-settings.announcement.update', 'driver') }}" class="space-y-4">
                        @csrf
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-extrabold text-slate-900">Driver Announcement</h3>
                                    <p class="text-[11px] text-slate-400">Broadcast alert banner for driver accounts.</p>
                                </div>
                            </div>

                            @php $driverAnnActive = $settings['driver_announcement']['enabled'] ?? false; @endphp
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold text-slate-600">Show banner</span>
                                <button type="button" 
                                    onclick="promptAnnouncementToggleConfirmation('driver', 'Driver Announcement', {{ $driverAnnActive ? 'false' : 'true' }})"
                                    class="relative inline-flex items-center cursor-pointer">
                                    <div class="w-9 h-5 {{ $driverAnnActive ? 'bg-emerald-600' : 'bg-slate-200' }} rounded-full transition-colors relative">
                                        <div class="absolute top-[2px] left-[2px] bg-white border-slate-300 border rounded-full h-4 w-4 transition-transform {{ $driverAnnActive ? 'translate-x-[16px]' : '' }}"></div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div>
                            <textarea
                                name="message"
                                rows="3"
                                placeholder="e.g. Maintenance scheduled for Saturday."
                                class="w-full rounded-xl border border-slate-200 p-3 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 bg-slate-50/50"
                            >{{ $settings['driver_announcement']['message'] ?? '' }}</textarea>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <button type="button" id="openDriverHistoryBtn" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-emerald-700 transition cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                                History Log ({{ count($settings['driver_announcement']['history'] ?? []) }})
                            </button>

                            <button type="submit" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                Save message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div></div>

    {{-- Standalone Single Toggle Form --}}
    <form id="singleToggleForm" method="POST" action="{{ route('content-settings.toggle') }}" class="hidden">
        @csrf
        <input type="hidden" name="group" id="toggleGroupInput">
        <input type="hidden" name="key" id="toggleKeyInput">
        <input type="hidden" name="value" id="toggleValueInput">
    </form>

    {{-- Standalone Announcement Toggle Form --}}
    <form id="announcementToggleForm" method="POST" action="" class="hidden">
        @csrf
        <input type="hidden" name="enabled" id="announcementEnabledInput">
    </form>

    {{-- Standalone Logo Upload Form --}}
    <form id="logoUploadForm" method="POST" action="{{ route('content-settings.logo.update') }}" enctype="multipart/form-data" class="hidden">
        @csrf
        <input type="file" id="logoInput" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="hidden">
    </form>

    {{-- Standalone Logo Remove Form --}}
    <form id="logoRemoveForm" method="POST" action="{{ route('content-settings.logo.destroy') }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    {{-- Toggle Confirmation Modal --}}
    <div id="toggleConfirmModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-100">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 mb-3" id="toggleModalIconContainer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v4"/>
                    <path d="M12 16h.01"/>
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-slate-900" id="toggleModalTitle">Confirm Toggle Action</h3>
            <p class="mt-1.5 text-xs text-slate-600 leading-relaxed" id="toggleModalDescription">Are you sure you want to change this module setting?</p>
            
            <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-100 mt-4">
                <button type="button" id="cancelToggleBtn" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" id="confirmToggleBtn" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 transition cursor-pointer">
                    Confirm & Apply
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Confirmation for Logo Replacement --}}
    <div id="logoModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-100">
            <h3 class="text-base font-extrabold text-slate-900">Confirm Logo Replacement</h3>
            <p class="mt-1 text-xs text-slate-500">Preview how your new logo looks before saving changes.</p>
            
            <div class="my-5 flex items-center justify-center rounded-xl bg-slate-50 p-6 border border-slate-200">
                <img id="modalLogoPreview" src="" alt="New Logo Preview" class="max-h-32 object-contain">
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" id="cancelLogoBtn" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" id="confirmLogoBtn" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 transition cursor-pointer">
                    Confirm & Save Logo
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Confirmation for Logo Removal --}}
    <div id="logoRemoveModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-100">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-red-50 text-red-600 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-slate-900">Reset to Default Logo?</h3>
            <p class="mt-1 text-xs text-slate-500">Are you sure you want to remove the custom logo? The system will revert back to the default logo across all pages.</p>
            
            <div class="flex items-center justify-end gap-3 pt-5">
                <button type="button" id="cancelRemoveLogoBtn" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" id="confirmRemoveLogoBtn" class="rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-red-700 transition cursor-pointer">
                    Yes, Reset Logo
                </button>
            </div>
        </div>
    </div>

    {{-- Driver Announcement History Log Modal --}}
    <div id="driverHistoryModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                    </div>
                    <h3 class="text-sm font-extrabold text-slate-900">Driver Announcement History Log</h3>
                </div>
                <button type="button" id="closeDriverHistoryBtn" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="my-4 max-h-72 overflow-y-auto divide-y divide-slate-100 pr-1">
                @forelse($settings['driver_announcement']['history'] ?? [] as $entry)
                    <div class="py-3 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1 rounded-md {{ ($entry['enabled'] ?? false) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }} px-2 py-0.5 text-[10px] font-bold">
                                {{ ($entry['enabled'] ?? false) ? 'Active Broadcast' : 'Disabled' }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-medium">
                                {{ \Carbon\Carbon::parse($entry['updated_at'] ?? now())->format('M d, Y • h:i A') }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-800 font-semibold">{{ $entry['message'] }}</p>
                        <p class="text-[10px] text-slate-400">Published by <span class="font-bold text-slate-600">{{ $entry['updated_by_name'] ?? 'Super Admin' }}</span></p>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-400 font-medium">
                        No announcement history logged yet.
                    </div>
                @endforelse
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-100">
                <button type="button" id="closeDriverHistoryModalBtn" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                    Close History Log
                </button>
            </div>
        </div>
    </div>

    {{-- Admin Announcement History Log Modal --}}
    <div id="adminHistoryModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-slate-100">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                    </div>
                    <h3 class="text-sm font-extrabold text-slate-900">Admin Announcement History Log</h3>
                </div>
                <button type="button" id="closeAdminHistoryBtn" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="my-4 max-h-72 overflow-y-auto divide-y divide-slate-100 pr-1">
                @forelse($settings['admin_announcement']['history'] ?? [] as $entry)
                    <div class="py-3 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1 rounded-md {{ ($entry['enabled'] ?? false) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }} px-2 py-0.5 text-[10px] font-bold">
                                {{ ($entry['enabled'] ?? false) ? 'Active Broadcast' : 'Disabled' }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-medium">
                                {{ \Carbon\Carbon::parse($entry['updated_at'] ?? now())->format('M d, Y • h:i A') }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-800 font-semibold">{{ $entry['message'] }}</p>
                        <p class="text-[10px] text-slate-400">Published by <span class="font-bold text-slate-600">{{ $entry['updated_by_name'] ?? 'Super Admin' }}</span></p>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-400 font-medium">
                        No announcement history logged yet.
                    </div>
                @endforelse
            </div>

            <div class="flex justify-end pt-2 border-t border-slate-100">
                <button type="button" id="closeAdminHistoryModalBtn" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                    Close History Log
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let pendingAction = null;

    function promptToggleConfirmation(group, key, label, newValue) {
        pendingAction = { type: 'module', group, key, value: newValue ? '1' : '0' };
        
        const modal = document.getElementById('toggleConfirmModal');
        const modalTitle = document.getElementById('toggleModalTitle');
        const modalDesc = document.getElementById('toggleModalDescription');
        
        const actionText = newValue ? 'enable' : 'disable';
        const actionBadge = newValue ? 'ON' : 'OFF';

        modalTitle.textContent = `Turn ${actionBadge} ${label}?`;
        modalDesc.innerHTML = `Are you sure you want to <span class="font-extrabold ${newValue ? 'text-emerald-700' : 'text-red-600'}">${actionText}</span> access for <strong>${label}</strong>?`;
        
        modal.classList.remove('hidden');
    }

    function promptAnnouncementToggleConfirmation(target, label, newValue) {
        pendingAction = { type: 'announcement', target, value: newValue ? '1' : '0' };
        
        const modal = document.getElementById('toggleConfirmModal');
        const modalTitle = document.getElementById('toggleModalTitle');
        const modalDesc = document.getElementById('toggleModalDescription');
        
        const actionText = newValue ? 'enable' : 'disable';
        const actionBadge = newValue ? 'ON' : 'OFF';

        modalTitle.textContent = `Turn ${actionBadge} ${label}?`;
        modalDesc.innerHTML = `Are you sure you want to <span class="font-extrabold ${newValue ? 'text-emerald-700' : 'text-red-600'}">${actionText}</span> the <strong>${label}</strong> broadcast?`;
        
        modal.classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const toggleConfirmModal = document.getElementById('toggleConfirmModal');
        const cancelToggleBtn = document.getElementById('cancelToggleBtn');
        const confirmToggleBtn = document.getElementById('confirmToggleBtn');
        const singleToggleForm = document.getElementById('singleToggleForm');
        const toggleGroupInput = document.getElementById('toggleGroupInput');
        const toggleKeyInput = document.getElementById('toggleKeyInput');
        const toggleValueInput = document.getElementById('toggleValueInput');

        if (cancelToggleBtn) {
            cancelToggleBtn.addEventListener('click', function () {
                pendingAction = null;
                toggleConfirmModal.classList.add('hidden');
            });
        }

        if (confirmToggleBtn) {
            confirmToggleBtn.addEventListener('click', function () {
                if (pendingAction) {
                    if (pendingAction.type === 'module') {
                        toggleGroupInput.value = pendingAction.group;
                        toggleKeyInput.value = pendingAction.key;
                        toggleValueInput.value = pendingAction.value;
                        singleToggleForm.submit();
                    } else if (pendingAction.type === 'announcement') {
                        const announcementToggleForm = document.getElementById('announcementToggleForm');
                        const announcementEnabledInput = document.getElementById('announcementEnabledInput');
                        announcementToggleForm.action = `/content-settings/announcement/${pendingAction.target}/toggle`;
                        announcementEnabledInput.value = pendingAction.value;
                        announcementToggleForm.submit();
                    }
                }
            });
        }

        const logoInput = document.getElementById('logoInput');
        const logoModal = document.getElementById('logoModal');
        const modalLogoPreview = document.getElementById('modalLogoPreview');
        const cancelLogoBtn = document.getElementById('cancelLogoBtn');
        const confirmLogoBtn = document.getElementById('confirmLogoBtn');
        const logoUploadForm = document.getElementById('logoUploadForm');

        const logoRemoveModal = document.getElementById('logoRemoveModal');
        const openRemoveModalBtn = document.getElementById('openRemoveModalBtn');
        const cancelRemoveLogoBtn = document.getElementById('cancelRemoveLogoBtn');
        const confirmRemoveLogoBtn = document.getElementById('confirmRemoveLogoBtn');
        const logoRemoveForm = document.getElementById('logoRemoveForm');

        const driverHistoryModal = document.getElementById('driverHistoryModal');
        const openDriverHistoryBtn = document.getElementById('openDriverHistoryBtn');
        const closeDriverHistoryBtn = document.getElementById('closeDriverHistoryBtn');
        const closeDriverHistoryModalBtn = document.getElementById('closeDriverHistoryModalBtn');

        const adminHistoryModal = document.getElementById('adminHistoryModal');
        const openAdminHistoryBtn = document.getElementById('openAdminHistoryBtn');
        const closeAdminHistoryBtn = document.getElementById('closeAdminHistoryBtn');
        const closeAdminHistoryModalBtn = document.getElementById('closeAdminHistoryModalBtn');

        if (logoInput) {
            logoInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        modalLogoPreview.src = e.target.result;
                        logoModal.classList.remove('hidden');
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        if (cancelLogoBtn) {
            cancelLogoBtn.addEventListener('click', function () {
                logoInput.value = '';
                modalLogoPreview.src = '';
                logoModal.classList.add('hidden');
            });
        }

        if (confirmLogoBtn) {
            confirmLogoBtn.addEventListener('click', function () {
                logoUploadForm.submit();
            });
        }

        if (openRemoveModalBtn) {
            openRemoveModalBtn.addEventListener('click', function () {
                logoRemoveModal.classList.remove('hidden');
            });
        }

        if (cancelRemoveLogoBtn) {
            cancelRemoveLogoBtn.addEventListener('click', function () {
                logoRemoveModal.classList.add('hidden');
            });
        }

        if (confirmRemoveLogoBtn) {
            confirmRemoveLogoBtn.addEventListener('click', function () {
                logoRemoveForm.submit();
            });
        }

        // History Modal Handlers
        if (openDriverHistoryBtn) {
            openDriverHistoryBtn.addEventListener('click', function () {
                driverHistoryModal.classList.remove('hidden');
            });
        }
        if (closeDriverHistoryBtn) {
            closeDriverHistoryBtn.addEventListener('click', function () {
                driverHistoryModal.classList.add('hidden');
            });
        }
        if (closeDriverHistoryModalBtn) {
            closeDriverHistoryModalBtn.addEventListener('click', function () {
                driverHistoryModal.classList.add('hidden');
            });
        }

        if (openAdminHistoryBtn) {
            openAdminHistoryBtn.addEventListener('click', function () {
                adminHistoryModal.classList.remove('hidden');
            });
        }
        if (closeAdminHistoryBtn) {
            closeAdminHistoryBtn.addEventListener('click', function () {
                adminHistoryModal.classList.add('hidden');
            });
        }
        if (closeAdminHistoryModalBtn) {
            closeAdminHistoryModalBtn.addEventListener('click', function () {
                adminHistoryModal.classList.add('hidden');
            });
        }
    });
</script>
@endpush
