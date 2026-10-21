{{-- Theme choice (saved per device). Used by System Settings and by the drivers' Display & Text Size page. --}}
@php
    $themes = [
        'green' => ['label' => 'Green', 'hint' => 'The original dark-green sidebar.', 'sidebar' => 'bg-[#004a2c]', 'bar' => 'bg-white/30', 'active' => 'bg-white/20'],
        'light' => ['label' => 'Light', 'hint' => 'White sidebar. Easier on bright screens.', 'sidebar' => 'bg-white border-r border-slate-200', 'bar' => 'bg-slate-300', 'active' => 'bg-emerald-100'],
    ];
@endphp

<section id="theme" class="scroll-mt-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm" aria-labelledby="theme-heading">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            </div>
            <div>
                <h3 id="theme-heading" class="text-sm font-extrabold text-slate-900">Theme</h3>
                <p class="text-[0.6875rem] text-slate-400">Choose how the sidebar looks on this device.</p>
            </div>
        </div>
        <p id="theme-status" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800" role="status" aria-live="polite"></p>
    </div>

    <fieldset class="mt-5">
        <legend class="sr-only">Choose a theme</legend>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach ($themes as $value => $theme)
                <label class="relative block cursor-pointer">
                    <input type="radio" name="theme" id="theme-{{ $value }}" value="{{ $value }}" class="peer sr-only" @checked($value === 'green')>
                    <span class="flex h-full flex-col gap-3 rounded-xl border-2 border-gray-200 bg-white p-3 transition hover:border-emerald-300 peer-checked:border-emerald-600 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-2">
                        {{-- Miniature of the app: sidebar beside a light page --}}
                        <span class="flex h-24 overflow-hidden rounded-lg border border-gray-200" aria-hidden="true">
                            <span class="flex w-1/4 flex-col gap-1.5 p-2 {{ $theme['sidebar'] }}">
                                <span class="h-1.5 w-3/4 rounded-full {{ $theme['bar'] }}"></span>
                                <span class="h-2 w-full rounded {{ $theme['active'] }}"></span>
                                <span class="h-1.5 w-3/4 rounded-full {{ $theme['bar'] }}"></span>
                                <span class="h-1.5 w-2/3 rounded-full {{ $theme['bar'] }}"></span>
                            </span>
                            <span class="flex flex-1 flex-col gap-2 bg-gray-50 p-2">
                                <span class="h-4 w-1/2 rounded border border-gray-200 bg-white"></span>
                                <span class="h-8 w-3/4 rounded border border-gray-200 bg-white"></span>
                            </span>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">{{ $theme['label'] }}</span>
                            <span class="block text-xs text-gray-500">{{ $theme['hint'] }}</span>
                        </span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-2 top-2 hidden h-5 w-5 text-emerald-600 peer-checked:block" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.7 7.7-5.5 5.5a1 1 0 0 1-1.4 0l-2.5-2.5a1 1 0 1 1 1.4-1.4l1.8 1.8 4.8-4.8a1 1 0 0 1 1.4 1.4Z" clip-rule="evenodd"/></svg>
                </label>
            @endforeach
        </div>
    </fieldset>

    <p class="mt-4 text-xs text-gray-500">Saved on this browser only, like the text size.</p>
</section>

<script>
    (function () {
        const labels = @json(collect($themes)->map(fn ($theme) => $theme['label']));
        const status = document.getElementById('theme-status');
        let current = 'green';
        try { current = localStorage.getItem('fleet_theme') === 'light' ? 'light' : 'green'; } catch (e) {}

        const show = (value) => { status.textContent = 'Current: ' + labels[value]; };
        document.getElementById('theme-' + current).checked = true;
        show(current);

        document.querySelectorAll('input[name="theme"]').forEach((input) => {
            input.addEventListener('change', () => {
                const value = input.value;
                if (value === 'light') {
                    document.documentElement.setAttribute('data-theme', 'light');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
                try { localStorage.setItem('fleet_theme', value); } catch (e) {}
                show(value);
            });
        });
    })();
</script>
