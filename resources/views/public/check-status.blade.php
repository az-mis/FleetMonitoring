<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Check Request Status</title>
    @include('partials.text-size')
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="relative min-h-screen bg-[#082b16] text-white antialiased">
    {{-- Fixed Full-screen Background --}}
    <div class="fixed inset-0 -z-10 bg-cover bg-center bg-fixed" style="background-image:linear-gradient(rgba(5,38,18,.68),rgba(5,38,18,.78)),url('{{ asset('FleetMS-bg.jpg') }}');"></div>

<main class="flex min-h-screen items-start justify-center px-4 pb-10 pt-8 sm:px-8 sm:pt-28">
    <div class="w-full max-w-xl rounded-3xl border border-emerald-300/30 bg-[#0a351b]/90 p-6 shadow-2xl shadow-black/40 backdrop-blur-sm">
        <nav class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-emerald-200/15 pb-3 text-[0.6875rem] text-emerald-100/75">
            <a href="{{ route('request-vehicle') }}" class="inline-flex min-h-8 items-center hover:text-white">← Request Form</a>
            <span class="hidden rounded-full border border-emerald-400/40 bg-emerald-950/60 px-3 py-1 font-semibold uppercase tracking-wide text-emerald-300 sm:inline-block"><span aria-hidden="true">⌕</span> Status Portal</span>
            <a href="{{ route('login') }}" class="inline-flex min-h-8 items-center hover:text-white">Staff Login →</a>
        </nav>
        <div class="mb-5">
            <img src="{{ $appLogoUrl ?? asset('da-logo.png') }}" alt="Agency logo" class="mb-4 h-11 w-11 rounded-full bg-white object-contain p-1">
            <h1 class="text-xl font-bold">Check Request Status</h1>
            <p class="mt-1 text-xs leading-5 text-emerald-100/70">Enter your 7-character reference code to view real-time trip approval, assigned driver details, and access your printable Trip Ticket.</p>
        </div>
        <form method="GET" action="{{ route('check-status') }}" class="flex gap-2">
            <div class="relative min-w-0 flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-emerald-300/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input
                    name="code"
                    aria-label="Reference code"
                    value="{{ request('code') }}"
                    maxlength="7"
                    minlength="7"
                    required
                    class="w-full rounded-xl border border-emerald-200/25 bg-[#03150a]/90 py-3 pl-10 pr-4 text-xs font-mono font-bold uppercase tracking-widest text-white placeholder:text-emerald-100/35 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/50"
                    placeholder="WUF8CEW"
                >
            </div>
            <button type="submit" class="rounded-xl bg-[#008751] hover:bg-[#007345] px-5 py-3 text-xs font-bold text-white shadow-lg transition cursor-pointer shrink-0">
                Check Status
            </button>
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
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-[0.6875rem] font-bold text-white shadow-xs hover:bg-emerald-500 transition cursor-pointer"
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
            <div class="mt-4 rounded-xl border border-red-300/25 bg-red-500/10 p-3.5 text-xs text-red-200">
                We couldn't find a request with reference code: <strong>{{ request('code') }}</strong>.
            </div>
        @elseif ($requestRecord)
            <div class="mt-4 space-y-3">
                {{-- Status Banner --}}
                @if ($requestRecord->status === 'approved')
                    <div class="rounded-xl border border-emerald-500/30 bg-[#052b14]/80 p-3.5 flex items-start gap-3">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-extrabold text-emerald-400 tracking-wide">Request Approved</h3>
                            <p class="mt-0.5 text-[0.6875rem] text-emerald-100/70">Your trip has been approved. You can now view assigned driver details and access your printable Trip Ticket below.</p>
                        </div>
                    </div>
                @elseif ($requestRecord->status === 'declined')
                    <div class="rounded-xl border border-rose-500/30 bg-[#380b12]/80 p-3.5 flex items-start gap-3">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-500/20 text-rose-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-extrabold text-rose-400 tracking-wide">Request Declined</h3>
                            <p class="mt-0.5 text-[0.6875rem] text-rose-100/70">{{ $requestRecord->decline_reason ? 'Reason: ' . $requestRecord->decline_reason : 'This travel request was declined by the administrator.' }}</p>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-amber-500/30 bg-[#382606]/80 p-3.5 flex items-start gap-3">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-500/20 text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-extrabold text-amber-400 tracking-wide">Pending Review</h3>
                            <p class="mt-0.5 text-[0.6875rem] text-amber-100/70">Your request is in the queue awaiting review and vehicle assignment.</p>
                        </div>
                    </div>
                @endif

                {{-- 2-Column Details Grid Card --}}
                <div class="rounded-2xl border border-emerald-400/20 bg-[#031b0c]/80 p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Vehicle --}}
                    <div class="flex items-start gap-2.5">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-950 text-emerald-400 ring-1 ring-emerald-500/20 mt-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10 17h4V5H2v12h3"/>
                                <path d="M14 17h1a2 2 0 1 0 4 0h1v-5l-3-4h-3"/>
                                <circle cx="7" cy="17" r="2"/>
                                <circle cx="17" cy="17" r="2"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[0.625rem] font-extrabold uppercase tracking-wider text-emerald-300/80">VEHICLE</p>
                            <p class="text-xs font-bold text-white mt-0.5 break-words">
                                {{ $requestRecord->vehicle?->plate_number ? $requestRecord->vehicle->plate_number . ' · ' . $requestRecord->vehicle->brand . ' ' . $requestRecord->vehicle->model : 'Pending Assignment' }}
                            </p>
                        </div>
                    </div>

                    {{-- Destination --}}
                    <div class="flex items-start gap-2.5">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-950 text-emerald-400 ring-1 ring-emerald-500/20 mt-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[0.625rem] font-extrabold uppercase tracking-wider text-emerald-300/80">DESTINATION</p>
                            <p class="text-xs font-bold text-white mt-0.5 break-words">{{ str_replace(';;', ', ', (string) $requestRecord->destination) }}</p>
                        </div>
                    </div>

                    {{-- Travel Date --}}
                    <div class="flex items-start gap-2.5">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-950 text-emerald-400 ring-1 ring-emerald-500/20 mt-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[0.625rem] font-extrabold uppercase tracking-wider text-emerald-300/80">TRAVEL DATE</p>
                            <p class="text-xs font-bold text-white mt-0.5 truncate">
                                {{ $requestRecord->travel_date->format('M j, Y') }}
                                {{ $requestRecord->travel_date_end ? ' – ' . $requestRecord->travel_date_end->format('M j, Y') : '' }}
                            </p>
                        </div>
                    </div>

                    {{-- Office / Section --}}
                    <div class="flex items-start gap-2.5">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-950 text-emerald-400 ring-1 ring-emerald-500/20 mt-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                                <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                                <path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/>
                                <path d="M10 6h4"/>
                                <path d="M10 10h4"/>
                                <path d="M10 14h4"/>
                                <path d="M10 18h4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[0.625rem] font-extrabold uppercase tracking-wider text-emerald-300/80">OFFICE / SECTION</p>
                            <p class="text-xs font-bold text-white mt-0.5 break-words">
                                {{ $requestRecord->requester_office ?: $requestRecord->location }}
                            </p>
                        </div>
                    </div>

                    {{-- Requester --}}
                    <div class="flex items-start gap-2.5 sm:col-span-2 border-t border-emerald-500/10 pt-3">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-950 text-emerald-400 ring-1 ring-emerald-500/20 mt-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[0.625rem] font-extrabold uppercase tracking-wider text-emerald-300/80">REQUESTER</p>
                            <p class="text-xs font-bold text-white mt-0.5 break-words">{{ $requestRecord->requester_name }}</p>
                        </div>
                    </div>
                </div>

                {{-- Printable Trip Ticket Button --}}
                @if ($requestRecord->status === 'approved')
                    <div class="pt-1">
                        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('trip-ticket', $requestRecord) }}" target="_blank" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-green-600 px-4 py-3 text-xs font-bold text-white shadow-lg hover:from-emerald-500 hover:to-green-500 transition cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 6 2 18 2 18 9"/>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                <rect width="12" height="8" x="6" y="14"/>
                            </svg>
                            <span>Open Printable Trip Ticket →</span>
                        </a>
                    </div>
                @endif
            </div>
        @endif
        <div class="mt-4 rounded-xl border border-emerald-200/15 bg-[#062512]/70 p-3">
            <h2 class="text-xs font-bold text-white">Where do I find my reference code?</h2>
            <p class="mt-1 text-[0.6875rem] leading-4 text-emerald-100/60">Your 7-character code was generated when you submitted the vehicle request form (e.g. 7K4RXWM).</p>
        </div>
        <footer class="mt-4 border-t border-emerald-200/15 pt-3 text-center text-[0.6875rem] text-emerald-100/55">♧ Official Government Fleet Management System<br><span class="text-[0.5625rem]">© {{ date('Y') }} {{ config('app.name', 'Fleet Monitoring') }}</span></footer>
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
