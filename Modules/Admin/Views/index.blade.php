@extends('layouts.app')

@section('title', 'Admins')
@section('page-title', 'Admins')

@section('content')
    @php
        $locationOptions = [
            'Oriental Mindoro',
            'Occidental Mindoro',
            'Marinduque',
            'Palawan',
            'Romblon',
            'Quezon City Satellite Office',
        ];
        $selectedLocation = request('location');
        $locationButtonLabel = $selectedLocation ?: 'All Locations';
    @endphp

    {{-- Page header --}}
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Admins</h1>
            <p class="text-sm text-slate-500">Manage your admin accounts</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <form method="GET" action="{{ route('admin.index') }}" id="admin-search-form" class="flex w-full min-w-0 items-center gap-2 sm:w-72 xl:w-96" onsubmit="return false;">
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-600/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        id="admin-search-input"
                        value="{{ request('search') }}"
                        autocomplete="off"
                        placeholder="Search name or email..."
                        oninput="onAdminSearchInput()"
                        class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-8 text-xs text-slate-800 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                    <svg id="admin-search-spinner" xmlns="http://www.w3.org/2000/svg" class="hidden absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                </div>

                <a href="{{ route('admin.index') }}" id="admin-search-clear" onclick="return clearAdminSearch(event)" class="shrink-0 text-xs font-semibold text-slate-500 hover:text-slate-700 {{ request('search') ? '' : 'hidden' }}">Clear</a>
            </form>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Location filter dropdown --}}
                <div class="relative">
                    <button type="button" onclick="toggleAdminLocationMenu()" class="inline-flex h-10 items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span class="max-w-[9rem] truncate">{{ $locationButtonLabel }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400 shrink-0 transition" id="admin-location-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div id="admin-location-menu" class="absolute left-0 z-20 mt-2 hidden w-64 sm:left-auto sm:right-0 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5">
                        <a href="{{ route('admin.index', array_filter(['search' => request('search')])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold transition {{ blank($selectedLocation) ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-gray-50' }}">
                            All Locations
                            @if(blank($selectedLocation))
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                            @endif
                        </a>
                        @foreach($locationOptions as $locationOption)
                            <a href="{{ route('admin.index', array_filter(['search' => request('search'), 'location' => $locationOption])) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold transition {{ $selectedLocation === $locationOption ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-gray-50' }}">
                                {{ $locationOption }}
                                @if($selectedLocation === $locationOption)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6 9 17l-5-5"/>
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- View toggle buttons (segmented switch) --}}
                <div class="flex h-10 shrink-0 items-center rounded-xl border border-slate-200 bg-white p-1">
                    <button
                        type="button"
                        id="view-toggle-list"
                        onclick="setAdminView('list')"
                        class="view-toggle-btn flex h-full w-8 items-center justify-center rounded-lg transition text-slate-400 hover:text-slate-600"
                        title="List View"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="8" y1="6" x2="21" y2="6"/>
                            <line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/>
                            <line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        id="view-toggle-grid"
                        onclick="setAdminView('grid')"
                        class="view-toggle-btn flex h-full w-8 items-center justify-center rounded-lg transition text-slate-400 hover:text-slate-600"
                        title="Grid View"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                            <rect x="14" y="14" width="7" height="7" rx="1"/>
                        </svg>
                    </button>
                </div>

                <button type="button" onclick="openAddAdminModal()" class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-[#005a36] px-4 text-xs font-extrabold text-white shadow-sm transition hover:bg-[#00432a] cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Add Admin
                </button>
            </div>
        </div>
    </div>

    <div id="admin-results">
        @include('admin::partials.admin-results', ['admins' => $admins, 'approvingOfficers' => $approvingOfficers ?? collect()])
    </div>

    {{-- Add Admin modal --}}
    <div id="add-admin-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/45 px-4 py-6 backdrop-blur-sm">
        <div class="my-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <h2 class="text-base font-extrabold text-emerald-900">Add Admin</h2>
                <button type="button" onclick="closeAddAdminModal()" class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.store') }}" id="add-admin-form" class="max-h-[82vh] space-y-3 overflow-y-auto px-5 py-4" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_admin_form" value="create">

                <div class="flex flex-col items-center justify-center text-center">
                    <div class="relative">
                        <div class="relative flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-2 border-emerald-500 bg-emerald-700 text-2xl font-extrabold text-white shadow-md ring-4 ring-emerald-50">
                            <img id="add-avatar-preview-img" src="" alt="" class="hidden h-full w-full object-cover">
                            <span id="add-avatar-preview-initial" class="flex h-full w-full items-center justify-center">?</span>
                        </div>
                        <input type="file" name="avatar" id="add-avatar" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewAddAvatarFile(this)">
                    </div>

                    <div class="mt-2.5 flex items-center justify-center gap-2">
                        <label for="add-avatar" class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 shadow-2xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                <circle cx="12" cy="13" r="3"/>
                            </svg>
                            <span id="add-avatar-btn-text">Select Photo</span>
                        </label>
                        <button
                            type="button"
                            id="add-avatar-remove-btn"
                            onclick="removeAddAvatar()"
                            class="hidden inline-flex cursor-pointer items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-bold text-red-600 transition hover:bg-red-100 shadow-2xs"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18"/>
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                            </svg>
                            <span>Remove</span>
                        </button>
                    </div>

                    <p class="mt-1 text-[11px] text-slate-500">Photo (optional, max 5MB)</p>
                    <p id="add-avatar-warning" class="mt-1 hidden text-xs font-semibold text-red-600"></p>
                    @error('avatar')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-3">
                    <div>
                        <label for="add-name" class="text-xs font-extrabold text-slate-700">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="add-name" value="{{ old('_admin_form') === 'create' ? old('name') : '' }}" placeholder="Enter Full Name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-red-300 @enderror" required>
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="add-email" class="text-xs font-extrabold text-slate-700">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="add-email" value="{{ old('_admin_form') === 'create' ? old('email') : '' }}" placeholder="Enter email" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('email') border-red-300 @enderror" required>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="add-password" class="text-xs font-extrabold text-slate-700">Temporary Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="add-password" placeholder="Set temporary password" oninput="document.getElementById('add-password_confirmation').value = this.value" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('password') border-red-300 @enderror" required>
                        <input type="hidden" name="password_confirmation" id="add-password_confirmation">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="add-role" class="text-xs font-extrabold text-slate-700">Role <span class="text-red-500">*</span></label>
                        <select name="role" id="add-role" onchange="syncAddRoleFields()" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" required>
                            <option value="{{ \Modules\Admin\Enums\UserRole::ADMIN->value }}" @selected(old('_admin_form') === 'create' ? old('role') === \Modules\Admin\Enums\UserRole::ADMIN->value : true)>Admin</option>
                            <option value="{{ \Modules\Admin\Enums\UserRole::SUPER_ADMIN->value }}" @selected(old('_admin_form') === 'create' && old('role') === \Modules\Admin\Enums\UserRole::SUPER_ADMIN->value)>Super Admin</option>
                        </select>
                    </div>

                    <div id="add-admin-location-field">
                        <label for="add-location" class="text-xs font-extrabold text-slate-700">Assigned Location / Office Province</label>
                        <select name="location" id="add-location" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="" @selected(blank(old('location')))>— All Locations (No restriction) —</option>
                            @foreach ($locationOptions as $locationOption)
                                <option value="{{ $locationOption }}" @selected(old('_admin_form') === 'create' && old('location') === $locationOption)>{{ $locationOption }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs leading-snug text-slate-500">Admins assigned to a specific province will only see vehicle requests from that location.</p>
                    </div>

                    <div>
                        <label for="add-phone-number" class="text-xs font-extrabold text-slate-700">Phone Number <span class="text-red-500">*</span></label>
                        <input type="text" name="phone_number" id="add-phone-number" value="{{ old('_admin_form') === 'create' ? old('phone_number') : '' }}" placeholder="e.g. 09171234567" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('phone_number') border-red-300 @enderror" required>
                        @error('phone_number')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div id="add-admin-signatories-field" class="border-t border-slate-200 pt-3">
                    <h3 class="text-xs font-extrabold text-slate-900">Trip Ticket Signatories (Approved by)</h3>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="add-signee1-name" class="text-xs font-bold text-slate-700">Signatory 1 Name</label>
                            <input type="text" name="signee1_name" id="add-signee1-name" value="{{ old('_admin_form') === 'create' ? old('signee1_name') : '' }}" placeholder="e.g. ARW D. BURGOS" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="add-signee1-title" class="text-xs font-bold text-slate-700">Signatory 1 Title / Office</label>
                            <input type="text" name="signee1_title" id="add-signee1-title" value="{{ old('_admin_form') === 'create' ? old('signee1_title') : '' }}" placeholder="e.g. OIC - ARDO - Oriental Mindoro" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="add-signee2-name" class="text-xs font-bold text-slate-700">Signatory 2 Name</label>
                            <input type="text" name="signee2_name" id="add-signee2-name" value="{{ old('_admin_form') === 'create' ? old('signee2_name') : '' }}" placeholder="e.g. EDSARDO F. LEIDO, JR." class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="add-signee2-title" class="text-xs font-bold text-slate-700">Signatory 2 Title / Office</label>
                            <input type="text" name="signee2_title" id="add-signee2-title" value="{{ old('_admin_form') === 'create' ? old('signee2_title') : '' }}" placeholder="e.g. GSS Regional Office Calapan" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-extrabold text-white shadow-md transition hover:bg-emerald-700">
                    Add Admin
                </button>
            </form>
        </div>
    </div>

    @if ($errors->any() && old('_admin_form') === 'create')
        <script>document.addEventListener('DOMContentLoaded', () => openAddAdminModal());</script>
    @endif

    {{-- Edit Admin modal --}}
    <div id="edit-admin-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/45 px-4 py-6 backdrop-blur-sm">
        <div class="my-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <h2 class="text-base font-extrabold text-emerald-900">Edit Admin</h2>
                <button type="button" onclick="closeEditAdminModal()" class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ old('_admin_action', route('admin.index')) }}" id="edit-admin-form" class="max-h-[82vh] space-y-3 overflow-y-auto px-5 py-4" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="_admin_form" value="edit">
                <input type="hidden" name="_admin_action" id="edit-admin-action-field" value="{{ old('_admin_action') }}">
                <input type="hidden" name="remove_avatar" id="edit-remove-avatar" value="0">

                <div class="flex flex-col items-center justify-center text-center">
                    <div class="relative">
                        <div class="relative flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-2 border-emerald-500 bg-emerald-700 text-2xl font-extrabold text-white shadow-md ring-4 ring-emerald-50">
                            <img id="edit-avatar-preview-img" src="" alt="" class="hidden h-full w-full object-cover">
                            <span id="edit-avatar-preview-initial" class="flex h-full w-full items-center justify-center"></span>
                        </div>
                        <input type="file" name="avatar" id="edit-avatar" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewEditAvatarFile(this)">
                    </div>

                    <div class="mt-2.5 flex items-center justify-center gap-2">
                        <label for="edit-avatar" class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 shadow-2xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                <circle cx="12" cy="13" r="3"/>
                            </svg>
                            <span id="edit-avatar-btn-text">Change Photo</span>
                        </label>
                        <button
                            type="button"
                            id="edit-avatar-remove-btn"
                            onclick="removeEditAvatar()"
                            class="hidden inline-flex cursor-pointer items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-bold text-red-600 transition hover:bg-red-100 shadow-2xs"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18"/>
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                            </svg>
                            <span>Remove</span>
                        </button>
                    </div>

                    <p class="mt-1 text-[11px] text-slate-500">Photo (optional, max 5MB)</p>
                    <p id="edit-avatar-warning" class="mt-1 hidden text-xs font-semibold text-red-600"></p>
                    @error('avatar')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-3">
                    <div>
                        <label for="edit-name" class="text-xs font-extrabold text-slate-700">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="edit-name" value="{{ old('_admin_form') === 'edit' ? old('name') : '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-red-300 @enderror" required>
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-email" class="text-xs font-extrabold text-slate-700">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="edit-email" value="{{ old('_admin_form') === 'edit' ? old('email') : '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('email') border-red-300 @enderror" required>
                        <p class="mt-1 text-xs text-slate-500">Email changes require a backend admin action.</p>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-role" class="text-xs font-extrabold text-slate-700">Role <span class="text-red-500">*</span></label>
                        <select name="role" id="edit-role" onchange="syncEditRoleFields()" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" required>
                            <option value="{{ \Modules\Admin\Enums\UserRole::ADMIN->value }}" @selected(old('_admin_form') === 'edit' && old('role') === \Modules\Admin\Enums\UserRole::ADMIN->value)>Admin</option>
                            <option value="{{ \Modules\Admin\Enums\UserRole::SUPER_ADMIN->value }}" @selected(old('_admin_form') === 'edit' && old('role') === \Modules\Admin\Enums\UserRole::SUPER_ADMIN->value)>Super Admin</option>
                        </select>
                    </div>

                    <div id="edit-admin-location-field">
                        <label for="edit-location" class="text-xs font-extrabold text-slate-700">Assigned Location / Office Province</label>
                        <select name="location" id="edit-location" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="" @selected(old('_admin_form') === 'edit' && blank(old('location')))>— All Locations (No restriction) —</option>
                            @foreach ($locationOptions as $locationOption)
                                <option value="{{ $locationOption }}" @selected(old('_admin_form') === 'edit' && old('location') === $locationOption)>{{ $locationOption }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs leading-snug text-slate-500">Admins assigned to a specific province will only see vehicle requests from that location.</p>
                    </div>

                    <div>
                        <label for="edit-phone-number" class="text-xs font-extrabold text-slate-700">Phone Number <span class="text-red-500">*</span></label>
                        <input type="text" name="phone_number" id="edit-phone-number" value="{{ old('_admin_form') === 'edit' ? old('phone_number') : '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500" required>
                    </div>
                </div>

                <div id="edit-admin-signatories-field" class="border-t border-slate-200 pt-3">
                    <h3 class="text-xs font-extrabold text-slate-900">Trip Ticket Signatories (Approved by)</h3>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="edit-signee1-name" class="text-xs font-bold text-slate-700">Signatory 1 Name</label>
                            <input type="text" name="signee1_name" id="edit-signee1-name" value="{{ old('_admin_form') === 'edit' ? old('signee1_name') : '' }}" placeholder="e.g. ARJAY D. BURGOS" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="edit-signee1-title" class="text-xs font-bold text-slate-700">Signatory 1 Title / Office</label>
                            <input type="text" name="signee1_title" id="edit-signee1-title" value="{{ old('_admin_form') === 'edit' ? old('signee1_title') : '' }}" placeholder="e.g. OIC - APCO-Oriental Mindoro" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="edit-signee2-name" class="text-xs font-bold text-slate-700">Signatory 2 Name</label>
                            <input type="text" name="signee2_name" id="edit-signee2-name" value="{{ old('_admin_form') === 'edit' ? old('signee2_name') : '' }}" placeholder="e.g. EDGARDO F. LEIDO, Jr." class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label for="edit-signee2-title" class="text-xs font-bold text-slate-700">Signatory 2 Title / Office</label>
                            <input type="text" name="signee2_title" id="edit-signee2-title" value="{{ old('_admin_form') === 'edit' ? old('signee2_title') : '' }}" placeholder="e.g. GSS Regional Office Calapan" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-emerald-500 to-emerald-700 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-emerald-700/20 transition hover:from-emerald-600 hover:to-emerald-800">
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    @if ($errors->any() && old('_admin_form') === 'edit')
        <script>document.addEventListener('DOMContentLoaded', () => openEditAdminModal(null));</script>
    @endif

    {{-- View details modal --}}
    <div id="view-admin-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Admin details</h2>
                <button type="button" onclick="closeViewModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-5 flex items-center gap-3">
                <div class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-50 text-base font-semibold text-emerald-700">
                    <img id="view-admin-avatar-img" src="" alt="" class="hidden h-full w-full object-cover">
                    <span id="view-admin-avatar"></span>
                </div>
                <div class="min-w-0">
                    <p id="view-admin-name" class="truncate text-base font-semibold text-gray-900"></p>
                    <p id="view-admin-email" class="truncate text-sm text-gray-500"></p>
                </div>
            </div>

            <dl class="mt-5 space-y-3 border-t border-gray-100 pt-4">
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Role</dt>
                    <dd id="view-admin-role" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Joined</dt>
                    <dd id="view-admin-created" class="text-sm font-medium text-gray-900"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-sm text-gray-500">Last updated</dt>
                    <dd id="view-admin-updated" class="text-sm font-medium text-gray-900"></dd>
                </div>
            </dl>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    onclick="closeViewModal()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal --}}
    <div id="delete-admin-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-red-50 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9v4M12 17h.01"/>
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-semibold text-gray-900">Delete admin?</h2>
            <p id="delete-admin-warning" class="mt-1 text-sm text-gray-500">
                This will permanently remove <span id="delete-admin-name" class="font-medium text-gray-700"></span>'s account. This action cannot be undone.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                >
                    Cancel
                </button>

                <form id="delete-admin-form" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition"
                    >
                        Yes, delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .view-toggle-btn { color: #94a3b8; }
        .view-toggle-btn.active { background: #005a36; border-color: #005a36; color: #fff; }
    </style>

    <script>
        function toggleAdminLocationMenu() {
            const menu = document.getElementById('admin-location-menu');
            const chevron = document.getElementById('admin-location-chevron');
            menu?.classList.toggle('hidden');
            chevron?.classList.toggle('rotate-180');
        }

        document.addEventListener('click', function (event) {
            const menu = document.getElementById('admin-location-menu');
            const chevron = document.getElementById('admin-location-chevron');
            if (!menu || menu.classList.contains('hidden')) return;
            if (event.target.closest('#admin-location-menu') || event.target.closest('[onclick="toggleAdminLocationMenu()"]')) return;
            menu.classList.add('hidden');
            chevron?.classList.remove('rotate-180');
        });

        function applyAdminView(mode) {
            const list = document.getElementById('admin-view-list');
            const grid = document.getElementById('admin-view-grid');
            const btnList = document.getElementById('view-toggle-list');
            const btnGrid = document.getElementById('view-toggle-grid');

            if (!list || !grid) return;

            if (mode === 'grid') {
                list.classList.add('hidden');
                grid.classList.remove('hidden');
                btnGrid?.classList.add('active');
                btnList?.classList.remove('active');
            } else {
                grid.classList.add('hidden');
                list.classList.remove('hidden');
                btnList?.classList.add('active');
                btnGrid?.classList.remove('active');
            }
        }

        function currentAdminViewMode() {
            return localStorage.getItem('admin_view_mode') || 'list';
        }

        function setAdminView(mode) {
            localStorage.setItem('admin_view_mode', mode);
            applyAdminView(mode);
        }

        document.addEventListener('DOMContentLoaded', function () {
            applyAdminView(currentAdminViewMode());
        });

        function openViewModal(btn) {
            const img = document.getElementById('view-admin-avatar-img');
            const initial = document.getElementById('view-admin-avatar');
            if (btn.dataset.avatar) {
                img.src = btn.dataset.avatar;
                img.classList.remove('hidden');
                initial.classList.add('hidden');
            } else {
                img.classList.add('hidden');
                initial.classList.remove('hidden');
                initial.textContent = btn.dataset.initial;
            }
            document.getElementById('view-admin-name').textContent = btn.dataset.name;
            document.getElementById('view-admin-email').textContent = btn.dataset.email;
            document.getElementById('view-admin-role').textContent = btn.dataset.role;
            document.getElementById('view-admin-created').textContent = btn.dataset.created;
            document.getElementById('view-admin-updated').textContent = btn.dataset.updated;

            const modal = document.getElementById('view-admin-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeViewModal() {
            const modal = document.getElementById('view-admin-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openDeleteModal(btn) {
            document.getElementById('delete-admin-name').textContent = btn.dataset.name;
            document.getElementById('delete-admin-form').action = btn.dataset.deleteUrl;

            const modal = document.getElementById('delete-admin-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('delete-admin-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        /* ---------------- Add Admin modal ---------------- */

        function openAddAdminModal() {
            const modal = document.getElementById('add-admin-modal');
            removeAddAvatar();
            syncAddRoleFields();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAddAdminModal() {
            const modal = document.getElementById('add-admin-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function syncAddRoleFields() {
            const roleSelect = document.getElementById('add-role');
            if (!roleSelect) return;

            const role = roleSelect.value;
            const isAdmin = role === '{{ \Modules\Admin\Enums\UserRole::ADMIN->value }}';
            const locationField = document.getElementById('add-admin-location-field');
            const signatoriesField = document.getElementById('add-admin-signatories-field');
            const adminOnlyInputs = [
                'add-location',
                'add-signee1-name',
                'add-signee1-title',
                'add-signee2-name',
                'add-signee2-title',
            ];

            [locationField, signatoriesField].forEach((field) => {
                if (field) field.classList.toggle('hidden', !isAdmin);
            });

            adminOnlyInputs.forEach((id) => {
                const input = document.getElementById(id);
                if (input) input.disabled = !isAdmin;
            });
        }

        function previewAddAvatarFile(input) {
            const warning = document.getElementById('add-avatar-warning');
            if (warning) {
                warning.classList.add('hidden');
                warning.textContent = '';
            }

            const file = input.files && input.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                if (warning) {
                    warning.textContent = 'Warning: Image size exceeds 5MB limit. Please select a smaller file.';
                    warning.classList.remove('hidden');
                } else {
                    alert('Warning: Image size exceeds 5MB limit.');
                }
                input.value = '';
                removeAddAvatar();
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('add-avatar-preview-img');
                img.src = e.target.result;
                img.classList.remove('hidden');
                document.getElementById('add-avatar-preview-initial').classList.add('hidden');
                const removeBtn = document.getElementById('add-avatar-remove-btn');
                if (removeBtn) {
                    removeBtn.classList.remove('hidden');
                    removeBtn.classList.add('inline-flex');
                }
                const btnText = document.getElementById('add-avatar-btn-text');
                if (btnText) btnText.textContent = 'Change Photo';
            };
            reader.readAsDataURL(file);
        }

        function removeAddAvatar() {
            document.getElementById('add-avatar').value = '';
            const warning = document.getElementById('add-avatar-warning');
            if (warning) {
                warning.classList.add('hidden');
                warning.textContent = '';
            }

            const img = document.getElementById('add-avatar-preview-img');
            img.src = '';
            img.classList.add('hidden');
            document.getElementById('add-avatar-preview-initial').classList.remove('hidden');
            const removeBtn = document.getElementById('add-avatar-remove-btn');
            if (removeBtn) {
                removeBtn.classList.add('hidden');
                removeBtn.classList.remove('inline-flex');
            }
            const btnText = document.getElementById('add-avatar-btn-text');
            if (btnText) btnText.textContent = 'Select Photo';
        }

        /* ---------------- Edit Admin modal ---------------- */

        function openEditAdminModal(btn) {
            const modal = document.getElementById('edit-admin-modal');
            const form = document.getElementById('edit-admin-form');

            document.getElementById('edit-avatar').value = '';
            document.getElementById('edit-remove-avatar').value = '0';

            if (btn) {
                form.action = btn.dataset.updateUrl;
                document.getElementById('edit-admin-action-field').value = btn.dataset.updateUrl;
                document.getElementById('edit-name').value = btn.dataset.name;
                document.getElementById('edit-email').value = btn.dataset.email;
                document.getElementById('edit-role').value = btn.dataset.roleValue || '{{ \Modules\Admin\Enums\UserRole::ADMIN->value }}';
                document.getElementById('edit-phone-number').value = btn.dataset.phoneNumber || '';
                document.getElementById('edit-location').value = btn.dataset.location || '';
                document.getElementById('edit-signee1-name').value = btn.dataset.signee1Name || '';
                document.getElementById('edit-signee1-title').value = btn.dataset.signee1Title || '';
                document.getElementById('edit-signee2-name').value = btn.dataset.signee2Name || '';
                document.getElementById('edit-signee2-title').value = btn.dataset.signee2Title || '';

                const img = document.getElementById('edit-avatar-preview-img');
                const initial = document.getElementById('edit-avatar-preview-initial');
                const removeBtn = document.getElementById('edit-avatar-remove-btn');
                const btnText = document.getElementById('edit-avatar-btn-text');
                const warning = document.getElementById('edit-avatar-warning');
                if (warning) {
                    warning.classList.add('hidden');
                    warning.textContent = '';
                }

                if (btn.dataset.avatar) {
                    img.src = btn.dataset.avatar;
                    img.classList.remove('hidden');
                    initial.classList.add('hidden');
                    removeBtn?.classList.remove('hidden');
                    removeBtn?.classList.add('inline-flex');
                    if (btnText) btnText.textContent = 'Change Photo';
                } else {
                    img.src = '';
                    img.classList.add('hidden');
                    initial.classList.remove('hidden');
                    initial.textContent = btn.dataset.initial || '?';
                    removeBtn?.classList.add('hidden');
                    removeBtn?.classList.remove('inline-flex');
                    if (btnText) btnText.textContent = 'Upload Photo';
                }
            }
            // When re-opened automatically after a validation error, the form
            // already has the posted values (action + old input) rendered server-side.

            syncEditRoleFields();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function syncEditRoleFields() {
            const role = document.getElementById('edit-role').value;
            const isAdmin = role === '{{ \Modules\Admin\Enums\UserRole::ADMIN->value }}';
            const locationField = document.getElementById('edit-admin-location-field');
            const signatoriesField = document.getElementById('edit-admin-signatories-field');
            const adminOnlyInputs = [
                'edit-location',
                'edit-signee1-name',
                'edit-signee1-title',
                'edit-signee2-name',
                'edit-signee2-title',
            ];

            [locationField, signatoriesField].forEach((field) => {
                field.classList.toggle('hidden', !isAdmin);
            });

            adminOnlyInputs.forEach((id) => {
                document.getElementById(id).disabled = !isAdmin;
            });
        }

        function previewEditAvatarFile(input) {
            const warning = document.getElementById('edit-avatar-warning');
            if (warning) {
                warning.classList.add('hidden');
                warning.textContent = '';
            }

            const file = input.files && input.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                if (warning) {
                    warning.textContent = 'Warning: Image size exceeds 5MB limit. Please select a smaller file.';
                    warning.classList.remove('hidden');
                } else {
                    alert('Warning: Image size exceeds 5MB limit.');
                }
                input.value = '';
                removeEditAvatar();
                return;
            }

            document.getElementById('edit-remove-avatar').value = '0';

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('edit-avatar-preview-img');
                img.src = e.target.result;
                img.classList.remove('hidden');
                document.getElementById('edit-avatar-preview-initial').classList.add('hidden');
                const removeBtn = document.getElementById('edit-avatar-remove-btn');
                if (removeBtn) {
                    removeBtn.classList.remove('hidden');
                    removeBtn.classList.add('inline-flex');
                }
                const btnText = document.getElementById('edit-avatar-btn-text');
                if (btnText) btnText.textContent = 'Change Photo';
            };
            reader.readAsDataURL(file);
        }

        function removeEditAvatar() {
            document.getElementById('edit-avatar').value = '';
            document.getElementById('edit-remove-avatar').value = '1';

            const warning = document.getElementById('edit-avatar-warning');
            if (warning) {
                warning.classList.add('hidden');
                warning.textContent = '';
            }

            const img = document.getElementById('edit-avatar-preview-img');
            img.src = '';
            img.classList.add('hidden');
            document.getElementById('edit-avatar-preview-initial').classList.remove('hidden');
            const removeBtn = document.getElementById('edit-avatar-remove-btn');
            if (removeBtn) {
                removeBtn.classList.add('hidden');
                removeBtn.classList.remove('inline-flex');
            }
            const btnText = document.getElementById('edit-avatar-btn-text');
            if (btnText) btnText.textContent = 'Upload Photo';
        }

        function closeEditAdminModal() {
            const modal = document.getElementById('edit-admin-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function handleEditAdminSubmit(event) {
            event.preventDefault();
            const form = document.getElementById('edit-admin-form');

            if (!form.reportValidity()) {
                return false;
            }

            const password = document.getElementById('edit-password').value;
            const confirmation = document.getElementById('edit-password_confirmation').value;
            if (password !== confirmation) {
                alert('Password and confirmation do not match.');
                return false;
            }

            document.getElementById('edit-confirm-name').textContent = document.getElementById('edit-name').value;
            document.getElementById('edit-confirm-email').textContent = document.getElementById('edit-email').value;
            document.getElementById('edit-confirm-password').textContent = password ? 'Will be changed' : 'Unchanged';

            const editAvatarFile = document.getElementById('edit-avatar').files[0];
            const removingAvatar = document.getElementById('edit-remove-avatar').value === '1';
            document.getElementById('edit-confirm-avatar').textContent = editAvatarFile
                ? editAvatarFile.name
                : (removingAvatar ? 'Will be removed' : 'Unchanged');

            document.getElementById('edit-admin-step-form').classList.add('hidden');
            document.getElementById('edit-admin-step-confirm').classList.remove('hidden');
            return false;
        }

        function backToEditAdminForm() {
            document.getElementById('edit-admin-step-confirm').classList.add('hidden');
            document.getElementById('edit-admin-step-form').classList.remove('hidden');
        }

        function confirmEditAdmin() {
            document.getElementById('edit-admin-form').submit();
        }

        /* ---------------- Live search ---------------- */

        let adminSearchTimer = null;
        let adminSearchController = null;

        function onAdminSearchInput() {
            const value = document.getElementById('admin-search-input').value;
            document.getElementById('admin-search-clear').classList.toggle('hidden', value.length === 0);

            clearTimeout(adminSearchTimer);
            adminSearchTimer = setTimeout(() => runAdminSearch(value), 300);
        }

        function clearAdminSearch(event) {
            event.preventDefault();
            document.getElementById('admin-search-input').value = '';
            document.getElementById('admin-search-clear').classList.add('hidden');
            runAdminSearch('');
            return false;
        }

        function runAdminSearch(term) {
            const url = new URL(window.location.href);
            if (term) {
                url.searchParams.set('search', term);
            } else {
                url.searchParams.delete('search');
            }

            const spinner = document.getElementById('admin-search-spinner');
            spinner.classList.remove('hidden');

            if (adminSearchController) {
                adminSearchController.abort();
            }
            adminSearchController = new AbortController();

            fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: adminSearchController.signal,
            })
                .then((response) => response.text())
                .then((html) => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const fresh = doc.getElementById('admin-results');
                    if (fresh) {
                        document.getElementById('admin-results').innerHTML = fresh.innerHTML;
                        applyAdminView(currentAdminViewMode());
                    }
                    window.history.replaceState({}, '', url.toString());
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        console.error('Admin search failed', error);
                    }
                })
                .finally(() => {
                    spinner.classList.add('hidden');
                });
        }

        function changePerPage(size) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', size);
            url.searchParams.set('page', '1');
            fetchAndSwapAdminResults(url);
        }

        function goToAdminPage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            fetchAndSwapAdminResults(url);
        }

        function fetchAndSwapAdminResults(url) {
            const spinner = document.getElementById('admin-search-spinner');
            if (spinner) spinner.classList.remove('hidden');

            fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then((response) => response.text())
                .then((html) => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const fresh = doc.getElementById('admin-results');
                    if (fresh) {
                        document.getElementById('admin-results').innerHTML = fresh.innerHTML;
                        applyAdminView(currentAdminViewMode());
                    }
                    window.history.replaceState({}, '', url.toString());
                })
                .catch((error) => console.error('Admin pagination failed', error))
                .finally(() => {
                    if (spinner) spinner.classList.add('hidden');
                });
        }
    </script>
@endsection
