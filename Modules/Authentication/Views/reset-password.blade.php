@extends('authentication::layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <div class="mb-5">
        <p class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-400/35 bg-emerald-950/60 px-3 py-1 text-[0.625rem] font-semibold uppercase tracking-wide text-emerald-300 md:hidden">
            <span class="h-2 w-2 rounded-full bg-emerald-400"></span> Fleet Management System
        </p>
        <h1 class="text-xl font-bold text-white">Choose a New Password</h1>
        <p class="mt-1 text-xs leading-5 text-emerald-100/65">Enter your email address and your new password.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4" onsubmit="this.querySelector('[type=submit]').disabled = true">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-xs font-semibold text-white">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                required
                autocomplete="username"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                class="mt-1.5 block w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-3 py-2.5 text-sm text-white placeholder:text-emerald-100/35 shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50"
            >
            @error('email')
                <p id="email-error" class="mt-1.5 text-xs font-semibold text-red-300" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-white">New password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autofocus
                autocomplete="new-password"
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                class="mt-1.5 block w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-3 py-2.5 text-sm text-white shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50"
            >
            @error('password')
                <p id="password-error" class="mt-1.5 text-xs font-semibold text-red-300" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-white">Confirm new password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="mt-1.5 block w-full rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-3 py-2.5 text-sm text-white shadow-inner focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-green-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-950/30 hover:from-emerald-400 hover:to-green-400 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-[#0a351b] disabled:cursor-not-allowed disabled:opacity-60 transition"
        >
            Reset Password
        </button>
    </form>

    <div class="mt-5 border-t border-emerald-200/15 pt-4 text-center">
        <a href="{{ route('login') }}" class="inline-flex min-h-8 items-center gap-1 text-xs font-semibold text-emerald-200 hover:text-white focus:outline-none focus-visible:underline">
            <span aria-hidden="true">←</span> Back to Sign In
        </a>
    </div>
@endsection
