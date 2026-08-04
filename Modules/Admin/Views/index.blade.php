@extends('layouts.app')

@section('title', 'Admins')
@section('page-title', 'Admins')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Manage Admins</h2>
            <p class="mt-1 text-sm text-gray-500">View, create, update, and remove admin accounts.</p>
        </div>

        <button
            type="button"
            onclick="openAddAdminModal()"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Admin
        </button>
    </div>

    {{-- Toolbar: search + view toggle --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.index') }}" id="admin-search-form" class="flex w-full max-w-sm items-center gap-2" onsubmit="return false;">
            <div class="relative w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input
                    type="text"
                    name="search"
                    id="admin-search-input"
                    value="{{ request('search') }}"
                    autocomplete="off"
                    placeholder="Search by name or email…"
                    oninput="onAdminSearchInput()"
                    class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-8 text-sm placeholder-gray-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                >
                <svg id="admin-search-spinner" xmlns="http://www.w3.org/2000/svg" class="hidden absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
            </div>

            <a href="{{ route('admin.index') }}" id="admin-search-clear" onclick="return clearAdminSearch(event)" class="shrink-0 text-sm font-medium text-gray-500 hover:text-gray-700 {{ request('search') ? '' : 'hidden' }}">Clear</a>
        </form>

        {{-- View toggle --}}
        <div class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white p-1 self-start">
            <button
                type="button"
                id="view-toggle-list"
                onclick="setAdminView('list')"
                class="view-toggle-btn flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                List
            </button>
            <button
                type="button"
                id="view-toggle-grid"
                onclick="setAdminView('grid')"
                class="view-toggle-btn flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                Grid
            </button>
        </div>
    </div>

    <div id="admin-results">
        @include('admin::partials.admin-results', ['admins' => $admins])
    </div>

    {{-- Add Admin modal --}}
    <div id="add-admin-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl my-auto">

            {{-- Step 1: form --}}
            <div id="add-admin-step-form">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">New admin account</h2>
                    <button type="button" onclick="closeAddAdminModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-sm text-gray-500">Create a new admin user.</p>

                <form method="POST" action="{{ route('admin.store') }}" id="add-admin-form" class="mt-5 space-y-4" enctype="multipart/form-data" onsubmit="return handleAddAdminSubmit(event)">
                    @csrf
                    <input type="hidden" name="role" value="{{ \Modules\Admin\Enums\UserRole::ADMIN->value }}">
                    <input type="hidden" name="_admin_form" value="create">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Profile picture <span class="font-normal text-gray-400">(optional)</span></label>
                        <div class="mt-1.5 flex items-center gap-4">
                            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-gray-400">
                                <img id="add-avatar-preview-img" src="" alt="" class="hidden h-full w-full object-cover">
                                <svg id="add-avatar-preview-icon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>
                                </svg>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="add-avatar" class="inline-flex w-fit cursor-pointer items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                    Choose photo
                                </label>
                                <input type="file" name="avatar" id="add-avatar" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewAvatarFile(this, 'add-avatar-preview-img', 'add-avatar-preview-icon')">
                                <span class="text-xs text-gray-400">JPG, PNG or WEBP, up to 2MB.</span>
                            </div>
                        </div>
                        @error('avatar')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="add-name" class="block text-sm font-medium text-gray-700">Full name</label>
                        <input
                            type="text"
                            name="name"
                            id="add-name"
                            value="{{ old('_admin_form') === 'create' ? old('name') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-red-300 @enderror"
                            required
                        >
                        @error('name')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="add-email" class="block text-sm font-medium text-gray-700">Email address</label>
                        <input
                            type="email"
                            name="email"
                            id="add-email"
                            value="{{ old('_admin_form') === 'create' ? old('email') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('email') border-red-300 @enderror"
                            required
                        >
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <div class="mt-1.5 flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5">
                            <x-admin::role-badge :role="\Modules\Admin\Enums\UserRole::ADMIN" />
                            <span class="text-sm text-gray-500">This form only creates Admin accounts.</span>
                        </div>
                    </div>

                    <div>
                        <label for="add-password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input
                            type="password"
                            name="password"
                            id="add-password"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('password') border-red-300 @enderror"
                            required
                        >
                        @error('password')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="add-password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="add-password_confirmation"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            required
                        >
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" onclick="closeAddAdminModal()" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition"
                        >
                            Continue
                        </button>
                    </div>
                </form>
            </div>

            {{-- Step 2: confirmation --}}
            <div id="add-admin-step-confirm" class="hidden">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-gray-900">Add this admin?</h2>
                <p class="mt-1 text-sm text-gray-500">Please confirm the details below before creating the account.</p>

                <dl class="mt-4 space-y-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Name</dt>
                        <dd id="add-confirm-name" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Email</dt>
                        <dd id="add-confirm-email" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Role</dt>
                        <dd class="text-sm font-medium text-gray-900">Admin</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Photo</dt>
                        <dd id="add-confirm-avatar" class="text-sm font-medium text-gray-900"></dd>
                    </div>
                </dl>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="backToAddAdminForm()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Back
                    </button>
                    <button type="button" onclick="confirmAddAdmin()" class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition">
                        Yes, add admin
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any() && old('_admin_form') === 'create')
        <script>document.addEventListener('DOMContentLoaded', () => openAddAdminModal());</script>
    @endif

    {{-- Edit Admin modal --}}
    <div id="edit-admin-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6 overflow-y-auto">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl my-auto">

            {{-- Step 1: form --}}
            <div id="edit-admin-step-form">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Edit admin account</h2>
                    <button type="button" onclick="closeEditAdminModal()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <p id="edit-admin-subtitle" class="mt-1 text-sm text-gray-500">Leave password blank to keep it unchanged.</p>

                <form method="POST" action="{{ old('_admin_action', route('admin.index')) }}" id="edit-admin-form" class="mt-5 space-y-4" enctype="multipart/form-data" onsubmit="return handleEditAdminSubmit(event)">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="role" value="{{ \Modules\Admin\Enums\UserRole::ADMIN->value }}">
                    <input type="hidden" name="_admin_form" value="edit">
                    <input type="hidden" name="_admin_action" id="edit-admin-action-field" value="{{ old('_admin_action') }}">
                    <input type="hidden" name="remove_avatar" id="edit-remove-avatar" value="0">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Profile picture <span class="font-normal text-gray-400">(optional)</span></label>
                        <div class="mt-1.5 flex items-center gap-4">
                            <div class="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-50 text-lg font-semibold text-emerald-700">
                                <img id="edit-avatar-preview-img" src="" alt="" class="hidden h-full w-full object-cover">
                                <span id="edit-avatar-preview-initial"></span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-3">
                                    <label for="edit-avatar" class="inline-flex w-fit cursor-pointer items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                        Choose photo
                                    </label>
                                    <button type="button" id="edit-avatar-remove-btn" onclick="removeEditAvatar()" class="hidden text-sm font-medium text-red-600 hover:text-red-700">
                                        Remove
                                    </button>
                                </div>
                                <input type="file" name="avatar" id="edit-avatar" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewEditAvatarFile(this)">
                                <span class="text-xs text-gray-400">JPG, PNG or WEBP, up to 2MB.</span>
                            </div>
                        </div>
                        @error('avatar')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-name" class="block text-sm font-medium text-gray-700">Full name</label>
                        <input
                            type="text"
                            name="name"
                            id="edit-name"
                            value="{{ old('_admin_form') === 'edit' ? old('name') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-red-300 @enderror"
                            required
                        >
                        @error('name')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-email" class="block text-sm font-medium text-gray-700">Email address</label>
                        <input
                            type="email"
                            name="email"
                            id="edit-email"
                            value="{{ old('_admin_form') === 'edit' ? old('email') : '' }}"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('email') border-red-300 @enderror"
                            required
                        >
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <div class="mt-1.5 flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5">
                            <x-admin::role-badge :role="\Modules\Admin\Enums\UserRole::ADMIN" />
                            <span class="text-sm text-gray-500">This form only manages Admin accounts.</span>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <label for="edit-password" class="block text-sm font-medium text-gray-700">New password <span class="font-normal text-gray-400">(optional)</span></label>
                        <input
                            type="password"
                            name="password"
                            id="edit-password"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('password') border-red-300 @enderror"
                        >
                        @error('password')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="edit-password_confirmation"
                            class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                        <button type="button" onclick="closeEditAdminModal()" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition"
                        >
                            Continue
                        </button>
                    </div>
                </form>
            </div>

            {{-- Step 2: confirmation --}}
            <div id="edit-admin-step-confirm" class="hidden">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-gray-900">Save these changes?</h2>
                <p class="mt-1 text-sm text-gray-500">Please confirm the updated details below.</p>

                <dl class="mt-4 space-y-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Name</dt>
                        <dd id="edit-confirm-name" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Email</dt>
                        <dd id="edit-confirm-email" class="truncate text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Password</dt>
                        <dd id="edit-confirm-password" class="text-sm font-medium text-gray-900"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-sm text-gray-500">Photo</dt>
                        <dd id="edit-confirm-avatar" class="text-sm font-medium text-gray-900"></dd>
                    </div>
                </dl>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="backToEditAdminForm()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Back
                    </button>
                    <button type="button" onclick="confirmEditAdmin()" class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition">
                        Yes, save changes
                    </button>
                </div>
            </div>
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
        .view-toggle-btn { color: #6b7280; }
        .view-toggle-btn.active { background: linear-gradient(to right, #059669, #16a34a); color: #fff; }
    </style>

    <script>
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
            document.getElementById('add-admin-step-confirm').classList.add('hidden');
            document.getElementById('add-admin-step-form').classList.remove('hidden');
            resetAvatarPreview('add-avatar', 'add-avatar-preview-img', 'add-avatar-preview-icon');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAddAdminModal() {
            const modal = document.getElementById('add-admin-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function resetAvatarPreview(inputId, imgId, iconId) {
            document.getElementById(inputId).value = '';
            document.getElementById(imgId).src = '';
            document.getElementById(imgId).classList.add('hidden');
            document.getElementById(iconId).classList.remove('hidden');
        }

        function previewAvatarFile(input, imgId, iconId) {
            const file = input.files && input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById(imgId);
                img.src = e.target.result;
                img.classList.remove('hidden');
                document.getElementById(iconId).classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        function handleAddAdminSubmit(event) {
            event.preventDefault();
            const form = document.getElementById('add-admin-form');

            if (!form.reportValidity()) {
                return false;
            }

            const password = document.getElementById('add-password').value;
            const confirmation = document.getElementById('add-password_confirmation').value;
            if (password !== confirmation) {
                alert('Password and confirmation do not match.');
                return false;
            }

            document.getElementById('add-confirm-name').textContent = document.getElementById('add-name').value;
            document.getElementById('add-confirm-email').textContent = document.getElementById('add-email').value;
            const addAvatarFile = document.getElementById('add-avatar').files[0];
            document.getElementById('add-confirm-avatar').textContent = addAvatarFile ? addAvatarFile.name : 'None';

            document.getElementById('add-admin-step-form').classList.add('hidden');
            document.getElementById('add-admin-step-confirm').classList.remove('hidden');
            return false;
        }

        function backToAddAdminForm() {
            document.getElementById('add-admin-step-confirm').classList.add('hidden');
            document.getElementById('add-admin-step-form').classList.remove('hidden');
        }

        function confirmAddAdmin() {
            document.getElementById('add-admin-form').submit();
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
                document.getElementById('edit-admin-subtitle').textContent = "Update " + btn.dataset.name + "'s details. Leave password blank to keep it unchanged.";
                document.getElementById('edit-password').value = '';
                document.getElementById('edit-password_confirmation').value = '';

                const img = document.getElementById('edit-avatar-preview-img');
                const initial = document.getElementById('edit-avatar-preview-initial');
                const removeBtn = document.getElementById('edit-avatar-remove-btn');
                if (btn.dataset.avatar) {
                    img.src = btn.dataset.avatar;
                    img.classList.remove('hidden');
                    initial.classList.add('hidden');
                    removeBtn.classList.remove('hidden');
                } else {
                    img.src = '';
                    img.classList.add('hidden');
                    initial.classList.remove('hidden');
                    initial.textContent = btn.dataset.initial || '';
                    removeBtn.classList.add('hidden');
                }
            }
            // When re-opened automatically after a validation error, the form
            // already has the posted values (action + old input) rendered server-side.

            document.getElementById('edit-admin-step-confirm').classList.add('hidden');
            document.getElementById('edit-admin-step-form').classList.remove('hidden');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function previewEditAvatarFile(input) {
            const file = input.files && input.files[0];
            if (!file) return;

            document.getElementById('edit-remove-avatar').value = '0';

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('edit-avatar-preview-img');
                img.src = e.target.result;
                img.classList.remove('hidden');
                document.getElementById('edit-avatar-preview-initial').classList.add('hidden');
                document.getElementById('edit-avatar-remove-btn').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        function removeEditAvatar() {
            document.getElementById('edit-avatar').value = '';
            document.getElementById('edit-remove-avatar').value = '1';

            const img = document.getElementById('edit-avatar-preview-img');
            img.src = '';
            img.classList.add('hidden');
            document.getElementById('edit-avatar-preview-initial').classList.remove('hidden');
            document.getElementById('edit-avatar-remove-btn').classList.add('hidden');
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
    </script>
@endsection
