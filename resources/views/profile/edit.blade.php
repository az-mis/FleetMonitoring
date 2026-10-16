@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
    @php
        $initial = strtoupper(substr($user->name, 0, 1));
        $locationLabel = $user->location ?: ($user->role?->value === 'super_admin' ? 'All Locations (Super Admin)' : 'Not assigned');
    @endphp

    <div class="space-y-4">
        {{-- Page header --}}
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">My Profile</h1>
            <p class="text-sm text-slate-500">Manage your personal account settings and credentials</p>
        </div>

        <form method="POST" action="{{ route('my-profile.update') }}" enctype="multipart/form-data" class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            @csrf
            @method('PUT')

            <div class="h-16 bg-gradient-to-br from-[#155f35] via-[#1d8148] to-[#269d59]"></div>

            <div class="-mt-9 px-5 pb-4 sm:px-6">
                <div class="flex flex-col items-center text-center">
                    <div id="avatar-container" class="relative flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-emerald-700 text-3xl font-extrabold text-white shadow-lg ring-1 ring-emerald-100">
                        <img id="avatar-preview-img" src="{{ $user->avatar_url ?? '' }}" alt="{{ $user->name }}" class="{{ $user->avatar_url ? '' : 'hidden' }} h-full w-full object-cover">
                        <span id="avatar-fallback" class="{{ $user->avatar_url ? 'hidden' : '' }} flex h-full w-full items-center justify-center">
                            {{ $initial }}
                        </span>
                    </div>
                    <input id="profile-avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" onchange="previewProfileAvatar(this)">
                    <input id="remove-avatar" type="checkbox" name="remove_avatar" value="1" class="hidden">
                    <p class="mt-1.5 text-xs text-gray-500">Photo: JPG, PNG or WebP (max 5MB)</p>
                    @error('avatar')<p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>@enderror

                    <h3 class="mt-1 text-lg font-extrabold text-gray-900">{{ $user->name }}</h3>
                    <p class="mt-0.5 text-sm text-gray-500">
                        <span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700">{{ $user->role?->label() }}</span>
                        <span class="mx-1">•</span><span class="break-all">{{ $user->email }}</span>
                    </p>

                    <div class="mt-2 flex flex-wrap items-center justify-center gap-2">
                        <label for="profile-avatar" id="profile-avatar-label" class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                                <path d="M3 21v-5h5"/>
                                <path d="M3 12a9 9 0 0 1 15.56-6.26L21 8"/>
                                <path d="M16 8h5V3"/>
                            </svg>
                            <span id="avatar-btn-text">{{ $user->avatar_url ? 'Replace' : 'Upload' }}</span>
                        </label>
                        <button type="button" id="avatar-remove-btn" onclick="removeProfileAvatar()" class="{{ $user->avatar_url ? 'inline-flex' : 'hidden' }} items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700 transition hover:bg-red-100 cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18"/>
                                <path d="M8 6V4h8v2"/>
                                <path d="m19 6-1 14H6L5 6"/>
                            </svg>
                            Remove
                        </button>
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
                                <label for="profile-name" class="mb-1 flex items-center gap-1 text-[11px] font-bold text-gray-700">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input id="profile-name" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @error('name')<p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="profile-phone" class="mb-1 flex items-center gap-1 text-[11px] font-bold text-gray-700">
                                    Phone Number <span class="text-red-500">*</span>
                                </label>
                                <input id="profile-phone" type="tel" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @error('phone_number')<p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="profile-email" class="mb-1 flex items-center gap-1 text-[11px] font-bold text-gray-700">
                                    Email Address
                                </label>
                                <div class="relative">
                                    <input id="profile-email" value="{{ $user->email }}" disabled class="w-full rounded-lg border border-gray-200 bg-gray-100/70 px-3 py-2 pr-8 text-xs text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="18" height="11" x="3" y="11" rx="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </div>
                                <p class="mt-1 text-[10px] text-gray-500">Account email is managed by your administrator.</p>
                            </div>

                            <div>
                                <label for="profile-location" class="mb-1 flex items-center gap-1 text-[11px] font-bold text-gray-700">
                                    Assigned Location / Office Province
                                </label>
                                <div class="relative">
                                    <input id="profile-location" value="{{ $locationLabel }}" disabled class="w-full rounded-lg border border-gray-200 bg-gray-100/70 px-3 py-2 pr-8 text-xs text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="18" height="11" x="3" y="11" rx="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </div>
                                <p class="mt-1 text-[10px] text-gray-500">Office assignment is managed by your administrator.</p>
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
                                <label for="profile-birth-date" class="mb-1 block text-[11px] font-bold text-gray-700">Birth Date</label>
                                <input id="profile-birth-date" type="date" name="birth_date" value="{{ old('birth_date', optional($user->birth_date)->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @error('birth_date')<p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="profile-address" class="mb-1 block text-[11px] font-bold text-gray-700">Residential Address</label>
                                <input id="profile-address" name="address" maxlength="255" value="{{ old('address', $user->address) }}" placeholder="e.g. Calapan City, Oriental Mindoro" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @error('address')<p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="profile-license-number" class="mb-1 block text-[11px] font-bold text-gray-700">Driver's License Number</label>
                                <div class="relative">
                                    <input id="profile-license-number" value="{{ $user->license_number ?: 'Not set' }}" disabled class="w-full rounded-lg border border-gray-200 bg-gray-100/70 px-3 py-2 pr-8 text-xs text-gray-600 font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="18" height="11" x="3" y="11" rx="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </div>
                            </div>

                            <div>
                                <label for="profile-license-expiration" class="mb-1 block text-[11px] font-bold text-gray-700">Driver's License Expiration</label>
                                <div class="relative">
                                    @php
                                        $expDate = $user->license_expiration_date;
                                        $daysLeft = $expDate ? (int) round(now()->startOfDay()->diffInDays($expDate->startOfDay(), false)) : null;
                                        $isExp = !is_null($daysLeft) && $daysLeft < 0;
                                        $isUrgent = !is_null($daysLeft) && $daysLeft >= 0 && $daysLeft <= 7;
                                        $isWarning = !is_null($daysLeft) && $daysLeft > 7 && $daysLeft <= 30;
                                    @endphp
                                    <input id="profile-license-expiration" value="{{ optional($expDate)->format('Y-m-d') ?: 'Not set' }}" disabled class="w-full rounded-lg border {{ $isExp || $isUrgent ? 'border-red-300 bg-red-50/50' : ($isWarning ? 'border-amber-300 bg-amber-50/50' : 'border-gray-200 bg-gray-100/70') }} px-3 py-2 pr-32 text-xs text-gray-700 font-semibold">
                                    @if($expDate)
                                        <span class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-0.5 text-[9px] font-extrabold uppercase {{ $isExp ? 'bg-red-600 text-white' : ($isUrgent ? 'bg-red-600 text-white animate-pulse' : ($isWarning ? 'bg-amber-500 text-white' : 'bg-emerald-100 text-emerald-800')) }}">
                                            @if($isExp)
                                                EXPIRED
                                            @elseif($isUrgent || $isWarning)
                                                EXPIRES IN {{ $daysLeft }} {{ Illuminate\Support\Str::plural('DAY', $daysLeft) }}
                                            @else
                                                ✓ VALID
                                            @endif
                                        </span>
                                    @endif
                                </div>
                                @if($isExp)
                                    <p class="mt-1.5 flex items-center gap-1.5 text-[11px] font-bold text-red-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        License expired on {{ $expDate->format('M d, Y') }}! Please submit your renewed license to your administrator.
                                    </p>
                                @elseif($isUrgent)
                                    <p class="mt-1.5 flex items-center gap-1.5 text-[11px] font-bold text-red-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        Urgent: Your license expires in {{ $daysLeft }} {{ Illuminate\Support\Str::plural('day', $daysLeft) }} ({{ $expDate->format('M d, Y') }})!
                                    </p>
                                @elseif($isWarning)
                                    <p class="mt-1.5 flex items-center gap-1.5 text-[11px] font-bold text-amber-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        Notice: License expires in {{ $daysLeft }} {{ Illuminate\Support\Str::plural('day', $daysLeft) }} ({{ $expDate->format('M d, Y') }}).
                                    </p>
                                @else
                                    <p class="mt-1 text-[10px] text-gray-500">Managed and verified by your fleet administrator.</p>
                                @endif
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

    <script>
        (function () {
            const avatarInput = document.getElementById('profile-avatar');
            const avatarLabel = document.getElementById('profile-avatar-label');
            if (!avatarInput || !avatarLabel) return;
            avatarInput.addEventListener('focus', () => avatarLabel.classList.add('ring-2', 'ring-emerald-500', 'ring-offset-1'));
            avatarInput.addEventListener('blur', () => avatarLabel.classList.remove('ring-2', 'ring-emerald-500', 'ring-offset-1'));
        })();

        function previewProfileAvatar(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];

            if (file.size > 5 * 1024 * 1024) {
                alert('The selected image exceeds 5MB. Please choose a smaller photo.');
                input.value = '';
                return;
            }

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                alert('Please choose a JPG, PNG or WebP image.');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.getElementById('avatar-preview-img');
                const fallback = document.getElementById('avatar-fallback');
                const removeBtn = document.getElementById('avatar-remove-btn');
                const removeInput = document.getElementById('remove-avatar');
                const btnText = document.getElementById('avatar-btn-text');

                if (img) {
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                }
                if (fallback) {
                    fallback.classList.add('hidden');
                }
                if (removeBtn) {
                    removeBtn.classList.remove('hidden');
                    removeBtn.classList.add('inline-flex');
                }
                if (removeInput) {
                    removeInput.checked = false;
                }
                if (btnText) {
                    btnText.textContent = 'Replace';
                }
            };
            reader.readAsDataURL(file);
        }

        function removeProfileAvatar() {
            const fileInput = document.getElementById('profile-avatar');
            const removeInput = document.getElementById('remove-avatar');
            const img = document.getElementById('avatar-preview-img');
            const fallback = document.getElementById('avatar-fallback');
            const removeBtn = document.getElementById('avatar-remove-btn');
            const btnText = document.getElementById('avatar-btn-text');

            if (fileInput) fileInput.value = '';
            if (removeInput) removeInput.checked = true;

            if (img) {
                img.src = '';
                img.classList.add('hidden');
            }
            if (fallback) {
                fallback.classList.remove('hidden');
            }
            if (removeBtn) {
                removeBtn.classList.add('hidden');
                removeBtn.classList.remove('inline-flex');
            }
            if (btnText) {
                btnText.textContent = 'Upload';
            }
        }
    </script>
@endsection
