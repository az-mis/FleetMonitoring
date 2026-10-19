@extends('authentication::layouts.guest')

@section('title', 'Confirm Password')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold tracking-tight text-pub-text">Confirm Your Password</h1>
        <p class="mt-1.5 text-sm leading-6 text-pub-muted">This is a secure area. Please confirm your password before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4" onsubmit="this.querySelector('[type=submit]').disabled = true">
        @csrf

        <div>
            <label for="password" class="block text-[0.8125rem] font-semibold text-pub-text">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                class="mt-1.5 block h-11 w-full rounded-xl border border-pub-border bg-pub-field px-3 py-2.5 text-sm text-pub-text transition focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40"
            >
            @error('password')
                <p id="password-error" class="mt-1.5 text-xs font-semibold text-pub-danger" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-pub-primary px-4 text-sm font-bold text-pub-on-primary shadow-lg shadow-emerald-950/40 hover:bg-pub-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent focus-visible:ring-offset-2 focus-visible:ring-offset-pub-panel disabled:cursor-not-allowed disabled:opacity-60 transition"
        >
            Confirm
        </button>
    </form>

    <div class="mt-5 border-t border-pub-border pt-4 text-center">
        <a href="{{ route('dashboard') }}" class="inline-flex min-h-8 items-center gap-1 text-xs font-semibold text-pub-accent hover:text-pub-text focus:outline-none focus-visible:underline">
            <span aria-hidden="true">←</span> Back to Dashboard
        </a>
    </div>
@endsection
