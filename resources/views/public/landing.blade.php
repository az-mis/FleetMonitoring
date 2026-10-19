<x-public-shell title="Fleet Management System" active="home" width="max-w-5xl" :center="true">
    @php
        $actions = [
            ['title' => 'Request a Vehicle', 'text' => 'Book an official vehicle for travel. No account needed.', 'url' => route('request-vehicle'), 'primary' => true,
                'icon' => '<path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/>'],
            ['title' => 'Check Request Status', 'text' => 'Track your request and print your trip ticket.', 'url' => route('check-status'), 'primary' => false,
                'icon' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>'],
            ['title' => 'Staff Login', 'text' => 'For administrators and drivers.', 'url' => route('login'), 'primary' => false,
                'icon' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>'],
        ];
        $features = [
            ['Role-based access', 'Separate, secure workflows for admins and drivers.', '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>'],
            ['Live availability', 'See vehicles and drivers free on each date.', '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
            ['Printable trip tickets', 'Official travel authorizations in one click.', '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/>'],
        ];
    @endphp

    <section class="text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-pub-border bg-pub-highlight px-3 py-1 text-[0.6875rem] font-semibold uppercase tracking-[0.14em] text-pub-accent">
            <span class="h-1.5 w-1.5 rounded-full bg-pub-accent"></span>
            Fleet Management System
        </span>
        <h1 class="mx-auto mt-5 max-w-3xl text-balance text-4xl font-extrabold leading-[1.08] tracking-tight sm:text-6xl">
            Smart fleet operations <span class="bg-gradient-to-r from-pub-accent to-pub-accent-2 bg-clip-text text-transparent">&amp; monitoring</span>
        </h1>
        <p class="mx-auto mt-5 max-w-2xl text-pretty text-base leading-7 text-pub-muted">
            One place to request official vehicles, assign drivers and track every trip in real time.
        </p>
    </section>

    <section class="mt-10 grid grid-cols-1 gap-3 sm:mt-12 md:grid-cols-3" aria-label="Get started">
        @foreach ($actions as $action)
            <a href="{{ $action['url'] }}"
               class="group flex items-start gap-4 rounded-2xl p-5 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-pub-accent {{ $action['primary'] ? 'bg-pub-primary text-pub-on-primary shadow-xl shadow-emerald-950/40 hover:bg-pub-primary-hover' : 'public-card hover:border-pub-border hover:bg-pub-highlight' }}">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $action['primary'] ? 'bg-pub-on-primary/10' : 'bg-pub-highlight text-pub-accent' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $action['icon'] !!}</svg>
                </span>
                <span class="min-w-0 flex-1 text-left">
                    <span class="flex items-center justify-between gap-2 text-base font-bold">
                        {{ $action['title'] }}
                        <svg class="h-4 w-4 shrink-0 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </span>
                    <span class="mt-1 block text-sm leading-5 {{ $action['primary'] ? 'text-pub-on-primary/75' : 'text-pub-muted' }}">{{ $action['text'] }}</span>
                </span>
            </a>
        @endforeach
    </section>

    <section class="mx-auto mt-10 grid max-w-4xl grid-cols-1 gap-x-8 gap-y-4 border-t border-pub-border pt-8 sm:grid-cols-3" aria-label="Features">
        @foreach ($features as [$title, $text, $icon])
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-pub-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>
                <div>
                    <p class="text-sm font-semibold">{{ $title }}</p>
                    <p class="mt-0.5 text-[0.8125rem] leading-5 text-pub-muted">{{ $text }}</p>
                </div>
            </div>
        @endforeach
    </section>
</x-public-shell>
