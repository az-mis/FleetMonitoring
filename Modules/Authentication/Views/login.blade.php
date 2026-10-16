@extends('authentication::layouts.guest')

@section('title', 'Log in')

@section('content')
    <div class="mb-5">
        <p class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-400/35 bg-emerald-950/60 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-300 md:hidden">
            <span class="h-2 w-2 rounded-full bg-emerald-400"></span> Fleet Management System
        </p>
        <h1 class="text-xl font-bold text-white">Sign In</h1>
        <p class="mt-1 text-xs text-emerald-100/65">Enter your account credentials to access your dashboard.</p>
    </div>

    @session('status')
        <div class="mt-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 border border-emerald-200">
            {{ $value }}
        </div>
    @endsession

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-white">Email address</label>
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-100/45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path>
                </svg>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    placeholder="name@agency.gov.ph"
                    class="block w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 py-2.5 pl-10 pr-3 text-sm text-white placeholder:text-emerald-100/35 shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50"
                >
            </div>
            @error('email')
                <p id="email-error" class="mt-1.5 text-xs font-semibold text-red-300" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-xs font-semibold text-white">Password</label>
            </div>
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-100/45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                </svg>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    placeholder="••••••••••••"
                    class="block w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 py-2.5 pl-10 pr-10 text-sm text-white placeholder:text-emerald-100/35 shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50"
                >
                <button type="button" id="toggle-password" class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg p-2 text-emerald-100/60 transition hover:text-emerald-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400" aria-label="Show password">
                    <svg id="eye-closed" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.1A10.8 10.8 0 0 1 12 4.8c5.2 0 8.7 4.8 9.7 7.2a13.8 13.8 0 0 1-3.1 4.2"></path><path d="M6.2 6.2A14 14 0 0 0 2.3 12c1 2.4 4.5 7.2 9.7 7.2 1 0 2-.2 2.9-.5"></path>
                    </svg>
                    <svg id="eye-open" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M2.3 12S5.8 4.8 12 4.8 21.7 12 21.7 12 18.2 19.2 12 19.2 2.3 12 2.3 12Z"></path><circle cx="12" cy="12" r="2.8"></circle>
                    </svg>
                </button>
            </div>
            @error('password')
                <p id="password-error" class="mt-1.5 text-xs font-semibold text-red-300" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-green-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-950/30 hover:from-emerald-400 hover:to-green-400 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-[#0a351b] transition"
        >
            Continue to Dashboard <span class="ml-1">→</span>
        </button>
    </form>

    <script>
        document.getElementById('toggle-password')?.addEventListener('click', function () {
            const password = document.getElementById('password');
            const hidden = password.type === 'password';
            password.type = hidden ? 'text' : 'password';
            document.getElementById('eye-closed').classList.toggle('hidden', !hidden);
            document.getElementById('eye-open').classList.toggle('hidden', hidden);
            this.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
        });
    </script>

    <div class="mt-5 border-t border-emerald-200/15 pt-4">
        <p class="mb-2 text-center text-[10px] font-semibold uppercase tracking-[.18em] text-emerald-100/65">Public Staff Portals</p>
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('request-vehicle') }}" class="rounded-lg border border-emerald-200/20 bg-[#062512]/65 px-2 py-2 text-center text-xs text-emerald-100/80 transition hover:border-emerald-300/50 hover:bg-emerald-900/50"><span aria-hidden="true">♧</span> &nbsp;Request a Vehicle</a>
            <a href="{{ route('check-status') }}" class="rounded-lg border border-emerald-200/20 bg-[#062512]/65 px-2 py-2 text-center text-xs text-emerald-100/80 transition hover:border-emerald-300/50 hover:bg-emerald-900/50"><span aria-hidden="true">⌕</span> &nbsp;Check Request Status</a>
        </div>
        <div class="mt-4 border-t border-emerald-200/15 pt-3 text-center text-[10px] text-emerald-100/65"><span aria-hidden="true">♧</span> Encrypted &amp; Protected Session<br><span class="mt-1 inline-block">© {{ date('Y') }} {{ config('app.name', 'Vehicle Monitoring & Management System') }}</span></div>
    </div>

@endsection
