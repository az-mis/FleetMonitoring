@extends('authentication::layouts.guest')

@section('title', 'Forgot Password')

@php
    // Reset links are only useful when the server can actually deliver email. Outside local
    // development and QA (where the email is read from the log), a "log"/"array" mailer means
    // nobody would receive the link.
    $mailReady = ! in_array(config('mail.default'), ['log', 'array'], true) || app()->environment(['local', 'qa']);
@endphp

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold tracking-tight text-pub-text">Forgot Password</h1>
        @if ($mailReady)
            <p class="mt-1.5 text-sm leading-6 text-pub-muted">
                Enter the email address of your account and we will send you a link to choose a new password.
            </p>
        @endif
    </div>

    @session('status')
        <div class="mb-4 rounded-xl border border-pub-border bg-pub-highlight px-4 py-3 text-xs font-semibold leading-5 text-pub-text" role="status">
            {{ $value }}
        </div>
    @endsession

    @if ($mailReady)
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4" onsubmit="this.querySelector('[type=submit]').disabled = true">
            @csrf

            <div>
                <label for="email" class="block text-[0.8125rem] font-semibold text-pub-text">Email address</label>
                <div class="relative mt-1.5">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
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
                        class="block h-11 w-full rounded-xl border border-pub-border bg-pub-field py-2.5 pl-10 pr-3 text-sm text-pub-text placeholder:text-pub-faint transition focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40"
                    >
                </div>
                @error('email')
                    <p id="email-error" class="mt-1.5 text-xs font-semibold text-pub-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-pub-primary px-4 text-sm font-bold text-pub-on-primary shadow-lg shadow-emerald-950/40 hover:bg-pub-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent focus-visible:ring-offset-2 focus-visible:ring-offset-pub-panel disabled:cursor-not-allowed disabled:opacity-60 transition"
            >
                Email Password Reset Link
            </button>
        </form>
    @else
        <div class="rounded-xl border border-pub-warning-border bg-pub-warning-soft px-4 py-3 text-xs leading-5 text-pub-warning" role="note">
            Password reset by email is not available on this server yet. Please ask your Fleet Management administrator to reset your password.
        </div>
    @endif

    <div class="mt-5 border-t border-pub-border pt-4 text-center">
        <a href="{{ route('login') }}" class="inline-flex min-h-8 items-center gap-1 text-xs font-semibold text-pub-accent hover:text-pub-text focus:outline-none focus-visible:underline">
            <span aria-hidden="true">←</span> Back to Sign In
        </a>
    </div>
@endsection
