@extends('authentication::layouts.guest')

@section('title', 'Confirm Password')

@section('content')
    <div class="mb-5">
        <h1 class="text-xl font-bold text-white">Confirm Your Password</h1>
        <p class="mt-1 text-xs leading-5 text-emerald-100/65">This is a secure area. Please confirm your password before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4" onsubmit="this.querySelector('[type=submit]').disabled = true">
        @csrf

        <div>
            <label for="password" class="block text-xs font-semibold text-white">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                class="mt-1.5 block w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-3 py-2.5 text-sm text-white shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50"
            >
            @error('password')
                <p id="password-error" class="mt-1.5 text-xs font-semibold text-red-300" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-green-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-950/30 hover:from-emerald-400 hover:to-green-400 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-[#0a351b] disabled:cursor-not-allowed disabled:opacity-60 transition"
        >
            Confirm
        </button>
    </form>

    <div class="mt-5 border-t border-emerald-200/15 pt-4 text-center">
        <a href="{{ route('dashboard') }}" class="inline-flex min-h-8 items-center gap-1 text-xs font-semibold text-emerald-200 hover:text-white focus:outline-none focus-visible:underline">
            <span aria-hidden="true">←</span> Back to Dashboard
        </a>
    </div>
@endsection
