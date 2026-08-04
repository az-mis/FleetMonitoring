@extends('layouts.app')

@section('title', 'Edit Admin')
@section('page-title', 'Edit Admin')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Back to Admins
        </a>
    </div>

    <div class="max-w-xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">Edit admin account</h2>
        <p class="mt-1 text-sm text-gray-500">Update {{ $admin->name }}'s details. Leave password blank to keep it unchanged.</p>

        <form method="POST" action="{{ route('admin.update', $admin) }}" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name', $admin->name) }}"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-red-300 @enderror"
                    required
                >
                @error('name')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email', $admin->email) }}"
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
                    <span class="text-sm text-gray-500">This page only manages Admin accounts.</span>
                </div>
                <input type="hidden" name="role" value="{{ \Modules\Admin\Enums\UserRole::ADMIN->value }}">
            </div>

            <div class="border-t border-gray-100 pt-5">
                <label for="password" class="block text-sm font-medium text-gray-700">New password <span class="font-normal text-gray-400">(optional)</span></label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('password') border-red-300 @enderror"
                >
                @error('password')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                <input
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                >
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                <a href="{{ route('admin.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button
                    type="submit"
                    class="rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-700 hover:to-green-700 transition"
                >
                    Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection
