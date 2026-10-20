{{--
    Shared frame for the public pages (home, sign-in, request form, status, password pages):
    background, top bar with the public links, and footer. $active marks the current link.
--}}
@props(['title' => config('app.name'), 'active' => null, 'width' => 'max-w-xl', 'center' => false])

@php
    $links = [
        'request' => ['label' => 'Request a Vehicle', 'short' => 'Request', 'url' => route('request-vehicle'),
            'icon' => '<path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/>'],
        'status' => ['label' => 'Check Status', 'short' => 'Status', 'url' => route('check-status'),
            'icon' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @include('partials.text-size')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="min-h-screen bg-pub-bg text-pub-text antialiased [color-scheme:var(--pub-scheme)] selection:bg-emerald-400/30 [&_option]:bg-pub-panel [&_option]:text-pub-text">
    <div class="fixed inset-0 -z-10" aria-hidden="true">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('FleetMS-bg.jpg') }}')"></div>
        <div class="absolute inset-0 bg-(image:--pub-wash)"></div>
        <div class="absolute inset-0 bg-(image:--pub-glow)"></div>
    </div>

    <div class="flex min-h-screen flex-col">
        <header class="mx-auto flex w-full max-w-6xl items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-5">
            <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent">
                <img src="{{ $appLogoUrl ?? asset('da-logo.png') }}" alt="" class="h-9 w-9 shrink-0 rounded-full bg-white object-contain p-0.5 ring-1 ring-pub-border">
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-[0.8125rem] font-bold tracking-tight sm:text-sm">Fleet Management</span>
                    <span class="block truncate text-[0.6875rem] font-medium uppercase tracking-[0.12em] text-pub-muted">{{ config('app.name') }}</span>
                </span>
            </a>

            {{-- The home page shows these same three as large cards, so its top bar keeps only the logo --}}
            @unless ($active === 'home')
            <nav class="flex shrink-0 items-center gap-0.5 sm:gap-1.5" aria-label="Public pages">
                @foreach ($links as $key => $link)
                    <a href="{{ $link['url'] }}"
                       @if ($active === $key) aria-current="page" @endif
                       title="{{ $link['label'] }}"
                       class="inline-flex h-9 items-center gap-1.5 rounded-lg px-2 text-[0.8125rem] sm:px-2.5 font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent {{ $active === $key ? 'bg-pub-highlight text-pub-text' : 'text-pub-muted hover:bg-pub-highlight hover:text-pub-text' }}">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $link['icon'] !!}</svg>
                        <span class="hidden sm:inline">{{ $link['label'] }}</span>
                        <span class="sr-only sm:hidden">{{ $link['label'] }}</span>
                    </a>
                @endforeach
                @if ($active !== 'login')
                    <a href="{{ route('login') }}" class="ml-1 inline-flex h-9 items-center whitespace-nowrap rounded-lg bg-pub-cta px-2.5 text-[0.8125rem] sm:px-3 font-semibold text-pub-on-cta shadow-sm transition hover:bg-pub-cta-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent">
                        Staff Login
                    </a>
                @endif
            </nav>
            @endunless
        </header>

        <main class="flex flex-1 justify-center px-4 pb-10 pt-2 sm:px-6 sm:pt-6 {{ $center ? 'items-center' : 'items-start' }}">
            <div class="w-full {{ $width }}">
                {{ $slot }}
            </div>
        </main>

        <footer class="px-4 pb-6 text-center text-xs text-pub-muted">
            © {{ date('Y') }} {{ config('app.name') }} · Official Government Fleet Management System · Authorized personnel only
        </footer>
    </div>

    {{ $scripts ?? '' }}
</body>
</html>
