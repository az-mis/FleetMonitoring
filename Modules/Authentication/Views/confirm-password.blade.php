@extends('authentication::layouts.guest')

@section('title', 'Confirm Password')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold tracking-tight text-white">Confirm Your Password</h1>
        <p class="mt-1.5 text-sm leading-6 text-emerald-50/60">This is a secure area. Please confirm your password before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4" onsubmit="this.querySelector('[type=submit]').disabled = true">
        @csrf

        <div>
            <label for="password" class="block text-[0.8125rem] font-semibold text-emerald-50">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                class="mt-1.5 block h-11 w-full rounded-xl border border-white/15 bg-black/25 px-3 py-2.5 text-sm text-white transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50"
            >
            @error('password')
                <p id="password-error" class="mt-1.5 text-xs font-semibold text-red-300" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 text-sm font-bold text-[#032611] shadow-lg shadow-emerald-950/40 hover:bg-emerald-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#072413] disabled:cursor-not-allowed disabled:opacity-60 transition"
        >
            Confirm
        </button>
    </form>

    <div class="mt-5 border-t border-white/10 pt-4 text-center">
        <a href="{{ route('dashboard') }}" class="inline-flex min-h-8 items-center gap-1 text-xs font-semibold text-emerald-200 hover:text-white focus:outline-none focus-visible:underline">
            <span aria-hidden="true">←</span> Back to Dashboard
        </a>
    </div>
@endsection
