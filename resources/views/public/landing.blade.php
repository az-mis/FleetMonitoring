<x-public-shell title="Fleet Management System" active="home">
    @php
        $chevron = '<svg class="h-[18px] w-[18px] shrink-0 text-pub-faint transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
    @endphp

    <h2 class="text-[1.65rem] font-bold tracking-tight">Welcome</h2>
    <p class="mt-1.5 text-[0.9375rem] text-pub-muted">What would you like to do today?</p>

    <div class="mt-7 grid gap-3">
        <a href="{{ route('request-vehicle') }}" class="group flex items-center gap-4 rounded-xl border border-pub-accent bg-pub-panel p-[1.1rem] shadow-[0_0_0_1px_var(--color-pub-accent),0_10px_24px_-14px_rgb(0_102_61/0.5)] transition focus:outline-none focus-visible:ring-4 focus-visible:ring-pub-accent/25">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-[10px] bg-pub-primary text-pub-on-primary">
                <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>
            </span>
            <span class="min-w-0 flex-1">
                <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-base font-bold">
                    Request a Vehicle
                    <span class="rounded-md bg-pub-highlight px-1.5 py-0.5 text-[0.6875rem] font-semibold text-pub-accent">No account needed</span>
                </span>
                <span class="mt-0.5 block text-sm text-pub-muted">Book an official vehicle for travel.</span>
            </span>
            {!! $chevron !!}
        </a>

        <form method="GET" action="{{ route('check-status') }}" class="rounded-xl border border-pub-line bg-pub-subtle p-[1.1rem]">
            <label for="code" class="flex items-center gap-2 text-[0.9375rem] font-bold">
                <svg class="h-[18px] w-[18px] text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                Check Request Status
            </label>
            <p class="mt-0.5 text-[0.8125rem] leading-5 text-pub-muted">Enter the 7-character reference code you received to track your request and print your trip ticket.</p>
            <div class="mt-3 flex gap-2">
                <input id="code" name="code" required minlength="7" maxlength="7" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="e.g. 7K4RXWM"
                       class="h-11 min-w-0 flex-1 rounded-lg border border-pub-border bg-pub-field px-3.5 font-mono text-base font-semibold uppercase tracking-[0.22em] text-pub-text shadow-xs transition placeholder:font-sans placeholder:font-normal placeholder:normal-case placeholder:tracking-normal placeholder:text-pub-faint focus:border-pub-accent focus:outline-none focus:ring-4 focus:ring-pub-accent/15">
                <button type="submit" class="h-11 shrink-0 rounded-lg bg-pub-primary px-4 text-sm font-semibold text-pub-on-primary shadow-xs transition hover:bg-pub-primary-hover focus:outline-none focus-visible:ring-4 focus-visible:ring-pub-accent/25">Track</button>
            </div>
        </form>

        <p class="mt-2 flex items-center gap-3 text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-pub-faint before:h-px before:flex-1 before:bg-pub-line after:h-px after:flex-1 after:bg-pub-line">Staff</p>

        <a href="{{ route('login') }}" class="group flex items-center gap-4 rounded-xl border border-pub-line bg-pub-panel p-[1.1rem] shadow-xs transition hover:border-emerald-300 hover:shadow-[0_8px_20px_-14px_rgb(16_24_40/0.25)] focus:outline-none focus-visible:ring-4 focus-visible:ring-pub-accent/25">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-[10px] bg-pub-highlight text-pub-accent">
                <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-base font-bold">Staff Login</span>
                <span class="mt-0.5 block text-sm text-pub-muted">For administrators and drivers.</span>
            </span>
            {!! $chevron !!}
        </a>
    </div>

    <div class="mt-7 flex gap-3 rounded-[10px] bg-pub-subtle px-4 py-3.5 text-[0.8125rem] leading-5 text-pub-muted">
        <svg class="mt-px h-[18px] w-[18px] shrink-0 text-pub-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01"/></svg>
        <p>Lost your reference code or need help with a request? Ask the fleet office at your station and give them your name and travel date.</p>
    </div>
</x-public-shell>
