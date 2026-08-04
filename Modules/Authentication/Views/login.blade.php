@extends('authentication::layouts.guest')

@section('title', 'Log in')

@section('content')
    <h1 class="text-xl font-semibold text-gray-900">Welcome back</h1>
    <p class="mt-1 text-sm text-gray-500">Log in to your Fleet Monitoring account.</p>

    @session('status')
        <div class="mt-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 border border-emerald-200">
            {{ $value }}
        </div>
    @endsession

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-200"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-emerald-700 hover:text-emerald-800 hover:underline">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-200"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center">
            <input
                id="remember"
                type="checkbox"
                name="remember"
                class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
            >
            <label for="remember" class="ml-2 block text-sm text-gray-600">Remember me</label>
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/10 hover:from-emerald-700 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition"
        >
            Log in
        </button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-gray-500">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-medium text-emerald-700 hover:text-emerald-800 hover:underline">Register</a>
        </p>
    @endif
@endsection
