<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Fleet Monitoring'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-[#082b16] text-white min-h-screen">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-cover bg-center px-4 py-8 sm:px-8"
          style="background-image: linear-gradient(rgba(5, 38, 18, .70), rgba(5, 38, 18, .78)), url('{{ asset('FleetMS-bg.jpg') }}');">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(93,160,94,.22),transparent_45%)]"></div>

        <div class="relative w-full max-w-5xl overflow-hidden rounded-3xl border border-emerald-300/30 bg-[#0a351b]/90 shadow-2xl shadow-black/40 backdrop-blur-sm">
            <div class="grid md:grid-cols-[1.05fr_.95fr]">
                <section class="hidden border-r border-emerald-200/15 bg-[#092f18]/75 p-6 sm:p-8 md:flex md:flex-col md:justify-between lg:p-9">
                    <div>
                        <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-emerald-400/40 bg-emerald-950/60 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_10px_#34d399]"></span>
                            Fleet Management System
                        </div>
                        <div class="flex items-center gap-3">
                            <img src="{{ $appLogoUrl ?? asset('da-logo.png') }}" alt="Agency logo" class="h-11 w-11 rounded-full bg-white object-contain p-1">
                            <h2 class="text-xl font-bold leading-tight text-white">Smart Fleet Operations &amp;<br>Monitoring</h2>
                        </div>
                        <p class="mt-3 max-w-md text-xs leading-5 text-emerald-100/75">A unified, secure platform for tracking vehicles, assigning drivers, and managing trip requests in real time.</p>

                        <div class="mt-5 space-y-2">
                            @foreach ([['♢', 'Role-Based Security', 'Super admin, admin & driver workflows'], ['▣', 'Real-Time Allocation', 'Permanent assignments & calendar availability'], ['▤', 'Trip Ticket Generation', 'Instant printable travel authorizations']] as $feature)
                                <div class="flex items-center gap-2 rounded-xl border border-emerald-300/10 bg-[#062512]/75 px-2.5 py-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/15 text-base text-emerald-300">{{ $feature[0] }}</span>
                                    <div><p class="text-xs font-semibold text-white">{{ $feature[1] }}</p><p class="text-[10px] text-emerald-100/60">{{ $feature[2] }}</p></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="border-t border-emerald-200/15 pt-4 text-[10px] text-emerald-100/55">Authorized Agency Personnel Only &nbsp;•&nbsp; All activities logged</div>
                </section>

                <section class="p-6 sm:p-8 lg:p-9">
                    @yield('content')
                </section>
            </div>
        </div>
    </main>
</body>
</html>
