@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
    @php
        $initial = strtoupper(substr($user->name, 0, 1));
        $locationLabel = $user->location ?: ($user->role?->value === 'super_admin' ? 'All Locations (Super Admin)' : 'Not assigned');
    @endphp

    <div class="space-y-4">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#155f35] via-[#1d8148] to-[#269d59] px-5 py-3 text-white shadow-lg shadow-emerald-900/15 sm:px-6">
            <div class="absolute -right-8 -top-12 h-40 w-40 rounded-full bg-white/10"></div>
            <div class="relative flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-extrabold tracking-tight">My Profile</h2>
                    <p class="mt-0.5 text-xs text-white/80">Manage your personal account settings and credentials.</p>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('my-profile.update') }}" enctype="multipart/form-data" class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            @csrf
            @method('PUT')

            <div class="h-16 bg-gradient-to-br from-[#155f35] via-[#1d8148] to-[#269d59]"></div>

            <div class="-mt-9 px-5 pb-4 sm:px-6">
                <div class="flex flex-col items-center text-center">
                    <div class="relative flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-emerald-700 text-3xl font-extrabold text-white shadow-lg ring-1 ring-emerald-100">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                        @else
                            {{ $initial }}
                        @endif
                    </div>
                    <input id="profile-avatar" type="file" name="avatar" accept="image/*" class="hidden">
                    <input id="remove-avatar" type="checkbox" name="remove_avatar" value="1" class="hidden">
                    <p class="mt-1.5 text-xs text-gray-500">Photo (max 5MB)</p>

                    <h3 class="mt-1 text-lg font-extrabold text-gray-900">{{ $user->name }}</h3>
                    <p class="mt-0.5 text-sm text-gray-500">
                        <span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700">{{ $user->role?->label() }}</span>
                        <span class="mx-1">•</span>{{ $user->email }}
                    </p>

                    <div class="mt-2 flex flex-wrap items-center justify-center gap-2">
                        <label for="profile-avatar" class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                                <path d="M3 21v-5h5"/>
                                <path d="M3 12a9 9 0 0 1 15.56-6.26L21 8"/>
                                <path d="M16 8h5V3"/>
                            </svg>
                            Replace
                        </label>
                        @if($user->avatar_url)
                            <button type="button" onclick="document.getElementById('remove-avatar').checked = true; this.classList.add('bg-red-600','text-white'); this.classList.remove('bg-red-50','text-red-700');" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700 transition hover:bg-red-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18"/>
                                    <path d="M8 6V4h8v2"/>
                                    <path d="m19 6-1 14H6L5 6"/>
                                </svg>
                                Remove
                            </button>
                        @endif
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2 text-left">
                    {{-- Left Column: ACCOUNT CREDENTIALS --}}
                    <section class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <div class="mb-3 border-b border-gray-200/60 pb-2">
                            <h4 class="flex items-center gap-1.5 text-xs font-extrabold uppercase tracking-wider text-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>
                                </svg>
                                Account Credentials
                            </h4>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="mb-1 flex items-center gap-1 text-[11px] font-bold text-gray-700">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @error('name')<p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-1 flex items-center gap-1 text-[11px] font-bold text-gray-700">
                                    Phone Number <span class="text-red-500">*</span>
                                </label>
                                <input name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @error('phone_number')<p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-1 flex items-center gap-1 text-[11px] font-bold text-gray-700">
                                    Email Address
                                </label>
                                <div class="relative">
                                    <input value="{{ $user->email }}" disabled class="w-full rounded-lg border border-gray-200 bg-gray-100/70 px-3 py-2 pr-8 text-xs text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="18" height="11" x="3" y="11" rx="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </div>
                                <p class="mt-1 text-[10px] text-gray-400">Account email is managed by your administrator.</p>
                            </div>

                            <div>
                                <label class="mb-1 flex items-center gap-1 text-[11px] font-bold text-gray-700">
                                    Assigned Location / Office Province
                                </label>
                                <div class="relative">
                                    <input value="{{ $locationLabel }}" disabled class="w-full rounded-lg border border-gray-200 bg-gray-100/70 px-3 py-2 pr-8 text-xs text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="18" height="11" x="3" y="11" rx="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </div>
                                <p class="mt-1 text-[10px] text-gray-400">Office assignment is managed by your administrator.</p>
                            </div>
                        </div>
                    </section>

                    {{-- Right Column: DRIVER DETAILS --}}
                    <section class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <div class="mb-3 border-b border-gray-200/60 pb-2">
                            <h4 class="flex items-center gap-1.5 text-xs font-extrabold uppercase tracking-wider text-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                </svg>
                                Driver Details
                            </h4>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-gray-700">Birth Date</label>
                                <input type="date" name="birth_date" value="{{ old('birth_date', optional($user->birth_date)->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-gray-700">Residential Address</label>
                                <input name="address" value="{{ old('address', $user->address) }}" placeholder="e.g. Calapan City, Oriental Mindoro" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-gray-700">Driver's License Expiration</label>
                                <div class="relative">
                                    @php
                                        $expDate = $user->license_expiration_date;
                                        $daysLeft = $expDate ? now()->startOfDay()->diffInDays($expDate->startOfDay(), false) : null;
                                        $isExp = !is_null($daysLeft) && $daysLeft < 0;
                                    @endphp
                                    <input value="{{ optional($expDate)->format('Y-m-d') ?: 'Not set' }}" disabled class="w-full rounded-lg border border-gray-200 bg-gray-100/70 px-3 py-2 pr-16 text-xs text-gray-600">
                                    @if($expDate)
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase {{ $isExp ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800' }}">
                                            ✓ {{ $isExp ? 'Expired' : 'Valid' }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-[10px] text-gray-400">Managed and verified by your fleet administrator.</p>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="mt-5 flex justify-end border-t border-gray-100 pt-4">
                    <button type="submit" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-extrabold text-white shadow-md hover:bg-emerald-800 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
