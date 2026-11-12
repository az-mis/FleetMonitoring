@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
    @php
        $initial = strtoupper(substr($user->name, 0, 1));
        $locationLabel = $user->location ?: ($user->role?->value === 'super_admin' ? 'All Locations (Super Admin)' : 'Not assigned');

        // Same field styles as the Edit Driver form
        $pfLabel = 'mb-1.5 block text-[0.8125rem] font-semibold text-slate-700';
        $pfInput = 'h-11 w-full rounded-lg border bg-white px-3 text-sm text-slate-800 placeholder-slate-400 transition focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600';
        $pfBorder = fn (string $field, string $bag = 'default') => $errors->getBag($bag)->has($field) ? 'border-red-400' : 'border-slate-200';
        $pfLocked = 'h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 pr-9 text-sm text-slate-500';
        $pfHint = 'mt-1.5 text-xs leading-snug text-slate-500';

        $expDate = $user->license_expiration_date;
        $daysLeft = $expDate ? (int) round(now()->startOfDay()->diffInDays($expDate->copy()->startOfDay(), false)) : null;
        $isExp = ! is_null($daysLeft) && $daysLeft < 0;
        $isUrgent = ! is_null($daysLeft) && $daysLeft >= 0 && $daysLeft <= 7;
        $isWarning = ! is_null($daysLeft) && $daysLeft > 7 && $daysLeft <= 30;
    @endphp

    <div class="space-y-5">
        {{-- Page header --}}
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">My Profile</h1>
            <p class="text-sm text-slate-500">Manage your personal account settings and credentials</p>
        </div>

        <form method="POST" action="{{ route('my-profile.update') }}" enctype="multipart/form-data" class="mx-auto max-w-4xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @method('PUT')
            <input id="remove-avatar" type="checkbox" name="remove_avatar" value="1" class="hidden">

            {{-- Header: who you are --}}
            <div class="flex items-center gap-3 bg-[#005a36] px-5 py-4 text-white sm:px-6">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-[#005a36]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-bold leading-tight tracking-tight sm:text-xl">{{ $user->name }}</h2>
                    <p class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-xs text-white/85 sm:text-[0.8125rem]">
                        <span class="rounded-full bg-white/15 px-2 py-0.5 font-semibold text-white ring-1 ring-white/25">{{ $user->role?->label() }}</span>
                        <span class="truncate">{{ $user->email }}</span>
                    </p>
                </div>
            </div>

            <div class="px-5 py-5 sm:px-6 sm:py-6">
                <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-12">

                    {{-- Photo: the largest thing on the page --}}
                    <div class="sm:col-span-5">
                        <p class="mb-2 text-sm font-bold text-slate-900">Profile Photo</p>

                        <div id="avatar-container" class="relative flex h-56 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-linear-to-b from-emerald-50 to-slate-100 sm:h-64">
                            <div id="avatar-fallback" class="{{ $user->avatar_url ? 'hidden' : 'flex' }} h-28 w-28 items-center justify-center rounded-full bg-white text-5xl font-bold text-emerald-800 shadow-sm ring-4 ring-emerald-100">
                                {{ $initial }}
                            </div>
                            <img id="avatar-preview-img" src="{{ $user->avatar_url ?? '' }}" alt="{{ $user->name }}" class="{{ $user->avatar_url ? '' : 'hidden' }} absolute inset-0 h-full w-full object-cover">
                            <button type="button" id="avatar-remove-btn" onclick="removeProfileAvatar()" title="Remove photo" aria-label="Remove photo" class="{{ $user->avatar_url ? 'flex' : 'hidden' }} absolute right-2 top-2 h-8 w-8 items-center justify-center rounded-full bg-white text-red-600 shadow-md ring-1 ring-black/5 transition hover:bg-red-50 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                            </button>
                        </div>

                        <input id="profile-avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="peer sr-only" onchange="previewProfileAvatar(this)">
                        <label
                            for="profile-avatar"
                            id="profile-avatar-label"
                            ondragover="handleProfilePhotoDrag(event, true)"
                            ondragleave="handleProfilePhotoDrag(event, false)"
                            ondrop="handleProfilePhotoDrop(event)"
                            class="mt-3 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-3 py-3.5 text-center transition hover:border-emerald-500 hover:bg-emerald-50/40 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500"
                        >
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                                <span id="avatar-btn-text">{{ $user->avatar_url ? 'Change Photo' : 'Upload Photo' }}</span>
                            </span>
                            <span class="text-xs text-slate-500">Click to upload or drag and drop</span>
                            <span class="text-[0.6875rem] text-slate-400">JPG, PNG, WEBP (Max 5MB)</span>
                        </label>
                        @error('avatar')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Name / Phone / Email / Location --}}
                    <div class="grid grid-cols-1 content-start gap-x-4 gap-y-4 sm:col-span-7">
                        <div>
                            <label for="profile-name" class="{{ $pfLabel }}">Full Name <span class="text-red-500">*</span></label>
                            <input id="profile-name" name="name" value="{{ old('name', $user->name) }}" required class="{{ $pfInput }} {{ $pfBorder('name') }}">
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="profile-phone" class="{{ $pfLabel }}">Phone Number <span class="text-red-500">*</span></label>
                            <input id="profile-phone" type="tel" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" required @if ($user->role === \Modules\Admin\Enums\UserRole::DRIVER) pattern="09[0-9]{9}" maxlength="11" inputmode="numeric" placeholder="09171234567" title="Must start with 09 and be exactly 11 digits (e.g. 09171234567)" @endif class="{{ $pfInput }} {{ $pfBorder('phone_number') }}">
                            @error('phone_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="profile-email" class="{{ $pfLabel }}">Email Address</label>
                            <div class="relative">
                                <input id="profile-email" value="{{ $user->email }}" disabled class="{{ $pfLocked }}">
                                @include('profile.partials.lock-icon')
                            </div>
                            <p class="{{ $pfHint }}">Account email is managed by your administrator.</p>
                        </div>

                        <div>
                            <label for="profile-location" class="{{ $pfLabel }}">Assigned Location / Office Province</label>
                            <div class="relative">
                                <input id="profile-location" value="{{ $locationLabel }}" disabled class="{{ $pfLocked }}">
                                @include('profile.partials.lock-icon')
                            </div>
                            <p class="{{ $pfHint }}">Office assignment is managed by your administrator.</p>
                        </div>
                    </div>

                    {{-- Birth date / Address --}}
                    <div class="sm:col-span-6">
                        <label for="profile-birth-date" class="{{ $pfLabel }}">Birth Date <span class="font-normal text-slate-400">(Optional)</span></label>
                        <input id="profile-birth-date" type="date" name="birth_date" value="{{ old('birth_date', optional($user->birth_date)->format('Y-m-d')) }}" max="{{ now()->subDay()->toDateString() }}" class="{{ $pfInput }} {{ $pfBorder('birth_date') }}">
                        @error('birth_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-6">
                        <label for="profile-address" class="{{ $pfLabel }}">Residential Address <span class="font-normal text-slate-400">(Optional)</span></label>
                        <input id="profile-address" name="address" maxlength="255" value="{{ old('address', $user->address) }}" placeholder="e.g. Calapan City, Oriental Mindoro" class="{{ $pfInput }} {{ $pfBorder('address') }}">
                        @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Driver's license (read-only, kept by the administrator) --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:col-span-12">
                        <div class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><circle cx="8" cy="12" r="2"/><path d="M14 10h4M14 14h4"/></svg>
                            <span>Driver's License <span class="font-normal text-slate-400">(Managed by your fleet administrator)</span></span>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="profile-license-number" class="{{ $pfLabel }}">License Number</label>
                                <div class="relative">
                                    <input id="profile-license-number" value="{{ $user->license_number ?: 'Not set' }}" disabled class="{{ $pfLocked }} font-medium text-slate-600">
                                    @include('profile.partials.lock-icon')
                                </div>
                            </div>
                            <div>
                                <label for="profile-license-expiration" class="{{ $pfLabel }}">License Expiration</label>
                                <div class="relative">
                                    <input id="profile-license-expiration" value="{{ optional($expDate)->format('M d, Y') ?: 'Not set' }}" disabled class="h-11 w-full rounded-lg border {{ $isExp || $isUrgent ? 'border-red-300 bg-red-50/60' : ($isWarning ? 'border-amber-300 bg-amber-50/60' : 'border-slate-200 bg-slate-50') }} px-3 pr-36 text-sm font-semibold text-slate-700">
                                    @if ($expDate)
                                        <span class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-0.5 text-[0.625rem] font-extrabold uppercase {{ $isExp ? 'bg-red-600 text-white' : ($isUrgent ? 'animate-pulse bg-red-600 text-white' : ($isWarning ? 'bg-amber-500 text-white' : 'bg-emerald-100 text-emerald-800')) }}">
                                            @if ($isExp)
                                                Expired
                                            @elseif ($isUrgent || $isWarning)
                                                Expires in {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }}
                                            @else
                                                ✓ Valid
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if ($isExp)
                            <p class="mt-3 flex items-center gap-1.5 text-xs font-bold text-red-600">
                                @include('profile.partials.warning-icon', ['color' => 'text-red-600'])
                                License expired on {{ $expDate->format('M d, Y') }}! Please submit your renewed license to your administrator.
                            </p>
                        @elseif ($isUrgent)
                            <p class="mt-3 flex items-center gap-1.5 text-xs font-bold text-red-600">
                                @include('profile.partials.warning-icon', ['color' => 'text-red-600'])
                                Urgent: Your license expires in {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }} ({{ $expDate->format('M d, Y') }})!
                            </p>
                        @elseif ($isWarning)
                            <p class="mt-3 flex items-center gap-1.5 text-xs font-bold text-amber-700">
                                @include('profile.partials.warning-icon', ['color' => 'text-amber-600'])
                                Notice: License expires in {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }} ({{ $expDate->format('M d, Y') }}).
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-white px-5 py-4 sm:px-6">
                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#005a36] px-5 text-sm font-bold text-white shadow-sm transition hover:bg-[#00432a] cursor-pointer sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                    Save Changes
                </button>
            </div>
        </form>

        {{-- Change password: posts to Fortify's user-password.update; errors use the updatePassword bag --}}
        @php
            $passwordErrors = $errors->getBag('updatePassword');
        @endphp
        <form id="change-password" method="POST" action="{{ route('user-password.update') }}" class="mx-auto max-w-4xl scroll-mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @method('PUT')
            <input type="text" name="username" value="{{ $user->email }}" autocomplete="username" class="hidden" aria-hidden="true" tabindex="-1">

            <div class="px-5 py-5 sm:px-6 sm:py-6">
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    <div class="mb-1 flex items-center gap-2 text-sm font-bold text-slate-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <h3>Change Password</h3>
                    </div>
                    <p class="mb-4 text-xs text-slate-500">Enter your current password, then choose a new one of at least 12 characters, with letters and numbers.</p>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label for="current-password" class="{{ $pfLabel }}">Current Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="current-password" type="password" name="current_password" required autocomplete="current-password" @if ($passwordErrors->has('current_password')) autofocus aria-invalid="true" @endif class="{{ $pfInput }} {{ $pfBorder('current_password', 'updatePassword') }} pr-10">
                                <x-password-eye for="current-password" />
                            </div>
                            @error('current_password', 'updatePassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="new-password" class="{{ $pfLabel }}">New Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="new-password" type="password" name="password" required minlength="12" autocomplete="new-password" @if (! $passwordErrors->has('current_password') && $passwordErrors->has('password')) autofocus @endif @if ($passwordErrors->has('password')) aria-invalid="true" @endif class="{{ $pfInput }} {{ $pfBorder('password', 'updatePassword') }} pr-10">
                                <x-password-eye for="new-password" />
                            </div>
                            @error('password', 'updatePassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="new-password-confirmation" class="{{ $pfLabel }}">Confirm New Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="new-password-confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" class="{{ $pfInput }} border-slate-200 pr-10">
                                <x-password-eye for="new-password-confirmation" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end border-t border-slate-100 bg-white px-5 py-4 sm:px-6">
                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#005a36] px-5 text-sm font-bold text-white shadow-sm transition hover:bg-[#00432a] cursor-pointer sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Change Password
                </button>
            </div>
        </form>
    </div>

    <script>
        function showProfilePhoto(src) {
            const img = document.getElementById('avatar-preview-img');
            const fallback = document.getElementById('avatar-fallback');
            const removeBtn = document.getElementById('avatar-remove-btn');
            img.src = src || '';
            img.classList.toggle('hidden', !src);
            fallback.classList.toggle('hidden', !!src);
            fallback.classList.toggle('flex', !src);
            removeBtn.classList.toggle('hidden', !src);
            removeBtn.classList.toggle('flex', !!src);
            document.getElementById('avatar-btn-text').textContent = src ? 'Change Photo' : 'Upload Photo';
        }

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
            reader.onload = (e) => {
                document.getElementById('remove-avatar').checked = false;
                showProfilePhoto(e.target.result);
            };
            reader.readAsDataURL(file);
        }

        function removeProfileAvatar() {
            document.getElementById('profile-avatar').value = '';
            document.getElementById('remove-avatar').checked = true;
            showProfilePhoto('');
        }

        function handleProfilePhotoDrag(event, over) {
            event.preventDefault();
            const zone = document.getElementById('profile-avatar-label');
            zone.classList.toggle('border-emerald-500', over);
            zone.classList.toggle('bg-emerald-50/60', over);
        }

        function handleProfilePhotoDrop(event) {
            handleProfilePhotoDrag(event, false);
            const file = event.dataTransfer?.files?.[0];
            if (!file) return;
            const input = document.getElementById('profile-avatar');
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            previewProfileAvatar(input);
        }
    </script>
@endsection
