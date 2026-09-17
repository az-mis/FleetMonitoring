<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Check Request Status</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#082b16] text-white antialiased">
<main class="flex min-h-screen items-start justify-center bg-cover bg-center px-4 pb-10 sm:px-8" style="padding-top:112px;background-image:linear-gradient(rgba(5,38,18,.68),rgba(5,38,18,.78)),url('{{ asset('FleetMS-bg.jpg') }}')">
    <div class="w-full max-w-xl rounded-3xl border border-emerald-300/30 bg-[#0a351b]/90 p-6 shadow-2xl shadow-black/40 backdrop-blur-sm">
        <nav class="mb-4 flex items-center justify-between border-b border-emerald-200/15 pb-3 text-[11px] text-emerald-100/75">
            <a href="{{ route('request-vehicle') }}" class="hover:text-white">← Request Form</a>
            <span class="rounded-full border border-emerald-400/40 bg-emerald-950/60 px-3 py-1 font-semibold uppercase tracking-wide text-emerald-300">⌕ Status Portal</span>
            <a href="{{ route('login') }}" class="hover:text-white">Staff Login →</a>
        </nav>
        <div class="mb-5">
            <img src="{{ $appLogoUrl ?? asset('da-logo.png') }}" alt="Agency logo" class="mb-4 h-11 w-11 rounded-full bg-white object-contain p-1">
            <h1 class="text-xl font-bold">Check Request Status</h1>
            <p class="mt-1 text-xs leading-5 text-emerald-100/70">Enter your 7-character reference code to view real-time trip approval, assigned driver details, and access your printable Trip Ticket.</p>
        </div>
        <form method="GET" action="{{ route('check-status') }}" class="flex gap-2">
            <input name="code" value="{{ request('code') }}" maxlength="7" minlength="7" required class="min-w-0 flex-1 rounded-xl border border-emerald-200/25 bg-[#062512]/80 px-4 py-3 text-xs uppercase tracking-widest text-white placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none" placeholder="E.G. 7K4RXWM">
            <button type="submit" class="rounded-xl bg-gradient-to-r from-emerald-600 to-green-600 px-5 py-3 text-xs font-bold shadow-lg hover:from-emerald-500 hover:to-green-500">Check Status</button>
        </form>
        @if (session('request_submitted'))
            <div class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-xs text-emerald-200">
                Request submitted successfully. Save your reference code: <strong>{{ session('request_submitted') }}</strong>
            </div>
        @elseif (request('code') && !$requestRecord)
            <div class="mt-4 rounded-xl border border-red-300/25 bg-red-500/10 p-3 text-xs text-red-200">
                We couldn't find a request with that reference code.
            </div>
        @elseif ($requestRecord)
            <div class="mt-4 rounded-xl border border-emerald-300/20 bg-[#062512]/70 p-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase tracking-wide text-emerald-100/55">Request status</span>
                    <span class="rounded-full bg-amber-400/15 px-2 py-1 text-xs font-bold uppercase text-amber-300">{{ $requestRecord->status }}</span>
                </div>
                <p class="mt-3 font-semibold text-white">{{ $requestRecord->requester_name }}</p>
                <p class="mt-1 text-xs text-emerald-100/65">{{ $requestRecord->destination }} · {{ $requestRecord->travel_date->format('M j, Y') }}</p>
                <p class="mt-1 text-xs text-emerald-100/65">Vehicle: {{ $requestRecord->vehicle?->plate_number ?? 'Pending assignment' }}</p>
                <a href="{{ route('trip-ticket', $requestRecord) }}" target="_blank" class="mt-3 inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500">Open Printable Trip Ticket →</a>
            </div>
        @endif
        <div class="mt-4 rounded-xl border border-emerald-200/15 bg-[#062512]/70 p-3">
            <h2 class="text-xs font-bold text-white">Where do I find my reference code?</h2>
            <p class="mt-1 text-[11px] leading-4 text-emerald-100/60">Your 7-character code was generated when you submitted the vehicle request form (e.g. 7K4RXWM).</p>
        </div>
        <footer class="mt-4 border-t border-emerald-200/15 pt-3 text-center text-[11px] text-emerald-100/55">♧ Official Government Fleet Management System<br><span class="text-[9px]">© {{ date('Y') }} {{ config('app.name', 'Fleet Monitoring') }}</span></footer>
    </div>
</main>
</body>
</html>
