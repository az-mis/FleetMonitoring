<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Check Request Status</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="relative min-h-screen bg-[#082b16] text-white antialiased">
    {{-- Fixed Full-screen Background --}}
    <div class="fixed inset-0 -z-10 bg-cover bg-center bg-fixed" style="background-image:linear-gradient(rgba(5,38,18,.68),rgba(5,38,18,.78)),url('{{ asset('FleetMS-bg.jpg') }}');"></div>

<main class="flex min-h-screen items-start justify-center px-4 pb-10 sm:px-8" style="padding-top:112px;">
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
            <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3.5 text-xs text-emerald-200">
                <div class="min-w-0">
                    Request submitted successfully. Save your reference code:
                    <strong class="font-mono text-sm text-white tracking-widest block sm:inline sm:ml-1 mt-0.5 sm:mt-0" id="submitted-code-val">{{ session('request_submitted') }}</strong>
                </div>
                <button
                    type="button"
                    onclick="copyCode('{{ session('request_submitted') }}')"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-[11px] font-bold text-white shadow-xs hover:bg-emerald-500 transition cursor-pointer"
                    id="copy-btn-el"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                        <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                    </svg>
                    <span id="copy-btn-txt">Copy Code</span>
                </button>
            </div>
        @elseif (request('code') && !$requestRecord)
            <div class="mt-4 rounded-xl border border-red-300/25 bg-red-500/10 p-3 text-xs text-red-200">
                We couldn't find a request with that reference code.
            </div>
        @elseif ($requestRecord)
            <div class="mt-4 rounded-xl border border-emerald-300/20 bg-[#062512]/70 p-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase tracking-wide text-emerald-100/55">Request status</span>
                    @if ($requestRecord->status === 'approved')
                        <span class="rounded-full bg-emerald-500/20 border border-emerald-400/40 px-2.5 py-0.5 text-xs font-bold uppercase text-emerald-300">Approved</span>
                    @elseif ($requestRecord->status === 'declined')
                        <span class="rounded-full bg-rose-500/20 border border-rose-400/40 px-2.5 py-0.5 text-xs font-bold uppercase text-rose-300">Declined</span>
                    @else
                        <span class="rounded-full bg-amber-400/15 border border-amber-400/30 px-2.5 py-0.5 text-xs font-bold uppercase text-amber-300">Pending</span>
                    @endif
                </div>
                <p class="mt-3 font-semibold text-white">{{ $requestRecord->requester_name }}</p>
                <p class="mt-1 text-xs text-emerald-100/65">{{ $requestRecord->destination }} · {{ $requestRecord->travel_date->format('M j, Y') }}</p>
                <p class="mt-1 text-xs text-emerald-100/65">Vehicle: {{ $requestRecord->vehicle?->plate_number ?? 'Pending assignment' }}</p>

                @if ($requestRecord->status === 'approved')
                    <a href="{{ route('trip-ticket', $requestRecord) }}" target="_blank" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-emerald-500 transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect width="12" height="8" x="6" y="14"/>
                        </svg>
                        <span>Open Printable Trip Ticket →</span>
                    </a>
                @elseif ($requestRecord->status === 'declined')
                    <div class="mt-3 rounded-lg border border-rose-400/20 bg-rose-500/10 p-2.5 text-xs text-rose-200">
                        This travel request was <strong>Declined</strong>. Trip Ticket is unavailable.
                        @if ($requestRecord->decline_reason)
                            <p class="mt-1 text-rose-300/80">Reason: {{ $requestRecord->decline_reason }}</p>
                        @endif
                    </div>
                @else
                    <div class="mt-3 rounded-lg border border-amber-400/20 bg-amber-500/10 p-2.5 text-xs text-amber-200">
                        Trip Ticket will be available once your request is <strong>Approved</strong> by the administrator.
                    </div>
                @endif
            </div>
        @endif
        <div class="mt-4 rounded-xl border border-emerald-200/15 bg-[#062512]/70 p-3">
            <h2 class="text-xs font-bold text-white">Where do I find my reference code?</h2>
            <p class="mt-1 text-[11px] leading-4 text-emerald-100/60">Your 7-character code was generated when you submitted the vehicle request form (e.g. 7K4RXWM).</p>
        </div>
        <footer class="mt-4 border-t border-emerald-200/15 pt-3 text-center text-[11px] text-emerald-100/55">♧ Official Government Fleet Management System<br><span class="text-[9px]">© {{ date('Y') }} {{ config('app.name', 'Fleet Monitoring') }}</span></footer>
    </div>
</main>
<script>
    function copyCode(code) {
        navigator.clipboard.writeText(code).then(() => {
            const txt = document.getElementById('copy-btn-txt');
            if (txt) {
                txt.textContent = 'Copied!';
                setTimeout(() => { txt.textContent = 'Copy Code'; }, 2000);
            }
        }).catch(() => {
            alert('Code: ' + code);
        });
    }
</script>
</body>
</html>
