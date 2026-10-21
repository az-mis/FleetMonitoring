<x-public-shell title="Check Request Status" active="status" width="max-w-xl">
    @php
        $status = $requestRecord?->status;
        // A declined request that had a ticket number was an approved trip that was later cancelled
        $banner = match ($status) {
            'approved' => ['tone' => 'emerald', 'title' => 'Request approved',
                'text' => 'Your trip is approved. Open the printable trip ticket below.',
                'icon' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m22 4-10 10.01-3-3"/>'],
            'declined' => ['tone' => 'rose', 'title' => $requestRecord->trip_ticket_number ? 'Trip cancelled' : 'Request declined',
                'text' => $requestRecord->decline_reason ? 'Reason: '.$requestRecord->decline_reason : 'This travel request was declined by the administrator.',
                'icon' => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>'],
            null => null,
            default => ['tone' => 'amber', 'title' => 'Pending review',
                'text' => 'Your request is waiting for an administrator to review it.',
                'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>'],
        };
        $tones = [
            'emerald' => 'border-pub-border bg-pub-highlight text-pub-accent',
            'rose' => 'border-pub-danger-border bg-pub-danger-soft text-pub-danger',
            'amber' => 'border-pub-warning-border bg-pub-warning-soft text-pub-warning',
        ];
    @endphp

    <div class="public-card p-6 sm:p-8">
        <h1 class="text-2xl font-bold tracking-tight">Check request status</h1>
        <p class="mt-1.5 text-sm leading-6 text-pub-muted">Enter the 7-character reference code you received when you submitted your request.</p>

        <form method="GET" action="{{ route('check-status') }}" class="mt-6 flex flex-col gap-2 sm:flex-row">
            <label for="code" class="sr-only">Reference code</label>
            <div class="relative min-w-0 flex-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-pub-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input
                    id="code"
                    name="code"
                    value="{{ request('code') }}"
                    maxlength="7"
                    minlength="7"
                    required
                    autocomplete="off"
                    autocapitalize="characters"
                    spellcheck="false"
                    placeholder="e.g. 7K4RXWM"
                    class="h-12 w-full rounded-xl border border-pub-border bg-pub-field pl-10 pr-4 font-mono text-base font-semibold uppercase tracking-[0.25em] text-pub-text placeholder:font-sans placeholder:normal-case placeholder:tracking-normal placeholder:text-pub-faint transition focus:border-pub-accent focus:outline-none focus:ring-2 focus:ring-pub-accent/40"
                >
            </div>
            <button type="submit" class="inline-flex h-12 shrink-0 items-center justify-center rounded-xl bg-pub-primary px-6 text-sm font-bold text-pub-on-primary shadow-lg shadow-emerald-950/40 transition hover:bg-pub-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent">
                Check status
            </button>
        </form>

        @if (session('request_submitted'))
            <div class="mt-5 rounded-xl border border-pub-border bg-pub-highlight p-4" role="status">
                <p class="text-sm font-semibold text-pub-accent">Request submitted. Save your reference code:</p>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <strong class="font-mono text-2xl tracking-[0.25em] text-pub-text">{{ session('request_submitted') }}</strong>
                    <button type="button" onclick="copyCode('{{ session('request_submitted') }}')" class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-pub-highlight px-3.5 text-[0.8125rem] font-semibold text-pub-text ring-1 ring-inset ring-pub-border transition hover:bg-pub-highlight">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span id="copy-btn-txt">Copy code</span>
                    </button>
                </div>
            </div>
        @elseif (request('code') && ! $requestRecord)
            <div class="mt-5 rounded-xl border border-pub-danger-border bg-pub-danger-soft p-4 text-sm text-pub-danger" role="alert">
                We couldn't find a request with reference code <strong class="font-mono tracking-widest text-pub-text">{{ request('code') }}</strong>. Check the code and try again.
            </div>
        @elseif ($requestRecord)
            <div class="mt-6 space-y-4">
                <div class="flex items-start gap-3 rounded-xl border p-4 {{ $tones[$banner['tone']] }}">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $banner['icon'] !!}</svg>
                    <div>
                        <h2 class="text-sm font-bold">{{ $banner['title'] }}</h2>
                        <p class="mt-0.5 text-sm leading-6 text-pub-muted">{{ $banner['text'] }}</p>
                    </div>
                </div>

                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 rounded-xl border border-pub-border bg-pub-highlight p-5 sm:grid-cols-2">
                    @foreach ([
                        'Reference' => $requestRecord->reference_code,
                        'Trip ticket' => $requestRecord->trip_ticket_number ?: 'Issued once approved',
                        'Vehicle' => $requestRecord->vehicle?->plate_number ? $requestRecord->vehicle->plate_number.' · '.$requestRecord->vehicle->brand.' '.$requestRecord->vehicle->model : 'Pending assignment',
                        'Travel date' => $requestRecord->travel_date->format('M j, Y').($requestRecord->travel_date_end && ! $requestRecord->travel_date_end->isSameDay($requestRecord->travel_date) ? ' – '.$requestRecord->travel_date_end->format('M j, Y') : ''),
                        'Destination' => str_replace(';;', ', ', (string) $requestRecord->destination),
                        'Office / section' => $requestRecord->requester_office ?: $requestRecord->location,
                        'Requester' => $requestRecord->requester_name,
                    ] as $label => $value)
                        <div class="min-w-0 {{ $label === 'Destination' ? 'sm:col-span-2' : '' }}">
                            <dt class="text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-pub-muted">{{ $label }}</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-pub-text">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($status === 'approved')
                    <a href="{{ \Modules\VehicleRequest\Controllers\VehicleRequestController::publicTripTicketUrl($requestRecord) }}" target="_blank" rel="noopener" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-pub-primary px-4 text-sm font-bold text-pub-on-primary shadow-lg shadow-emerald-950/40 transition hover:bg-pub-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                        Open printable trip ticket
                    </a>
                @endif
            </div>
        @endif

        @unless ($requestRecord || session('request_submitted'))
            <div class="mt-6 flex items-start gap-3 border-t border-pub-border pt-5 text-[0.8125rem] leading-5 text-pub-muted">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                <p>Your code was shown when you submitted the request form. Lost it? Ask the fleet office at your station and give them your name and travel date.</p>
            </div>
        @endunless
    </div>

    <x-slot:scripts>
        <script>
            function copyCode(code) {
                navigator.clipboard.writeText(code).then(() => {
                    const txt = document.getElementById('copy-btn-txt');
                    if (txt) {
                        txt.textContent = 'Copied!';
                        setTimeout(() => { txt.textContent = 'Copy code'; }, 2000);
                    }
                }).catch(() => {
                    alert('Code: ' + code);
                });
            }
        </script>
    </x-slot:scripts>
</x-public-shell>
