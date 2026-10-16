{{-- Text size choice (saved per device). Used by System Settings and by the drivers' Display & Text Size page. --}}
@php
    // px sizes on purpose: each sample shows its own size, whatever size is currently applied
    $textSizes = [
        'standard' => ['label' => 'Standard', 'hint' => 'Default size', 'sample' => 16],
        'large' => ['label' => 'Large', 'hint' => '12% bigger', 'sample' => 18],
        'larger' => ['label' => 'Larger', 'hint' => '25% bigger', 'sample' => 20],
        'largest' => ['label' => 'Largest', 'hint' => '37% bigger', 'sample' => 22],
    ];
@endphp

<section id="text-size" class="scroll-mt-6 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm" aria-labelledby="text-size-heading">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
            </div>
            <div>
                <h3 id="text-size-heading" class="text-sm font-extrabold text-slate-900">Display &amp; Text Size</h3>
                <p class="text-[0.6875rem] text-slate-400">Make the system easier to read on this device. Text, buttons and spacing grow together.</p>
            </div>
        </div>
        <p id="text-size-status" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800" role="status" aria-live="polite"></p>
    </div>

    <fieldset class="mt-5">
        <legend class="sr-only">Choose a text size</legend>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($textSizes as $value => $size)
                <label class="relative block cursor-pointer">
                    <input type="radio" name="text_size" id="text-size-{{ $value }}" value="{{ $value }}" class="peer sr-only" @checked($value === 'standard')>
                    <span class="flex h-full flex-col items-center justify-center gap-1 rounded-xl border-2 border-gray-200 bg-white px-3 py-4 text-center transition hover:border-emerald-300 hover:bg-emerald-50/40 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-2">
                        <span class="font-bold leading-none text-gray-900" style="font-size: {{ $size['sample'] * 1.5 }}px" aria-hidden="true">Aa</span>
                        <span class="mt-1 text-sm font-semibold text-gray-900">{{ $size['label'] }}</span>
                        <span class="text-xs text-gray-500">{{ $size['hint'] }}</span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-2 top-2 hidden h-5 w-5 text-emerald-600 peer-checked:block" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.7 7.7-5.5 5.5a1 1 0 0 1-1.4 0l-2.5-2.5a1 1 0 1 1 1.4-1.4l1.8 1.8 4.8-4.8a1 1 0 0 1 1.4 1.4Z" clip-rule="evenodd"/></svg>
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- A realistic sample so people can judge the size before leaving the page --}}
    <div class="mt-5 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4">
        <p class="text-[0.6875rem] font-bold uppercase tracking-wider text-gray-500">Preview</p>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white p-3 shadow-sm">
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900">Trip Ticket 26-10-902 · QAA 1001</p>
                <p class="text-xs text-gray-500">Puerto Princesa City Hall · Oct 21, 2026 · Driver: Juan Dela Cruz</p>
            </div>
            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[0.6875rem] font-bold text-emerald-800">APPROVED</span>
        </div>
    </div>

    <p class="mt-4 text-xs text-gray-500">
        Saved on this browser only. Choose it again on each computer or phone you use.
        You can also zoom any page with <kbd class="rounded border border-gray-300 bg-gray-50 px-1 font-sans">Ctrl</kbd> + <kbd class="rounded border border-gray-300 bg-gray-50 px-1 font-sans">+</kbd>
        (<kbd class="rounded border border-gray-300 bg-gray-50 px-1 font-sans">⌘</kbd> + <kbd class="rounded border border-gray-300 bg-gray-50 px-1 font-sans">+</kbd> on Mac).
    </p>
</section>

<script>
    (function () {
        const labels = @json(collect($textSizes)->map(fn ($size) => $size['label']));
        const status = document.getElementById('text-size-status');
        let current = 'standard';
        try { current = localStorage.getItem('fleet_text_size') || 'standard'; } catch (e) {}
        if (!labels[current]) current = 'standard';

        const show = (value) => { status.textContent = 'Current: ' + labels[value]; };
        document.getElementById('text-size-' + current).checked = true;
        show(current);

        document.querySelectorAll('input[name="text_size"]').forEach((input) => {
            input.addEventListener('change', () => {
                const value = input.value;
                if (value === 'standard') {
                    document.documentElement.removeAttribute('data-text-size');
                } else {
                    document.documentElement.setAttribute('data-text-size', value);
                }
                try { localStorage.setItem('fleet_text_size', value); } catch (e) {}
                show(value);
            });
        });
    })();
</script>
