{{--
    Shared frame for the public pages (home, request form, status, sign-in and password pages): a brand panel with
    the page's pitch on the left (on top on phones) and the page content on the right. $active marks the current
    page; every page except home is "compact" (shorter pitch, Home link). $points are [icon or step number, title, text].
--}}
@props([
    'title' => config('app.name'),
    'active' => null,
    'width' => 'max-w-lg',
    'kicker' => 'Official fleet system',
    'heading' => 'Smart fleet operations & monitoring',
    'lede' => 'Request official vehicles, assign drivers and track every trip in real time.',
    'points' => null,
])

@php
    $compact = $active !== 'home';
    $icons = [
        'shield' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'printer' => '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'car' => '<path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/>',
    ];
    $points ??= [
        ['shield', 'Role-based access', 'Separate, secure workflows for admins and drivers.'],
        ['calendar', 'Live availability', 'See vehicles and drivers free on each date.'],
        ['printer', 'Printable trip tickets', 'Official travel authorizations in one click.'],
    ];
    $links = [
        'request' => ['Request a Vehicle', route('request-vehicle')],
        'status' => ['Check Status', route('check-status')],
        'login' => ['Staff Login', route('login')],
    ];
@endphp

<!DOCTYPE html>
{{-- Space for the scrollbar is kept on every page, so the panel split sits at the same place on short and long pages --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="[scrollbar-gutter:stable]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @include('partials.text-size')
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="min-h-screen bg-pub-bg text-pub-text antialiased [color-scheme:var(--pub-scheme)] selection:bg-emerald-400/30 [&_option]:bg-pub-panel [&_option]:text-pub-text">
    <div class="flex min-h-screen flex-col lg:grid lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
        <aside class="relative isolate flex flex-col overflow-hidden px-5 pt-6 text-white sm:px-8 lg:sticky lg:top-0 lg:h-screen lg:px-12 lg:py-9 {{ $compact ? 'pb-6' : 'pb-8' }}">
            <div class="absolute inset-0 -z-20 bg-cover bg-center" style="background-image: url('{{ asset('FleetMS-bg.jpg') }}')" aria-hidden="true"></div>
            <div class="absolute inset-0 -z-10" style="background: linear-gradient(180deg, rgb(5 36 23 / 0.82), rgb(5 36 23 / 0.9) 55%, rgb(5 36 23 / 0.97))" aria-hidden="true"></div>

            <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3 self-start rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300">
                <span class="flex shrink-0">
                    <img src="{{ asset('da-logo.png') }}" alt="" class="h-10 w-10 rounded-full bg-white object-contain p-px">
                    <img src="{{ $appIconUrl ?? $appLogoUrl ?? asset('favicon.png') }}" alt="" class="-ml-2 h-10 w-10 rounded-full bg-white object-contain p-px ring-2 ring-pub-deep">
                </span>
                <span class="min-w-0 leading-tight">
                    <span class="block text-[0.9375rem] font-bold">Fleet Management System</span>
                    <span class="block text-xs text-white/65">Department of Agriculture – MIMAROPA</span>
                </span>
            </a>

            <div class="max-w-[30rem] lg:mt-auto {{ $compact ? 'mt-5' : 'mt-10' }}">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-300">{{ $kicker }}</p>
                {{-- On home this is the page heading; elsewhere the page has its own h1 in the content panel --}}
                @if ($compact)
                    <p class="mt-2 text-balance text-2xl font-bold leading-[1.1] tracking-tight lg:mt-3.5 lg:text-[2.5rem]">{{ $heading }}</p>
                @else
                    <h1 class="mt-3.5 text-balance text-[2.2rem] font-bold leading-[1.08] tracking-[-0.03em] sm:text-[2.6rem] lg:text-[3.1rem]">{{ $heading }}</h1>
                @endif
                <p class="mt-4 text-base leading-relaxed text-white/70 {{ $compact ? 'hidden lg:block' : '' }}">{{ $lede }}</p>
                <ul class="pub-side-points mt-7 hidden gap-3.5 lg:grid">
                    @foreach ($points as [$icon, $pointTitle, $pointText])
                        <li class="flex items-start gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-[7px] border border-white/10 bg-white/10 text-[0.8125rem] font-bold text-emerald-300">
                                @if (is_int($icon))
                                    {{ $icon }}
                                @else
                                    <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$icon] !!}</svg>
                                @endif
                            </span>
                            <span>
                                <span class="block text-sm font-semibold text-white">{{ $pointTitle }}</span>
                                <span class="block text-[0.8125rem] leading-5 text-white/60">{{ $pointText }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <p class="pub-side-legal mt-10 hidden border-t border-white/10 pt-5 text-xs leading-5 text-white/55 lg:block">An official system of the Department of Agriculture – Regional Field Office MIMAROPA, Republic of the Philippines.</p>
        </aside>

        <main class="flex min-w-0 flex-1 flex-col bg-pub-panel px-5 pb-6 sm:px-8 lg:min-h-screen lg:px-12 lg:py-9">
            {{-- Home shows these same pages as its launcher, so its panel has no top bar --}}
            @if ($compact)
                <div class="flex items-center gap-5 pt-4 text-[0.8125rem] text-pub-faint lg:pt-0">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 rounded-md font-semibold text-pub-muted transition hover:text-pub-text focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        Home
                    </a>
                    <nav class="ml-auto hidden items-center gap-5 lg:flex" aria-label="Public pages">
                        @foreach ($links as $key => [$label, $url])
                            @continue($key === $active)
                            <a href="{{ $url }}" class="rounded-md transition hover:text-pub-text focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent">{{ $label }}</a>
                        @endforeach
                    </nav>
                </div>
            @endif

            <div class="mx-auto w-full py-8 lg:my-auto lg:py-10 {{ $width }}">
                {{ $slot }}
            </div>

            <footer class="mt-auto text-center text-xs leading-5 text-pub-faint lg:text-left">
                © {{ date('Y') }} DA MIMAROPA · Powered by MIS · Developed by Alvin Zoleta
            </footer>
        </main>
    </div>

    {{ $scripts ?? '' }}
</body>
</html>
