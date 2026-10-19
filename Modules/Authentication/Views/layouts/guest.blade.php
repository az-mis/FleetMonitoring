<x-public-shell :title="View::yieldContent('title', config('app.name', 'Fleet Monitoring'))" active="login" width="max-w-4xl" :center="true">
    @php
        $features = [
            ['Role-based access', 'Separate workflows for super admins, admins and drivers.', '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>'],
            ['Live availability', 'Vehicle assignments and a calendar of free dates.', '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
            ['Printable trip tickets', 'Official travel authorizations, ready to print.', '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/>'],
        ];
    @endphp

    <div class="public-card overflow-hidden">
        <div class="grid md:grid-cols-[1fr_1.05fr]">
            <section class="relative hidden flex-col justify-between overflow-hidden border-r border-pub-border bg-gradient-to-br from-pub-highlight via-transparent to-transparent p-9 md:flex">
                <div>
                    <h2 class="text-2xl font-extrabold leading-tight tracking-tight">Smart fleet operations<br><span class="text-pub-accent">&amp; monitoring</span></h2>
                    <p class="mt-3 max-w-sm text-sm leading-6 text-pub-muted">Vehicles, drivers and official trips for the whole region, in one secure place.</p>

                    <ul class="mt-8 space-y-5">
                        @foreach ($features as [$title, $text, $icon])
                            <li class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-pub-highlight text-pub-accent ring-1 ring-inset ring-pub-border">
                                    <svg class="h-[1.125rem] w-[1.125rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-semibold">{{ $title }}</span>
                                    <span class="mt-0.5 block text-[0.8125rem] leading-5 text-pub-muted">{{ $text }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <p class="mt-10 flex items-center gap-2 text-xs text-pub-muted">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="16" height="11" x="4" y="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    Encrypted session · All activity is logged
                </p>
            </section>

            <section class="p-6 sm:p-9">
                @yield('content')
            </section>
        </div>
    </div>
</x-public-shell>
