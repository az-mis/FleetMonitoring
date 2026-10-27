@extends('authentication::layouts.guest')

@section('title', 'Log in')

@section('content')
    <div class="mb-5">
        <h1 class="text-[1.65rem] font-bold tracking-tight text-pub-text">Sign In</h1>
        <p class="mt-1.5 text-sm leading-6 text-pub-muted">Enter your account credentials to access your dashboard.</p>
    </div>

    @session('status')
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-pub-text" role="status">
            {{ $value }}
        </div>
    @endsession

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-[0.8125rem] font-semibold text-pub-body">Email address</label>
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
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
                    class="block h-11 w-full rounded-lg border border-pub-border bg-pub-field shadow-xs py-2.5 pl-10 pr-3 text-sm text-pub-text placeholder:text-pub-faint transition focus:border-pub-accent focus:outline-none focus:ring-4 focus:ring-pub-accent/15"
                >
            </div>
            @error('email')
                <p id="email-error" class="mt-1.5 text-xs font-semibold text-pub-danger" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-[0.8125rem] font-semibold text-pub-body">Password</label>
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
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
                    class="block h-11 w-full rounded-lg border border-pub-border bg-pub-field shadow-xs py-2.5 pl-10 pr-10 text-sm text-pub-text placeholder:text-pub-faint transition focus:border-pub-accent focus:outline-none focus:ring-4 focus:ring-pub-accent/15"
                >
                <button type="button" id="toggle-password" class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg p-2 text-pub-muted transition hover:text-pub-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent" aria-label="Show password">
                    <svg id="eye-closed" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.1A10.8 10.8 0 0 1 12 4.8c5.2 0 8.7 4.8 9.7 7.2a13.8 13.8 0 0 1-3.1 4.2"></path><path d="M6.2 6.2A14 14 0 0 0 2.3 12c1 2.4 4.5 7.2 9.7 7.2 1 0 2-.2 2.9-.5"></path>
                    </svg>
                    <svg id="eye-open" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M2.3 12S5.8 4.8 12 4.8 21.7 12 21.7 12 18.2 19.2 12 19.2 2.3 12 2.3 12Z"></path><circle cx="12" cy="12" r="2.8"></circle>
                    </svg>
                </button>
            </div>
            @error('password')
                <p id="password-error" class="mt-1.5 text-xs font-semibold text-pub-danger" role="alert">{{ $message }}</p>
            @enderror
            @if (Route::has('password.request'))
                <div class="mt-2 flex justify-end">
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-pub-accent hover:text-pub-text hover:underline focus:outline-none focus-visible:underline">Forgot password?</a>
                </div>
            @endif
        </div>

        <button
            type="submit"
            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-pub-primary px-4 text-sm font-semibold text-pub-on-primary shadow-xs hover:bg-pub-primary-hover focus:outline-none focus-visible:ring-4 focus-visible:ring-pub-accent/25 transition"
        >
            Sign in <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
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

    <p class="mt-6 border-t border-pub-line pt-5 text-center text-[0.8125rem] text-pub-muted">
        Need a vehicle?
        <a href="{{ route('request-vehicle') }}" class="font-semibold text-pub-accent hover:text-pub-accent hover:underline">Request one without signing in</a>
    </p>

@endsection
