@extends('authentication::layouts.guest')

@section('title', 'Forgot Password')

@php
    // Reset links are only useful when the server can actually deliver email. Outside local
    // development, a "log"/"array" mailer means nobody would receive the link.
    $mailReady = ! in_array(config('mail.default'), ['log', 'array'], true) || app()->isLocal();
@endphp

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold tracking-tight text-white">Forgot Password</h1>
        @if ($mailReady)
            <p class="mt-1.5 text-sm leading-6 text-emerald-50/60">
                Enter the email address of your account and we will send you a link to choose a new password.
            </p>
        @endif
    </div>

    @session('status')
        <div class="mb-4 rounded-xl border border-emerald-300/40 bg-emerald-500/15 px-4 py-3 text-xs font-semibold leading-5 text-emerald-100" role="status">
            {{ $value }}
        </div>
    @endsession

    @if ($mailReady)
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4" onsubmit="this.querySelector('[type=submit]').disabled = true">
            @csrf

            <div>
                <label for="email" class="block text-[0.8125rem] font-semibold text-emerald-50">Email address</label>
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
                        class="block h-11 w-full rounded-xl border border-white/15 bg-black/25 py-2.5 pl-10 pr-3 text-sm text-white placeholder:text-emerald-100/35 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50"
                    >
                </div>
                @error('email')
                    <p id="email-error" class="mt-1.5 text-xs font-semibold text-red-300" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 text-sm font-bold text-[#032611] shadow-lg shadow-emerald-950/40 hover:bg-emerald-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#072413] disabled:cursor-not-allowed disabled:opacity-60 transition"
            >
                Email Password Reset Link
            </button>
        </form>
    @else
        <div class="rounded-xl border border-amber-300/40 bg-amber-400/10 px-4 py-3 text-xs leading-5 text-amber-100" role="note">
            Password reset by email is not available on this server yet. Please ask your Fleet Management administrator to reset your password.
        </div>
    @endif

    <div class="mt-5 border-t border-white/10 pt-4 text-center">
        <a href="{{ route('login') }}" class="inline-flex min-h-8 items-center gap-1 text-xs font-semibold text-emerald-200 hover:text-white focus:outline-none focus-visible:underline">
            <span aria-hidden="true">←</span> Back to Sign In
        </a>
    </div>
@endsection
